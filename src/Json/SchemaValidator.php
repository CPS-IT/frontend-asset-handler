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

namespace CPSIT\FrontendAssetHandler\Json;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use Ergebnis\Json;
use Ergebnis\Json\Pointer;
use Ergebnis\Json\SchemaValidator as ErgebnisSchemaValidator;
use JsonException;
use Symfony\Component\Filesystem;

/**
 * SchemaValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SchemaValidator
{
    private const JSON_SCHEMA_FILE = '../../resources/configuration.schema.json';

    private readonly string $schemaFile;
    private ErgebnisSchemaValidator\ValidationResult $lastValidationErrors;

    public function __construct(
        private readonly Filesystem\Filesystem $filesystem,
        private readonly ErgebnisSchemaValidator\SchemaValidator $validator,
    ) {
        $this->schemaFile = $this->resolveSchemaFile();
        $this->lastValidationErrors = ErgebnisSchemaValidator\ValidationResult::create();
    }

    /**
     * @throws JsonException
     */
    public function validate(Config\Config $config): bool
    {
        $json = Json\Json::fromString(json_encode($config, JSON_THROW_ON_ERROR));
        $schema = Json\Json::fromFile($this->schemaFile);
        $jsonPointer = Pointer\JsonPointer::document();

        $this->lastValidationErrors = $this->validator->validate($json, $schema, $jsonPointer);

        return $this->lastValidationErrors->isValid();
    }

    public function getLastValidationErrors(): ErgebnisSchemaValidator\ValidationResult
    {
        return $this->lastValidationErrors;
    }

    private function resolveSchemaFile(): string
    {
        $schemaFile = Filesystem\Path::join(__DIR__, self::JSON_SCHEMA_FILE);

        if (!$this->filesystem->exists($schemaFile)) {
            // @codeCoverageIgnoreStart
            throw Exception\FilesystemFailureException::forMissingPath($schemaFile);
            // @codeCoverageIgnoreEnd
        }

        return $schemaFile;
    }
}
