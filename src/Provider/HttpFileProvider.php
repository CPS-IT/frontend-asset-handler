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

namespace CPSIT\FrontendAssetHandler\Provider;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Traits;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception as GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message;
use Symfony\Component\Console;

/**
 * HttpFileProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class HttpFileProvider implements ProviderInterface, ChattyInterface
{
    use Traits\OutputAwareTrait;

    private ?Console\Helper\ProgressBar $progressBar = null;
    private int $expectedBytes = -1;

    public function __construct(
        private readonly ClientInterface $client,
        private readonly Asset\Revision\RevisionProvider $revisionProvider,
    ) {
    }

    public static function getName(): string
    {
        return 'http';
    }

    public function fetchAsset(Asset\Definition\Source $source): Asset\Asset
    {
        $this->initializeProgressBarStyles();

        // Try to get revision from source
        $source['revision'] = $revision = $source->getRevision() ?? $this->revisionProvider->getRevision($source);
        if (null !== $revision) {
            $this->output->writeln(sprintf('Frontend revision: <info>%s</info>', $revision->get()));
        }

        // Process download
        $url = $this->getAssetUrl($source);
        $temporaryFile = Helper\FilesystemHelper::createTemporaryFile(pathinfo($url, PATHINFO_BASENAME));
        $this->processDownload($url, $temporaryFile);

        // Verify downloaded file
        if (!$this->verifyDownload($temporaryFile)) {
            throw Exception\DownloadFailedException::forFailedVerification($url, $temporaryFile);
        }

        return new Asset\TemporaryAsset($source, $temporaryFile);
    }

    /**
     * @throws Exception\MissingConfigurationException
     */
    public function getAssetUrl(Asset\Definition\Source $source): string
    {
        $config = array_map(Helper\StringHelper::urlEncode(...), $source->getConfig());

        if (null === $source->getUrl()) {
            throw Exception\MissingConfigurationException::forKey('source/url');
        }

        return Helper\StringHelper::interpolate($source->getUrl(), $config);
    }

    private function processDownload(string $url, string $targetFile): Message\ResponseInterface
    {
        $this->output->writeln(sprintf('Source url: <info>%s</info>', $url));

        $progress = $this->output->startProgress('Downloading assets...');

        try {
            $response = $this->client->request('GET', $url, [
                RequestOptions::SINK => $targetFile,
                RequestOptions::PROGRESS => $this->advanceProgress(...),
            ]);
        } catch (GuzzleException\RequestException $exception) {
            $progress->fail();

            $response = $exception->getResponse();

            if (null !== $response) {
                switch ($response->getStatusCode()) {
                    case 401:
                        throw Exception\DownloadFailedException::forUnauthorizedRequest($url, $exception);
                    case 404:
                        throw Exception\DownloadFailedException::forUnavailableTarget($url, $exception);
                }
            }

            throw Exception\DownloadFailedException::create($url, $targetFile, $exception);
        } catch (GuzzleException\GuzzleException $exception) {
            $progress->fail();

            throw Exception\DownloadFailedException::create($url, $targetFile, $exception);
        }

        if (200 !== $response->getStatusCode()) {
            $progress->fail();

            throw Exception\DownloadFailedException::create($url, $targetFile);
        }

        $progress->finish();

        return $response;
    }

    private function verifyDownload(string $targetFile): bool
    {
        return file_exists($targetFile) && filesize($targetFile) === $this->expectedBytes;
    }

    public function advanceProgress(int $total, int $downloaded): void
    {
        if (0 === $total) {
            $this->expectedBytes = -1;

            return;
        }

        if (null === $this->progressBar) {
            $this->progressBar = new Console\Helper\ProgressBar($this->output, $total);
            $this->progressBar->setFormat('http_download');
            $this->progressBar->start();
        }

        $this->expectedBytes = $total;

        if ($total === $downloaded) {
            // @codeCoverageIgnoreStart
            $this->progressBar->finish();
            $this->progressBar->clear();
            $this->progressBar = null;
            // @codeCoverageIgnoreEnd
        } else {
            $this->progressBar->setProgress($downloaded);
        }
    }

    private function initializeProgressBarStyles(): void
    {
        // Early return if progress bar styles were already initialized
        if (null !== Console\Helper\ProgressBar::getFormatDefinition('http_download')) {
            return;
        }

        Console\Helper\ProgressBar::setFormatDefinition('http_download', ' %percent:3s%% [%bar%] %current_bytes%/%max_bytes%');
        Console\Helper\ProgressBar::setPlaceholderFormatterDefinition('current_bytes', fn (Console\Helper\ProgressBar $bar) => Helper\StringHelper::formatBytes($bar->getProgress()));
        Console\Helper\ProgressBar::setPlaceholderFormatterDefinition('max_bytes', fn (Console\Helper\ProgressBar $bar) => Helper\StringHelper::formatBytes($bar->getMaxSteps()));
    }
}
