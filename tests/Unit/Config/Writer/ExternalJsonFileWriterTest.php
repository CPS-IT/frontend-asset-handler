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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Writer;

use CPSIT\FrontendAssetHandler\Config\Config;
use CPSIT\FrontendAssetHandler\Config\Writer\ExternalJsonFileWriter;
use CPSIT\FrontendAssetHandler\Exception\UnprocessableConfigFileException;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;

/**
 * ExternalJsonFileWriterTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExternalJsonFileWriterTest extends ContainerAwareTestCase
{
    private ExternalJsonFileWriter $subject;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(ExternalJsonFileWriter::class);
        $this->filesystem = $this->container->get(Filesystem::class);
    }

    #[Test]
    public function writeCreatesANewConfigFileIfItDoesNotExistYet(): void
    {
        $targetFile = $this->filesystem->tempnam(sys_get_temp_dir(), 'fah_assets.json_');
        $this->filesystem->remove($targetFile);

        $config = new Config([
            'frontend-assets' => [
                [
                    'source' => [
                        'url' => 'baz',
                    ],
                    'target' => [
                        'path' => 'baz',
                    ],
                ],
            ],
        ], $targetFile);

        $expected = json_encode([
            'frontend-assets' => [
                [
                    'source' => [
                        'url' => 'baz',
                    ],
                    'target' => [
                        'path' => 'baz',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->subject->write($config);

        self::assertFileExists($targetFile);
        self::assertJsonStringEqualsJsonFile($targetFile, $expected);
    }

    #[Test]
    public function writeThrowsExceptionIfOriginalFileIsNotSupported(): void
    {
        $originalFile = dirname(__DIR__, 2).'/Fixtures/JsonFiles/unsupported-json.json';
        $config = new Config([], $originalFile);

        $this->expectException(UnprocessableConfigFileException::class);
        $this->expectExceptionCode(1643103076);
        $this->expectExceptionMessage(
            sprintf('The config file "%s" cannot be processed.', $originalFile)
        );

        $this->subject->write($config);
    }

    #[Test]
    public function writeMergesOriginalFileWithGivenConfig(): void
    {
        $originalFile = dirname(__DIR__, 2).'/Fixtures/JsonFiles/assets.json';
        $targetFile = $this->filesystem->tempnam(sys_get_temp_dir(), 'fah_assets.json_');
        $config = new Config([
            'frontend-assets' => [
                [
                    'source' => [
                        'url' => 'baz',
                    ],
                    'target' => [
                        'path' => 'baz',
                    ],
                ],
            ],
        ], $targetFile);

        $expected = json_encode([
            'frontend-assets' => [
                [
                    'source' => [
                        'url' => 'baz',
                    ],
                    'target' => [
                        'path' => 'baz',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->filesystem->copy($originalFile, $targetFile, true);

        $this->subject->write($config);

        self::assertFileExists($targetFile);
        self::assertJsonStringEqualsJsonFile($targetFile, $expected);

        $this->filesystem->remove($targetFile);
    }

    #[Test]
    public function canWriteReturnsTrueIfGivenFileIsNoComposerJsonFile(): void
    {
        $config = new Config([], '/foo/baz/assets.json');

        self::assertTrue(ExternalJsonFileWriter::canWrite($config));
    }

    #[Test]
    public function canWriteReturnsFalseIfGivenFileIsNoJsonFile(): void
    {
        $config = new Config([], '/foo/baz/image.png');

        self::assertFalse(ExternalJsonFileWriter::canWrite($config));
    }

    #[Test]
    #[DataProvider('canWriteReturnsFalseIfGivenFileIsComposerJsonFileDataProvider')]
    public function canWriteReturnsFalseIfGivenFileIsComposerJsonFile(string $filePath): void
    {
        $config = new Config([], $filePath);

        self::assertFalse(ExternalJsonFileWriter::canWrite($config));
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function canWriteReturnsFalseIfGivenFileIsComposerJsonFileDataProvider(): Generator
    {
        yield 'absolute path' => ['/foo/baz/composer.json'];
        yield 'relative path' => ['../baz/composer.json'];
        yield 'filename only' => ['composer.json'];
    }
}
