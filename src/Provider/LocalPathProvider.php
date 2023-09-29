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

namespace CPSIT\FrontendAssetHandler\Provider;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Traits;
use Symfony\Component\Filesystem;
use Symfony\Component\Process;

use function str_starts_with;

/**
 * LocalPathProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class LocalPathProvider implements ProviderInterface, ChattyInterface
{
    use Traits\OutputAwareTrait;

    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
    ) {}

    public static function getName(): string
    {
        return 'local';
    }

    public function fetchAsset(Asset\Definition\Source $source): Asset\Asset
    {
        $url = $this->getAssetUrl($source);

        // Generate asset source
        if (null !== $source->getCommand()) {
            $config = $source->getConfig();
            $config['cwd'] = Helper\FilesystemHelper::getWorkingDirectory();
            $config['url'] = $url;

            $command = Helper\StringHelper::interpolate($source->getCommand(), $config);

            $this->generateSourceFile($command);
        }

        // Check if file exists
        if (!$this->isValidFile($url)) {
            throw Exception\FilesystemFailureException::forInvalidFile($url);
        }

        return new Asset\TemporaryAsset($source, $url);
    }

    public function getAssetUrl(Asset\Definition\Source $source): string
    {
        if (null === $source->getUrl()) {
            throw Exception\MissingConfigurationException::forKey('source/url');
        }

        $config = $source->getConfig();
        $config['cwd'] = Helper\FilesystemHelper::getWorkingDirectory();

        // Create temporary filename as placeholder
        if (str_starts_with($source->getUrl(), '{temp}')) {
            $config['temp'] = Helper\FilesystemHelper::createTemporaryFile(filenameOnly: true);
        }

        $url = Helper\StringHelper::interpolate($source->getUrl(), $config);

        return Helper\FilesystemHelper::resolveRelativePath($url);
    }

    private function generateSourceFile(string $command): void
    {
        $progress = $this->output->startProgress('Generating source file...');

        // Run command
        $process = Process\Process::fromShellCommandline($command);
        $process->run();

        // Early return if command fails
        if (!$process->isSuccessful()) {
            $progress->fail();

            $this->output->writeln($process->getErrorOutput());

            throw Exception\FilesystemFailureException::forFailedCommandExecution($command);
        }

        $progress->finish();
    }

    private function isValidFile(string $file): bool
    {
        return Filesystem\Path::isLocal($file) && $this->filesystem->exists($file);
    }
}
