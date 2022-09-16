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

namespace CPSIT\FrontendAssetHandler\Config\Parser;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Json;
use CPSIT\FrontendAssetHandler\Value;
use Ergebnis\Json\SchemaValidator;
use JsonException;

/**
 * Parser.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
class Parser
{
    public function __construct(
        protected readonly Value\ValueProcessor $valueProcessor,
        protected readonly Json\SchemaValidator $validator,
    ) {
    }

    /**
     * @throws Exception\InvalidConfigurationException
     * @throws JsonException
     */
    public function parse(ParserInstructions $instructions): Config\Config
    {
        $config = clone $instructions->getConfig();
        $requiredKeys = $instructions->getRequiredKeys();

        // Validate config
        if (!$this->validator->validate($config)) {
            throw Exception\InvalidConfigurationException::asReported($this->validator->getLastValidationErrors()->errors());
        }

        // Filter by required keys
        $config['frontend-assets'] = Helper\ArrayHelper::filterSubArraysByKeys($config['frontend-assets'], $requiredKeys);

        // Process values
        if ($instructions->shouldProcessValues()) {
            $config['frontend-assets'] = $this->valueProcessor->process($config['frontend-assets']);
        }

        return $config;
    }

    public function getLastValidationErrors(): SchemaValidator\ValidationResult
    {
        return $this->validator->getLastValidationErrors();
    }
}
