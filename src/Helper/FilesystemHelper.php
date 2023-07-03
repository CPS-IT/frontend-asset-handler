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

namespace CPSIT\FrontendAssetHandler\Helper;

use Composer\InstalledVersions;
use CPSIT\FrontendAssetHandler\Exception;
use Ergebnis\Json\Normalizer;
use OutOfBoundsException;
use Phar;
use Symfony\Component\Filesystem;

use function getcwd;
use function ltrim;
use function register_shutdown_function;

/**
 * FilesystemHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final class FilesystemHelper
{
    private static ?Filesystem\Filesystem $filesystem = null;

    /**
     * @var list<string>
     */
    private static array $tempFiles = [];

    public static function getProjectDirectory(): string
    {
        $projectDirectory = null;

        try {
            $projectDirectory = InstalledVersions::getInstallPath('cpsit/frontend-asset-handler');
        } catch (OutOfBoundsException) {
            // Intentionally left blank.
        }

        // @codeCoverageIgnoreStart
        if (null === $projectDirectory) {
            throw Exception\FilesystemFailureException::forUnresolvableProjectDirectory();
        }
        // @codeCoverageIgnoreEnd

        return Filesystem\Path::canonicalize($projectDirectory);
    }

    public static function getWorkingDirectory(): string
    {
        if ('' !== Phar::running()) {
            $cwd = getcwd();
        } else {
            $cwd = InstalledVersions::getRootPackage()['install_path'];
        }

        // @codeCoverageIgnoreStart
        if (false === $cwd) {
            throw Exception\FilesystemFailureException::forUnresolvableWorkingDirectory();
        }
        // @codeCoverageIgnoreEnd

        return Filesystem\Path::canonicalize($cwd);
    }

    public static function resolveRelativePath(string $relativePath): string
    {
        $filesystem = self::getFilesystem();

        if ($filesystem->isAbsolutePath($relativePath)) {
            return $relativePath;
        }

        return Filesystem\Path::join(self::getWorkingDirectory(), $relativePath);
    }

    public static function parseJsonFileContents(string $filePath): Normalizer\Json
    {
        if (!file_exists($filePath)) {
            throw Exception\FilesystemFailureException::forMissingPath($filePath);
        }

        $encoded = (string) file_get_contents($filePath);

        return Normalizer\Json::fromEncoded($encoded);
    }

    public static function createTemporaryFile(string $extension = '', bool $filenameOnly = false): string
    {
        $filesystem = self::getFilesystem();

        // Remove all temporary files on shutdown
        if ([] === self::$tempFiles) {
            register_shutdown_function(fn () => $filesystem->remove(self::$tempFiles));
        }

        // Create temporary file
        $extension = ltrim($extension, '.');
        $tempFile = $filesystem->tempnam(sys_get_temp_dir(), 'frontend_asset_handler_', '' !== $extension ? '.'.$extension : '');

        if ($filenameOnly) {
            // Remove file if only filename should be returned
            $filesystem->remove($tempFile);
        } else {
            // Register temporary file to be removed on shutdown
            self::$tempFiles[] = $tempFile;
        }

        return $tempFile;
    }

    private static function getFilesystem(): Filesystem\Filesystem
    {
        if (null === self::$filesystem) {
            self::$filesystem = new Filesystem\Filesystem();
        }

        return self::$filesystem;
    }
}
