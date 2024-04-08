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

use function array_key_exists;
use function explode;
use function is_array;

/**
 * ArrayHelper.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final class ArrayHelper
{
    /**
     * @param array<int, array<string, mixed>> $array
     * @param list<string>                     $keys
     *
     * @return array<int, array<string, mixed>>
     */
    public static function filterSubArraysByKeys(array $array, array $keys): array
    {
        if ([] === $keys) {
            return $array;
        }

        $filteredArray = [];
        $keyArray = array_flip($keys);

        foreach ($array as $index => $subArray) {
            $filteredSubArray = array_intersect_key($subArray, $keyArray);

            if ([] !== $filteredSubArray) {
                $filteredArray[$index] = $filteredSubArray;
            }
        }

        return $filteredArray;
    }

    /**
     * @param array<mixed> $array
     *
     * @throws Exception\MissingConfigurationException
     */
    public static function getArrayValueByPath(array $array, string $path): mixed
    {
        $value = $array;

        // Assure required structure in array
        $currentPathSegment = [];
        foreach (explode('/', $path) as $segment) {
            $currentPathSegment[] = $segment;
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                throw Exception\MissingConfigurationException::forKey(implode('/', $currentPathSegment));
            }
        }

        return $value;
    }

    /**
     * @param array<mixed> $array
     *
     * @return array<mixed>
     */
    public static function setArrayValueByPath(array $array, string $path, mixed $value): array
    {
        $node = &$array;

        // Assure required structure in array
        foreach (str_getcsv($path, '/') as $segment) {
            if (!isset($node[$segment])) {
                $node[$segment] = [];
            }

            $node = &$node[$segment];
        }

        // Apply value to array
        $node = $value;

        return $array;
    }

    /**
     * @param array<mixed> $array
     *
     * @return array<mixed>
     */
    public static function unsetArrayValueByPath(array $array, string $path): array
    {
        $parentNode = null;
        $node = &$array;
        $pathSegments = array_filter(explode('/', $path), fn (string $segment): bool => '' !== trim($segment));

        // Early return if array is empty or path is empty
        if ([] === $array || [] === $pathSegments) {
            return $array;
        }

        foreach ($pathSegments as $segment) {
            // Early return if array does not contain current path segment
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $array;
            }

            $parentNode = &$node;
            $node = &$node[$segment];
        }

        // Apply value to array
        if (isset($segment)) {
            unset($parentNode[$segment]);
        }

        return $array;
    }
}
