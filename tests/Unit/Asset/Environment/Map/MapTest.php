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

use CPSIT\FrontendAssetHandler\Asset\Environment\Map\Map;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\Pair;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\PassthroughTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\VersionTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * MapTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MapTest extends TestCase
{
    /**
     * @var list<Pair>
     */
    private array $pairs;
    private Map $subject;

    protected function setUp(): void
    {
        $this->pairs = [
            new Pair('main', new PassthroughTransformer()),
            new Pair('develop', new PassthroughTransformer()),
            new Pair('feature/*', new SlugTransformer()),
        ];
        $this->subject = new Map($this->pairs);
    }

    #[Test]
    public function constructorSortsPairsByIndex(): void
    {
        $pairs = [
            1 => new Pair('main', new PassthroughTransformer()),
            0 => new Pair('develop', new PassthroughTransformer()),
        ];
        $expected = [
            0 => $pairs[0],
            1 => $pairs[1],
        ];

        /* @phpstan-ignore-next-line */
        $subject = new Map($pairs);

        self::assertSame($expected, $subject->getPairs());
    }

    #[Test]
    public function mergeMergesMapsAndReturnsNewObject(): void
    {
        $other = new Map([
            new Pair('main', new VersionTransformer('1.0.0')),
            new Pair('bugfix/*', new SlugTransformer('bugfix-{slug}')),
        ]);
        $expected = [
            new Pair('main', new VersionTransformer('1.0.0')),
            new Pair('develop', new PassthroughTransformer()),
            new Pair('feature/*', new SlugTransformer()),
            new Pair('bugfix/*', new SlugTransformer('bugfix-{slug}')),
        ];

        $actual = $this->subject->merge($other);

        self::assertEquals($expected, $actual->getPairs());
        self::assertNotSame($this->subject, $actual);
    }

    #[Test]
    public function toArrayReturnsMapAsArray(): void
    {
        $expected = [
            'main' => [
                'transformer' => PassthroughTransformer::getName(),
                'options' => [],
            ],
            'develop' => [
                'transformer' => PassthroughTransformer::getName(),
                'options' => [],
            ],
            'feature/*' => [
                'transformer' => SlugTransformer::getName(),
                'options' => [
                    'pattern' => '{slug}',
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->toArray());
    }

    #[Test]
    public function getPairsReturnsMapPairs(): void
    {
        self::assertEquals($this->pairs, $this->subject->getPairs());
    }

    #[Test]
    public function getIteratorReturnsIteratorForMapPairs(): void
    {
        self::assertSame($this->pairs, iterator_to_array($this->subject->getIterator()));
    }
}
