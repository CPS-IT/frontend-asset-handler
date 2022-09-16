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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Helper;

use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Helper;
use Generator;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * StringHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StringHelperTest extends TestCase
{
    /**
     * @test
     */
    public function formatBytesThrowsExceptionIfGivenBytesAreNegative(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionCode(1624613494);
        $this->expectExceptionMessage('Number of bytes must not be lower than zero, -1 given.');

        Helper\StringHelper::formatBytes(-1);
    }

    /**
     * @test
     *
     * @dataProvider formatBytesReturnsHumanReadableBytesDataProvider
     */
    public function formatBytesReturnsHumanReadableBytes(int $bytes, string $expected): void
    {
        self::assertSame($expected, Helper\StringHelper::formatBytes($bytes));
    }

    /**
     * @test
     */
    public function interpolateThrowsExceptionIfReplacementIsMissing(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "foo" is missing or invalid.');

        Helper\StringHelper::interpolate('{foo}', []);
    }

    /**
     * @test
     */
    public function interpolateReplacesPlaceholderValuesByGivenReplacements(): void
    {
        $replacePairs = [
            'name' => 'Bob',
            'location' => 'Berlin',
        ];
        $actual = Helper\StringHelper::interpolate('Hello, {name}! Welcome to {location} :)', $replacePairs);

        self::assertSame('Hello, Bob! Welcome to Berlin :)', $actual);
    }

    /**
     * @test
     */
    public function extractPlaceholdersReturnsExtractedAndResolvedPlaceholdersFromGivenString(): void
    {
        $string = 'Hello, my name is {name}! I\'m living in {city}.';

        self::assertSame(['name', 'city'], Helper\StringHelper::extractPlaceholders($string));
    }

    /**
     * @test
     *
     * @dataProvider urlEncodeReturnsUrlEncodedValueDataProvider
     */
    public function urlEncodeReturnsUrlEncodedValue(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, Helper\StringHelper::urlEncode($value));
    }

    /**
     * @return \Generator<string, array{int, string}>
     */
    public function formatBytesReturnsHumanReadableBytesDataProvider(): Generator
    {
        yield 'zero bytes' => [0, '0 B'];
        yield 'bytes' => [100, '100 B'];
        yield 'kilobytes' => [(int) (1024 * 1.57), '1.57 KB'];
        yield 'megabytes' => [(int) (1024 * 1024 * 3.023), '3.02 MB'];
        yield 'gigabytes' => [(int) (1024 * 1024 * 1024 * 99.999), '100 GB'];
        yield 'terabytes' => [(int) (1024 * 1024 * 1024 * 1024 * 1.026), '1.03 TB'];
        yield 'petabytes' => [(int) (1024 * 1024 * 1024 * 1024 * 1024 * 67.2), '67.2 PB'];
    }

    /**
     * @return Generator<string, array{mixed, mixed}>
     */
    public function urlEncodeReturnsUrlEncodedValueDataProvider(): Generator
    {
        $value = 'äöüß';
        $expected = '%C3%A4%C3%B6%C3%BC%C3%9F';
        $class = new class($value) implements Stringable {
            public function __construct(
                private readonly string $value,
            ) {
            }

            public function __toString()
            {
                return $this->value;
            }
        };

        yield 'string' => [$value, $expected];
        yield 'stringable class' => [$class, $expected];
        yield 'other scalar value' => [false, false];
    }
}
