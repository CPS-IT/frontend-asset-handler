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

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Processor;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

use function array_replace_recursive;
use function is_array;
use function is_string;

/**
 * TargetConfigStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class TargetConfigStep extends BaseStep implements InteractiveStepInterface
{
    public function __construct(
        private readonly DependencyInjection\ServiceLocator $processors,
    ) {
        parent::__construct();
    }

    public function getInputOptions(): array
    {
        return [
            new Console\Input\InputOption(
                'target-type',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Type of the asset target, resolves to a supported asset processor',
                Processor\FileArchiveProcessor::getName(),
            ),
            new Console\Input\InputOption(
                'target-path',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Path where to extract fetched assets, can be either absolute or relative to the config file',
            ),
            new Console\Input\InputOption(
                'target-revision-file',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Filename of the asset target\'s revision file',
                Asset\Definition\Target::DEFAULT_REVISION_FILE,
            ),
            new Console\Input\InputOption(
                'target-config-extra',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Additional configuration for the asset target definition, should be a JSON-encoded string',
            ),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        $input = $this->getInput($request);
        $io = new Console\Style\SymfonyStyle($input, $this->output);

        if ($input->isInteractive()) {
            $io->title('Target');
        }

        // Initialize additional variables
        $additionalVariables = [];

        // Target type
        $targetType = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createChoiceQuestion(
                'Type',
                $this->processors->getProvidedServices(),
                $request->getOption('target-type'),
            ),
        );
        $request->setOption('target-type', $targetType);

        // Target path
        $targetPath = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Path',
                $request->getOption('target-path'),
                validator: 'notEmpty',
            ),
        );
        $request->setOption('target-path', $targetPath);

        // Additional variables for FileArchiveProcessor only)
        if (Processor\FileArchiveProcessor::getName() === $targetType) {
            $this->askForAdditionalVariable(
                $request,
                'Base archive path',
                'base',
                $additionalVariables,
                '',
            );
        }

        // Target revision file
        $targetRevisionFile = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Revision file',
                $request->getOption('target-revision-file'),
                validator: 'notEmpty',
            ),
        );
        $request->setOption('target-revision-file', $targetRevisionFile);

        // Target config extra
        $targetConfigExtra = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Additional config',
                $request->getOption('target-config-extra'),
                validator: 'json',
            ),
        );

        if (is_string($targetConfigExtra)) {
            $additionalVariables = array_replace_recursive(
                $additionalVariables,
                json_decode($targetConfigExtra, true, flags: JSON_THROW_ON_ERROR),
            );
        }

        $request->setOption('target-config-extra', $additionalVariables);

        // Build target
        $this->buildTarget($request);

        return true;
    }

    private function buildTarget(Config\Initialization\InitializationRequest $request): void
    {
        $config = $request->getConfig();
        $definitionId = (int) $request->getOption('definition-id');

        // Define default target configuration
        $targetConfig = [
            'type' => $request->getOption('target-type'),
            'path' => $request->getOption('target-path'),
            'revision-file' => $request->getOption('target-revision-file'),
        ];

        // Merge additional target configuration
        $targetConfigExtra = $request->getOption('target-config-extra');
        if (is_array($targetConfigExtra)) {
            $targetConfig = array_replace_recursive($targetConfig, $targetConfigExtra);
        }

        // Apply target
        $config['frontend-assets'][$definitionId]['target'] = $targetConfig;
    }
}
