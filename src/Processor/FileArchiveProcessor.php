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

namespace CPSIT\FrontendAssetHandler\Processor;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Traits;
use FilesystemIterator;
use Phar;
use PharData;
use Symfony\Component\Filesystem;

use function dirname;
use function strlen;

/**
 * FileArchiveProcessor.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FileArchiveProcessor implements ProcessorInterface, ChattyInterface
{
    use Traits\DefaultConfigurationAwareTrait;
    use Traits\OutputAwareTrait;
    use Traits\TargetPathBuilderTrait;

    private const ZIP = 'zip';
    private const TAR = 'tar';
    private const TAR_GZ = 'tar.gz';

    private const SUPPORTED_TYPES = [
        self::ZIP,
        self::TAR,
        self::TAR_GZ,
    ];

    private const DEFAULT_CONFIGURATION = [
        'base' => '',
    ];

    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
    ) {}

    public static function getName(): string
    {
        return 'archive';
    }

    /**
     * @throws Exception\MissingConfigurationException
     */
    public function processAsset(Asset\Asset $asset): string
    {
        // Only assets of type "TemporaryAsset" can be processed by this processor
        // since it can only handle temporary archives which were previously fetched
        // and need to be extracted to the appropriate target path
        if (!($asset instanceof Asset\TemporaryAsset)) {
            throw Exception\UnsupportedAssetException::create($asset);
        }

        // Early return if the asset's target is missing
        if (null === $asset->getTarget()) {
            throw Exception\UnsupportedAssetException::create($asset);
        }

        // Validate and merge asset target
        $this->validateAssetDefinition($asset->getTarget());
        $this->applyDefaultConfiguration($asset->getTarget());

        // Open archive containing the frontend assets
        $archive = $this->openArchive($asset);
        $targetPath = $this->getAssetPath($asset);

        // Build base path to be extracted from archive
        $basePath = trim((string) $asset->getTarget()['base'], " \\/\t\n\r\0\x0B");
        $basePath = '' !== $basePath ? $basePath.'/' : null;

        // Initialize directories
        $progress = $this->output->startProgress('Truncating target directory...');
        try {
            $tempPath = $this->createTempDirectory($asset);
            $this->filesystem->remove($tempPath);
            $this->filesystem->remove($targetPath);

            $progress->finish();
        } catch (\Exception $exception) {
            $progress->fail();

            throw $exception;
        }

        // Extract files from archive
        $progress = $this->output->startProgress('Extracting downloaded assets...');
        try {
            if ($archive->extractTo($tempPath, $basePath)) {
                $progress->finish();
            } else {
                throw Exception\FilesystemFailureException::forArchiveExtraction($asset->getTempFile());
            }
        } catch (\Exception $exception) {
            $progress->fail();

            throw Exception\FilesystemFailureException::forArchiveExtraction($asset->getTempFile(), $exception);
        }

        // Handle temporary assets
        $progress = $this->output->startProgress('Removing temporary files...');
        try {
            $this->filesystem->mirror(Filesystem\Path::join($tempPath, $basePath ?? ''), $targetPath);
            $this->filesystem->remove($tempPath);

            $progress->finish();
        } catch (\Exception $exception) {
            $progress->fail();

            throw $exception;
        }

        return $targetPath;
    }

    public function getAssetPath(Asset\Asset $asset): string
    {
        // Early return if the target is missing
        if (null === $asset->getTarget()) {
            throw Exception\UnsupportedAssetException::create($asset);
        }

        return $this->buildTargetPath($asset->getTarget());
    }

    private function openArchive(Asset\TemporaryAsset $asset): PharData
    {
        switch ($this->determineType($asset)) {
            case self::ZIP:
                $archive = new PharData($asset->getTempFile(), Phar::ZIP | FilesystemIterator::SKIP_DOTS);
                break;

            case self::TAR:
                $archive = new PharData($asset->getTempFile());
                break;

            case self::TAR_GZ:
                $archive = new PharData($asset->getTempFile());
                /** @var PharData $archive */
                $archive = $archive->decompress();
                break;

            default:
                // @codeCoverageIgnoreStart
                throw Exception\UnsupportedAssetException::create($asset);
                // @codeCoverageIgnoreEnd
        }

        return $archive;
    }

    private function determineType(Asset\TemporaryAsset $asset): string
    {
        $normalizedBasename = strtolower(pathinfo($asset->getTempFile(), PATHINFO_BASENAME));

        foreach (self::SUPPORTED_TYPES as $type) {
            if (substr($normalizedBasename, -(strlen($type) + 1)) === '.'.$type) {
                return $type;
            }
        }

        // @codeCoverageIgnoreStart
        throw Exception\UnsupportedAssetException::create($asset);
        // @codeCoverageIgnoreEnd
    }

    private function createTempDirectory(Asset\TemporaryAsset $asset): string
    {
        $parentDirectory = dirname($asset->getTempFile());
        $basename = basename($asset->getTempFile(), '.'.$this->determineType($asset));
        $tempDirectory = Filesystem\Path::join($parentDirectory, sprintf('%s_contents', $basename));
        $this->filesystem->mkdir($tempDirectory);

        return $tempDirectory;
    }

    /**
     * @return array{base: string}
     */
    protected function getDefaultConfiguration(): array
    {
        return self::DEFAULT_CONFIGURATION;
    }
}
