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

namespace CPSIT\FrontendAssetHandler\Exception;

use RuntimeException;
use Throwable;

/**
 * DownloadFailedException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DownloadFailedException extends RuntimeException
{
    public static function create(string $url, string $targetFile, Throwable $previous = null): self
    {
        return new self(
            sprintf('An error occurred while downloading "%s" to "%s".', $url, $targetFile),
            1623862554,
            $previous
        );
    }

    public static function forUnauthorizedRequest(string $url, Throwable $previous = null): self
    {
        return new self(
            sprintf('You are not authorized to download "%s" (Error 401).', $url),
            1624037646,
            $previous
        );
    }

    public static function forUnavailableTarget(string $url, Throwable $previous = null): self
    {
        return new self(
            sprintf('The requested URL "%s" is not available (Error 404).', $url),
            1624037782,
            $previous
        );
    }

    public static function forFailedVerification(string $url, string $targetFile, Throwable $previous = null): self
    {
        return new self(
            sprintf('Download verification failed for target file "%s" from source "%s".', $targetFile, $url),
            1625218841,
            $previous
        );
    }
}
