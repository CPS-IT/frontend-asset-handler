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

use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use Ergebnis\Json\Normalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

use function dirname;

/**
 * FilesystemHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemHelperTest extends TestCase
{
    /**
     * @test
     */
    public function getProjectDirectoryReturnsProjectDirectory(): void
    {
        $expected = dirname(__DIR__, 3);

        self::assertSame($expected, Helper\FilesystemHelper::getProjectDirectory());
    }

    /**
     * @test
     */
    public function resolveRelativePathReturnsGivenPathIfItIsAnAbsolutePath(): void
    {
        $path = '/foo/baz';

        self::assertSame($path, Helper\FilesystemHelper::resolveRelativePath($path));
    }

    /**
     * @test
     */
    public function resolveRelativePathMakesRelativePathAbsolute(): void
    {
        $path = 'foo';
        $expected = dirname(__DIR__, 3).'/foo';

        self::assertSame($expected, Helper\FilesystemHelper::resolveRelativePath($path));
    }

    /**
     * @test
     */
    public function parseJsonFileContentsThrowsExceptionIfGivenFileDoesNotExist(): void
    {
        $this->expectException(Exception\FilesystemFailureException::class);
        $this->expectExceptionCode(1624633845);
        $this->expectExceptionMessage('The path "foo" was expected to exist, but it does not.');

        Helper\FilesystemHelper::parseJsonFileContents('foo');
    }

    /**
     * @test
     */
    public function parseJsonFileContentsThrowsExceptionIfGivenFileDoesNotContainJson(): void
    {
        $filePath = dirname(__DIR__).'/Fixtures/JsonFiles/invalid-json.json';

        $this->expectException(Normalizer\Exception\InvalidJsonEncodedException::class);

        Helper\FilesystemHelper::parseJsonFileContents($filePath);
    }

    /**
     * @test
     */
    public function parseJsonFileContentsReturnsParsedJsonFileContents(): void
    {
        $filePath = dirname(__DIR__).'/Fixtures/JsonFiles/valid-json.json';

        $expected = new stdClass();
        $expected->foo = 'baz';
        $actual = Helper\FilesystemHelper::parseJsonFileContents($filePath);

        self::assertInstanceOf(Normalizer\Json::class, $actual);
        self::assertEquals($expected, $actual->decoded());
        self::assertJsonStringEqualsJsonString('{"foo":"baz"}', $actual->encoded());
    }
}
