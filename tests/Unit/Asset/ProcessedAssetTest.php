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

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\ProcessedAsset;
use PHPUnit\Framework\TestCase;

/**
 * ProcessedAssetTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ProcessedAssetTest extends TestCase
{
    /**
     * @test
     */
    public function constructorSetsProcessedTargetPath(): void
    {
        $processedTargetPath = 'foo';
        $subject = new ProcessedAsset(new Source([]), new Target([]), $processedTargetPath);

        self::assertSame('foo', $subject->getProcessedTargetPath());
    }
}
