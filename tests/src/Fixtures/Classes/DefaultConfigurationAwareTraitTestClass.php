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

namespace CPSIT\FrontendAssetHandler\Tests\Fixtures\Classes;

use CPSIT\FrontendAssetHandler\Traits;

/**
 * DefaultConfigurationAwareTraitTestClass.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final readonly class DefaultConfigurationAwareTraitTestClass
{
    use Traits\DefaultConfigurationAwareTrait {
        applyDefaultConfiguration as public runApplyDefaultConfiguration;
        validateAssetDefinition as public runValidateAssetDefinition;
    }

    /**
     * @param array<string, mixed> $defaultConfiguration
     */
    public function __construct(
        private array $defaultConfiguration,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultConfiguration(): array
    {
        return $this->defaultConfiguration;
    }
}
