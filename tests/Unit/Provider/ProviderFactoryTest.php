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

use CPSIT\FrontendAssetHandler\Exception\UnsupportedClassException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedTypeException;
use CPSIT\FrontendAssetHandler\Provider\HttpFileProvider;
use CPSIT\FrontendAssetHandler\Provider\ProviderFactory;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyProvider;
use Exception;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * ProviderFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProviderFactoryTest extends ContainerAwareTestCase
{
    private NullOutput $output;
    private ProviderFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new NullOutput();
        $this->subject = new ProviderFactory(
            new ServiceLocator([
                // Default provider
                'http' => fn (): HttpFileProvider => $this->container->get(HttpFileProvider::class),
                // Dummy providers
                'dummy' => fn (): DummyProvider => new DummyProvider(),
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
    public function getThrowsExceptionIfResolvedProviderClassIsInvalid(): void
    {
        $this->expectException(UnsupportedClassException::class);
        $this->expectExceptionCode(1623911858);
        $this->expectExceptionMessage('The given class "Exception" is either not available or not supported.');

        $this->subject->get('invalid');
    }

    /**
     * @test
     */
    public function getReturnsInstantiatedProviderOfGivenType(): void
    {
        $actual = $this->subject->get('dummy');

        self::assertInstanceOf(DummyProvider::class, $actual);
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
