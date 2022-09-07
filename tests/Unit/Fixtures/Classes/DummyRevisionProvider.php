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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes;

use CPSIT\FrontendAssetHandler\Asset\Definition\AssetDefinition;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use CPSIT\FrontendAssetHandler\Asset\Revision\RevisionProvider;

/**
 * DummyRevisionProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyRevisionProvider extends RevisionProvider
{
    /**
     * @var array<string|null>
     */
    public $expectedRevisions = [];

    public function getRevision(AssetDefinition $definition): ?Revision
    {
        $revision = array_shift($this->expectedRevisions);

        if (null !== $revision) {
            return new Revision($revision);
        }

        return null;
    }
}
