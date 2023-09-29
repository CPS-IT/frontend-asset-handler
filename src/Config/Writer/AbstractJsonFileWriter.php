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

namespace CPSIT\FrontendAssetHandler\Config\Writer;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use Ergebnis\Json;
use Ergebnis\Json\Normalizer;
use JsonException;

use function is_array;

/**
 * AbstractJsonFileWriter.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
abstract class AbstractJsonFileWriter implements ConfigWriterInterface
{
    public function __construct(
        protected readonly Normalizer\Format\Formatter $formatter,
    ) {}

    /**
     * @return array{Json\Json, Normalizer\Format\Format}
     *
     * @throws JsonException
     */
    protected function replaceInOriginalFileAtGivenPath(Config\Config $config, string $path): array
    {
        $filePath = $config->getFilePath();
        $json = Helper\FilesystemHelper::parseJsonFileContents($filePath);
        $jsonArray = json_decode($json->encoded(), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($jsonArray)) {
            throw Exception\UnprocessableConfigFileException::create($filePath);
        }

        $mergedJson = Helper\ArrayHelper::setArrayValueByPath(
            $jsonArray,
            $path,
            Helper\ArrayHelper::getArrayValueByPath($config->asArray(), $path),
        );

        return [
            Json\Json::fromString(json_encode($mergedJson, JSON_THROW_ON_ERROR)),
            Normalizer\Format\Format::fromJson($json),
        ];
    }

    protected function doWrite(Json\Json $json, string $filePath, Normalizer\Format\Format $format): bool
    {
        $formatted = $this->formatter->format($json, $format);

        return (bool) file_put_contents($filePath, $formatted->encoded());
    }
}
