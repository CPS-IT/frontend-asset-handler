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

namespace CPSIT\FrontendAssetHandler\Tests\Processor;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console as SymfonyConsole;
use Symfony\Component\DependencyInjection;

/**
 * ProcessorFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessorFactoryTest extends Tests\ContainerAwareTestCase
{
    private SymfonyConsole\Output\NullOutput $output;
    private Processor\ProcessorFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new SymfonyConsole\Output\NullOutput();
        $this->subject = new Processor\ProcessorFactory(
            new DependencyInjection\ServiceLocator([
                // Default processor
                'archive' => fn () => $this->container->get(Processor\FileArchiveProcessor::class),
                // Dummy processors
                'dummy' => fn () => new Tests\Fixtures\Classes\DummyProcessor(),
                'existing' => fn () => new Tests\Fixtures\Classes\DummyProcessor(),
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
    public function getReturnsInstantiatedProcessorOfGivenType(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Fixtures\Classes\DummyProcessor::class, $actual);
    }

    #[Test]
    public function getAppliesOutputToChattyProcessors(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(Tests\Fixtures\Classes\DummyProcessor::class, $actual);
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
