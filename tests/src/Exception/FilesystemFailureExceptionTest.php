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

namespace CPSIT\FrontendAssetHandler\Tests\Exception;

use CPSIT\FrontendAssetHandler\Exception\FilesystemFailureException;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * FilesystemFailureExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemFailureExceptionTest extends TestCase
{
    #[Test]
    public function forFileCreationReturnsExceptionForGivenFile(): void
    {
        $previous = new Exception('dummy');
        $subject = FilesystemFailureException::forFileCreation('foo', $previous);

        self::assertSame('Unable to create file "foo".', $subject->getMessage());
        self::assertSame(1623913131, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }

    #[Test]
    public function forArchiveExtractionReturnsExceptionForGivenFile(): void
    {
        $previous = new Exception('dummy');
        $subject = FilesystemFailureException::forArchiveExtraction('foo', $previous);

        self::assertSame('Failed to extract archive "foo".', $subject->getMessage());
        self::assertSame(1624040854, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }

    #[Test]
    public function forMissingPathReturnsExceptionForGivenPath(): void
    {
        $subject = FilesystemFailureException::forMissingPath('foo');

        self::assertSame('The path "foo" was expected to exist, but it does not.', $subject->getMessage());
        self::assertSame(1624633845, $subject->getCode());
    }

    #[Test]
    public function forInvalidFileContentsReturnsExceptionForGivenPath(): void
    {
        $subject = FilesystemFailureException::forInvalidFileContents('foo');

        self::assertSame('The contents of file "foo" are invalid.', $subject->getMessage());
        self::assertSame(1627923069, $subject->getCode());
    }

    #[Test]
    public function forFailedWriteOperationReturnsExceptionForFailedWriteOperation(): void
    {
        $subject = FilesystemFailureException::forFailedWriteOperation('foo');

        self::assertSame('An error occurred when writing file contents to "foo".', $subject->getMessage());
        self::assertSame(1639415423, $subject->getCode());
    }

    #[Test]
    public function forUnresolvableProjectDirectoryReturnsExceptionForUnresolvableProjectDirectory(): void
    {
        $subject = FilesystemFailureException::forUnresolvableProjectDirectory();

        self::assertSame('Unable to resolve the project\'s root path.', $subject->getMessage());
        self::assertSame(1662449245, $subject->getCode());
    }

    #[Test]
    public function forUnresolvableWorkingDirectoryReturnsExceptionForUnresolvableWorkingDirectory(): void
    {
        $subject = FilesystemFailureException::forUnresolvableWorkingDirectory();

        self::assertSame('Unable to resolve the current working directory.', $subject->getMessage());
        self::assertSame(1662449443, $subject->getCode());
    }
}
