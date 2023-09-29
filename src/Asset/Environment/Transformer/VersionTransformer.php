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

use function is_string;

/**
 * VersionTransformer.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VersionTransformer implements TransformerInterface
{
    public function __construct(
        private readonly string $version,
    ) {}

    /**
     * @param array{version?: string} $config
     */
    public static function fromArray(array $config): static
    {
        if (!is_string($version = $config['version'] ?? null)) {
            throw MissingConfigurationException::forKey('version');
        }

        return new self($version);
    }

    /**
     * @return array{version: string}
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
        ];
    }

    public function transform(string $input): string
    {
        return $this->version;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getName(): string
    {
        return 'version';
    }
}
