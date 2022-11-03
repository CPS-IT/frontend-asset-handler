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

namespace CPSIT\FrontendAssetHandler\Asset\Environment\Map;

use IteratorAggregate;
use Traversable;

use function array_values;
use function ksort;

/**
 * Map.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @implements \IteratorAggregate<int, Pair>
 */
final class Map implements IteratorAggregate
{
    /**
     * @var array<string, int>
     */
    private array $pairPatterns;

    /**
     * @param list<Pair> $pairs
     */
    public function __construct(
        private array $pairs,
    ) {
        $this->updateIndexes();
    }

    public function merge(self $map): self
    {
        $otherPairs = [];
        $lastIndex = (int) array_key_last($this->pairs) + 1;

        foreach ($map->pairPatterns as $pattern => $index) {
            $newIndex = $this->pairPatterns[$pattern] ?? $lastIndex++;
            $otherPairs[$newIndex] = $map->pairs[$index];
        }

        $mergedMap = array_replace($this->pairs, $otherPairs);

        ksort($mergedMap);

        return new self(array_values($mergedMap));
    }

    /**
     * @return array<string, array{transformer: string, options: array<string, mixed>}>
     */
    public function toArray(): array
    {
        return array_reduce($this->pairs, fn (array $carry, Pair $item): array => $carry + $item->toArray(), []);
    }

    /**
     * @return list<Pair>
     */
    public function getPairs(): array
    {
        return $this->pairs;
    }

    public function getIterator(): Traversable
    {
        return yield from $this->pairs;
    }

    private function updateIndexes(): void
    {
        ksort($this->pairs);

        $this->reIndexPairPatterns();
    }

    private function reIndexPairPatterns(): void
    {
        $this->pairPatterns = [];

        foreach ($this->pairs as $index => $pair) {
            $this->pairPatterns[$pair->getInputPattern()] = $index;
        }
    }
}
