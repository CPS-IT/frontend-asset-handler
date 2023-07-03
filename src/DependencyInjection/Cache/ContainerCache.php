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

namespace CPSIT\FrontendAssetHandler\DependencyInjection\Cache;

use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Exception;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection as SymfonyDI;
use Symfony\Component\Filesystem;

use function assert;
use function dirname;
use function function_exists;
use function implode;
use function is_a;
use function is_string;
use function str_replace;
use function ucwords;

/**
 * ConfigCache.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ContainerCache
{
    private const CONTAINER_NAMESPACE = 'CPSIT\\FrontendAssetHandler\\DependencyInjection';

    private readonly bool $debug;
    private readonly string $cacheDir;
    private Config\ConfigCache $cache;
    private string $containerClassName;

    public function __construct(
        private readonly ?string $configFile = null,
        private readonly bool $includeTestSources = false,
        bool $debug = null,
        string $cacheDir = null,
    ) {
        $this->debug = $debug ?? $this->isDebugEnabled();
        $this->cacheDir = $cacheDir ?? $this->getDefaultCacheDirectory();

        $this->createCache();
    }

    public function isFresh(): bool
    {
        return $this->cache->isFresh();
    }

    public function get(): ?SymfonyDI\ContainerInterface
    {
        if ($this->isFresh()) {
            return $this->doGet();
        }

        return null;
    }

    public function flush(): void
    {
        $cachePath = $this->cache->getPath();

        if (file_exists($cachePath)) {
            unlink($cachePath);
        }

        $this->createCache();
    }

    public function flushAll(): void
    {
        $filesystem = new Filesystem\Filesystem();
        $filesystem->remove([
            $this->getProjectCacheDirectory(),
            $this->getGlobalCacheDirectory(),
        ]);

        $this->createCache();
    }

    public function write(SymfonyDI\ContainerBuilder $containerBuilder): SymfonyDI\ContainerInterface
    {
        if ($this->debug) {
            $containerXmlFilename = basename($this->cache->getPath(), '.php');
            $containerXmlPath = dirname($this->cache->getPath()).'/'.$containerXmlFilename.'.xml';

            $containerBuilder->addCompilerPass(
                new DependencyInjection\CompilerPass\ContainerBuilderDebugDumpPass($containerXmlPath)
            );
        }

        $containerBuilder->compile();

        $dumper = new SymfonyDI\Dumper\PhpDumper($containerBuilder);
        $dumpedContainer = $dumper->dump([
            'namespace' => self::CONTAINER_NAMESPACE,
            'class' => $this->containerClassName,
        ]);
        assert(is_string($dumpedContainer));

        $this->cache->write($dumpedContainer, $containerBuilder->getResources());

        return $this->doGet() ?? $containerBuilder;
    }

    public function getConfigFile(): ?string
    {
        return $this->configFile;
    }

    public function getPath(): string
    {
        return $this->cache->getPath();
    }

    private function doGet(): ?SymfonyDI\ContainerInterface
    {
        if (!file_exists($this->cache->getPath())) {
            return null;
        }

        require_once $this->cache->getPath();

        /** @var class-string $className */
        $className = self::CONTAINER_NAMESPACE.'\\'.$this->containerClassName;

        // @codeCoverageIgnoreStart
        if (!is_a($className, SymfonyDI\ContainerInterface::class, true)) {
            throw Exception\UnsupportedClassException::create($className);
        }
        // @codeCoverageIgnoreEnd

        return new $className();
    }

    private function createCache(): void
    {
        $basenameComponents = ['container'];

        if ($this->includeTestSources) {
            $basenameComponents[] = 'test';
        }
        if (null !== $this->configFile) {
            $basenameComponents[] = sha1($this->configFile);
        }

        $cachePath = Filesystem\Path::join($this->cacheDir, implode('_', $basenameComponents).'.php');

        $this->cache = new Config\ConfigCache($cachePath, $this->debug);
        $this->containerClassName = 'FrontendAssetHandler'.str_replace(' ', '', ucwords(implode(' ', $basenameComponents)));
    }

    private function isDebugEnabled(): bool
    {
        return function_exists('xdebug_break');
    }

    private function getDefaultCacheDirectory(): string
    {
        if ($this->includeTestSources) {
            return $this->getProjectCacheDirectory();
        }

        return $this->getGlobalCacheDirectory();
    }

    private function getProjectCacheDirectory(): string
    {
        return dirname(__DIR__, 3).'/var/cache';
    }

    private function getGlobalCacheDirectory(): string
    {
        return sys_get_temp_dir().'/frontend-asset-handler/cache';
    }
}
