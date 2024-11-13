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

namespace CPSIT\FrontendAssetHandler\Tests\Helper;

use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ArrayHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ArrayHelperTest extends TestCase
{
    /**
     * @param array<int, array<string, string>> $array
     * @param list<string>                      $keys
     * @param array<int, array<string, string>> $expected
     */
    #[Test]
    #[DataProvider('filterSubArraysByKeysFiltersEachSubArrayByGivenKeysDataProvider')]
    public function filterSubArraysByKeysFiltersEachSubArrayByGivenKeys(array $array, array $keys, array $expected): void
    {
        self::assertSame($expected, Helper\ArrayHelper::filterSubArraysByKeys($array, $keys));
    }

    #[Test]
    public function getArrayValueByPathThrowsExceptionIfGivenPathDoesNotExist(): void
    {
        $this->expectException(Exception\MissingConfigurationException::class);
        $this->expectExceptionMessage('Configuration for key "foo/baz" is missing or invalid.');
        $this->expectExceptionCode(1623867663);

        Helper\ArrayHelper::getArrayValueByPath(['foo' => 'baz'], 'foo/baz');
    }

    #[Test]
    public function getArrayValueByPathReturnsArrayValueAtGivenPath(): void
    {
        $array = [
            'foo' => [
                'baz' => [
                    'hello' => 'world!',
                ],
            ],
        ];

        self::assertSame(['hello' => 'world!'], Helper\ArrayHelper::getArrayValueByPath($array, 'foo/baz'));
    }

    /**
     * @param array<string, array<mixed>> $expected
     */
    #[Test]
    #[DataProvider('setArrayValueByPathAppliesGivenValueToArrayAtGivenPathDataProvider')]
    public function setArrayValueByPathAppliesGivenValueToArrayAtGivenPath(string $path, mixed $value, array $expected): void
    {
        $array = [
            'foo' => [
                'baz' => null,
            ],
        ];

        self::assertSame($expected, Helper\ArrayHelper::setArrayValueByPath($array, $path, $value));
    }

    /**
     * @param array<mixed> $array
     * @param array<mixed> $expected
     */
    #[Test]
    #[DataProvider('unsetArrayValueByPathUnsetsGivenValueAtGivenPathInArrayDataProvider')]
    public function unsetArrayValueByPathUnsetsGivenValueAtGivenPathInArray(array $array, string $path, array $expected): void
    {
        self::assertSame($expected, Helper\ArrayHelper::unsetArrayValueByPath($array, $path));
    }

    /**
     * @return Generator<string, array{array<int, array<string, string>>, list<string>, array<int, array<string, string>>}>
     */
    public static function filterSubArraysByKeysFiltersEachSubArrayByGivenKeysDataProvider(): Generator
    {
        $array = [
            0 => [
                'foo' => 'baz',
                'hello' => 'world',
            ],
            1 => [
                'foo' => 'another baz',
            ],
            2 => [
                'hello' => 'another world',
            ],
        ];

        yield 'empty array' => [[], [], []];
        yield 'no keys' => [$array, [], $array];
        yield 'matching key "foo"' => [$array, ['foo'], [0 => ['foo' => 'baz'], 1 => ['foo' => 'another baz']]];
        yield 'matching key "hello"' => [$array, ['hello'], [0 => ['hello' => 'world'], 2 => ['hello' => 'another world']]];
        yield 'two matching keys' => [$array, ['foo', 'hello'], $array];
    }

    /**
     * @return Generator<string, array{string, string|array<string, string>, array<string, array<mixed>>}>
     */
    public static function setArrayValueByPathAppliesGivenValueToArrayAtGivenPathDataProvider(): Generator
    {
        $default = [
            'foo' => [
                'baz' => [
                    'hello' => 'world!',
                ],
            ],
        ];
        $withMissingNodes = [
            'foo' => [
                'baz' => null,
                'bar' => [
                    'hello' => 'world!',
                ],
            ],
        ];

        yield 'string as value' => ['foo/baz/hello', 'world!', $default];
        yield 'array as value (known nodes)' => ['foo/baz', ['hello' => 'world!'], $default];
        yield 'array as value (unknown nodes)' => ['foo/bar', ['hello' => 'world!'], $withMissingNodes];
    }

    /**
     * @return Generator<string, array<int, string|array<mixed>>>
     */
    public static function unsetArrayValueByPathUnsetsGivenValueAtGivenPathInArrayDataProvider(): Generator
    {
        $array = [
            'foo' => [
                'baz' => [
                    'hello' => 'world!',
                ],
            ],
        ];
        $modifiedArray = $array;
        unset($modifiedArray['foo']['baz']);

        yield 'empty array' => [[], 'foo', []];
        yield 'empty path' => [$array, '', $array];
        yield 'unknown path' => [$array, 'baz', $array];
        yield 'known path' => [$array, 'foo/baz', $modifiedArray];
    }
}
