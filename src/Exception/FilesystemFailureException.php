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

use function sprintf;

/**
 * FilesystemFailureException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemFailureException extends RuntimeException
{
    public static function forFileCreation(string $file, ?Throwable $previous = null): self
    {
        return new self(sprintf('Unable to create file "%s".', $file), 1623913131, $previous);
    }

    public static function forArchiveExtraction(string $file, ?Throwable $previous = null): self
    {
        return new self(sprintf('Failed to extract archive "%s".', $file), 1624040854, $previous);
    }

    public static function forMissingPath(string $path): self
    {
        return new self(sprintf('The path "%s" was expected to exist, but it does not.', $path), 1624633845);
    }

    public static function forInvalidFile(string $path): self
    {
        return new self(sprintf('The file "%s" is invalid or not supported.', $path), 1670866771);
    }

    public static function forInvalidFileContents(string $path): self
    {
        return new self(sprintf('The contents of file "%s" are invalid.', $path), 1627923069);
    }

    public static function forFailedWriteOperation(string $path): self
    {
        return new self(sprintf('An error occurred when writing file contents to "%s".', $path), 1639415423);
    }

    public static function forFailedCommandExecution(string $command): self
    {
        return new self(sprintf('An error occurred while executing "%s".', $command), 1670866484);
    }

    public static function forUnresolvableProjectDirectory(): self
    {
        return new self('Unable to resolve the project\'s root path.', 1662449245);
    }

    public static function forUnresolvableWorkingDirectory(): self
    {
        return new self('Unable to resolve the current working directory.', 1662449443);
    }
}
