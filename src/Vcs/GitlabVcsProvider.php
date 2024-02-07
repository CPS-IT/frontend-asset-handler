<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace CPSIT\FrontendAssetHandler\Vcs;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Traits;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client;
use Psr\Http\Message;

use function array_replace_recursive;
use function http_build_query;
use function is_array;
use function json_decode;
use function parse_str;

/**
 * GitlabVcsProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GitlabVcsProvider implements DeployableVcsProviderInterface
{
    use Traits\DefaultConfigurationAwareTrait;

    private const DEFAULT_BASE_URL = 'https://gitlab.com';
    private const BASE_API_PATH = '/api/v4';

    private const DEFAULT_CONFIGURATION = [
        'base-url' => self::DEFAULT_BASE_URL,
        'access-token' => null,
        'project-id' => null,
    ];

    private Message\UriInterface $baseUrl;

    public function __construct(
        private readonly ClientInterface $client,
        ?string $baseUrl = null,
        private ?string $accessToken = null,
        private ?int $projectId = null,
        private ?string $environment = null,
    ) {
        $this->baseUrl = $this->createBaseUrl($baseUrl ?? self::DEFAULT_BASE_URL);
    }

    /**
     * @throws Exception\MissingConfigurationException
     */
    public function withVcs(Asset\Definition\Vcs $vcs): static
    {
        // Validate and merge VCS configuration
        $this->validateAssetDefinition($vcs);
        $this->applyDefaultConfiguration($vcs);

        // Apply VCS configuration
        $clone = clone $this;
        $clone->baseUrl = $this->createBaseUrl((string) $vcs['base-url']);
        $clone->accessToken = (string) $vcs['access-token'];
        $clone->projectId = (int) $vcs['project-id'];
        $clone->environment = $vcs->getEnvironment();

        return $clone;
    }

    public static function getName(): string
    {
        return 'gitlab';
    }

    public function getSourceUrl(): string
    {
        $response = $this->sendRequest('/projects/{project-id}');

        return Helper\ArrayHelper::getArrayValueByPath(
            $this->parseJsonResponse($response),
            'web_url',
        );
    }

    public function getLatestRevision(?string $environment = null): ?Asset\Revision\Revision
    {
        try {
            $response = $this->sendRequest(
                '/projects/{project-id}/deployments',
                additionalQueryParams: [
                    'environment' => $environment ?? $this->environment,
                    'status' => 'success',
                    'order_by' => 'updated_at',
                    'sort' => 'desc',
                    'page' => 1,
                    'per_page' => 1,
                ],
            );
            $revision = Helper\ArrayHelper::getArrayValueByPath(
                $this->parseJsonResponse($response),
                '0/sha',
            );

            return new Asset\Revision\Revision($revision);
        } catch (Client\ClientExceptionInterface|Exception\InvalidResponseException|Exception\MissingConfigurationException) {
            return null;
        }
    }

    public function hasRevision(Asset\Revision\Revision $revision): bool
    {
        try {
            return (bool) $this->sendRequest(
                '/projects/{project-id}/repository/commits/{revision}',
                [
                    'revision' => $revision->get(),
                ],
            );
        } catch (Client\ClientExceptionInterface) {
            return false;
        }
    }

    public function getActiveDeployments(): array
    {
        $deployments = [];
        $endpoint = '/projects/{project-id}/deployments';

        $createdResponse = $this->sendRequest(
            $endpoint,
            additionalQueryParams: [
                'environment' => $this->environment,
                'status' => 'created',
            ],
        );
        $runningResponse = $this->sendRequest(
            $endpoint,
            additionalQueryParams: [
                'environment' => $this->environment,
                'status' => 'running',
            ],
        );

        foreach ($this->parseJsonResponse($createdResponse) as $createdDeployment) {
            $deployments[] = new Dto\Deployment(
                new Psr7\Uri($createdDeployment['deployable']['pipeline']['web_url']),
                new Asset\Revision\Revision($createdDeployment['deployable']['pipeline']['sha']),
            );
        }
        foreach ($this->parseJsonResponse($runningResponse) as $runningDeployment) {
            $deployments[] = new Dto\Deployment(
                new Psr7\Uri($runningDeployment['deployable']['pipeline']['web_url']),
                new Asset\Revision\Revision($runningDeployment['deployable']['pipeline']['sha']),
            );
        }

        return $deployments;
    }

    /**
     * @param array<string, mixed> $additionalParameters
     * @param array<string, mixed> $additionalQueryParams
     *
     * @throws Client\ClientExceptionInterface
     */
    private function sendRequest(
        string $endpoint,
        array $additionalParameters = [],
        array $additionalQueryParams = [],
    ): Message\ResponseInterface {
        $parameters = array_replace_recursive(
            [
                'project-id' => $this->projectId,
                'environment' => $this->environment,
            ],
            $additionalParameters,
        );
        $endpoint = Helper\StringHelper::interpolate($endpoint, $parameters);

        $requestUri = $this->baseUrl->withPath(
            rtrim($this->baseUrl->getPath(), '/').'/'.ltrim($endpoint, '/'),
        );

        if ([] !== $additionalQueryParams) {
            $queryParams = [];
            parse_str($requestUri->getQuery(), $queryParams);

            $queryParams = array_replace_recursive($queryParams, $additionalQueryParams);
            $requestUri = $requestUri->withQuery(http_build_query($queryParams));
        }

        return $this->client->request('GET', $requestUri, [
            RequestOptions::HEADERS => [
                'PRIVATE-TOKEN' => $this->accessToken,
            ],
        ]);
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws Exception\InvalidResponseException
     */
    private function parseJsonResponse(Message\ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $json = json_decode($body, true);

        if (!is_array($json)) {
            throw Exception\InvalidResponseException::create($body);
        }

        return $json;
    }

    private function createBaseUrl(string $baseUrl): Message\UriInterface
    {
        $baseUri = new Psr7\Uri($baseUrl);
        $basePath = $baseUri->getPath();
        $apiPath = rtrim($basePath, '/').self::BASE_API_PATH;

        return $baseUri->withPath($apiPath);
    }

    /**
     * @return array{base-url: string|null, access-token: string|null, project-id: int|null}
     */
    protected function getDefaultConfiguration(): array
    {
        return self::DEFAULT_CONFIGURATION;
    }
}
