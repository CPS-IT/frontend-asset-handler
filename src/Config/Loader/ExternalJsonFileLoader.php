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

namespace CPSIT\FrontendAssetHandler\Config\Loader;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use Symfony\Component\Filesystem;

use function basename;
use function is_array;
use function strtolower;

/**
 * ExternalJsonFileLoader.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExternalJsonFileLoader implements ConfigLoaderInterface
{
    public function load(string $filePath): Config\Config
    {
        $json = Helper\FilesystemHelper::parseJsonFileContents($filePath);
        $jsonArray = json_decode($json->encoded(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($jsonArray)) {
            throw Exception\UnprocessableConfigFileException::create($filePath);
        }
        if (!is_array($jsonArray['frontend-assets'] ?? null)) {
            throw Exception\MissingConfigurationException::forKey('frontend-assets');
        }

        return new Config\Config($jsonArray, $filePath);
    }

    public static function canLoad(string $filePath): bool
    {
        if ('composer.json' === strtolower(basename($filePath))) {
            return false;
        }

        return Filesystem\Path::hasExtension($filePath, 'json', true);
    }
}
