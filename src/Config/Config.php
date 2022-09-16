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

namespace CPSIT\FrontendAssetHandler\Config;

use ArrayIterator;
use ArrayObject;
use JsonSerializable;

/**
 * Config.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @extends \ArrayObject<string, mixed>
 */
final class Config extends ArrayObject implements JsonSerializable
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        array $config,
        private readonly string $filePath,
    ) {
        parent::__construct($config, 0, ArrayIterator::class);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return array<string, mixed>
     */
    public function asArray(): array
    {
        return (array) $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->asArray();
    }
}
