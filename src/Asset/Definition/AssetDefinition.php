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

namespace CPSIT\FrontendAssetHandler\Asset\Definition;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use Traversable;

use function array_key_exists;
use function array_replace_recursive;
use function json_encode;

/**
 * AssetDefinition.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
abstract class AssetDefinition implements ArrayAccess, IteratorAggregate, JsonSerializable, Stringable
{
    /**
     * @var array<string, mixed>
     */
    protected array $defaults = [];

    /**
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $this->applyDefaults($config);
    }

    abstract public function getType(): string;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->config);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->config[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->config[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->config);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->config;
    }

    public function __toString(): string
    {
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    protected function applyDefaults(array $config): array
    {
        return array_replace_recursive($this->defaults, $config);
    }
}
