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

namespace CPSIT\FrontendAssetHandler\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Handler;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

/**
 * HandlerConfigStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class HandlerConfigStep extends BaseStep implements InteractiveStepInterface
{
    public function __construct(
        private readonly DependencyInjection\ServiceLocator $handlers,
    ) {
        parent::__construct();
    }

    public function getInputOptions(): array
    {
        return [
            new Console\Input\InputOption(
                'handler-type',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Type of the asset handler, resolves to a supported asset handler',
                Handler\AssetHandler::getName(),
            ),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        $input = $this->getInput($request);
        $io = new Console\Style\SymfonyStyle($input, $this->output);

        $io->title('Handler');

        // Ask for handler type
        $handlerType = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createChoiceQuestion(
                'Type',
                $this->handlers->getProvidedServices(),
                $request->getOption('handler-type'),
            ),
        );
        $request->setOption('handler-type', $handlerType);

        // Build handler
        if (null !== $handlerType) {
            $this->buildHandler($request, (string) $handlerType);
        }

        return true;
    }

    private function buildHandler(Config\Initialization\InitializationRequest $request, string $handlerType): void
    {
        $config = $request->getConfig();
        $definitionId = (int) $request->getOption('definition-id');

        $config['frontend-assets'][$definitionId]['handler'] = $handlerType;
    }
}
