<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) Fabien Potencier <fabien@symfony.com>
 */

namespace CPSIT\FrontendAssetHandler\DependencyInjection\CompilerPass;

use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;

/**
 * Dumps the ContainerBuilder to a cache file so that it can be used by
 * debugging tools such as the debug:container console command.
 *
 * @author Ryan Weaver <ryan@thatsquality.com>
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal Only to be used for testing purposes
 *
 * @codeCoverageIgnore
 *
 * @see https://github.com/symfony/framework-bundle/blob/5.4/DependencyInjection/Compiler/ContainerBuilderDebugDumpPass.php
 */
final class ContainerBuilderDebugDumpPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function __construct(private readonly string $cachePath) {}

    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $cache = new Config\ConfigCache($this->cachePath, true);
        if (!$cache->isFresh()) {
            $cache->write((new DependencyInjection\Dumper\XmlDumper($container))->dump(), $container->getResources());
        }
    }
}
