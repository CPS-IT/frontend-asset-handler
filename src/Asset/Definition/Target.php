<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Asset\Definition;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Processor;

/**
 * Target.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
class Target extends AssetDefinition
{
    final public const DEFAULT_REVISION_FILE = 'REVISION';

    protected array $defaults = [
        /* @see Processor\FileArchiveProcessor::getName() */
        'type' => 'archive',
        'revision-file' => self::DEFAULT_REVISION_FILE,
    ];

    public function getType(): string
    {
        return $this['type'];
    }

    public function getPath(): ?string
    {
        return $this['path'];
    }

    public function getRevisionFile(): string
    {
        return $this['revision-file'];
    }

    public function getRevision(): ?Asset\Revision\Revision
    {
        return $this['revision'];
    }
}
