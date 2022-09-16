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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes;

use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Traits;
use Symfony\Component\Console as SymfonyConsole;

/**
 * DummyInteractiveStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DummyInteractiveStep implements Config\Initialization\Step\InteractiveStepInterface, ChattyInterface
{
    use Traits\OutputAwareTrait;

    public ?Config\Config $expectedConfig = null;

    public function __construct()
    {
        $this->setOutput(new SymfonyConsole\Output\NullOutput());
    }

    public function getInputOptions(): array
    {
        return [
            new SymfonyConsole\Input\InputOption('config', mode: SymfonyConsole\Input\InputOption::VALUE_REQUIRED),
            new SymfonyConsole\Input\InputOption('foo', mode: SymfonyConsole\Input\InputOption::VALUE_REQUIRED),
            new SymfonyConsole\Input\InputOption('baz', mode: SymfonyConsole\Input\InputOption::VALUE_REQUIRED, default: 'baz'),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        if (null !== $this->expectedConfig) {
            $request->setConfig($this->expectedConfig);
        }

        $request->getConfig()['frontend-assets'][0]['request-options'] = $request->getOptions();

        return true;
    }

    public function getOutput(): Console\Output\TrackableOutput
    {
        return $this->output;
    }
}
