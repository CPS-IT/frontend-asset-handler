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

namespace CPSIT\FrontendAssetHandler\Config\Parser;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use ReflectionFunction;
use Symfony\Component\Filesystem;

use function in_array;
use function is_callable;

/**
 * ServicesParser.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ServicesParser
{
    /**
     * @return list<string>
     */
    public function parse(Config\Config $config): array
    {
        $services = [];

        if ([] === ($config['services'] ?? [])) {
            return [];
        }

        foreach ($config['services'] as $service) {
            $filePath = Helper\FilesystemHelper::resolveRelativePath($service);
            $this->validateService($filePath);
            $services[] = $filePath;
        }

        return $services;
    }

    private function validateService(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw Exception\FilesystemFailureException::forMissingPath($filePath);
        }

        $extension = Filesystem\Path::getExtension($filePath, true);

        // Early return if file is a YAML file
        if (in_array($extension, ['yaml', 'yml'], true)) {
            return;
        }

        // Throw exception if file is neither a YAML nor a PHP file
        if ('php' !== $extension) {
            throw Exception\UnprocessableConfigFileException::create($filePath);
        }

        $callable = require $filePath;

        if (!is_callable($callable)) {
            throw Exception\UnprocessableConfigFileException::create($filePath);
        }

        $reflection = new ReflectionFunction($callable(...));
        $parameters = $reflection->getParameters();

        if ([] === $parameters) {
            throw Exception\UnprocessableConfigFileException::create($filePath);
        }
    }
}
