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

namespace CPSIT\FrontendAssetHandler\Tests\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console;

use function dirname;
use function sprintf;

/**
 * ConfigFileStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFileStepTest extends Tests\ContainerAwareTestCase
{
    use InitializationRequestTrait;
    use Tests\InteractiveConsoleInputTrait;

    private Console\Output\BufferedOutput $output;
    private Config\Initialization\Step\ConfigFileStep $subject;
    private Config\Initialization\InitializationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new Console\Output\BufferedOutput();
        $this->subject = $this->container->get(Config\Initialization\Step\ConfigFileStep::class);
        $this->subject->setOutput($this->output);
        $this->request = $this->createRequest($this->subject);
    }

    #[Test]
    public function executeThrowsExceptionIfGivenConfigFileIsInvalid(): void
    {
        $this->request->setConfigFile(dirname(__DIR__, 3).'/Fixtures/JsonFiles/invalid-assets.json');

        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1643113965);

        $this->subject->execute($this->request);
    }

    #[Test]
    public function executeCreatesNewConfigIfGivenConfigFileDoesNotExist(): void
    {
        $configFile = dirname(__DIR__, 3).'/Fixtures/JsonFiles/foo.json';

        $this->request->setConfigFile($configFile);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame($configFile, $this->request->getConfig()->getFilePath());
        self::assertSame(0, $this->request->getOption('definition-id'));
    }

    #[Test]
    #[DataProvider('executeUsesTheExistingConfigFileWithANewDefinitionIdDataProvider')]
    public function executeUsesTheExistingConfigFileWithANewDefinitionId(int $definitionId, int $expected): void
    {
        $this->request->setOption('definition-id', $definitionId);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame($expected, $this->request->getOption('definition-id'));
    }

    #[Test]
    public function executeAsksAndExtendsGivenConfigFile(): void
    {
        $configFile = $this->request->getConfigFile();
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes'], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame($configFile, $this->request->getConfigFile());
        self::assertSame(2, $this->request->getOption('definition-id'));

        $output = $this->output->fetch();

        self::assertStringContainsString(
            sprintf('You have configured the file %s for your Frontend assets.', $configFile),
            $output,
        );
        self::assertStringContainsString(
            sprintf('A file with the name %s already exists.', $configFile),
            $output,
        );
        self::assertStringContainsString(
            'You can add a new asset definition to the existing config file or create a new config file.',
            $output,
        );
        self::assertStringContainsString(
            sprintf('Add a new asset definition to %s?', $configFile),
            $output,
        );
        self::assertStringContainsString(
            sprintf('Alright, the config file %s will be extended by a new asset definition.', $configFile),
            $output,
        );
    }

    #[Test]
    public function executeAsksAndUsesAnotherConfigFileIfExistingConfigFileShouldNotBeExtended(): void
    {
        $configFile = '/tmp/foo.json';
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['no', $configFile], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame($configFile, $this->request->getConfigFile());
        self::assertSame(0, $this->request->getOption('definition-id'));

        $output = $this->output->fetch();

        self::assertStringContainsString(
            sprintf('Alright, the config file %s will be used for the new asset definition.', $configFile),
            $output,
        );
    }

    /**
     * @return Generator<string, array{int, int}>
     */
    public static function executeUsesTheExistingConfigFileWithANewDefinitionIdDataProvider(): Generator
    {
        yield 'lowest possible new definition ID' => [2, 2];
        yield 'any higher new definition ID' => [42, 2];
    }
}
