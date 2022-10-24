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

namespace CPSIT\FrontendAssetHandler\Asset\Definition;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;

use function is_array;

/**
 * AssetDefinitionFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final class AssetDefinitionFactory
{
    public function __construct(
        private readonly Asset\Environment\Map\MapFactory $mapFactory,
    ) {
    }

    /**
     * @param array{environments?: array<string, mixed>, source?: array<string, mixed>} $config
     */
    public function buildSource(array $config, string $branch): Asset\Definition\Source
    {
        $sourceConfig = $config['source'] ?? [];
        $sourceConfig['environment'] = $this->resolveEnvironment($config, $branch);

        return new Asset\Definition\Source($sourceConfig);
    }

    /**
     * @param array{target?: array<string, mixed>} $config
     */
    public function buildTarget(array $config): Asset\Definition\Target
    {
        return new Asset\Definition\Target($config['target'] ?? []);
    }

    /**
     * @param array{environments?: array<string, mixed>, source?: array<string, mixed>, vcs?: array<string, mixed>} $config
     */
    public function buildVcs(array $config, string $branch): ?Asset\Definition\Vcs
    {
        $vcsConfig = $config['vcs'] ?? null;

        if (!is_array($vcsConfig)) {
            return null;
        }

        $vcsConfig['environment'] = $this->resolveEnvironment($config, $branch);

        return new Asset\Definition\Vcs($vcsConfig);
    }

    /**
     * @param array{environments?: array<string, mixed>, source?: array<string, mixed>} $config
     */
    private function resolveEnvironment(array $config, string $branch): string
    {
        $environmentResolver = $this->buildEnvironmentResolver(
            $config['environments'] ?? [],
            $config['source']['version'] ?? null
        );

        return $environmentResolver->resolve($branch);
    }

    /**
     * @param array{map?: array<string, mixed>, merge?: bool} $configuration
     *
     * @throws Exception\MissingConfigurationException
     */
    private function buildEnvironmentResolver(array $configuration, string $version = null): Asset\Environment\EnvironmentResolver
    {
        $mapConfig = $configuration['map'] ?? null;
        $merge = (bool) ($configuration['merge'] ?? false);
        $defaultMap = Asset\Environment\Map\MapFactory::createDefault($version);

        if (empty($mapConfig)) {
            return new Asset\Environment\EnvironmentResolver($defaultMap);
        }

        $map = $this->mapFactory->createFromArray($mapConfig, $version);
        if ($merge) {
            $map = $defaultMap->merge($map);
        }

        return new Asset\Environment\EnvironmentResolver($map);
    }
}
