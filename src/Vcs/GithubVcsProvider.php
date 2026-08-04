<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2022 Elias Häußler <e.haeussler@familie-redlich.de>
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

use function explode;
use function in_array;
use function is_array;
use function json_decode;

/**
 * GithubVcsProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GithubVcsProvider implements DeployableVcsProviderInterface
{
    use Traits\DefaultConfigurationAwareTrait;

    private const API_URL = 'https://api.github.com/graphql';
    private const SUCCESSFUL_DEPLOYMENT_STATUS = 'SUCCESS';
    private const ACTIVE_DEPLOYMENT_STATUSES = [
        'PENDING',
        'QUEUED',
        'IN_PROGRESS',
        'WAITING',
    ];

    private const DEFAULT_CONFIGURATION = [
        'access-token' => null,
        'repository' => null,
    ];

    private const QUERY_REPOSITORY_URL = <<<'GRAPHQL'
        query($owner: String!, $name: String!) {
            repository(owner: $owner, name: $name) {
                url
            }
        }
    GRAPHQL;

    private const QUERY_DEPLOYMENTS = <<<'GRAPHQL'
        query($owner: String!, $name: String!, $environment: String!) {
            repository(owner: $owner, name: $name) {
                deployments(environments: [$environment], first: 30, orderBy: {field: CREATED_AT, direction: DESC}) {
                    nodes {
                        commitOid
                        latestStatus {
                            state
                            logUrl
                        }
                    }
                }
            }
        }
    GRAPHQL;

    private const QUERY_HAS_REVISION = <<<'GRAPHQL'
        query($owner: String!, $name: String!, $oid: GitObjectID!) {
            repository(owner: $owner, name: $name) {
                object(oid: $oid) {
                    id
                }
            }
        }
    GRAPHQL;

    public function __construct(
        private readonly ClientInterface $client,
        private ?string $accessToken = null,
        private ?string $owner = null,
        private ?string $name = null,
        private ?string $environment = null,
    ) {}

    public function withVcs(Asset\Definition\Vcs $vcs): static
    {
        // Validate and merge VCS configuration
        $this->validateAssetDefinition($vcs);
        $this->applyDefaultConfiguration($vcs);

        // Apply VCS configuration
        $clone = clone $this;
        $clone->accessToken = (string) $vcs['access-token'];
        [$clone->owner, $clone->name] = explode('/', (string) $vcs['repository'], 2);
        $clone->environment = $vcs->getEnvironment();

        return $clone;
    }

    public static function getName(): string
    {
        return 'github';
    }

    public function getSourceUrl(): string
    {
        $data = $this->request(self::QUERY_REPOSITORY_URL, [
            'owner' => $this->owner,
            'name' => $this->name,
        ]);

        return Helper\ArrayHelper::getArrayValueByPath($data, 'repository/url');
    }

    public function getLatestRevision(?string $environment = null): ?Asset\Revision\Revision
    {
        try {
            $data = $this->request(self::QUERY_DEPLOYMENTS, [
                'owner' => $this->owner,
                'name' => $this->name,
                'environment' => $environment ?? $this->environment,
            ]);

            $nodes = Helper\ArrayHelper::getArrayValueByPath($data, 'repository/deployments/nodes');
        } catch (\Exception) {
            return null;
        }

        // Find latest successful deployment
        foreach ($nodes as $node) {
            $state = $node['latestStatus']['state'] ?? null;

            if (self::SUCCESSFUL_DEPLOYMENT_STATUS === $state) {
                return new Asset\Revision\Revision($node['commitOid']);
            }
        }

        return null;
    }

    public function hasRevision(Asset\Revision\Revision $revision): bool
    {
        try {
            $data = $this->request(self::QUERY_HAS_REVISION, [
                'owner' => $this->owner,
                'name' => $this->name,
                'oid' => $revision->get(),
            ]);

            return null !== Helper\ArrayHelper::getArrayValueByPath($data, 'repository/object');
        } catch (\Exception) {
            return false;
        }
    }

    public function getActiveDeployments(): array
    {
        $deployments = [];

        $data = $this->request(self::QUERY_DEPLOYMENTS, [
            'owner' => $this->owner,
            'name' => $this->name,
            'environment' => $this->environment,
        ]);

        $nodes = Helper\ArrayHelper::getArrayValueByPath($data, 'repository/deployments/nodes');

        foreach ($nodes as $node) {
            $state = $node['latestStatus']['state'] ?? null;

            if (in_array($state, self::ACTIVE_DEPLOYMENT_STATUSES, true)) {
                $deployments[] = new Dto\Deployment(
                    new Psr7\Uri($node['latestStatus']['logUrl']),
                    new Asset\Revision\Revision($node['commitOid']),
                );
            }
        }

        return $deployments;
    }

    /**
     * @param non-empty-string     $query
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     *
     * @throws Exception\InvalidResponseException
     */
    private function request(string $query, array $variables): array
    {
        $response = $this->client->request('POST', self::API_URL, [
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$this->accessToken],
            RequestOptions::JSON => ['query' => $query, 'variables' => $variables],
        ]);

        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !is_array($decoded['data'] ?? null)) {
            throw Exception\InvalidResponseException::create($body);
        }

        return $decoded['data'];
    }

    /**
     * @return array{access-token: string|null, repository: string|null}
     */
    protected function getDefaultConfiguration(): array
    {
        return self::DEFAULT_CONFIGURATION;
    }
}
