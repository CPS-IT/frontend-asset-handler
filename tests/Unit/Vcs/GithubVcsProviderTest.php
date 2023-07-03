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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Vcs;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use CPSIT\FrontendAssetHandler\Vcs;
use Generator;
use GuzzleHttp\Exception as GuzzleException;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message;
use Throwable;

/**
 * GithubVcsProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GithubVcsProviderTest extends TestCase
{
    use Tests\Unit\ClientMockTrait;

    private Asset\Definition\Vcs $vcs;
    private Vcs\GithubVcsProvider $subject;

    protected function setUp(): void
    {
        $this->vcs = new Asset\Definition\Vcs([
            'type' => Vcs\GithubVcsProvider::getName(),
            'access-token' => 'foo',
            'repository' => 'foo/baz',
            'environment' => 'baz',
        ]);
        $this->subject = new Vcs\GithubVcsProvider($this->getPreparedClient());
    }

    #[Test]
    public function withVcsThrowsExceptionIfAccessTokenIsMissing(): void
    {
        unset($this->vcs['access-token']);

        $this->expectException(Exception\MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "access-token" is missing or invalid.');

        $this->subject->withVcs($this->vcs);
    }

    #[Test]
    public function withVcsThrowsExceptionIfRepositoryIsMissing(): void
    {
        unset($this->vcs['repository']);

        $this->expectException(Exception\MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "repository" is missing or invalid.');

        $this->subject->withVcs($this->vcs);
    }

    #[Test]
    public function withVcsReturnsClonedInstance(): void
    {
        $actual = $this->subject->withVcs($this->vcs);

        self::assertNotSame($this->subject, $actual);
    }

    #[Test]
    public function getSourceUrlThrowsExceptionIfResponseIsUnexpected(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":"baz"}');
        $response->getBody()->rewind();

        $this->expectExceptionObject(Exception\InvalidResponseException::create('{"data":"baz"}'));

        $this->subject->withVcs($this->vcs)->getSourceUrl();
    }

    #[Test]
    public function getSourceUrlReturnsSourceUrl(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":{"repository":{"url":"foo"}}}');
        $response->getBody()->rewind();

        self::assertSame('foo', $this->subject->withVcs($this->vcs)->getSourceUrl());
    }

    #[Test]
    public function getSourceUrlReusesInitializedGraphQLClient(): void
    {
        $this->expectNotToPerformAssertions();

        for ($i = 0; $i < 2; ++$i) {
            $this->mockHandler->append($response = new Psr7\Response());

            $response->getBody()->write('{"data":{"repository":{"url":"foo"}}}');
            $response->getBody()->rewind();
        }

        $subject = $this->subject->withVcs($this->vcs);

        // First call creates a new client
        $subject->getSourceUrl();

        // Second call re-uses existing client
        $subject->getSourceUrl();
    }

    #[Test]
    public function getLatestRevisionReturnsNullIfApiResponseIsUnexpected(): void
    {
        $this->mockHandler->append(new GuzzleException\TransferException());

        self::assertNull($this->subject->withVcs($this->vcs)->getLatestRevision());
    }

    #[Test]
    public function getLatestRevisionReturnsNullIfApiResponseIsInvalid(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":"foo"}');
        $response->getBody()->rewind();

        self::assertNull($this->subject->withVcs($this->vcs)->getLatestRevision());
    }

    #[Test]
    public function getLatestRevisionReturnsRevisionForPreConfiguredEnvironment(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":{"repository":{"deployments":{"nodes":[{"latestStatus":{"state":"SUCCESS"},"commitOid":"1234567890"}]}}}}');
        $response->getBody()->rewind();

        $expected = new Asset\Revision\Revision('1234567890');

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getLatestRevision());
        self::assertStringContainsString('environments: \\"baz\\"', (string) $this->getLastRequest()->getBody());
    }

    #[Test]
    public function getLatestRevisionReturnsRevisionForGivenEnvironment(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":{"repository":{"deployments":{"nodes":[{"latestStatus":{"state":"SUCCESS"},"commitOid":"1234567890"}]}}}}');
        $response->getBody()->rewind();

        $expected = new Asset\Revision\Revision('1234567890');

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getLatestRevision('foo'));
        self::assertStringContainsString('environments: \\"foo\\"', (string) $this->getLastRequest()->getBody());
    }

    #[Test]
    public function getLatestRevisionReturnsNullIfNoSuccessfulDeploymentsAreAvailable(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"data":{"repository":{"deployments":{"nodes":[]}}}}');
        $response->getBody()->rewind();

        self::assertNull($this->subject->withVcs($this->vcs)->getLatestRevision());
    }

    #[Test]
    #[DataProvider('hasRevisionReturnsTrueIfRevisionExistsInVcsDataProvider')]
    public function hasRevisionReturnsTrueIfRevisionExistsInVcs(
        Message\ResponseInterface|Throwable $response,
        bool $expected,
    ): void {
        $this->mockHandler->append($response);

        $revision = new Asset\Revision\Revision('1234567890');

        self::assertSame($expected, $this->subject->withVcs($this->vcs)->hasRevision($revision));
        self::assertStringContainsString(
            'object(oid: \\"1234567890\\")',
            (string) $this->getLastRequest()->getBody(),
        );
    }

    /**
     * @param list<Vcs\Dto\Deployment> $expected
     */
    #[Test]
    #[DataProvider('getActiveDeploymentsReturnsActiveDeploymentsIfAnyPipelinesAreActiveDataProvider')]
    public function getActiveDeploymentsReturnsActiveDeploymentsIfAnyPipelinesAreActive(
        Message\ResponseInterface $response,
        array $expected,
    ): void {
        $this->mockHandler->append($response);

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getActiveDeployments());
    }

    /**
     * @return Generator<string, array{Message\ResponseInterface|Throwable, bool}>
     */
    public static function hasRevisionReturnsTrueIfRevisionExistsInVcsDataProvider(): Generator
    {
        $response = new Psr7\Response();
        $response->getBody()->write('{"data":{"repository":{"object":"foo"}}}');
        $response->getBody()->rewind();

        yield 'exception' => [new GuzzleException\TransferException(), false];
        yield 'unexpected response' => [$response->withStatus(404), false];
        yield 'valid response' => [$response, true];
    }

    /**
     * @return Generator<string, array{Message\ResponseInterface, list<Vcs\Dto\Deployment>}>
     */
    public static function getActiveDeploymentsReturnsActiveDeploymentsIfAnyPipelinesAreActiveDataProvider(): Generator
    {
        $uri = new Psr7\Uri('https://www.example.com');
        $revision = new Asset\Revision\Revision('1234567890');
        $deployment = new Vcs\Dto\Deployment($uri, $revision);

        /**
         * @param list<string> $statuses
         */
        $createResponse = function (array $statuses) use ($deployment): Message\ResponseInterface {
            $response = new Psr7\Response();
            $json = [
                'data' => [
                    'repository' => [
                        'deployments' => [
                            'nodes' => array_map(
                                static fn (string $status) => [
                                    'latestStatus' => [
                                        'state' => $status,
                                        'logUrl' => 'https://www.example.com',
                                    ],
                                    'commitOid' => $deployment->getRevision()->get(),
                                ],
                                $statuses,
                            ),
                        ],
                    ],
                ],
            ];

            $response->getBody()->write(json_encode($json, JSON_THROW_ON_ERROR));
            $response->getBody()->rewind();

            return $response;
        };

        yield 'pending deployment' => [$createResponse(['PENDING']), [$deployment]];
        yield 'queued deployment' => [$createResponse(['QUEUED']), [$deployment]];
        yield 'active deployment' => [$createResponse(['IN_PROGRESS']), [$deployment]];
        yield 'waiting deployment' => [$createResponse(['WAITING']), [$deployment]];
        yield 'multiple deployments' => [$createResponse(['PENDING', 'QUEUED', 'IN_PROGRESS', 'WAITING']), [$deployment, $deployment, $deployment, $deployment]];
        yield 'no active deployments' => [$createResponse([]), []];
    }
}
