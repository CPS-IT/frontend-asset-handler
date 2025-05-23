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
use Ergebnis\Json;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function dirname;
use function pathinfo;

/**
 * FilesystemHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FilesystemHelperTest extends TestCase
{
    #[Test]
    public function getProjectDirectoryReturnsProjectDirectory(): void
    {
        $expected = dirname(__DIR__, 3);

        self::assertSame($expected, Helper\FilesystemHelper::getProjectDirectory());
    }

    #[Test]
    public function getWorkingDirectoryReturnsCurrentWorkingDirectory(): void
    {
        $expected = dirname(__DIR__, 3);

        self::assertSame($expected, Helper\FilesystemHelper::getWorkingDirectory());
    }

    #[Test]
    public function resolveRelativePathReturnsGivenPathIfItIsAnAbsolutePath(): void
    {
        $path = '/foo/baz';

        self::assertSame($path, Helper\FilesystemHelper::resolveRelativePath($path));
    }

    #[Test]
    public function resolveRelativePathMakesRelativePathAbsolute(): void
    {
        $path = 'foo';
        $expected = dirname(__DIR__, 3).'/foo';

        self::assertSame($expected, Helper\FilesystemHelper::resolveRelativePath($path));
    }

    #[Test]
    public function parseJsonFileContentsThrowsExceptionIfGivenFileDoesNotExist(): void
    {
        $this->expectException(Exception\FilesystemFailureException::class);
        $this->expectExceptionCode(1624633845);
        $this->expectExceptionMessage('The path "foo" was expected to exist, but it does not.');

        Helper\FilesystemHelper::parseJsonFileContents('foo');
    }

    #[Test]
    public function parseJsonFileContentsThrowsExceptionIfGivenFileDoesNotContainJson(): void
    {
        $filePath = dirname(__DIR__).'/Fixtures/JsonFiles/invalid-json.json';

        $this->expectException(Json\Exception\NotJson::class);

        Helper\FilesystemHelper::parseJsonFileContents($filePath);
    }

    #[Test]
    public function parseJsonFileContentsReturnsParsedJsonFileContents(): void
    {
        $filePath = dirname(__DIR__).'/Fixtures/JsonFiles/valid-json.json';

        $expected = new stdClass();
        $expected->foo = 'baz';
        $actual = Helper\FilesystemHelper::parseJsonFileContents($filePath);

        self::assertEquals($expected, $actual->decoded());
        self::assertJsonStringEqualsJsonString('{"foo":"baz"}', $actual->encoded());
    }

    #[Test]
    #[DataProvider('createTemporaryFileCreatesTemporaryFileDataProvider')]
    public function createTemporaryFileCreatesTemporaryFile(string $extension, string $expected): void
    {
        $actual = Helper\FilesystemHelper::createTemporaryFile($extension);

        self::assertFileExists($actual);
        self::assertSame($expected, pathinfo($actual, PATHINFO_EXTENSION));
    }

    #[Test]
    public function createTemporaryFileReturnsOnlyFilename(): void
    {
        $actual = Helper\FilesystemHelper::createTemporaryFile(filenameOnly: true);

        self::assertFileDoesNotExist($actual);
    }

    #[Test]
    #[DataProvider('getFileExtensionReturnsFileExtensionDataProvider')]
    public function getFileExtensionReturnsFileExtension(string $path, string $expected): void
    {
        self::assertSame($expected, Helper\FilesystemHelper::getFileExtension($path));
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function createTemporaryFileCreatesTemporaryFileDataProvider(): Generator
    {
        yield 'no extension' => ['', ''];
        yield 'extension without dot' => ['foo', 'foo'];
        yield 'extension with dot' => ['.foo', 'foo'];
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function getFileExtensionReturnsFileExtensionDataProvider(): Generator
    {
        yield 'no extension' => ['/tmp/foo', ''];
        yield 'simple extension' => ['/tmp/foo.bar', 'bar'];
        yield 'gzipped extension' => ['/tmp/foo.bar.gz', 'bar.gz'];
    }
}
