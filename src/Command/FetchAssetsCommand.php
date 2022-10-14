<?php

declare(strict_types=1);

/*
 * This file is part of the Composer project "cpsit/frontend-asset-handler".
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

namespace CPSIT\FrontendAssetHandler\Command;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Handler;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Strategy;
use Symfony\Component\Console;

use function count;

/**
 * FetchAssetsCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FetchAssetsCommand extends BaseAssetsCommand
{
    private const SUCCESSFUL = 0;
    private const ERRONEOUS = 1;

    private bool $hasWarnings = false;

    private Console\Style\SymfonyStyle $io;

    public function __construct(
        DependencyInjection\Cache\ContainerCache $cache,
        Config\ConfigFacade $configFacade,
        Config\Parser\Parser $configParser,
        private readonly Handler\HandlerFactory $handlerFactory,
        private readonly Asset\Definition\AssetDefinitionFactory $assetDefinitionFactory,
    ) {
        parent::__construct('fetch', $cache, $configFacade, $configParser);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Downloads and extracts Frontend assets from a defined source to a dedicated path in the current project.'
        );

        $this->addArgument(
            'branch',
            Console\Input\InputArgument::OPTIONAL,
            'Optional branch name, will be resolved to an asset environment and overrides default environment derived from current branch',
            Helper\VcsHelper::getCurrentBranch()
        );
        $this->addOption(
            'force',
            'f',
            Console\Input\InputOption::VALUE_NONE,
            'Force fresh download of Frontend assets even if local assets are already up to date.',
        );
        $this->addOption(
            'failsafe',
            's',
            Console\Input\InputOption::VALUE_NONE,
            'Fall back to latest assets if download for the given environment fails.',
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
        $this->handlerFactory->setOutput($output);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $successful = true;
        $branch = $input->getArgument('branch');

        // Handle missing or invalid environments
        if (empty($branch)) {
            throw Exception\UnsupportedEnvironmentException::forMissingVCS();
        }
        if ('' === trim((string) $branch)) {
            throw Exception\UnsupportedEnvironmentException::forInvalidEnvironment($branch);
        }

        // Fetch asset definitions
        $config = $this->loadConfig(['source', 'target', 'environments', 'handler']);
        $assetDefinitions = $config['frontend-assets'];
        $assetCount = is_countable($assetDefinitions) ? count($assetDefinitions) : 0;

        // Decide for strategy
        $strategy = null;
        if ($input->getOption('force')) {
            $strategy = Strategy\Strategy::FetchExisting;
        }

        // Show header
        $this->io->writeln(sprintf('Project branch: <info>%s</info>', $branch));

        // Process assets
        foreach ($assetDefinitions as $key => $assetDefinition) {
            if ($assetCount > 1) {
                $this->io->section(sprintf('Processing of <info>asset definition #%d</info>', $key + 1));
            }

            // Create handler
            $handler = $this->handlerFactory->get($assetDefinition['handler'] ?? 'default');

            // Create source and target
            $source = $this->assetDefinitionFactory->buildSource($assetDefinition, $branch);
            $target = $this->assetDefinitionFactory->buildTarget($assetDefinition);

            // Show environment
            $environment = $source->getEnvironment();
            $this->io->writeln(sprintf('Asset environment: <info>%s</info>', $environment));

            // Show asset definition as JSON
            $this->io->writeln(
                [
                    'Definition:',
                    json_encode(
                        [
                            '<comment>source</comment>' => $source->getConfig(),
                            '<comment>target</comment>' => $target->getConfig(),
                        ],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                    ),
                    '',
                ],
                Console\Output\OutputInterface::VERBOSITY_VERBOSE
            );

            // Perform asset handling
            if (!$this->performAssetHandling($handler, $source, $target, $strategy, $input->getOption('failsafe'))) {
                $successful = false;
            }
        }

        if (!$successful && $assetCount > 1) {
            $this->io->warning(sprintf('Command finished with errors%s.', $this->hasWarnings ? ' and warnings' : ''));

            return self::ERRONEOUS;
        }

        if ($this->hasWarnings) {
            $this->io->warning('Command finished with warnings.');
        }

        return self::SUCCESSFUL;
    }

    private function performAssetHandling(
        Handler\HandlerInterface $handler,
        Asset\Definition\Source $source,
        Asset\Definition\Target $target,
        Strategy\Strategy $strategy = null,
        bool $failsafe = false,
    ): bool {
        try {
            $asset = $handler->handle($source, $target, $strategy);
        } catch (Exception\DownloadFailedException $exception) {
            if (!$failsafe || Asset\Environment\Environment::Latest->value === $source->getEnvironment()) {
                $this->io->error($exception->getMessage());

                return false;
            }

            $this->io->writeln('<comment>Error while fetching assets, falling back to latest assets.</comment>');

            $source['environment'] = Asset\Environment\Environment::Latest->value;

            return $this->performAssetHandling($handler, $source, $target);
        }

        if ($asset instanceof Asset\ExistingAsset) {
            $this->hasWarnings = true;
            $this->io->warning(
                sprintf(
                    'Assets%s are already downloaded. Use -f to re-download them.',
                    null !== $asset->getRevision() ? ' of revision '.$asset->getRevision()->getShort() : ''
                )
            );

            return true;
        }

        if (!($asset instanceof Asset\ProcessedAsset)) {
            $this->io->error('Error while fetching assets: The asset handler was unable to handle this asset source.');

            return false;
        }

        $this->io->success(sprintf('Assets successfully downloaded to %s.', $asset->getProcessedTargetPath()));

        return true;
    }
}
