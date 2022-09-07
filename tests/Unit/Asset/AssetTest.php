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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset;

use CPSIT\FrontendAssetHandler\Asset\Asset;
use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use PHPUnit\Framework\TestCase;

/**
 * AssetTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetTest extends TestCase
{
    /**
     * @test
     */
    public function constructorSetsSource(): void
    {
        $source = new Source(['foo' => 'baz']);
        $subject = new Asset($source);

        self::assertSame($source, $subject->getSource());
        self::assertSame('baz', $subject->getSource()['foo']);
    }

    /**
     * @test
     */
    public function setTargetSetsTarget(): void
    {
        $target = new Target(['foo' => 'baz']);
        $subject = new Asset(new Source([]));
        $subject->setTarget($target);

        self::assertSame($target, $subject->getTarget());
        self::assertSame('baz', $subject->getTarget()['foo']);
    }
}
