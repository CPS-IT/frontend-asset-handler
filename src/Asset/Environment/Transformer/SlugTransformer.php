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

namespace CPSIT\FrontendAssetHandler\Asset\Environment\Transformer;

use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Helper;

/**
 * SlugTransformer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final readonly class SlugTransformer implements TransformerInterface
{
    public function __construct(
        private string $pattern = '{slug}',
    ) {
        $this->validatePattern();
    }

    /**
     * @param array{pattern?: string} $config
     */
    public static function fromArray(array $config): static
    {
        if (!isset($config['pattern'])) {
            return new self();
        }

        return new self($config['pattern']);
    }

    /**
     * @return array{pattern: string}
     */
    public function toArray(): array
    {
        return [
            'pattern' => $this->pattern,
        ];
    }

    public function transform(string $input): string
    {
        $pairs = [
            '{slug}' => $this->slugify($input),
        ];

        return Helper\StringHelper::interpolate($this->pattern, $pairs);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'slug';
    }

    private function slugify(string $input): string
    {
        return str_replace('/', '-', $input);
    }

    private function validatePattern(): void
    {
        if (!str_contains($this->pattern, '{slug}')) {
            throw MissingConfigurationException::forKey('pattern');
        }
    }
}
