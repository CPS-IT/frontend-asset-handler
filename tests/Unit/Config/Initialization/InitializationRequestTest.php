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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Initialization;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console;

/**
 * InitializationRequestTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InitializationRequestTest extends TestCase
{
    private Console\Input\ArrayInput $input;
    private Config\Initialization\InitializationRequest $subject;

    protected function setUp(): void
    {
        $this->input = new Console\Input\ArrayInput([], new Console\Input\InputDefinition([
            new Console\Input\InputOption('config', mode: Console\Input\InputOption::VALUE_REQUIRED),
            new Console\Input\InputOption('dummy-option', mode: Console\Input\InputOption::VALUE_OPTIONAL),
        ]));
        $this->subject = new Config\Initialization\InitializationRequest('foo', ['foo' => 'baz'], $this->input);
    }

    /**
     * @test
     */
    public function fromCommandInputThrowsExceptionIfConfigFileIsMissing(): void
    {
        $this->expectExceptionObject(Exception\MissingConfigurationException::create());

        Config\Initialization\InitializationRequest::fromCommandInput($this->input);
    }

    /**
     * @test
     */
    public function fromCommandInputReturnsInitializationRequestBuiltFromGivenInput(): void
    {
        $this->input->setOption('config', 'foo');
        $this->input->setOption('dummy-option', 'baz');

        $actual = Config\Initialization\InitializationRequest::fromCommandInput($this->input);

        self::assertSame([], $actual->getConfig()->asArray());
        self::assertSame('foo', $actual->getConfigFile());
        self::assertSame(
            [
                'config' => 'foo',
                'dummy-option' => 'baz',
            ],
            $actual->getOptions(),
        );
        self::assertSame($this->input, $actual->getInput());
    }

    /**
     * @test
     */
    public function getConfigCreatesNewConfigIfItDoesNotExistYet(): void
    {
        $config = $this->subject->getConfig();

        self::assertInstanceOf(Config\Config::class, $config);
        self::assertSame('foo', $config->getFilePath());
        self::assertSame($config, $this->subject->getConfig());
    }

    /**
     * @test
     */
    public function setConfigAppliesConfig(): void
    {
        $config = new Config\Config(['foo' => 'baz'], 'foo');

        $this->subject->setConfig($config);

        self::assertSame($config, $this->subject->getConfig());
    }

    /**
     * @test
     */
    public function setConfigFileAppliesConfigFile(): void
    {
        self::assertSame('foo', $this->subject->getConfigFile());

        $this->subject->setConfigFile('baz');

        self::assertSame('baz', $this->subject->getConfigFile());
    }

    /**
     * @test
     */
    public function getOptionThrowsExceptionIfOptionDoesNotExist(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionCode(1663086743);
        $this->expectExceptionMessage('The initialization option "baz" does not exist.');

        $this->subject->getOption('baz');
    }

    /**
     * @test
     */
    public function setOptionAppliesOption(): void
    {
        $this->subject->setOption('baz', 'foo');

        self::assertSame('foo', $this->subject->getOption('baz'));
    }
}
