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
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use Symfony\Component\Console;

use function json_encode;
use function sprintf;

/**
 * InitConfigCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InitConfigCommandTest extends Tests\Unit\CommandTesterAwareTestCase
{
    private Tests\Unit\Fixtures\Classes\DummyStep $firstStep;
    private Tests\Unit\Fixtures\Classes\DummyInteractiveStep $secondStep;
    private Config\Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstStep = $this->container->get(Tests\Unit\Fixtures\Classes\DummyStep::class);
        $this->secondStep = $this->container->get(Tests\Unit\Fixtures\Classes\DummyInteractiveStep::class);

        self::assertNotNull($this->configFile);

        $this->config = $this->container->get(Config\ConfigFacade::class)->load($this->configFile);
    }

    /**
     * @test
     */
    public function executeAddsOptionsFromInteractiveStepsToInputDefinition(): void
    {
        $this->secondStep->expectedConfig = $this->config;

        $exitCode = $this->commandTester->execute([
            '--config' => $this->config->getFilePath(),
            '--foo' => 'foo',
            '--baz' => 'baz',
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame(
            $this->config['frontend-assets'][0]['request-options'],
            [
                'config' => $this->config->getFilePath(),
                'foo' => 'foo',
                'baz' => 'baz',
            ],
        );
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfStepFails(): void
    {
        $this->firstStep->expectedReturn = false;

        $exitCode = $this->commandTester->execute(['--config' => 'foo']);
        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            sprintf('Action "%s" failed', $this->firstStep::class),
            $output,
        );
    }

    /**
     * @test
     */
    public function executeAppliesOutputToInteractiveSteps(): void
    {
        self::assertInstanceOf(Console\Output\NullOutput::class, $this->secondStep->getOutput()->getOutput());

        $this->secondStep->expectedConfig = $this->config;

        $exitCode = $this->commandTester->execute(['--config' => $this->configFile]);

        self::assertSame(0, $exitCode);
        self::assertNotInstanceOf(Console\Output\NullOutput::class, $this->secondStep->getOutput()->getOutput());
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfInitializedConfigIsInvalid(): void
    {
        $this->expectException(Exception\InvalidConfigurationException::class);
        $this->expectExceptionCode(1643113965);

        $this->commandTester->execute(['--config' => 'foo']);
    }

    /**
     * @test
     */
    public function executeWritesInitializedConfig(): void
    {
        unset($this->config['frontend-assets'][1]);

        $this->secondStep->expectedConfig = $this->config;

        $exitCode = $this->commandTester->execute(['--config' => $this->config->getFilePath()]);
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Asset configuration was successfully written', $output);
        self::assertJsonStringEqualsJsonFile($this->config->getFilePath(), json_encode($this->config) ?: '');
    }

    protected static function getCoveredCommand(): string
    {
        return Command\InitConfigCommand::class;
    }
}
