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

namespace CPSIT\FrontendAssetHandler\DependencyInjection;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Helper;
use Symfony\Component\Config as SymfonyConfig;
use Symfony\Component\DependencyInjection as SymfonyDI;
use Symfony\Component\Filesystem;

use function dirname;

/**
 * ContainerFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ContainerFactory
{
    private readonly ?string $configFile;
    private readonly Cache\ContainerCache $cache;
    private readonly Config\Loader\ConfigLoaderInterface $configLoader;
    private readonly Config\Parser\ServicesParser $servicesParser;

    public function __construct(
        string $configFile = null,
        bool $debug = null,
        private readonly bool $includeTestSources = false,
    ) {
        $this->configFile = $configFile ? Helper\FilesystemHelper::resolveRelativePath($configFile, true) : null;
        $this->cache = new Cache\ContainerCache($this->configFile, $this->includeTestSources, $debug);
        $this->configLoader = new Config\Loader\ExternalJsonFileLoader();
        $this->servicesParser = new Config\Parser\ServicesParser();
    }

    public function get(): SymfonyDI\ContainerInterface
    {
        // Return existing container from cache
        if ($container = $this->cache->get()) {
            $this->setSyntheticServices($container);

            return $container;
        }

        // Initialize container
        $containerBuilder = new SymfonyDI\ContainerBuilder();
        $loader = $this->createLoader($containerBuilder);

        // Define some default parameters
        $containerBuilder->getParameterBag()->add([
            'app.root_dir' => dirname(__DIR__, 2),
            'app.config_file' => $this->configFile,
        ]);

        $configFiles = [
            'services.yaml',
            'services.php',
        ];

        if ($this->includeTestSources) {
            $configFiles[] = 'services_test.yaml';
            $configFiles[] = 'services_test.php';
        }

        // Include external service configuration (skipped when creating failsafe container)
        if (null !== $this->configFile) {
            $config = $this->configLoader->load($this->configFile);
            $services = $this->servicesParser->parse($config)['services'];
            $configFiles = array_merge($configFiles, $services);
        }

        // Load all config files
        foreach ($configFiles as $configFile) {
            $loader->load($configFile);
        }

        // Dump and cache container
        $container = $this->cache->write($containerBuilder);
        $this->setSyntheticServices($container);

        return $container;
    }

    public function rebuild(): SymfonyDI\ContainerInterface
    {
        $this->cache->flush();

        return $this->get();
    }

    private function setSyntheticServices(SymfonyDI\ContainerInterface $container): void
    {
        $container->set('app.cache', $this->cache);
    }

    private function createLoader(SymfonyDI\ContainerBuilder $containerBuilder): SymfonyConfig\Loader\DelegatingLoader
    {
        $configDirectory = Filesystem\Path::join(dirname(__DIR__, 2), 'config');
        $resolver = new SymfonyConfig\Loader\LoaderResolver([
            new SymfonyDI\Loader\YamlFileLoader($containerBuilder, new SymfonyConfig\FileLocator($configDirectory)),
            new SymfonyDI\Loader\PhpFileLoader($containerBuilder, new SymfonyConfig\FileLocator($configDirectory)),
        ]);

        return new SymfonyConfig\Loader\DelegatingLoader($resolver);
    }
}
