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

namespace CPSIT\FrontendAssetHandler\Config\Parser;

use CPSIT\FrontendAssetHandler\Config;

use function in_array;

/**
 * ParserInstructions.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
class ParserInstructions
{
    protected bool $processValues = true;

    /**
     * @var list<string>
     */
    protected array $requiredKeys = [];

    public function __construct(
        protected Config\Config $config,
    ) {
    }

    public function getConfig(): Config\Config
    {
        return $this->config;
    }

    public function shouldProcessValues(): bool
    {
        return $this->processValues;
    }

    public function processValues(bool $processValues): self
    {
        $this->processValues = $processValues;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRequiredKeys(): array
    {
        return $this->requiredKeys;
    }

    public function requireKey(string $key): self
    {
        if (!in_array($key, $this->requiredKeys, true)) {
            $this->requiredKeys[] = $key;
        }

        return $this;
    }
}
