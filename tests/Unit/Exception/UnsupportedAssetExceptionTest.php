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

use CPSIT\FrontendAssetHandler\Asset\Asset;
use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedAssetException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * UnsupportedAssetExceptionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class UnsupportedAssetExceptionTest extends TestCase
{
    #[Test]
    public function createReturnsExceptionForGivenAsset(): void
    {
        $source = new Source(['foo' => 'baz']);
        $target = new Target(['foo' => 'baz']);
        $asset = new Asset($source, $target);

        $subject = UnsupportedAssetException::create($asset);

        self::assertInstanceOf(UnsupportedAssetException::class, $subject);
        self::assertSame(
            sprintf('The asset with source "%s" and target "%s" is not supported.', $source, $target),
            $subject->getMessage()
        );
        self::assertSame(1623922009, $subject->getCode());
    }
}
