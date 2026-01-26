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

namespace CPSIT\FrontendAssetHandler\Command;

use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Json;
use JsonException;
use Symfony\Component\Console;

use function sprintf;

/**
 * InitConfigCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InitConfigCommand extends Console\Command\Command
{
    private const SUCCESSFUL = 0;
    private const ERROR_FAILED_ACTION = 1;

    private Console\Style\SymfonyStyle $io;

    /**
     * @param non-empty-list<Config\Initialization\Step\StepInterface> $initSteps
     */
    public function __construct(
        private readonly array $initSteps,
        private readonly Config\ConfigFacade $configFacade,
        private readonly Json\SchemaValidator $validator,
    ) {
        parent::__construct('init');
    }

    protected function configure(): void
    {
        $this->setDescription('Initialize a new configuration file to handle Frontend assets');

        $this->addOptionsFromConfigurableInitSteps();
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
    }

    /**
     * @throws Exception\InvalidConfigurationException
     * @throws Exception\MissingConfigurationException
     * @throws JsonException
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        if ($input->isInteractive()) {
            $output->writeln([
                'Welcome to the Frontend Asset Handler!',
                'You can use the following command to initialize a new asset configuration for your Frontend assets.',
                'Follow the guide and answer all relevant questions to get started.',
            ]);
        }

        $request = Config\Initialization\InitializationRequest::fromCommandInput($input);

        // Run init steps
        foreach ($this->initSteps as $step) {
            if ($step instanceof ChattyInterface) {
                $step->setOutput($output);
            }

            if (!$step->execute($request)) {
                return $this->exitOnFailedInitStep($step);
            }
        }

        // Validate config
        if (!$this->validator->validate($request->getConfig())) {
            throw Exception\InvalidConfigurationException::asReported($this->validator->getLastValidationErrors()->errors());
        }

        // Write config
        if (!$this->configFacade->write($request->getConfig())) {
            throw Exception\FilesystemFailureException::forFailedWriteOperation($request->getConfigFile());
        }

        if ($input->isInteractive()) {
            $this->io->newLine();
        }

        $this->io->success(
            sprintf('Asset configuration was successfully written to %s', $request->getConfigFile()),
        );

        return self::SUCCESSFUL;
    }

    private function addOptionsFromConfigurableInitSteps(): void
    {
        $fullDefinition = $this->getDefinition();
        $nativeDefinition = $this->getNativeDefinition();

        foreach ($this->initSteps as $step) {
            if (!$step instanceof Config\Initialization\Step\InteractiveStepInterface) {
                continue;
            }

            $options = $step->getInputOptions();

            // Add options to full and native definition
            $fullDefinition->addOptions($options);
            if ($nativeDefinition !== $fullDefinition) {
                $nativeDefinition->addOptions($options);
            }
        }
    }

    private function exitOnFailedInitStep(Config\Initialization\Step\StepInterface $failedAction): int
    {
        $this->io->error(
            sprintf('Action "%s" failed to initialize the configuration.', $failedAction::class),
        );

        return self::ERROR_FAILED_ACTION;
    }
}
