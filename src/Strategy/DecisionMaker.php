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

namespace CPSIT\FrontendAssetHandler\Strategy;

use CPSIT\FrontendAssetHandler\Asset;

/**
 * DecisionMaker.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
class DecisionMaker
{
    public function __construct(
        protected Asset\Revision\RevisionProvider $revisionProvider,
    ) {
    }

    public function decide(Asset\Definition\Source $source, Asset\Definition\Target $target): Strategy
    {
        $source['revision'] = $this->revisionProvider->getRevision($source);
        if (null === $source->getRevision()) {
            return Strategy::FetchNew;
        }

        $target['revision'] = $this->revisionProvider->getRevision($target);
        if (null === $target->getRevision()) {
            return Strategy::FetchNew;
        }

        if ($source->getRevision()->equals($target->getRevision())) {
            return Strategy::UseExisting;
        }

        return Strategy::FetchNew;
    }
}
