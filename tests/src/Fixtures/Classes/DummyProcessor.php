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

namespace CPSIT\FrontendAssetHandler\Tests\Fixtures\Classes;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Processor;
use Symfony\Component\Console;

/**
 * DummyProcessor.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyProcessor implements Processor\ProcessorInterface, ChattyInterface
{
    public ?Console\Output\OutputInterface $output = null;
    public bool $shouldReturnValidPath = true;

    public static function getName(): string
    {
        return 'dummy';
    }

    public function processAsset(Asset\Asset $asset): string
    {
        if (!$this->shouldReturnValidPath) {
            return '';
        }

        return 'foo';
    }

    public function getAssetPath(Asset\Asset $asset): string
    {
        return '/tmp';
    }

    public function setOutput(Console\Output\OutputInterface $output): void
    {
        $this->output = $output;
    }
}
