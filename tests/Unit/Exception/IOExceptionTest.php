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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Exception;

use CPSIT\FrontendAssetHandler\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console;

use function sprintf;

/**
 * IOExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class IOExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function forUnsupportedOutputReturnsExceptionForGivenOutput(): void
    {
        $actual = Exception\IOException::forUnsupportedOutput(new Console\Output\NullOutput());

        self::assertInstanceOf(Exception\IOException::class, $actual);
        self::assertSame(sprintf('The output "%s" is not supported.', Console\Output\NullOutput::class), $actual->getMessage());
        self::assertSame(1661872012, $actual->getCode());
    }

    /**
     * @test
     */
    public function forMissingOutputStreamReturnsExceptionForMissingOutputStream(): void
    {
        $actual = Exception\IOException::forMissingOutputStream();

        self::assertInstanceOf(Exception\IOException::class, $actual);
        self::assertSame('No output stream is available.', $actual->getMessage());
        self::assertSame(1661873512, $actual->getCode());
    }

    /**
     * @test
     */
    public function forUnprocessableOutputStreamReturnsExceptionForUnprocessableOutputStream(): void
    {
        $actual = Exception\IOException::forUnprocessableOutputStream();

        self::assertInstanceOf(Exception\IOException::class, $actual);
        self::assertSame('The output stream cannot be processed.', $actual->getMessage());
        self::assertSame(1661873639, $actual->getCode());
    }
}
