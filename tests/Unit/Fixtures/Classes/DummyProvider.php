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

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Provider;
use Symfony\Component\Console;
use Throwable;

use function array_shift;

/**
 * DummyProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyProvider implements Provider\ProviderInterface, ChattyInterface
{
    public ?Console\Output\OutputInterface $output = null;

    /**
     * @var list<Throwable>
     */
    public array $expectedExceptions = [];

    /**
     * @var list<Asset\Asset>
     */
    public array $expectedAssets = [];

    public static function getName(): string
    {
        return 'dummy';
    }

    public function fetchAsset(Asset\Definition\Source $source): Asset\Asset
    {
        if ([] !== $this->expectedExceptions) {
            throw array_shift($this->expectedExceptions);
        }

        if ([] !== $this->expectedAssets) {
            return array_shift($this->expectedAssets);
        }

        return new Asset\Asset($source);
    }

    public function getAssetUrl(Asset\Definition\Source $source): string
    {
        return 'https://www.example.com';
    }

    public function setOutput(Console\Output\OutputInterface $output): void
    {
        $this->output = $output;
    }
}
