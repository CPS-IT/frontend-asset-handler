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

namespace CPSIT\FrontendAssetHandler\Config\Initialization;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use OutOfBoundsException;
use Symfony\Component\Console;

use function array_key_exists;
use function is_string;
use function sprintf;

/**
 * InitializationRequest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class InitializationRequest
{
    private ?Config\Config $config = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private string $configFile,
        private array $options = [],
        private readonly ?Console\Input\InputInterface $input = null,
    ) {}

    /**
     * @throws Exception\MissingConfigurationException
     */
    public static function fromCommandInput(Console\Input\InputInterface $input): self
    {
        $configFile = $input->getOption('config');

        if (!is_string($configFile)) {
            throw Exception\MissingConfigurationException::create();
        }

        return new self($configFile, $input->getOptions(), $input);
    }

    public function getConfig(): Config\Config
    {
        if (null === $this->config) {
            $this->config = new Config\Config([], $this->configFile);
        }

        return $this->config;
    }

    public function setConfig(Config\Config $config): self
    {
        $this->config = $config;
        $this->configFile = $config->getFilePath();

        return $this;
    }

    public function getConfigFile(): string
    {
        return $this->configFile;
    }

    public function setConfigFile(string $configFile): self
    {
        $this->configFile = $configFile;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name): mixed
    {
        if (!array_key_exists($name, $this->options)) {
            throw new OutOfBoundsException(sprintf('The initialization option "%s" does not exist.', $name), 1663086743);
        }

        return $this->options[$name];
    }

    public function setOption(string $name, mixed $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function getInput(): ?Console\Input\InputInterface
    {
        return $this->input;
    }
}
