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

namespace CPSIT\FrontendAssetHandler\Config;

use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;

/**
 * ConfigFacade.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final class ConfigFacade
{
    /**
     * @param iterable<Loader\ConfigLoaderInterface> $loaders
     * @param iterable<Writer\ConfigWriterInterface> $writers
     */
    public function __construct(
        private readonly iterable $loaders,
        private readonly iterable $writers,
    ) {
    }

    public function load(string $file): Config
    {
        $filePath = Helper\FilesystemHelper::resolveRelativePath($file, true);

        foreach ($this->loaders as $loader) {
            if ($loader::canLoad($filePath)) {
                return $loader->load($filePath);
            }
        }

        throw Exception\UnprocessableConfigFileException::create($filePath);
    }

    public function write(Config $config): bool
    {
        foreach ($this->writers as $writer) {
            if ($writer::canWrite($config)) {
                return $writer->write($config);
            }
        }

        throw Exception\UnprocessableConfigFileException::create($config->getFilePath());
    }
}
