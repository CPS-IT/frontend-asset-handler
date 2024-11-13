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

namespace CPSIT\FrontendAssetHandler\Tests\Handler;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Handler;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\DependencyInjection;

/**
 * HandlerFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class HandlerFactoryTest extends Tests\ContainerAwareTestCase
{
    private SymfonyConsole\Output\NullOutput $output;
    private Handler\HandlerFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new SymfonyConsole\Output\NullOutput();
        $this->subject = new Handler\HandlerFactory(
            new DependencyInjection\ServiceLocator([
                // Default handler
                'default' => fn () => $this->container->get(Handler\AssetHandler::class),
                // Dummy handlers
                'dummy' => fn () => new Tests\Fixtures\Classes\DummyHandler(),
            ]),
        );
        $this->subject->setOutput($this->output);
    }

    #[Test]
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedTypeException::create('foo'));

        $this->subject->get('foo');
    }

    #[Test]
    public function getReturnsInstantiatedProviderOfGivenType(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Fixtures\Classes\DummyHandler::class, $actual);
    }

    #[Test]
    public function getAppliesOutputToChattyHandlers(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Fixtures\Classes\DummyHandler::class, $actual);
        self::assertInstanceOf(Console\Output\TrackableOutput::class, $actual->output);
        self::assertSame($this->output, $actual->output->getOutput());
    }

    #[Test]
    public function hasReturnsTrueIfGivenTypeIsAvailable(): void
    {
        self::assertTrue($this->subject->has('dummy'));
        self::assertFalse($this->subject->has('foo'));
    }
}
