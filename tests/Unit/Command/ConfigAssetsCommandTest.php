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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Command;

use CPSIT\FrontendAssetHandler\Command;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use Generator;

use function dirname;
use function json_encode;

/**
 * ConfigAssetsCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigAssetsCommandTest extends Tests\Unit\CommandTesterAwareTestCase
{
    /**
     * @test
     */
    public function executeFailsAndWritesErrorIfPathIsEmpty(): void
    {
        $exitCode = $this->commandTester->execute([]);

        self::assertSame(2, $exitCode);
        self::assertStringContainsString('The configuration path must not be empty.', $this->commandTester->getDisplay());
    }

    /**
     * @test
     *
     * @dataProvider executeFailsAndWritesErrorIfConflictingParametersAreGivenDataProvider
     *
     * @param array<string, bool|string> $input
     */
    public function executeFailsAndWritesErrorIfConflictingParametersAreGiven(array $input, string $expected): void
    {
        $exitCode = $this->commandTester->execute($input);

        self::assertSame(4, $exitCode);
        self::assertStringContainsString($expected, $this->commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfNoConfigFileIsConfigured(): void
    {
        $this->initializeCommandTester();

        $this->expectExceptionObject(Exception\MissingConfigurationException::create());

        $this->commandTester->execute([
            'path' => 'foo',
            '--unset' => true,
        ]);
    }

    /**
     * @test
     *
     * @dataProvider executeUnsetsConfigurationAtGivenPathDataProvider
     *
     * @param array{frontend-assets: list<array<string, array<string, mixed>>>} $expected
     */
    public function executeUnsetsConfigurationAtGivenPath(string $path, array $expected): void
    {
        $exitCode = $this->commandTester->execute([
            'path' => $path,
            '--unset' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertMatchesRegularExpression(
            '/Configuration at \S+ was successfully unset\./',
            $this->commandTester->getDisplay()
        );
        self::assertNotNull($this->configFile);
        self::assertJsonStringEqualsJsonFile($this->configFile, json_encode($expected, JSON_THROW_ON_ERROR));
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfConfigurationIsInvalidAfterUnset(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1643113965);
        $this->expectExceptionMessage(
            'The configuration is invalid: '.PHP_EOL.'  * [/frontend-assets/0/source/type]: The property type is required'
        );

        $this->commandTester->execute([
            'path' => '0/source/type',
            '--unset' => true,
        ]);
    }

    /**
     * @test
     */
    public function executeFailsAndPrintsErrorIfEssentialConfigIsMissing(): void
    {
        $this->initializeCommandTester(dirname(__DIR__).'/Fixtures/JsonFiles/invalid-assets.json');

        $exitCode = $this->commandTester->execute([
            '--validate' => true,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(8, $exitCode);
        self::assertStringContainsString('Your asset configuration is invalid.', $output);
        self::assertMatchesRegularExpression('/\[0]\[target]\s+The property target is required/', $output);
        self::assertMatchesRegularExpression('/\[0]\[source]\[type]\s+The property type is required/', $output);
        self::assertMatchesRegularExpression('/\[0]\[source]\[url]\s+The property url is required/', $output);
    }

    /**
     * @test
     */
    public function executePrintsSuccessMessageIfConfigIsValid(): void
    {
        $exitCode = $this->commandTester->execute([
            '--validate' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Your asset configuration is valid.', $this->commandTester->getDisplay());
    }

    /**
     * @return \Generator<string, array{array<string, bool|string>, string}>
     */
    public function executeFailsAndWritesErrorIfConflictingParametersAreGivenDataProvider(): Generator
    {
        yield 'unset and new value' => [
            [
                'newValue' => 'foo',
                'path' => 'baz',
                '--unset' => true,
            ],
            'You cannot write or validate and unset a configuration value one at a time.',
        ];
        yield 'validate and new value' => [
            [
                'newValue' => 'foo',
                'path' => 'baz',
                '--validate' => true,
            ],
            'You cannot write or validate and unset a configuration value one at a time.',
        ];
        yield 'unset, validate and new value' => [
            [
                'newValue' => 'foo',
                'path' => 'baz',
                '--unset' => true,
                '--validate' => true,
            ],
            'You cannot write or validate and unset a configuration value one at a time.',
        ];
        yield 'unset and validate' => [
            [
                'path' => 'baz',
                '--unset' => true,
                '--validate' => true,
            ],
            'You cannot write and validate configuration value one at a time.',
        ];
    }

    /**
     * @return \Generator<string, array{string, array{frontend-assets: list<array<string, array<string, mixed>>>}}>
     */
    public function executeUnsetsConfigurationAtGivenPathDataProvider(): Generator
    {
        $firstAssetDefinition = [
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
        ];
        $secondAssetDefinition = [
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
        ];

        $secondAssetDefinitionOnly = [
            'frontend-assets' => [
                $secondAssetDefinition,
            ],
        ];
        $allAssetDefinitions = [
            'frontend-assets' => [
                $firstAssetDefinition,
                $secondAssetDefinition,
            ],
        ];

        yield 'first asset definition with prefix' => ['frontend-assets/0', $secondAssetDefinitionOnly];
        yield 'first asset definition without prefix' => ['0', $secondAssetDefinitionOnly];
        yield 'invalid asset definition' => ['2', $allAssetDefinitions];
    }

    protected static function getCoveredCommand(): string
    {
        return Command\ConfigAssetsCommand::class;
    }
}
