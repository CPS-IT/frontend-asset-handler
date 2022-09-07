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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Parser;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use Generator;
use PHPUnit\Framework\TestCase;

use function dirname;

/**
 * ServicesParserTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ServicesParserTest extends TestCase
{
    private Config\Parser\ServicesParser $subject;

    protected function setUp(): void
    {
        $this->subject = new Config\Parser\ServicesParser();
    }

    /**
     * @test
     */
    public function parseDoesNothingIfNoServicesAreConfiguredInGivenConfig(): void
    {
        $config = new Config\Config([], 'foo');
        $expected = new Config\Config(['services' => []], 'foo');

        self::assertEquals($expected, $this->subject->parse($config));
    }

    /**
     * @test
     */
    public function parseThrowsExceptionIfServiceConfigurationIsMissing(): void
    {
        $config = new Config\Config(['services' => ['/foo']], 'foo');

        $this->expectExceptionObject(Exception\FilesystemFailureException::forMissingPath('/foo'));

        $this->subject->parse($config);
    }

    /**
     * @test
     */
    public function parseThrowsExceptionIfServiceConfigurationIsNotSupported(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/JsonFiles/assets.json';
        $config = new Config\Config(['services' => [$filePath]], 'foo');

        $this->expectExceptionObject(Exception\UnprocessableConfigFileException::create($filePath));

        $this->subject->parse($config);
    }

    /**
     * @test
     */
    public function parseThrowsExceptionIfServiceConfigurationIsInvalid(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/PhpFiles/invalid-service.php';
        $config = new Config\Config(['services' => [$filePath]], 'foo');

        $this->expectExceptionObject(Exception\UnprocessableConfigFileException::create($filePath));

        $this->subject->parse($config);
    }

    /**
     * @test
     */
    public function parseThrowsExceptionIfServiceConfigurationIsIncomplete(): void
    {
        $filePath = dirname(__DIR__, 2).'/Fixtures/PhpFiles/incomplete-service.php';
        $config = new Config\Config(['services' => [$filePath]], 'foo');

        $this->expectExceptionObject(Exception\UnprocessableConfigFileException::create($filePath));

        $this->subject->parse($config);
    }

    /**
     * @test
     *
     * @dataProvider parseParsesSupportedFilesDataProvider
     */
    public function parseParsesSupportedFiles(string $filePath): void
    {
        $config = new Config\Config(['services' => [$filePath]], 'foo');

        self::assertEquals($config, $this->subject->parse($config));
    }

    /**
     * @return Generator<string, array{string}>
     */
    public function parseParsesSupportedFilesDataProvider(): Generator
    {
        $basePath = dirname(__DIR__, 2).'/Fixtures';

        yield 'php file' => [$basePath.'/PhpFiles/services.php'];
        yield 'yml file' => [$basePath.'/YamlFiles/services.yml'];
        yield 'yaml file' => [$basePath.'/YamlFiles/services.yaml'];
    }
}
