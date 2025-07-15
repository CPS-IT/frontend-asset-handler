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

namespace CPSIT\FrontendAssetHandler\Vcs;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use Symfony\Component\DependencyInjection;

/**
 * VcsProviderFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final readonly class VcsProviderFactory
{
    /**
     * @param DependencyInjection\ServiceLocator<VcsProviderInterface> $providers
     */
    public function __construct(
        private DependencyInjection\ServiceLocator $providers,
    ) {}

    /**
     * @throws Exception\UnsupportedTypeException
     * @throws Exception\UnsupportedClassException
     */
    public function get(string $type, ?Asset\Definition\Vcs $vcs = null): VcsProviderInterface
    {
        if (!$this->has($type)) {
            throw Exception\UnsupportedTypeException::create($type);
        }

        // Fetch provider instance
        $provider = $this->providers->get($type);

        if (null !== $vcs) {
            $provider = $provider->withVcs($vcs);
        }

        return $provider;
    }

    public function has(string $type): bool
    {
        return $this->providers->has($type);
    }
}
