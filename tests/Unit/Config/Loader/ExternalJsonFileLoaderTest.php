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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Loader;

use CPSIT\FrontendAssetHandler\Config\Config;
use CPSIT\FrontendAssetHandler\Config\Loader\ExternalJsonFileLoader;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Exception\UnprocessableConfigFileException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * ExternalJsonFileLoaderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExternalJsonFileLoaderTest extends TestCase
{
    private ExternalJsonFileLoader $subject;

    protected function setUp(): void
    {
        $this->subject = new ExternalJsonFileLoader();
    }

    #[Test]
    public function loadThrowsExceptionIfJsonIsUnsupported(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/JsonFiles/unsupported-json.json';

        $this->expectException(UnprocessableConfigFileException::class);
        $this->expectExceptionCode(1643103076);
        $this->expectExceptionMessage(
            sprintf('The config file "%s" cannot be processed.', $filePath),
        );

        $this->subject->load($filePath);
    }

    #[Test]
    public function loadThrowsExceptionIfJsonStructureIsInvalid(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/JsonFiles/valid-json.json';

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "frontend-assets" is missing or invalid.');

        $this->subject->load($filePath);
    }

    #[Test]
    public function loadReturnsResolvedConfigObject(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/JsonFiles/assets.json';
        $expected = new Config([
            'frontend-assets' => [
                [
                    'handler' => 'dummy',
                    'source' => [
                        'type' => 'dummy',
                        'url' => 'https://www.example.com/assets/{environment}.tar.gz',
                        'revision-url' => 'https://www.example.com/assets/{environment}/REVISION',
                    ],
                    'target' => [
                        'type' => 'dummy',
                        'path' => 'foo',
                    ],
                    'environments' => [
                        'map' => [
                            'foo' => 'foo',
                        ],
                    ],
                    'vcs' => [
                        'type' => 'dummy',
                        'foo' => 'foo',
                    ],
                ],
                [
                    'handler' => 'dummy',
                    'source' => [
                        'type' => 'dummy',
                        'url' => 'https://www.example.com/assets/{environment}.tar.gz',
                        'revision-url' => 'https://www.example.com/assets/{environment}/REVISION',
                    ],
                    'target' => [
                        'type' => 'dummy',
                        'path' => 'baz',
                    ],
                    'environments' => [
                        'map' => [
                            'baz' => 'baz',
                        ],
                    ],
                    'vcs' => [
                        'type' => 'dummy',
                        'baz' => 'baz',
                    ],
                ],
            ],
        ], $filePath);

        self::assertEquals($expected, $this->subject->load($filePath));
    }

    #[Test]
    public function canLoadReturnsTrueIfGivenFileIsNoComposerJsonFile(): void
    {
        self::assertTrue(ExternalJsonFileLoader::canLoad('/foo/baz/assets.json'));
    }

    #[Test]
    public function canLoadReturnsFalseIfGivenFileIsNoJsonFile(): void
    {
        self::assertFalse(ExternalJsonFileLoader::canLoad('/foo/baz/image.png'));
    }

    #[Test]
    #[DataProvider('canLoadReturnsFalseIfGivenFileIsComposerJsonFileDataProvider')]
    public function canLoadReturnsFalseIfGivenFileIsComposerJsonFile(string $filePath): void
    {
        self::assertFalse(ExternalJsonFileLoader::canLoad($filePath));
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function canLoadReturnsFalseIfGivenFileIsComposerJsonFileDataProvider(): Generator
    {
        yield 'absolute path' => ['/foo/baz/composer.json'];
        yield 'relative path' => ['../baz/composer.json'];
        yield 'filename only' => ['composer.json'];
    }
}
