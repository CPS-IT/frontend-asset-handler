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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Provider;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use CPSIT\FrontendAssetHandler\Asset\Revision\RevisionProvider;
use CPSIT\FrontendAssetHandler\Asset\TemporaryAsset;
use CPSIT\FrontendAssetHandler\Exception\DownloadFailedException;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Provider\HttpFileProvider;
use CPSIT\FrontendAssetHandler\Tests\Unit\BufferedConsoleOutput;
use CPSIT\FrontendAssetHandler\Tests\Unit\ClientMockTrait;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * HttpFileProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class HttpFileProviderTest extends ContainerAwareTestCase
{
    use ClientMockTrait;

    private Source $source;
    private BufferedConsoleOutput $output;
    private HttpFileProvider $subject;
    private int $expectedBytes = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $client = $this->getPreparedClient($this->getProgressMiddleware());
        $filesystem = $this->container->get(Filesystem::class);

        $this->source = new Source([
            'environment' => 'latest',
            'url' => 'https://www.example.com/assets/{environment}.tar.gz',
            'revision-url' => 'https://www.example.com/assets/{environment}/REVISION',
            'revision' => new Revision('1234567890'),
        ]);
        $this->output = new BufferedConsoleOutput();
        $this->subject = new HttpFileProvider($client, new RevisionProvider($client, $filesystem));
        $this->subject->setOutput($this->output);
    }

    /**
     * @test
     */
    public function fetchAssetPrintsRevisionFromSource(): void
    {
        $this->mockHandler->append(new Response());

        try {
            $this->subject->fetchAsset($this->source);
        } catch (DownloadFailedException) {
            // Intended fallthrough.
        }

        self::assertStringContainsString('Frontend revision: 1234567890', $this->output->fetch());
    }

    /**
     * @test
     */
    public function fetchAssetPrintsRevisionFromRevisionProvider(): void
    {
        unset($this->source['revision']);

        $revisionResponse = new Response();
        $revisionBody = $revisionResponse->getBody();
        $revisionBody->write('0987654321');
        $revisionBody->rewind();
        $this->mockHandler->append($revisionResponse);
        $this->mockHandler->append(new Response());

        try {
            $this->subject->fetchAsset($this->source);
        } catch (DownloadFailedException) {
            // Intended fallthrough.
        }

        self::assertStringContainsString('Frontend revision: 0987654321', $this->output->fetch());
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfRequestIsUnauthorized(): void
    {
        $this->mockHandler->append(new Response(401));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1624037646);
        $this->expectExceptionMessage('You are not authorized to download "https://www.example.com/assets/latest.tar.gz" (Error 401).');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfRequestTargetIsUnavailable(): void
    {
        $this->mockHandler->append(new Response(404));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1624037782);
        $this->expectExceptionMessage('The requested URL "https://www.example.com/assets/latest.tar.gz" is not available (Error 404).');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfRequestFails(): void
    {
        $this->mockHandler->append(new Response(500));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1623862554);
        $this->expectExceptionMessageMatches('#^An error occurred while downloading "https://www\\.example\\.com/assets/latest\\.tar\\.gz" to "[^"]+"\\.$#');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfGuzzleExceptionOccurs(): void
    {
        $exception = new class() extends Exception implements GuzzleException {
        };
        $this->mockHandler->append($exception);

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1623862554);
        $this->expectExceptionMessageMatches('#^An error occurred while downloading "https://www\\.example\\.com/assets/latest\\.tar\\.gz" to "[^"]+"\\.$#');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfResponseCodeInUnexpected(): void
    {
        $this->mockHandler->append(new Response(204));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1623862554);
        $this->expectExceptionMessageMatches('#^An error occurred while downloading "https://www\\.example\\.com/assets/latest\\.tar\\.gz" to "[^"]+"\\.$#');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfDownloadCannotBeVerified(): void
    {
        $this->mockHandler->append(new Response(200));

        $this->expectException(DownloadFailedException::class);
        $this->expectExceptionCode(1625218841);
        $this->expectExceptionMessageMatches('#^Download verification failed for target file "[^"]+" from source "https://www\\.example\\.com/assets/latest\\.tar\\.gz"\\.$#');

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetReturnsTemporaryAsset(): void
    {
        $response = new Response();
        $body = $response->getBody();
        $body->write('foo');
        $body->rewind();
        $this->mockHandler->append($response);

        // Determine expected bytes of downloaded file
        $filesystem = new Filesystem();
        $targetFile = $filesystem->tempnam(sys_get_temp_dir(), 'asset_handler_test_');
        /* @phpstan-ignore-next-line */
        $filesystem->dumpFile($targetFile, $body);
        $this->expectedBytes = filesize($targetFile) ?: 0;
        $filesystem->remove($targetFile);

        $actual = $this->subject->fetchAsset($this->source);

        self::assertInstanceOf(TemporaryAsset::class, $actual);
        self::assertFileExists($actual->getTempFile());
        self::assertStringEndsWith('.tar.gz', $actual->getTempFile());
    }

    /**
     * @test
     */
    public function getAssetUrlThrowsExceptionIfSourceUrlIsNotConfigured(): void
    {
        unset($this->source['url']);

        $this->expectExceptionObject(MissingConfigurationException::forKey('source/url'));

        $this->subject->getAssetUrl($this->source);
    }

    private function getProgressMiddleware(): callable
    {
        // Mock RequestOptions::PROGRESS since this is not supported by the MockHandler
        return fn (callable $handler): callable => function (RequestInterface $request, array $options) use ($handler) {
            if (isset($options[RequestOptions::PROGRESS])) {
                $this->subject->advanceProgress($this->expectedBytes, 0);
            }

            return $handler($request, $options);
        };
    }
}
