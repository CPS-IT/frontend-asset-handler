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
use CPSIT\FrontendAssetHandler\Vcs;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

use function array_replace_recursive;
use function is_array;
use function is_string;

/**
 * VcsConfigStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class VcsConfigStep extends BaseStep implements InteractiveStepInterface
{
    public function __construct(
        private readonly DependencyInjection\ServiceLocator $vcsProviders,
    ) {
        parent::__construct();
    }

    public function getInputOptions(): array
    {
        return [
            new Console\Input\InputOption(
                'vcs-type',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Type of the asset\'s VCS, resolves to a supported VCS provider',
            ),
            new Console\Input\InputOption(
                'vcs-config-extra',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'Additional configuration for the asset VCS definition, should be a JSON-encoded string',
            ),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        $input = $this->getInput($request);
        $io = new Console\Style\SymfonyStyle($input, $this->output);

        $io->title('VCS');

        // Early return if VCS should not be configured
        if (!$this->shouldConfigureVcs($request)) {
            return true;
        }

        // Initialize additional variables
        $additionalVariables = [];

        // VCS type
        $vcsType = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createChoiceQuestion(
                'Type',
                $this->vcsProviders->getProvidedServices(),
                $request->getOption('vcs-type'),
            ),
        );
        $request->setOption('vcs-type', $vcsType);

        // Additional variables for specific providers
        switch ($vcsType) {
            case Vcs\GitlabVcsProvider::getName():
                $this->requestAdditionalVariablesForGitlabVcsProvider($request, $additionalVariables);
                break;

            case Vcs\GithubVcsProvider::getName():
                $this->requestAdditionalVariablesForGithubVcsProvider($request, $additionalVariables);
                break;
        }

        // VCS config extra
        $vcsConfigExtra = $this->questionHelper->ask(
            $input,
            $this->output,
            $this->createQuestion(
                'Additional config',
                $request->getOption('vcs-config-extra'),
                validator: 'json',
            ),
        );

        if (is_string($vcsConfigExtra)) {
            $additionalVariables = array_replace_recursive(
                $additionalVariables,
                json_decode($vcsConfigExtra, true, flags: JSON_THROW_ON_ERROR),
            );
        }

        $request->setOption('vcs-config-extra', $additionalVariables);

        // Build VCS
        $this->buildVcs($request);

        return true;
    }

    /**
     * @param array<string, mixed> $additionalVariables
     */
    private function requestAdditionalVariablesForGitlabVcsProvider(
        Config\Initialization\InitializationRequest $request,
        array &$additionalVariables,
    ): void {
        $this->askForAdditionalVariable(
            $request,
            'Base URL',
            'base-url',
            $additionalVariables,
            'https://gitlab.com',
            ['notEmpty', 'url'],
        );

        $this->askForAdditionalVariable(
            $request,
            'Access token',
            'access-token',
            $additionalVariables,
            validator: 'notEmpty',
        );

        $this->askForAdditionalVariable(
            $request,
            'Project ID',
            'project-id',
            $additionalVariables,
            validator: 'integer',
        );
    }

    /**
     * @param array<string, mixed> $additionalVariables
     */
    private function requestAdditionalVariablesForGithubVcsProvider(
        Config\Initialization\InitializationRequest $request,
        array &$additionalVariables,
    ): void {
        $this->askForAdditionalVariable(
            $request,
            'Access token',
            'access-token',
            $additionalVariables,
            validator: 'notEmpty',
        );

        $this->askForAdditionalVariable(
            $request,
            'Repository (<owner>/<name>)',
            'repository',
            $additionalVariables,
            validator: 'notEmpty',
        );
    }

    private function buildVcs(Config\Initialization\InitializationRequest $request): void
    {
        $config = $request->getConfig();
        $definitionId = (int) $request->getOption('definition-id');

        // Define default VCS configuration
        $vcsConfig = [
            'type' => $request->getOption('vcs-type'),
        ];

        // Merge additional VCS configuration
        $vcsConfigExtra = $request->getOption('vcs-config-extra');
        if (is_array($vcsConfigExtra)) {
            $vcsConfig = array_replace_recursive($vcsConfig, $vcsConfigExtra);
        }

        // Apply VCS
        $config['frontend-assets'][$definitionId]['vcs'] = $vcsConfig;
    }

    private function shouldConfigureVcs(Config\Initialization\InitializationRequest $request): bool
    {
        if (null !== $request->getOption('vcs-type')) {
            return true;
        }

        $this->output->writeln('The following VCS configuration is optional.');

        return $this->askBooleanQuestion($request, 'Add VCS configuration?');
    }
}
