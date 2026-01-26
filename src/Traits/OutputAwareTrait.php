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

namespace CPSIT\FrontendAssetHandler\Traits;

use CPSIT\FrontendAssetHandler\Console;
use Symfony\Component\Console as SymfonyConsole;

/**
 * OutputAwareTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait OutputAwareTrait
{
    protected Console\Output\TrackableOutput $output;

    public function setOutput(SymfonyConsole\Output\OutputInterface $output): void
    {
        if (!$output instanceof Console\Output\TrackableOutput) {
            $output = new Console\Output\TrackableOutput($output);
        }

        $this->output = $output;
    }
}
