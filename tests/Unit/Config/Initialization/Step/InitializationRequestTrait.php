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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\Config;
use Symfony\Component\Console;

use function array_map;
use function dirname;
use function in_array;

/**
 * InitializationRequestTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait InitializationRequestTrait
{
    private function createRequest(
        Config\Initialization\Step\InteractiveStepInterface $subject,
        string $configFile = null,
        int $definitionId = 0,
    ): Config\Initialization\InitializationRequest {
        $definition = $subject->getInputOptions();

        $definedOptions = array_map(
            fn (Console\Input\InputOption $inputOption): string => $inputOption->getName(),
            $definition,
        );

        if (!in_array('config', $definedOptions, true)) {
            $definition[] = new Console\Input\InputOption('config', mode: Console\Input\InputOption::VALUE_REQUIRED);
        }
        if (!in_array('definition-id', $definedOptions, true)) {
            $definition[] = new Console\Input\InputOption('definition-id', mode: Console\Input\InputOption::VALUE_REQUIRED);
        }

        $configFile ??= dirname(__DIR__, 3).'/Fixtures/JsonFiles/assets.json';

        return Config\Initialization\InitializationRequest::fromCommandInput(
            new Console\Input\ArrayInput(
                [
                    '--config' => $configFile,
                    '--definition-id' => $definitionId,
                ],
                new Console\Input\InputDefinition($definition),
            ),
        );
    }
}
