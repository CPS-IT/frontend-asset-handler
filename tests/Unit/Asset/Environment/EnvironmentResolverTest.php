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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Environment;

use CPSIT\FrontendAssetHandler\Asset\Environment\Environment;
use CPSIT\FrontendAssetHandler\Asset\Environment\EnvironmentResolver;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\MapFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * EnvironmentResolverTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class EnvironmentResolverTest extends TestCase
{
    private EnvironmentResolver $subject;

    protected function setUp(): void
    {
        $this->subject = new EnvironmentResolver(MapFactory::createDefault());
    }

    #[Test]
    public function resolveReturnsDefaultEnvironmentIfGivenBranchDoesNotMatch(): void
    {
        self::assertSame(Environment::Stable->value, $this->subject->resolve('foo'));
    }

    #[Test]
    public function resolveReturnsTransformedEnvironment(): void
    {
        self::assertSame(Environment::Stable->value, $this->subject->resolve('main'));
        self::assertSame(Environment::Stable->value, $this->subject->resolve('master'));
        self::assertSame(Environment::Latest->value, $this->subject->resolve('develop'));
        self::assertSame(Environment::Latest->value, $this->subject->resolve('release/*'));
        self::assertSame('fe-feature-foo', $this->subject->resolve('feature/foo'));
        self::assertSame('preview', $this->subject->resolve('preview'));
        self::assertSame('integration', $this->subject->resolve('integration'));
        self::assertSame('1.0.0', $this->subject->resolve('1.0.0'));
        self::assertSame(Environment::Stable->value, $this->subject->resolve('test/foo.foo.foo'));
    }

    #[Test]
    public function getMapReturnsMap(): void
    {
        self::assertEquals(MapFactory::createDefault(), $this->subject->getMap());
    }
}
