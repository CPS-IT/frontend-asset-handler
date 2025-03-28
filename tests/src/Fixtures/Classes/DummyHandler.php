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
use CPSIT\FrontendAssetHandler\Handler;
use CPSIT\FrontendAssetHandler\Strategy;
use Symfony\Component\Console;
use Throwable;

use function array_shift;

/**
 * DummyHandler.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyHandler implements Handler\HandlerInterface, ChattyInterface
{
    public ?Console\Output\OutputInterface $output = null;

    /**
     * @var list<Throwable|Asset\Asset>
     */
    public array $returnQueue = [];
    public ?Strategy\Strategy $lastStrategy = null;

    public static function getName(): string
    {
        return 'dummy';
    }

    public function handle(
        Asset\Definition\Source $source,
        Asset\Definition\Target $target,
        ?Strategy\Strategy $strategy = null,
    ): Asset\Asset {
        $this->lastStrategy = $strategy;

        $nextReturn = array_shift($this->returnQueue);

        if ($nextReturn instanceof Throwable) {
            throw $nextReturn;
        }

        if ($nextReturn instanceof Asset\Asset) {
            return $nextReturn;
        }

        return new Asset\Asset($source, $target);
    }

    public function setOutput(Console\Output\OutputInterface $output): void
    {
        $this->output = $output;
    }
}
