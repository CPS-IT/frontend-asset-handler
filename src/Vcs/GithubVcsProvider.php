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
use GraphQL\Client;
use GraphQL\QueryBuilder;
use GraphQL\RawObject;
use GraphQL\Results;
use GraphQL\Util;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7;

use function class_exists;
use function explode;
use function in_array;
use function is_array;

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

    private ?Client $graphQlClient = null;

    public function __construct(
        private readonly ClientInterface $client,
        private ?string $accessToken = null,
        private ?string $owner = null,
        private ?string $name = null,
        private ?string $environment = null,
    ) {
    }

    public function withVcs(Asset\Definition\Vcs $vcs): static
    {
        // Validate and merge VCS configuration
        $this->validateAssetDefinition($vcs);
        $this->applyDefaultConfiguration($vcs);

        // Apply VCS configuration
        $clone = clone $this;
        $clone->graphQlClient = null;
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
        $results = $this->sendRequest(
            $this->createQueryBuilder()->selectField('url'),
        );

        return Helper\ArrayHelper::getArrayValueByPath(
            $this->parseGraphQLData($results),
            'repository/url',
        );
    }

    public function getLatestRevision(string $environment = null): ?Asset\Revision\Revision
    {
        try {
            $results = $this->sendRequest(
                $this->createQueryBuilder()->selectField(
                    (new QueryBuilder\QueryBuilder('deployments'))
                        ->setArgument('environments', $environment ?? $this->environment)
                        ->setArgument('first', 30)
                        ->setArgument('orderBy', new RawObject('{field:CREATED_AT, direction:DESC}'))
                        ->selectField(
                            (new QueryBuilder\QueryBuilder('nodes'))
                                ->selectField('commitOid')
                                ->selectField(
                                    (new QueryBuilder\QueryBuilder('latestStatus'))
                                        ->selectField('state')
                                )
                        )
                ),
            );

            $nodes = Helper\ArrayHelper::getArrayValueByPath(
                $this->parseGraphQLData($results),
                'repository/deployments/nodes',
            );
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
            $results = $this->sendRequest(
                $this->createQueryBuilder()->selectField(
                    (new QueryBuilder\QueryBuilder('object'))
                        ->setArgument('oid', $revision->get())
                        ->selectField('id')
                ),
            );

            return null !== Helper\ArrayHelper::getArrayValueByPath(
                $this->parseGraphQLData($results),
                'repository/object',
            );
        } catch (\Exception) {
            return false;
        }
    }

    public function getActiveDeployments(): array
    {
        $deployments = [];

        $results = $this->sendRequest(
            $this->createQueryBuilder()->selectField(
                (new QueryBuilder\QueryBuilder('deployments'))
                    ->setArgument('environments', $this->environment)
                    ->setArgument('first', 30)
                    ->setArgument('orderBy', new RawObject('{field:CREATED_AT, direction:DESC}'))
                    ->selectField(
                        (new QueryBuilder\QueryBuilder('nodes'))
                            ->selectField('commitOid')
                            ->selectField(
                                (new QueryBuilder\QueryBuilder('latestStatus'))
                                    ->selectField('logUrl')
                                    ->selectField('state')
                            )
                    )
            ),
        );

        $nodes = Helper\ArrayHelper::getArrayValueByPath(
            $this->parseGraphQLData($results),
            'repository/deployments/nodes',
        );

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

    private function createQueryBuilder(): QueryBuilder\QueryBuilder
    {
        return (new QueryBuilder\QueryBuilder('repository'))
            ->setArgument('owner', $this->owner)
            ->setArgument('name', $this->name)
        ;
    }

    private function sendRequest(QueryBuilder\QueryBuilder $queryBuilder): Results
    {
        return $this->getClient()->runQuery(
            (new QueryBuilder\QueryBuilder())->selectField($queryBuilder),
            true,
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception\InvalidResponseException
     */
    private function parseGraphQLData(Results $results): array
    {
        $data = $results->getData();

        if (!is_array($data)) {
            throw Exception\InvalidResponseException::create($results->getResponseBody());
        }

        return $data;
    }

    /**
     * @throws Exception\MissingPackageException
     */
    private function getClient(): Client
    {
        if (null !== $this->graphQlClient) {
            return $this->graphQlClient;
        }

        // @codeCoverageIgnoreStart
        if (!class_exists(Client::class)) {
            throw Exception\MissingPackageException::create('gmostafa/php-graphql-client');
        }
        // @codeCoverageIgnoreEnd

        $this->graphQlClient = new Client(
            self::API_URL,
            ['Authorization' => 'Bearer '.$this->accessToken],
            httpClient: new Util\GuzzleAdapter($this->client),
        );

        return $this->graphQlClient;
    }

    /**
     * @return array{access-token: string|null, repository: string|null}
     */
    protected function getDefaultConfiguration(): array
    {
        return self::DEFAULT_CONFIGURATION;
    }
}
