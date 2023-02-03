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

namespace CPSIT\FrontendAssetHandler\DependencyInjection\CompilerPass;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use RuntimeException;
use Symfony\Component\DependencyInjection;

use function call_user_func;
use function in_array;

/**
 * EnvironmentTransformerCompilerPass.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class EnvironmentTransformerCompilerPass implements DependencyInjection\Compiler\CompilerPassInterface
{
    public function process(DependencyInjection\ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds('asset_environment.transformer');
        $transformers = [];

        foreach ($serviceIds as $serviceId => $tags) {
            if (!$container->hasDefinition($serviceId) || ($service = $container->findDefinition($serviceId))->isAbstract()) {
                continue;
            }

            if (null === ($className = $service->getClass())) {
                throw new RuntimeException(sprintf('Unable to determine class name for service "%s".', $serviceId), 1644422571);
            }
            /** @var class-string $className */
            if (!in_array(Asset\Environment\Transformer\TransformerInterface::class, (array) class_implements($className), true)) {
                throw Exception\UnsupportedClassException::create($className);
            }

            /** @var class-string<Asset\Environment\Transformer\TransformerInterface> $className */
            $name = call_user_func([$className, 'getName']);
            $transformers[$name] = $className;
        }

        $container->setParameter('asset_environment.transformers', $transformers);
    }
}
