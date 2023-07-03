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

namespace CPSIT\FrontendAssetHandler\Helper;

use CPSIT\FrontendAssetHandler\Exception;
use OutOfBoundsException;
use Stringable;

use function is_object;
use function is_string;
use function rawurlencode;

/**
 * StringHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final class StringHelper
{
    private const PLACEHOLDER_REGEX = '/{([\\w-]+)}/';

    /**
     * @see https://gist.github.com/liunian/9338301#gistcomment-3114711
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes < 0) {
            throw new OutOfBoundsException(sprintf('Number of bytes must not be lower than zero, %d given.', $bytes), 1624613494);
        }

        $i = 0;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for (; $bytes > 1024; ++$i) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * @param iterable<string, mixed> $replacePairs
     *
     * @throws Exception\MissingConfigurationException
     */
    public static function interpolate(string $string, iterable $replacePairs): string
    {
        $normalizedPairs = [];

        foreach ($replacePairs as $key => $value) {
            $normalizedPairs['{'.trim($key, '{}').'}'] = $value;
        }

        $result = strtr($string, $normalizedPairs);

        if ([] !== ($matches = self::extractPlaceholders($result))) {
            throw Exception\MissingConfigurationException::forKey(reset($matches));
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public static function extractPlaceholders(string $string): array
    {
        if (false === preg_match_all(self::PLACEHOLDER_REGEX, $string, $matches)) {
            // @codeCoverageIgnoreStart
            throw Exception\UnexpectedValueException::forInvalidString($string);
            // @codeCoverageIgnoreEnd
        }

        return $matches[1];
    }

    public static function urlEncode(mixed $value): mixed
    {
        if (is_string($value)) {
            return rawurlencode($value);
        }

        if (is_object($value) && $value instanceof Stringable) {
            return rawurlencode((string) $value);
        }

        return $value;
    }
}
