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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Exception;

use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * MissingConfigurationExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MissingConfigurationExceptionTest extends TestCase
{
    #[Test]
    public function createReturnsExceptionForMissingConfiguration(): void
    {
        $subject = MissingConfigurationException::create();

        self::assertInstanceOf(MissingConfigurationException::class, $subject);
        self::assertSame('The asset configuration is missing.', $subject->getMessage());
        self::assertSame(1661844293, $subject->getCode());
    }

    #[Test]
    public function forKeyReturnsExceptionForGivenKey(): void
    {
        $subject = MissingConfigurationException::forKey('foo');

        self::assertInstanceOf(MissingConfigurationException::class, $subject);
        self::assertSame('Configuration for key "foo" is missing or invalid.', $subject->getMessage());
        self::assertSame(1623867663, $subject->getCode());
    }
}
