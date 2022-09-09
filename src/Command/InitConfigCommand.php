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

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Json;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

use function array_key_last;
use function array_merge;
use function json_decode;
use function sprintf;
use function str_starts_with;
use function strtolower;

/**
 * InitConfigCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InitConfigCommand extends Console\Command\Command
{
    private const SUCCESSFUL = 0;

    private Console\Style\SymfonyStyle $io;

    public function __construct(
        private readonly Config\ConfigFacade $configFacade,
        private readonly Config\Parser\Parser $configParser,
        private readonly Json\SchemaValidator $validator,
        private readonly DependencyInjection\ServiceLocator $handlers,
        private readonly DependencyInjection\ServiceLocator $processors,
        private readonly DependencyInjection\ServiceLocator $providers,
        private readonly DependencyInjection\ServiceLocator $vcsProviders,
    ) {
        parent::__construct('init');
    }

    protected function configure(): void
    {
        $this->setDescription('Initialize a new configuration file to handle Frontend assets');

        $this->addOption(
            'source-type',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Type of the asset source, resolves to a supported asset provider',
        );
        $this->addOption(
            'source-url',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'URL to locate the asset source files, can contain placeholders in the form {<config key>}',
        );
        $this->addOption(
            'source-revision-url',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'URL to locate the revision of asset source files, can contain placeholders in the form {<config key>}',
        );
        $this->addOption(
            'source-config-extra',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Additional configuration for the asset source definition, should be a JSON-encoded string',
        );

        $this->addOption(
            'target-type',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Type of the asset target, resolves to a supported asset processor',
        );
        $this->addOption(
            'target-path',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Path where to extract fetched assets, can be either absolute or relative to the config file',
        );
        $this->addOption(
            'target-revision-file',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Filename of the asset target\'s revision file',
            Asset\Definition\Target::DEFAULT_REVISION_FILE,
        );
        $this->addOption(
            'target-config-extra',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Additional configuration for the asset target definition, should be a JSON-encoded string',
        );

        $this->addOption(
            'handler-type',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Type of the asset handler, resolves to a supported asset handler',
        );

        $this->addOption(
            'vcs-type',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Type of the asset\'s VCS, resolves to a supported VCS provider',
        );
        $this->addOption(
            'vcs-config-extra',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'Additional configuration for the asset VCS definition, should be a JSON-encoded string',
        );

        $this->addOption(
            'definition-id',
            null,
            Console\Input\InputOption::VALUE_REQUIRED,
            'ID of the asset definition to be added to the asset configuration file',
            0,
        );
    }

    protected function initialize(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);
    }

    protected function interact(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): void
    {
        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $output->writeln([
            'Welcome to the Frontend Asset Handler!',
            'You can use the following command to initialize a new asset configuration for your Frontend assets.',
            'Follow the guide and answer all relevant questions to get started.',
        ]);

        $this->selectConfigFileAndDefinitionId($input, $output);

        $this->io->title('Source');

        $question = $this->createChoiceQuestion(
            'Type',
            $this->providers->getProvidedServices(),
            $input->getOption('source-type'),
        );
        $input->setOption('source-type', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('URL', $input->getOption('source-url'));
        $question->setValidator(Validators\UrlValidator::validate(...));
        $input->setOption('source-url', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Revision URL', $input->getOption('source-revision-url'));
        $question->setValidator(Validators\UrlValidator::validate(...));
        $input->setOption('source-revision-url', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Additional config', $input->getOption('source-config-extra'));
        $question->setValidator(Validators\JsonValidator::validate(...));
        $input->setOption('source-config-extra', $helper->ask($input, $output, $question));

        $this->io->newLine();
        $this->io->title('Target');

        $question = $this->createChoiceQuestion(
            'Type',
            $this->processors->getProvidedServices(),
            $input->getOption('target-type'),
        );
        $input->setOption('target-type', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Path', $input->getOption('target-path'));
        $question->setValidator(Validators\NotEmptyValidator::validate(...));
        $input->setOption('target-path', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Revision file', $input->getOption('target-revision-file'));
        $question->setValidator(Validators\NotEmptyValidator::validate(...));
        $input->setOption('target-revision-file', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Additional config', $input->getOption('target-config-extra'));
        $question->setValidator(Validators\JsonValidator::validate(...));
        $input->setOption('target-config-extra', $helper->ask($input, $output, $question));

        $this->io->newLine();
        $this->io->title('Handler');

        $question = $this->createChoiceQuestion(
            'Handler',
            $this->handlers->getProvidedServices(),
            $input->getOption('handler-type'),
        );
        $input->setOption('handler-type', $helper->ask($input, $output, $question));

        $this->io->newLine();
        $this->io->title('VCS');

        $continue = true;

        if (null === $input->getOption('vcs-type')) {
            $output->writeln('The following VCS configuration is optional.');
            $question = $this->createQuestion('Add VCS configuration?', 'Y', 'n');
            $continue = str_starts_with(strtolower((string) $helper->ask($input, $output, $question)), 'y');
        }

        if (!$continue) {
            return;
        }

        $question = $this->createChoiceQuestion(
            'Type',
            $this->vcsProviders->getProvidedServices(),
            $input->getOption('vcs-type'),
        );
        $input->setOption('vcs-type', $helper->ask($input, $output, $question));

        $question = $this->createQuestion('Additional config', $input->getOption('vcs-config-extra'));
        $question->setValidator(Validators\JsonValidator::validate(...));
        $input->setOption('vcs-config-extra', $helper->ask($input, $output, $question));
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        if (!$input->isInteractive()) {
            throw new Console\Exception\RuntimeException('This console command can only be run in interactive mode.', 1662395429);
        }

        // Resolve config file
        $configFile = Helper\FilesystemHelper::resolveRelativePath($input->getOption('config'), true);
        $definitionId = $input->getOption('definition-id');

        // Create config
        $config = $this->loadConfig($configFile) ?? new Config\Config([], $configFile);

        // Build source
        $sourceConfigExtra = $input->getOption('source-config-extra');
        $source = new Asset\Definition\Source(
            array_merge(
                [
                    'type' => $input->getOption('source-type'),
                    'url' => $input->getOption('source-url'),
                    'revision-url' => $input->getOption('source-revision-url'),
                ],
                null !== $sourceConfigExtra ? json_decode((string) $sourceConfigExtra, true) : [],
            ),
        );
        $config['frontend-assets'][$definitionId]['source'] = $source->getConfig();

        // Build target
        $targetConfigExtra = $input->getOption('target-config-extra');
        $target = new Asset\Definition\Target(
            array_merge(
                [
                    'type' => $input->getOption('target-type'),
                    'path' => $input->getOption('target-path'),
                    'revision-file' => $input->getOption('target-revision-file'),
                ],
                null !== $targetConfigExtra ? json_decode((string) $targetConfigExtra, true) : [],
            ),
        );
        $config['frontend-assets'][$definitionId]['target'] = $target->getConfig();

        // Build handler
        if (null !== $input->getOption('handler-type')) {
            $config['frontend-assets'][$definitionId]['handler'] = $input->getOption('handler-type');
        }

        // Build VCS
        if ($input->getOption('vcs-type')) {
            $vcsConfigExtra = $input->getOption('vcs-config-extra');
            $vcs = new Asset\Definition\Vcs(
                array_merge(
                    [
                        'type' => $input->getOption('vcs-type'),
                    ],
                    null !== $vcsConfigExtra ? json_decode((string) $vcsConfigExtra, true) : [],
                ),
            );
            $config['frontend-assets'][$definitionId]['vcs'] = $vcs->getConfig();
        }

        // Validate config
        if (!$this->validator->validate($config)) {
            throw Exception\InvalidConfigurationException::asReported($this->validator->getLastValidationErrors()->errors());
        }

        // Write config
        $this->configFacade->write($config);

        $this->io->newLine();
        $this->io->success(
            sprintf('Asset configuration was successfully written to %s', $config->getFilePath()),
        );

        return self::SUCCESSFUL;
    }

    private function selectConfigFileAndDefinitionId(
        Console\Input\InputInterface $input,
        Console\Output\OutputInterface $output,
    ): void {
        /** @var Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');

        // Load config file
        $configFile = $input->getOption('config');
        $config = $this->loadConfig($configFile);

        // Early return if given config file does not exist yet
        if (null === $config) {
            $input->setOption('definition-id', 0);

            return;
        }

        $upperDefinitionId = (int) array_key_last($config['frontend-assets']);

        // Early return if given definition id is valid
        if ((int) $input->getOption('definition-id') > $upperDefinitionId) {
            $input->setOption('definition-id', $upperDefinitionId + 1);

            return;
        }

        $output->writeln([
            '',
            sprintf('You have configured the file <info>%s</info> for your Frontend assets.', $configFile),
            sprintf('A file with the name <info>%s</info> already exists.', $configFile),
            'You can <comment>add a new asset definition</comment> to the existing config file or <comment>create a new config file</comment>.',
            '',
        ]);

        $question = $this->createQuestion(
            sprintf('Add a new asset definition to %s?', $configFile),
            'Y',
            'n',
        );
        $shouldExtendConfig = str_starts_with(strtolower((string) $helper->ask($input, $output, $question)), 'y');

        // Early return if existing config file should be extended
        if ($shouldExtendConfig) {
            $input->setOption('definition-id', $upperDefinitionId + 1);

            $output->writeln([
                sprintf('Alright, the config file <info>%s</info> will be extended by a new asset definition.', $configFile),
            ]);

            return;
        }

        $question = new Console\Question\Question('<info>Path to the new config file</info>: ');

        $input->setOption('config', $helper->ask($input, $output, $question));
        $input->setOption('definition-id', 0);

        $output->writeln([
            sprintf('Alright, the config file <info>%s</info> will be used for the new asset definition.', $input->getOption('config')),
        ]);
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

    private function createQuestion(string $label, mixed $default = null, string $alternative = null): Console\Question\Question
    {
        $label = $this->decorateQuestionLabel($label, $default, $alternative);

        return new Console\Question\Question($label, $default);
    }

    /**
     * @param array<string, string> $choices
     */
    private function createChoiceQuestion(string $label, array $choices, mixed $default = null): Console\Question\ChoiceQuestion
    {
        $label = $this->decorateQuestionLabel($label, $default);

        return new Console\Question\ChoiceQuestion($label, $choices, $default);
    }

    private function decorateQuestionLabel(string $label, mixed $default, string $alternative = null): string
    {
        $label = sprintf('▶ <info>%s</info>', $label);

        if (null !== $default) {
            $label .= ' ['.$default.($alternative ? '/'.$alternative : '').']';
        }

        return $label.': ';
    }
}
