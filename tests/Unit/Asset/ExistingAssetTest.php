<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "fr/frontend-asset-handling".
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
use CPSIT\FrontendAssetHandler\Asset\ExistingAsset;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ExistingAssetTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExistingAssetTest extends TestCase
{
    #[Test]
    public function constructorSetsTargetPath(): void
    {
        $targetPath = 'foo';
        $subject = new ExistingAsset(new Source([]), new Target([]), $targetPath);

        self::assertSame('foo', $subject->getTargetPath());
    }

    #[Test]
    public function constructorSetsRevision(): void
    {
        $revision = new Revision('1234567');
        $subject = new ExistingAsset(new Source([]), new Target([]), 'foo', $revision);

        self::assertSame($revision, $subject->getRevision());
    }

    #[Test]
    public function getRevisionFallsBackToSourceAndTargetIfAssetHasNoAssociatedRevision(): void
    {
        $source = new Source([]);
        $target = new Target([]);
        $subject = new ExistingAsset($source, $target, 'foo');

        self::assertNull($subject->getRevision());

        $targetRevision = new Revision('targetRevision');
        $target['revision'] = $targetRevision;

        self::assertSame($targetRevision, $subject->getRevision(), 'Revision from target');

        $sourceRevision = new Revision('sourceRevision');
        $source['revision'] = $sourceRevision;

        self::assertSame($sourceRevision, $subject->getRevision(), 'Revision from source');
    }
}
