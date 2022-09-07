<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2022 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Environment\Map;

use CPSIT\FrontendAssetHandler\Asset\Environment\Map\Pair;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\PassthroughTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer;
use PHPUnit\Framework\TestCase;

/**
 * PairTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class PairTest extends TestCase
{
    private Pair $subject;

    protected function setUp(): void
    {
        $this->subject = new Pair('feature/*', new SlugTransformer('fe-{slug}'));
    }

    /**
     * @test
     */
    public function transformReturnsNullIfTransformerCannotTransformBranch(): void
    {
        self::assertNull($this->subject->transform('foo'));
    }

    /**
     * @test
     */
    public function transformReturnsTransformedEnvironment(): void
    {
        self::assertSame('fe-feature-foo', $this->subject->transform('feature/foo'));
    }

    /**
     * @test
     */
    public function canTransformReturnsTrueForExactlyMatchingBranch(): void
    {
        $subject = new Pair('main', new PassthroughTransformer());

        self::assertTrue($subject->canTransform('main'));
    }

    /**
     * @test
     */
    public function canTransformReturnsTrueForBranchMatchingWithWildcard(): void
    {
        self::assertTrue($this->subject->canTransform('feature/foo'));
    }

    /**
     * @test
     */
    public function canTransformReturnsFalseForNonMatchingBranch(): void
    {
        self::assertFalse($this->subject->canTransform('foo'));
    }

    /**
     * @test
     */
    public function toArrayReturnsArrayRepresentation(): void
    {
        $expected = [
            'feature/*' => [
                'transformer' => SlugTransformer::getName(),
                'options' => [
                    'pattern' => 'fe-{slug}',
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->toArray());
    }

    /**
     * @test
     */
    public function getInputPatternReturnsInputPattern(): void
    {
        self::assertSame('feature/*', $this->subject->getInputPattern());
    }
}
