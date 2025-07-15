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

use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\TransformerInterface;

/**
 * Pair.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class Pair
{
    public function __construct(
        private string $inputPattern,
        private TransformerInterface $transformer,
    ) {}

    public function transform(string $branch): ?string
    {
        if ($this->canTransform($branch)) {
            return $this->transformer->transform($branch);
        }

        return null;
    }

    public function canTransform(string $branch): bool
    {
        return $this->matches($branch);
    }

    /**
     * @return array<string, array{transformer: string, options: array<string, mixed>}>
     */
    public function toArray(): array
    {
        return [
            $this->inputPattern => [
                'transformer' => $this->transformer::getName(),
                'options' => $this->transformer->toArray(),
            ],
        ];
    }

    public function getInputPattern(): string
    {
        return $this->inputPattern;
    }

    private function matches(string $input): bool
    {
        if (str_starts_with($this->inputPattern, '/') && str_ends_with($this->inputPattern, '/')) {
            return 1 === preg_match($this->inputPattern, $input);
        }

        return fnmatch($this->inputPattern, $input);
    }
}
