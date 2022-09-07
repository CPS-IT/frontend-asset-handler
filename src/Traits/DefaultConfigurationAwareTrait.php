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

namespace CPSIT\FrontendAssetHandler\Traits;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;

/**
 * DefaultConfigurationAwareTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait DefaultConfigurationAwareTrait
{
    /**
     * @return array<string, mixed>
     */
    abstract protected function getDefaultConfiguration(): array;

    protected function applyDefaultConfiguration(Asset\Definition\AssetDefinition $assetDefinition): void
    {
        foreach ($this->getDefaultConfiguration() as $configKey => $configValue) {
            if (!isset($assetDefinition[$configKey])) {
                $assetDefinition[$configKey] = $configValue;
            }
        }
    }

    /**
     * @throws Exception\MissingConfigurationException
     */
    protected function validateAssetDefinition(Asset\Definition\AssetDefinition $assetDefinition): void
    {
        /** @var mixed $configValue */
        foreach ($this->getDefaultConfiguration() as $configKey => $configValue) {
            if (!isset($assetDefinition[$configKey]) && null === $configValue) {
                throw Exception\MissingConfigurationException::forKey($configKey);
            }
        }
    }
}
