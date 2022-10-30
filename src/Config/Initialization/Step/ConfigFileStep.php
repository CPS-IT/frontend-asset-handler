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
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use Symfony\Component\Console;

use function array_key_last;
use function sprintf;

/**
 * ConfigFileStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class ConfigFileStep extends BaseStep implements InteractiveStepInterface
{
    public function __construct(
        private readonly Config\ConfigFacade $configFacade,
        private readonly Config\Parser\Parser $configParser,
    ) {
        parent::__construct();
    }

    public function getInputOptions(): array
    {
        return [
            new Console\Input\InputOption(
                'definition-id',
                null,
                Console\Input\InputOption::VALUE_REQUIRED,
                'ID of the asset definition to be added to the asset configuration file',
                0,
            ),
        ];
    }

    public function execute(Config\Initialization\InitializationRequest $request): bool
    {
        $input = $this->getInput($request);

        // Load config file
        $configFile = $request->getConfigFile();
        $config = $this->loadConfig($configFile);

        // Early return if given config file does not exist yet
        if (null === $config) {
            $request->setConfig(new Config\Config([], $configFile));
            $request->setOption('definition-id', 0);

            return true;
        }

        // Fetch latest asset definition ID
        $upperDefinitionId = (int) array_key_last($config['frontend-assets']);

        // Early return if given definition id is valid
        if ((int) $request->getOption('definition-id') > $upperDefinitionId) {
            $request->setConfig($config);
            $request->setOption('definition-id', $upperDefinitionId + 1);

            return true;
        }

        // Early return if existing config file should be extended
        if ($this->shouldExtendConfig($request)) {
            $request->setConfig($config);
            $request->setOption('definition-id', $upperDefinitionId + 1);

            $this->output->writeln([
                sprintf('Alright, the config file <info>%s</info> will be extended by a new asset definition.', $configFile),
            ]);

            return true;
        }

        // Use another config file
        $configFile = $this->questionHelper->ask(
            $input,
            $this->output,
            new Console\Question\Question('<info>Path to the new config file</info>: '),
        );
        $request->setConfigFile(Helper\FilesystemHelper::resolveRelativePath($configFile));
        $request->setConfig(new Config\Config([], $request->getConfigFile()));
        $request->setOption('definition-id', 0);

        $this->output->writeln([
            sprintf('Alright, the config file <info>%s</info> will be used for the new asset definition.', $request->getConfigFile()),
        ]);

        return true;
    }

    private function shouldExtendConfig(Config\Initialization\InitializationRequest $request): bool
    {
        $configFile = $request->getConfigFile();

        $this->output->writeln([
            '',
            sprintf('You have configured the file <info>%s</info> for your Frontend assets.', $configFile),
            sprintf('A file with the name <info>%s</info> already exists.', $configFile),
            'You can <comment>add a new asset definition</comment> to the existing config file or <comment>create a new config file</comment>.',
            '',
        ]);

        return $this->askBooleanQuestion($request, sprintf('Add a new asset definition to %s?', $configFile));
    }

    private function loadConfig(string $configFile): ?Config\Config
    {
        try {
            $config = $this->configFacade->load($configFile);
        } catch (Exception\FilesystemFailureException) {
            return null;
        }

        $instructions = new Config\Parser\ParserInstructions($config);
        $instructions->processValues(false);

        return $this->configParser->parse($instructions);
    }
}
