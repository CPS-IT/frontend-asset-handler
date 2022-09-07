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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Processor;

use CPSIT\FrontendAssetHandler\Exception\UnsupportedClassException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedTypeException;
use CPSIT\FrontendAssetHandler\Processor\FileArchiveProcessor;
use CPSIT\FrontendAssetHandler\Processor\ProcessorFactory;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyProcessor;
use Exception;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ProcessorFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessorFactoryTest extends ContainerAwareTestCase
{
    private NullOutput $output;
    private ProcessorFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new NullOutput();
        $this->subject = new ProcessorFactory(
            new ServiceLocator([
                // Default processor
                'archive' => fn (): FileArchiveProcessor => $this->container->get(FileArchiveProcessor::class),
                // Dummy processors
                'dummy' => fn (): DummyProcessor => new DummyProcessor(),
                'existing' => fn (): DummyProcessor => new DummyProcessor(),
                'invalid' => fn (): Exception => new Exception(),
            ])
        );
        $this->subject->setOutput($this->output);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionCode(1624618683);
        $this->expectExceptionMessage('The given type "foo" is not supported by this factory.');

        $this->subject->get('foo');
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfResolvedProcessorClassIsInvalid(): void
    {
        $this->expectException(UnsupportedClassException::class);
        $this->expectExceptionCode(1623911858);
        $this->expectExceptionMessage('The given class "Exception" is either not available or not supported.');

        $this->subject->get('invalid');
    }

    /**
     * @test
     */
    public function getReturnsInstantiatedProcessorOfGivenType(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(DummyProcessor::class, $actual);
        self::assertSame($this->output, $actual->output);
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
