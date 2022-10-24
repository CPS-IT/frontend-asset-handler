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
use Symfony\Component\Filesystem;

use function getcwd;

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

    public static function resolveRelativePath(string $relativePath, bool $relativeToWorkingDirectory = false): string
    {
        $filesystem = new Filesystem\Filesystem();

        if ($filesystem->isAbsolutePath($relativePath)) {
            return $relativePath;
        }

        $basePath = $relativeToWorkingDirectory ? getcwd() : self::getProjectDirectory();

        // @codeCoverageIgnoreStart
        if (false === $basePath) {
            throw Exception\FilesystemFailureException::forUnresolvableWorkingDirectory();
        }
        // @codeCoverageIgnoreEnd

        return Filesystem\Path::join($basePath, $relativePath);
    }

    public static function parseJsonFileContents(string $filePath): Normalizer\Json
    {
        if (!file_exists($filePath)) {
            throw Exception\FilesystemFailureException::forMissingPath($filePath);
        }

        $encoded = file_get_contents($filePath) ?: '';

        return Normalizer\Json::fromEncoded($encoded);
    }
}
