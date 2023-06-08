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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Vcs;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use CPSIT\FrontendAssetHandler\Vcs;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection;

/**
 * VcsProviderFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsProviderFactoryTest extends Tests\Unit\ContainerAwareTestCase
{
    private Vcs\VcsProviderFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Vcs\VcsProviderFactory(
            new DependencyInjection\ServiceLocator([
                // Default provider
                'gitlab' => fn () => $this->container->get(Vcs\GitlabVcsProvider::class),
                // Dummy providers
                'dummy' => fn () => new Tests\Unit\Fixtures\Classes\DummyVcsProvider(),
            ])
        );
    }

    #[Test]
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectException(Exception\UnsupportedTypeException::class);
        $this->expectExceptionCode(1624618683);
        $this->expectExceptionMessage('The given type "foo" is not supported by this factory.');

        $this->subject->get('foo');
    }

    #[Test]
    public function getReturnsInstantiatedProviderOfGivenType(): void
    {
        self::assertInstanceOf(
            Tests\Unit\Fixtures\Classes\DummyVcsProvider::class,
            $this->subject->get('dummy', new Asset\Definition\Vcs(['type' => 'dummy'])),
        );
    }

    #[Test]
    public function hasReturnsTrueIfGivenTypeIsAvailable(): void
    {
        self::assertTrue($this->subject->has('dummy'));
        self::assertFalse($this->subject->has('foo'));
    }
}
