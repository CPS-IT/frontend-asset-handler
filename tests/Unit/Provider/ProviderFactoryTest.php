<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Provider;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Tests;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\DependencyInjection;

/**
 * ProviderFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProviderFactoryTest extends Tests\Unit\ContainerAwareTestCase
{
    private SymfonyConsole\Output\NullOutput $output;
    private Provider\ProviderFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new SymfonyConsole\Output\NullOutput();
        $this->subject = new Provider\ProviderFactory(
            new DependencyInjection\ServiceLocator([
                // Default provider
                'http' => fn () => $this->container->get(Provider\HttpFileProvider::class),
                // Dummy providers
                'dummy' => fn () => new Tests\Unit\Fixtures\Classes\DummyProvider(),
                'invalid' => fn () => new \Exception(),
            ])
        );
        $this->subject->setOutput($this->output);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedTypeException::create('foo'));

        $this->subject->get('foo');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfResolvedProviderClassIsInvalid(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedClassException::create(\Exception::class));

        $this->subject->get('invalid');
    }

    /**
     * @test
     */
    public function getReturnsInstantiatedProviderOfGivenType(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Unit\Fixtures\Classes\DummyProvider::class, $actual);
    }

    /**
     * @test
     */
    public function getAppliesOutputToChattyProviders(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Unit\Fixtures\Classes\DummyProvider::class, $actual);
        self::assertInstanceOf(Console\Output\TrackableOutput::class, $actual->output);
        self::assertSame($this->output, $actual->output->getOutput());
    }

    /**
     * @test
     */
    public function hasReturnsTrueIfGivenTypeIsAvailable(): void
    {
        self::assertTrue($this->subject->has('dummy'));
        self::assertFalse($this->subject->has('foo'));
    }
}
