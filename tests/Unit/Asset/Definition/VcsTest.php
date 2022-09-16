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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Definition;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use PHPUnit\Framework\TestCase;

/**
 * VcsTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsTest extends TestCase
{
    private Asset\Definition\Vcs $subject;

    protected function setUp(): void
    {
        $this->subject = new Asset\Definition\Vcs(['type' => 'foo']);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfTypeIsMissing(): void
    {
        $this->expectExceptionObject(Exception\MissingConfigurationException::forKey('vcs/type'));

        new Asset\Definition\Vcs([]);
    }

    /**
     * @test
     */
    public function getTypeReturnsType(): void
    {
        self::assertSame('foo', $this->subject->getType());

        $this->subject['type'] = 'baz';
        self::assertSame('baz', $this->subject->getType());
    }

    /**
     * @test
     */
    public function getEnvironmentReturnsEnvironmentOrNull(): void
    {
        self::assertNull($this->subject->getEnvironment());

        $this->subject['environment'] = 'foo';
        self::assertSame('foo', $this->subject->getEnvironment());
    }
}
