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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Exception;

use CPSIT\FrontendAssetHandler\Exception\DownloadFailedException;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DownloadFailedExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DownloadFailedExceptionTest extends TestCase
{
    #[Test]
    public function createReturnsException(): void
    {
        $previous = new Exception('dummy');
        $subject = DownloadFailedException::create('foo', 'baz', $previous);

        self::assertInstanceOf(DownloadFailedException::class, $subject);
        self::assertSame('An error occurred while downloading "foo" to "baz".', $subject->getMessage());
        self::assertSame(1623862554, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }

    #[Test]
    public function forUnauthorizedRequestReturnsException(): void
    {
        $previous = new Exception('dummy');
        $subject = DownloadFailedException::forUnauthorizedRequest('foo', $previous);

        self::assertInstanceOf(DownloadFailedException::class, $subject);
        self::assertSame('You are not authorized to download "foo" (Error 401).', $subject->getMessage());
        self::assertSame(1624037646, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }

    #[Test]
    public function forUnavailableRequestReturnsException(): void
    {
        $previous = new Exception('dummy');
        $subject = DownloadFailedException::forUnavailableTarget('foo', $previous);

        self::assertInstanceOf(DownloadFailedException::class, $subject);
        self::assertSame('The requested URL "foo" is not available (Error 404).', $subject->getMessage());
        self::assertSame(1624037782, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }

    #[Test]
    public function forFailedVerificationReturnsException(): void
    {
        $previous = new Exception('dummy');
        $subject = DownloadFailedException::forFailedVerification('foo', 'baz', $previous);

        self::assertInstanceOf(DownloadFailedException::class, $subject);
        self::assertSame('Download verification failed for target file "baz" from source "foo".', $subject->getMessage());
        self::assertSame(1625218841, $subject->getCode());
        self::assertSame($previous, $subject->getPrevious());
    }
}
