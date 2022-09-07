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

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Value;
use CPSIT\FrontendAssetHandler\Vcs;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    $container->registerForAutoconfiguration(Config\Loader\ConfigLoaderInterface::class)->addTag('config.loader');
    $container->registerForAutoconfiguration(Config\Writer\ConfigWriterInterface::class)->addTag('config.writer');
    $container->registerForAutoconfiguration(Processor\ProcessorInterface::class)->addTag('asset_handling.processor');
    $container->registerForAutoconfiguration(Provider\ProviderInterface::class)->addTag('asset_handling.provider');
    $container->registerForAutoconfiguration(Value\Placeholder\PlaceholderProcessorInterface::class)->addTag('value.placeholder_processor');
    $container->registerForAutoconfiguration(Asset\Environment\Transformer\TransformerInterface::class)->addTag('asset_environment.transformer');
    $container->registerForAutoconfiguration(Vcs\VcsProviderInterface::class)->addTag('asset_handling.vcs_provider');

    // Compiler passes
    $container->addCompilerPass(new DependencyInjection\CompilerPass\EnvironmentTransformerCompilerPass());
};
