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
use CPSIT\FrontendAssetHandler\Provider;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

use function array_replace_recursive;
use function is_array;
use function is_string;
use function json_decode;

/**
 * SourceConfigStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class SourceConfigStep extends BaseStep implements InteractiveStepInterface
{
    public function __construct(
        private readonly DependencyInjection\ServiceLocator $providers,
    ) {
        parent::__construct();
    }

    public function getInputOptions(): array
    {
        return [
            new Console\Input\InputOption(
                'source-type',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Type of the asset source, resolves to a supported asset provider',
                Provider\HttpFileProvider::getName(),
            ),
            new Console\Input\InputOption(
                'source-url',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'URL to locate the asset source files, can contain placeholders in the form {<config key>}',
            ),
            new Console\Input\InputOption(
                'source-revision-url',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'URL to locate the revision of asset source files, can contain placeholders in the form {<config key>}',
            ),
            new Console\Input\InputOption(
                'source-version',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Locked version of an asset source to be used for the stable environment',
            ),
            new Console\Input\InputOption(
                'source-config-extra',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Additional configuration for the asset source definition, should be a JSON-encoded string',
            ),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        $input = $this->getInput($request);
        $io = new Console\Style\SymfonyStyle($input, $this->output);

        if ($input->isInteractive()) {
            $io->title('Source');
        }

        // Initialize additional variables
        $additionalVariables = [];

        // Source type
        $sourceType = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createChoiceQuestion(
                'Type',
                $this->providers->getProvidedServices(),
                $request->getOption('source-type'),
            ),
        );
        $request->setOption('source-type', $sourceType);

        // Source url
        $sourceUrl = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'URL',
                $request->getOption('source-url'),
                validator: ['notEmpty', 'url'],
            ),
        );
        $request->setOption('source-url', $sourceUrl);

        // Source url placeholders
        $this->askForPlaceholderVariables(
            $request,
            $sourceUrl,
            'URL placeholder "%s" (optional)',
            $additionalVariables,
        );

        // Source revision url
        $sourceRevisionUrl = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Revision URL',
                $request->getOption('source-revision-url'),
                validator: 'url',
            ),
        );
        $request->setOption('source-revision-url', $sourceRevisionUrl);

        // Source revision url placeholders
        $this->askForPlaceholderVariables(
            $request,
            $sourceRevisionUrl,
            'Revision URL placeholder "%s" (optional)',
            $additionalVariables,
        );

        // Source version
        $sourceVersion = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Locked version',
                $request->getOption('source-version'),
            ),
        );
        $request->setOption('source-version', $sourceVersion);

        // Source config extra
        $sourceConfigExtra = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Additional config',
                $request->getOption('source-config-extra'),
                validator: 'json',
            ),
        );

        if (is_string($sourceConfigExtra)) {
            $additionalVariables = array_replace_recursive(
                $additionalVariables,
                json_decode($sourceConfigExtra, true, flags: JSON_THROW_ON_ERROR),
            );
        }

        $request->setOption('source-config-extra', $additionalVariables);

        // Build source
        $this->buildSource($request);

        return true;
    }

    private function buildSource(Config\Initialization\InitializationRequest $request): void
    {
        $config = $request->getConfig();
        $definitionId = (int) $request->getOption('definition-id');

        // Define default source configuration
        $sourceConfig = [
            'type' => $request->getOption('source-type'),
            'url' => $request->getOption('source-url'),
        ];

        // Add revision URL
        if (is_string($request->getOption('source-revision-url'))) {
            $sourceConfig['revision-url'] = $request->getOption('source-revision-url');
        }

        // Add version
        if (is_string($request->getOption('source-version'))) {
            $sourceConfig['version'] = $request->getOption('source-version');
        }

        // Merge additional source configuration
        $sourceConfigExtra = $request->getOption('source-config-extra');
        if (is_array($sourceConfigExtra)) {
            $sourceConfig = array_replace_recursive($sourceConfig, $sourceConfigExtra);
        }

        // Apply source
        $config['frontend-assets'][$definitionId]['source'] = $sourceConfig;
    }
}
