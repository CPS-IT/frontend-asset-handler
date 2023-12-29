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

namespace CPSIT\FrontendAssetHandler\Console;

use Composer\InstalledVersions;
use CPSIT\FrontendAssetHandler\Command;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Helper\FilesystemHelper;
use OutOfBoundsException;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection as SymfonyDI;

use function in_array;

/**
 * Application.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class Application extends Console\Application
{
    private const FAILSAFE_COMMANDS = [
        'cc',
        'clear-cache',
        'help',
        'init',
        'list',
    ];

    private Console\Input\InputInterface $input;
    private ?SymfonyDI\ContainerInterface $failsafeContainer = null;
    private ?SymfonyDI\ContainerInterface $container = null;
    private bool $commandsAdded = false;

    public function __construct()
    {
        parent::__construct('Frontend Asset Handler', $this->determineCurrentVersion() ?? 'UNKNOWN');
    }

    protected function getDefaultInputDefinition(): Console\Input\InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();
        $inputDefinition->addOption(
            new Console\Input\InputOption(
                'config',
                'c',
                Console\Input\InputOption::VALUE_REQUIRED,
                'Path to the assets configuration file',
                FilesystemHelper::resolveRelativePath('assets.json'),
            ),
        );

        return $inputDefinition;
    }

    public function doRun(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $this->input = $input;

        return parent::doRun($input, $output);
    }

    protected function doRunCommand(
        Console\Command\Command $command,
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
    ): int {
        // Show introduction header for all asset commands
        if ($command instanceof Command\BaseAssetsCommand || $command instanceof Command\InitConfigCommand) {
            $io = new Console\Style\SymfonyStyle($input, $output);
            $io->section(sprintf('Running <comment>%s</comment>', $this->getLongVersion()));
        }

        return parent::doRunCommand($command, $input, $output);
    }

    public function find(string $name): Console\Command\Command
    {
        // If only the available commands should be listed, we can safely add
        // them from the failsafe container
        if ('list' === $name) {
            $this->addAssetCommands(true);
        }

        // Add only the clear-cache command if it's requested. This way, we can
        // avoid issues with a potentially outdated container.
        if (in_array($name, ['clear-cache', 'cc'], true)) {
            $this->addClearCacheCommand();
        }

        try {
            return parent::find($name);
        } catch (Console\Exception\CommandNotFoundException) {
            // In case a command was requested that is not yet added to the list of
            // available commands, we add them now. If the requested command is
            // considered failsafe or the help of a command is requested, we use
            // the failsafe container to add commands. This avoids booting up the
            // whole container without having a valid config file, which would lead
            // to exceptions when initializing the requested command.
            $this->addAssetCommands($this->isHelpRequested() || in_array($name, self::FAILSAFE_COMMANDS, true));
        }

        // Try to find the command again after we've added all available commands
        return parent::find($name);
    }

    private function addAssetCommands(bool $failsafe = false): void
    {
        // Early return if commands were already added
        if ($this->commandsAdded) {
            return;
        }

        if ($failsafe) {
            $container = $this->createFailsafeContainer();
        } else {
            $container = $this->createContainer();
        }

        $this->addClearCacheCommand();
        $this->addCommands([
            $container->get(Command\ConfigAssetsCommand::class),
            $container->get(Command\FetchAssetsCommand::class),
            $container->get(Command\InitConfigCommand::class),
            $container->get(Command\InspectAssetsCommand::class),
        ]);

        $this->commandsAdded = true;
    }

    private function addClearCacheCommand(): void
    {
        $this->add($this->createFailsafeContainer()->get(Command\ClearCacheCommand::class));
    }

    private function isHelpRequested(): bool
    {
        return $this->input->hasParameterOption(['--help', '-h'], true);
    }

    private function createContainer(): SymfonyDI\ContainerInterface
    {
        if (null === $this->container) {
            $temporaryInput = clone $this->input;
            $temporaryInput->bind($this->getDefinition());
            $containerFactory = new DependencyInjection\ContainerFactory($temporaryInput->getOption('config'));
            $this->container = $containerFactory->get();
        }

        return $this->container;
    }

    private function createFailsafeContainer(): SymfonyDI\ContainerInterface
    {
        if (null === $this->failsafeContainer) {
            $containerFactory = new DependencyInjection\ContainerFactory();
            $this->failsafeContainer = $containerFactory->get();
        }

        return $this->failsafeContainer;
    }

    public function getLongVersion(): string
    {
        return parent::getLongVersion().' <bg=blue;fg=white>#StandWith</><bg=yellow;fg=black>Ukraine</>';
    }

    private function determineCurrentVersion(): ?string
    {
        try {
            return InstalledVersions::getPrettyVersion('cpsit/frontend-asset-handler');
        } catch (OutOfBoundsException) {
            return null;
        }
    }
}
