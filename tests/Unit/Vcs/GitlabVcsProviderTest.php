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
 * GitlabVcsProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class GitlabVcsProviderTest extends TestCase
{
    use Tests\Unit\ClientMockTrait;

    private Asset\Definition\Vcs $vcs;
    private Vcs\GitlabVcsProvider $subject;

    protected function setUp(): void
    {
        $this->vcs = new Asset\Definition\Vcs([
            'type' => Vcs\GitlabVcsProvider::getName(),
            'base-url' => 'https://gitlab.example.com',
            'project-id' => 123,
            'access-token' => 'foo',
            'environment' => 'baz',
        ]);
        $this->subject = new Vcs\GitlabVcsProvider($this->getPreparedClient());
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
    public function withVcsThrowsExceptionIfProjectIdIsMissing(): void
    {
        unset($this->vcs['project-id']);

        $this->expectException(Exception\MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "project-id" is missing or invalid.');

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

        $response->getBody()->write('foo');
        $response->getBody()->rewind();

        $this->expectExceptionObject(Exception\InvalidResponseException::create('foo'));

        $this->subject->withVcs($this->vcs)->getSourceUrl();
    }

    #[Test]
    public function getSourceUrlReturnsSourceUrl(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('{"web_url":"foo"}');
        $response->getBody()->rewind();

        self::assertSame('foo', $this->subject->withVcs($this->vcs)->getSourceUrl());
    }

    #[Test]
    public function getLatestRevisionReturnsNullIfApiResponseIsUnexpected(): void
    {
        $this->mockHandler->append(new GuzzleException\TransferException());

        self::assertNull($this->subject->withVcs($this->vcs)->getLatestRevision());
    }

    #[Test]
    public function getLatestRevisionReturnsRevisionForEnvironmentDerivedFromVcsObject(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('[{"sha":"1234567890"}]');
        $response->getBody()->rewind();

        $expected = new Asset\Revision\Revision('1234567890');

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getLatestRevision());
        self::assertStringContainsString('environment=baz', (string) $this->getLastRequest()->getUri());
    }

    #[Test]
    public function getLatestRevisionReturnsRevisionForGivenEnvironment(): void
    {
        $this->mockHandler->append($response = new Psr7\Response());

        $response->getBody()->write('[{"sha":"1234567890"}]');
        $response->getBody()->rewind();

        $expected = new Asset\Revision\Revision('1234567890');

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getLatestRevision('foo'));
        self::assertStringContainsString('environment=foo', (string) $this->getLastRequest()->getUri());
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
        self::assertSame(
            'https://gitlab.example.com/api/v4/projects/123/repository/commits/1234567890',
            (string) $this->getLastRequest()->getUri(),
        );
    }

    /**
     * @param list<Vcs\Dto\Deployment> $expected
     */
    #[Test]
    #[DataProvider('getActiveDeploymentsReturnsActiveDeploymentsIfPipelinesAreEitherCreatedOrRunningDataProvider')]
    public function getActiveDeploymentsReturnsActiveDeploymentsIfPipelinesAreEitherCreatedOrRunning(
        string $createdResponseJson,
        string $runningResponseJson,
        array $expected,
    ): void {
        $createdResponse = new Psr7\Response(200, body: $createdResponseJson);
        $runningResponse = new Psr7\Response(200, body: $runningResponseJson);

        $this->mockHandler->append($createdResponse, $runningResponse);

        self::assertEquals($expected, $this->subject->withVcs($this->vcs)->getActiveDeployments());
    }

    /**
     * @return Generator<string, array{Message\ResponseInterface|Throwable, bool}>
     */
    public static function hasRevisionReturnsTrueIfRevisionExistsInVcsDataProvider(): Generator
    {
        yield 'exception' => [new GuzzleException\TransferException(), false];
        yield 'unexpected response' => [new Psr7\Response(404), false];
        yield 'valid response' => [new Psr7\Response(), true];
    }

    /**
     * @return \Generator<string, array{string, string, list<Vcs\Dto\Deployment>}>
     */
    public static function getActiveDeploymentsReturnsActiveDeploymentsIfPipelinesAreEitherCreatedOrRunningDataProvider(): Generator
    {
        $uri = new Psr7\Uri('https://www.example.com');
        $revision = new Asset\Revision\Revision('1234567890');
        $deployment = new Vcs\Dto\Deployment($uri, $revision);

        $nonEmptyJson = '[{"deployable":{"pipeline":{"sha":"1234567890","web_url":"https://www.example.com"}}}]';
        $emptyJson = '{}';

        yield 'pipeline created' => [$nonEmptyJson, $emptyJson, [$deployment]];
        yield 'pipeline running' => [$emptyJson, $nonEmptyJson, [$deployment]];
        yield 'pipeline created and running' => [$nonEmptyJson, $nonEmptyJson, [$deployment, $deployment]];
        yield 'no active pipeline' => [$emptyJson, $emptyJson, []];
    }
}
