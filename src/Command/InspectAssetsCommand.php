<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
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
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Vcs;
use Symfony\Component\Console;

use function array_pop;
use function count;
use function sprintf;

/**
 * InspectAssetsCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InspectAssetsCommand extends BaseAssetsCommand
{
    private const SUCCESSFUL = 0;

    private const DIFF_UP_TO_DATE = '<info>(✓)</info>';
    private const DIFF_NEEDS_UPDATE = '<error>(↓)</error>';
    private const DIFF_UNKNOWN = '<comment>(?)</comment>';

    private Console\Style\SymfonyStyle $io;

    public function __construct(
        DependencyInjection\Cache\ContainerCache $cache,
        Config\ConfigFacade $configFacade,
        Config\Parser\Parser $configParser,
        private readonly Vcs\VcsProviderFactory $vcsProviderFactory,
        private readonly Asset\Revision\RevisionProvider $revisionProvider,
        private readonly Asset\Definition\AssetDefinitionFactory $assetDefinitionFactory,
        private readonly Provider\ProviderFactory $providerFactory,
        private readonly Processor\ProcessorFactory $processorFactory,
    ) {
        parent::__construct('inspect', $cache, $configFacade, $configParser);
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Inspects frontend assets for the requested branch, including active deployments'
        );

        $this->setHelp(implode(PHP_EOL, [
            'Revision diff legend:',
            '',
            sprintf('<error>%s Needs update</error>', self::DIFF_NEEDS_UPDATE),
            sprintf('<info>%s Up-to-date</info>', self::DIFF_UP_TO_DATE),
            sprintf('<comment>%s Unknown</comment>', self::DIFF_UNKNOWN),
        ]));

        $this->addArgument(
            'branch',
            Console\Input\InputArgument::OPTIONAL,
            'Optional branch name, will be resolved to an asset environment and overrides default environment derived from current branch',
            Helper\VcsHelper::getCurrentBranch()
        );
        $this->addOption(
            'wait-for-deployments',
            'w',
            Console\Input\InputOption::VALUE_NONE,
            'Waits until active deployments for the requested branch are finished'
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);

        $branch = $input->getArgument('branch');
        $wait = $input->getOption('wait-for-deployments');

        // Handle missing or invalid environments
        /* @phpstan-ignore-next-line */
        if (null === $branch) {
            throw Exception\UnsupportedEnvironmentException::forMissingVCS();
        }
        if ('' === trim($branch)) {
            throw Exception\UnsupportedEnvironmentException::forInvalidEnvironment($branch);
        }

        // Fetch asset definitions
        $config = $this->loadConfig(['vcs', 'source', 'target', 'environments']);
        $assetDefinitions = $config['frontend-assets'];
        $assetCount = is_countable($assetDefinitions) ? count($assetDefinitions) : 0;

        // Show project branch
        $this->io->writeln(sprintf('Project branch: <info>%s</info>', $branch));

        // Check for active deployments
        foreach ($assetDefinitions as $key => $assetDefinition) {
            if ($assetCount > 1) {
                $this->io->section(sprintf('Inspecting <info>asset definition #%d</info>', $key + 1));
            }

            // Create source, target and VCS
            $source = $this->assetDefinitionFactory->buildSource($assetDefinition, $branch);
            $target = $this->assetDefinitionFactory->buildTarget($assetDefinition);
            $vcs = $this->assetDefinitionFactory->buildVcs($assetDefinition, $branch);

            // Show asset definition as JSON
            $this->io->writeln(
                [
                    '<info>Definition:</info>',
                    json_encode(
                        [
                            '<comment>source</comment>' => $source->getConfig(),
                            '<comment>target</comment>' => $target->getConfig(),
                            '<comment>vcs</comment>' => $vcs?->getConfig(),
                        ],
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                    ),
                    '',
                ],
                Console\Output\OutputInterface::VERBOSITY_VERBOSE
            );

            $this->describeAsset($source, $target, $vcs);

            if (!$wait || null === $vcs) {
                continue;
            }

            $vcsProvider = $this->vcsProviderFactory->get($vcs->getType(), $vcs);

            // Continue loop if VCS provider is not deployable
            if (!($vcsProvider instanceof Vcs\DeployableVcsProviderInterface)) {
                continue;
            }

            while ([] !== $vcsProvider->getActiveDeployments()) {
                $this->io->writeln('Waiting for the Frontend assets to be deployed ...');
                sleep(10);
            }
        }

        return self::SUCCESSFUL;
    }

    private function describeAsset(
        Asset\Definition\Source $source,
        Asset\Definition\Target $target,
        Asset\Definition\Vcs $vcs = null,
    ): void {
        $definitionList = [
            ['Asset environment' => $source->getEnvironment()],
            new Console\Helper\TableSeparator(),
        ];

        // Fetch source and target revision
        $sourceRevision = $this->revisionProvider->getRevision($source);
        $targetRevision = $this->revisionProvider->getRevision($target);

        // Define revision diff symbol
        $unknown = '<comment>Unknown</comment>';
        $revisionDiffSymbol = $this->getRevisionDiffSymbol($sourceRevision, $targetRevision);

        // Instantiate provider and processor to access asset URLs
        $provider = $this->providerFactory->get($source->getType());
        $processor = $this->processorFactory->get($target->getType());
        $vcsProvider = null;

        // Show VCS revision and URL
        if (null !== $vcs) {
            $vcsProvider = $this->vcsProviderFactory->get($vcs->getType(), $vcs);
            $vcsRevision = $vcsProvider->getLatestRevision();

            $definitionList[] = ['VCS revision' => $vcsRevision ?? $unknown];
            $definitionList[] = ['VCS url' => $vcsProvider->getSourceUrl()];
            $definitionList[] = new Console\Helper\TableSeparator();
        }

        // Show revisions and asset URLs
        $definitionList[] = ['Source revision' => $sourceRevision ?? $unknown];
        $definitionList[] = ['Source url' => $provider->getAssetUrl($source)];
        $definitionList[] = new Console\Helper\TableSeparator();
        $definitionList[] = ['Target revision' => sprintf('%s %s', $targetRevision ?? $unknown, $revisionDiffSymbol)];
        $definitionList[] = ['Target path' => $processor->getAssetPath(new Asset\Asset($source, $target))];

        // Show definition list
        $this->io->definitionList(...$definitionList);

        // Show active deployments
        if ($vcsProvider instanceof Vcs\DeployableVcsProviderInterface
            && [] !== ($deployments = $vcsProvider->getActiveDeployments())
        ) {
            $this->io->writeln('Active deployments:');
            $this->io->definitionList(...$this->decorateDeployments($deployments));
        }
    }

    private function getRevisionDiffSymbol(
        ?Asset\Revision\Revision $sourceRevision,
        ?Asset\Revision\Revision $targetRevision,
    ): string {
        if (null === $sourceRevision || null === $targetRevision) {
            return self::DIFF_UNKNOWN;
        }

        if ($sourceRevision->equals($targetRevision)) {
            return self::DIFF_UP_TO_DATE;
        }

        return self::DIFF_NEEDS_UPDATE;
    }

    /**
     * @param list<Vcs\Dto\Deployment> $deployments
     *
     * @return list<array<string, string>|Console\Helper\TableSeparator>
     */
    private function decorateDeployments(array $deployments): array
    {
        $decoratedDeployments = [];

        foreach ($deployments as $deployment) {
            $decoratedDeployments[] = ['Revision' => $deployment->getRevision()->get()];
            $decoratedDeployments[] = ['Deployment url' => (string) $deployment->getUri()];
            $decoratedDeployments[] = new Console\Helper\TableSeparator();
        }

        array_pop($decoratedDeployments);

        return $decoratedDeployments;
    }
}
