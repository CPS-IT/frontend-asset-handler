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

namespace CPSIT\FrontendAssetHandler\Config\Writer;

use CPSIT\FrontendAssetHandler\Config;
use Ergebnis\Json\Normalizer;
use Symfony\Component\Filesystem;

use function strtolower;

/**
 * ExternalJsonFileWriter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExternalJsonFileWriter extends AbstractJsonFileWriter
{
    public function __construct(
        Normalizer\Format\Formatter $formatter,
        private readonly Filesystem\Filesystem $filesystem,
    ) {
        parent::__construct($formatter);
    }

    public function write(Config\Config $config): bool
    {
        if (!$this->filesystem->exists($config->getFilePath())) {
            $this->filesystem->dumpFile($config->getFilePath(), '{}');
        }

        [$mergedJson, $format] = $this->replaceInOriginalFileAtGivenPath($config, 'frontend-assets');

        return $this->doWrite($mergedJson, $config->getFilePath(), $format);
    }

    public static function canWrite(Config\Config $config): bool
    {
        if ('composer.json' === strtolower(basename($config->getFilePath()))) {
            return false;
        }

        return Filesystem\Path::hasExtension($config->getFilePath(), 'json', true);
    }
}
