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

namespace CPSIT\FrontendAssetHandler\Command;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Exception;
use JsonException;
use Symfony\Component\Console;

/**
 * BaseAssetsCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class BaseAssetsCommand extends Console\Command\Command
{
    public function __construct(
        string $name,
        protected readonly DependencyInjection\Cache\ContainerCache $cache,
        protected readonly Config\ConfigFacade $configFacade,
        protected readonly Config\Parser\Parser $configParser,
    ) {
        parent::__construct($name);
    }

    /**
     * @param list<string> $requiredKeys
     *
     * @throws Exception\InvalidConfigurationException
     * @throws Exception\MissingConfigurationException
     * @throws JsonException
     */
    protected function loadConfig(
        array $requiredKeys = [],
        bool $processValues = true
    ): Config\Config {
        $configFile = $this->cache->getConfigFile();

        // Early return if config file is missing
        if (null === $configFile) {
            throw Exception\MissingConfigurationException::create();
        }

        $config = $this->configFacade->load($configFile);
        $instructions = new Config\Parser\ParserInstructions($config);
        $instructions->processValues($processValues);

        foreach ($requiredKeys as $requiredKey) {
            $instructions->requireKey($requiredKey);
        }

        return $this->configParser->parse($instructions);
    }
}
