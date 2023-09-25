<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "fr/frontend-asset-handling".
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

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\DependencyInjection;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Json;
use Ergebnis\Json\Printer;
use Ergebnis\Json\SchemaValidator;
use JsonException;
use Symfony\Component\Console;

use function count;
use function explode;

/**
 * ConfigAssetsCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigAssetsCommand extends BaseAssetsCommand
{
    private const SUCCESSFUL = 0;
    private const ERROR_INVALID_PATH = 1;
    private const ERROR_EMPTY_PATH = 2;
    private const ERROR_CONFLICTING_PARAMETERS = 4;
    private const ERROR_INVALID_CONFIG = 8;

    private const PATH_PREFIX_PATTERN = '#^/?frontend-assets/#';

    private Console\Style\SymfonyStyle $io;

    public function __construct(
        DependencyInjection\Cache\ContainerCache $cache,
        Config\ConfigFacade $configFacade,
        Config\Parser\Parser $configParser,
        private readonly Json\SchemaValidator $validator,
        private readonly Printer\Printer $printer,
    ) {
        parent::__construct('config', $cache, $configFacade, $configParser);
    }

    protected function configure(): void
    {
        $this->setDescription('Read, write or validate asset definitions from asset configuration file.');

        $this->addArgument(
            'path',
            Console\Input\InputArgument::OPTIONAL,
            'Configuration path to be read or written',
        );
        $this->addArgument(
            'newValue',
            Console\Input\InputArgument::OPTIONAL,
            'New value to be written to given configuration path',
        );
        $this->addOption(
            'unset',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Unset given asset configuration',
        );
        $this->addOption(
            'validate',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Validate given asset configuration',
        );
        $this->addOption(
            'json',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Treat new value as JSON-encoded string',
        );
        $this->addOption(
            'process-values',
            null,
            Console\Input\InputOption::VALUE_NONE,
            'Run value processors when reading or validating asset configuration',
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $this->io = new Console\Style\SymfonyStyle($input, $output);

        $path = trim((string) $input->getArgument('path'), "/ \n\r\t\v\x00");
        $newValue = $input->getArgument('newValue');
        $unset = $input->getOption('unset');
        $validate = $input->getOption('validate');
        $json = $input->getOption('json');
        $processValues = $input->getOption('process-values');

        // Strip "frontend-assets" prefix
        $path = preg_replace(self::PATH_PREFIX_PATTERN, '', $path);

        // @codeCoverageIgnoreStart
        if (null === $path) {
            $this->io->error('The configuration path is invalid.');

            return self::ERROR_INVALID_PATH;
        }
        // @codeCoverageIgnoreEnd

        if ('' === $path && !$validate) {
            $this->io->error('The configuration path must not be empty.');

            return self::ERROR_EMPTY_PATH;
        }
        if (($unset || $validate) && null !== $newValue) {
            $this->io->error('You cannot write or validate and unset a configuration value one at a time.');

            return self::ERROR_CONFLICTING_PARAMETERS;
        }
        if ($unset && $validate) {
            $this->io->error('You cannot write and validate configuration value one at a time.');

            return self::ERROR_CONFLICTING_PARAMETERS;
        }

        // Unset configuration value
        if ($unset) {
            $finalPath = $this->writeConfiguration($path, null);

            $this->io->success(
                sprintf('Configuration at %s was successfully unset.', $this->decoratePath($finalPath))
            );

            return self::SUCCESSFUL;
        }

        // Validate configuration value
        if ($validate) {
            try {
                $this->readConfiguration(processValues: $processValues);
            } catch (Exception\InvalidConfigurationException $exception) {
                $validationResult = $this->configParser->getLastValidationErrors();

                if ($validationResult->isValid()) {
                    throw $exception;
                }

                $this->io->error('Your asset configuration is invalid.');
                $this->io->table(
                    ['Config path', 'Error'],
                    array_map($this->validationErrorToTableRow(...), $validationResult->errors())
                );

                return self::ERROR_INVALID_CONFIG;
            }

            $this->io->success('Your asset configuration is valid.');

            return self::SUCCESSFUL;
        }

        // Read configuration value
        if (null === $newValue) {
            [$finalPath, $value] = $this->readConfiguration($path, $processValues);

            $this->io->writeln([
                sprintf(
                    'Current configuration value of asset definition <comment>%s</comment>:',
                    $this->decoratePath($finalPath)
                ),
                '',
                $this->printer->print(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                '',
            ]);

            return self::SUCCESSFUL;
        }

        // Write configuration value
        if ($json) {
            $jsonValue = \Ergebnis\Json\Json::fromString($newValue);
            $finalPath = $this->writeConfiguration($path, $jsonValue->decoded());

            $this->io->writeln([
                sprintf(
                    '<info>Configuration at <comment>%s</comment> was successfully written:</info>',
                    $this->decoratePath($finalPath)
                ),
                '',
                $this->printer->print($jsonValue->encoded()),
                '',
            ]);
        } else {
            $finalPath = $this->writeConfiguration($path, $newValue);

            $this->io->success(
                sprintf('Configuration at %s was successfully set to "%s".', $this->decoratePath($finalPath), $newValue)
            );
        }

        return self::SUCCESSFUL;
    }

    private function writeConfiguration(string $path, mixed $newValue): string
    {
        $config = $this->loadConfig(processValues: false);
        $assetDefinitions = $config['frontend-assets'];
        $finalPath = $this->buildAndValidatePath($assetDefinitions, $path);

        if (!$this->doWrite($config, $finalPath, $newValue)) {
            throw Exception\FilesystemFailureException::forFailedWriteOperation($finalPath);
        }

        return $finalPath;
    }

    /**
     * @return array{string, mixed}
     *
     * @throws Exception\InvalidConfigurationException
     * @throws Exception\MissingConfigurationException
     * @throws JsonException
     */
    private function readConfiguration(string $path = '', bool $processValues = false): array
    {
        $config = $this->loadConfig(processValues: $processValues);
        $assetDefinitions = $config['frontend-assets'];

        if ('' === $path) {
            return $assetDefinitions;
        }

        $finalPath = $this->buildAndValidatePath($assetDefinitions, $path);
        $value = Helper\ArrayHelper::getArrayValueByPath($assetDefinitions, $finalPath);

        return [$finalPath, $value];
    }

    /**
     * @throws Exception\InvalidConfigurationException
     * @throws JsonException
     */
    private function doWrite(Config\Config $config, string $path, mixed $newValue): bool
    {
        if (null === $newValue) {
            // Unset config
            $config['frontend-assets'] = Helper\ArrayHelper::unsetArrayValueByPath($config['frontend-assets'], $path);
        } else {
            $config['frontend-assets'] = Helper\ArrayHelper::setArrayValueByPath($config['frontend-assets'], $path, $newValue);
        }

        // Re-index array to avoid invalid schema errors
        $config['frontend-assets'] = array_values($config['frontend-assets']);

        // Validate new config
        if (!$this->validator->validate($config)) {
            throw Exception\InvalidConfigurationException::asReported($this->validator->getLastValidationErrors()->errors());
        }

        return $this->configFacade->write($config);
    }

    /**
     * @param array<int, array<string, mixed>> $assetDefinitions
     *
     * @throws Exception\InvalidConfigurationException
     */
    private function buildAndValidatePath(array $assetDefinitions, string $path): string
    {
        $pathSegments = str_getcsv($path, '/');
        $strictPath = is_numeric($pathSegments[0]);

        if (!$strictPath) {
            // Path must not be ambiguous
            if (count($assetDefinitions) > 1) {
                throw Exception\InvalidConfigurationException::forAmbiguousKey($path);
            }
            array_splice($pathSegments, 0, 0, '0');
        }

        return implode('/', $pathSegments);
    }

    private function decoratePath(string $path): string
    {
        $pathSegments = array_map(fn (string $segment): string => sprintf('[%s]', $segment), explode('/', $path));

        return implode('', $pathSegments);
    }

    /**
     * @return array{string, string}
     */
    private function validationErrorToTableRow(SchemaValidator\ValidationError $error): array
    {
        $path = $error->jsonPointer()->toJsonString();
        $decoratedPath = $this->decoratePath(preg_replace(self::PATH_PREFIX_PATTERN, '', $path) ?? $path);

        return [
            sprintf('<comment>%s</comment>', $decoratedPath),
            $error->message()->toString(),
        ];
    }
}
