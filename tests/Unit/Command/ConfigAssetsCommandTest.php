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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem;
use UnexpectedValueException;

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
    private Filesystem\Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->container->get(Filesystem\Filesystem::class);
    }

    #[Test]
    public function executeFailsAndWritesErrorIfPathIsEmpty(): void
    {
        $exitCode = $this->commandTester->execute([]);

        self::assertSame(2, $exitCode);
        self::assertStringContainsString('The configuration path must not be empty.', $this->commandTester->getDisplay());
    }

    /**
     * @param array<string, bool|string> $input
     */
    #[Test]
    #[DataProvider('executeFailsAndWritesErrorIfConflictingParametersAreGivenDataProvider')]
    public function executeFailsAndWritesErrorIfConflictingParametersAreGiven(array $input, string $expected): void
    {
        $exitCode = $this->commandTester->execute($input);

        self::assertSame(4, $exitCode);
        self::assertStringContainsString($expected, $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeThrowsExceptionIfNoConfigFileIsConfigured(): void
    {
        $this->initializeCommandTester();

        $this->expectExceptionObject(Exception\MissingConfigurationException::create());

        $this->commandTester->execute([
            'path' => 'foo',
            '--unset' => true,
        ]);
    }

    #[Test]
    public function executeThrowsExceptionIfPlaceholderProcessorsAreFailing(): void
    {
        $this->initializeCommandTester(dirname(__DIR__).'/Fixtures/JsonFiles/placeholder-assets.json');

        $this->expectExceptionObject(
            new UnexpectedValueException('The environment variable "SOURCE_TYPE" is not available.', 1628147471)
        );

        $this->commandTester->execute([
            '--validate' => true,
            '--process-values' => true,
        ]);
    }

    /**
     * @param array{frontend-assets: list<array<string, array<string, mixed>>>} $expected
     */
    #[Test]
    #[DataProvider('executeUnsetsConfigurationAtGivenPathDataProvider')]
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

    #[Test]
    public function executeThrowsExceptionIfConfigurationIsInvalidAfterUnset(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1643113965);
        $this->expectExceptionMessage(
            'The configuration is invalid: '.PHP_EOL.'  * [/frontend-assets/0/source/url]: The property url is required'
        );

        $this->commandTester->execute([
            'path' => '0/source/url',
            '--unset' => true,
        ]);
    }

    #[Test]
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
        self::assertMatchesRegularExpression('/\[0]\[source]\[url]\s+The property url is required/', $output);
    }

    #[Test]
    public function executePrintsSuccessMessageIfConfigIsValid(): void
    {
        $exitCode = $this->commandTester->execute([
            '--validate' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Your asset configuration is valid.', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeThrowsExceptionIfGivenPathIsAmbiguous(): void
    {
        $this->expectExceptionObject(Exception\InvalidConfigurationException::forAmbiguousKey('source'));

        $this->commandTester->execute([
            'path' => 'source',
        ]);
    }

    #[Test]
    public function executeThrowsExceptionIfGivenPathDoesNotExist(): void
    {
        $this->expectExceptionObject(Exception\MissingConfigurationException::forKey('0/foo'));

        $this->commandTester->execute([
            'path' => '0/foo',
        ]);
    }

    #[Test]
    public function executeFallsBackToFirstAssetDefinitionIfOnlyASingleAssetDefinitionIsConfigured(): void
    {
        $this->initializeCommandTester(dirname(__DIR__).'/Fixtures/JsonFiles/single-assets.json');

        $exitCode = $this->commandTester->execute([
            'path' => 'source/type',
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'Current configuration value of asset definition [0][source][type]:'.PHP_EOL.PHP_EOL.'"dummy',
            $output,
        );
    }

    #[Test]
    public function executeReturnsConfigurationForGivenPath(): void
    {
        $exitCode = $this->commandTester->execute([
            'path' => '1/source/type',
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'Current configuration value of asset definition [1][source][type]:'.PHP_EOL.PHP_EOL.'"dummy',
            $output,
        );
    }

    #[Test]
    public function executeThrowsExceptionIfNewConfigIsInvalid(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);

        $this->commandTester->execute([
            'path' => '0',
            'newValue' => '{"foo":"bar"}',
            '--json' => true,
        ]);
    }

    #[Test]
    public function executeWritesNewValueToGivenPath(): void
    {
        $originalFile = $this->container->get('app.cache')->getConfigFile()
            ?? self::fail('No config file given.');
        $targetFile = $this->filesystem->tempnam(sys_get_temp_dir(), 'fah_assets.json_', '.json');

        $this->filesystem->copy($originalFile, $targetFile, true);

        $this->initializeCommandTester($targetFile);

        $exitCode = $this->commandTester->execute([
            'path' => '0/source/type',
            'newValue' => 'foo',
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Configuration at [0][source][type] was successfully set to "foo".', $output);

        $this->filesystem->remove($targetFile);
    }

    #[Test]
    public function executeWritesJsonEncodedNewValueToGivenPath(): void
    {
        $originalFile = $this->container->get('app.cache')->getConfigFile()
            ?? self::fail('No config file given.');
        $targetFile = $this->filesystem->tempnam(sys_get_temp_dir(), 'fah_assets.json_', '.json');

        $this->filesystem->copy($originalFile, $targetFile, true);

        $this->initializeCommandTester($targetFile);

        $exitCode = $this->commandTester->execute([
            'path' => '0/source',
            'newValue' => '{"type":"foo","url":"foo","revision-url":"foo"}',
            '--json' => true,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Configuration at [0][source] was successfully written:', $output);
        self::assertStringContainsString('"type": "foo"', $output);
        self::assertStringContainsString('"url": "foo"', $output);
        self::assertStringContainsString('"revision-url": "foo"', $output);

        $this->filesystem->remove($targetFile);
    }

    /**
     * @return Generator<string, array{array<string, bool|string>, string}>
     */
    public static function executeFailsAndWritesErrorIfConflictingParametersAreGivenDataProvider(): Generator
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
     * @return Generator<string, array{string, array{frontend-assets: list<array<string, string|array<string, mixed>>>}}>
     */
    public static function executeUnsetsConfigurationAtGivenPathDataProvider(): Generator
    {
        $firstAssetDefinition = [
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
        ];
        $secondAssetDefinition = [
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
