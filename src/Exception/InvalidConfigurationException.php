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

namespace CPSIT\FrontendAssetHandler\Exception;

use Ergebnis\Json\SchemaValidator;
use Exception;

use function sprintf;

/**
 * InvalidConfigurationException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InvalidConfigurationException extends Exception
{
    public static function forAmbiguousKey(string $key): self
    {
        return new self(sprintf('The given key "%s" is ambiguous. Please provide an explicit configuration key.', $key), 1630945791);
    }

    /**
     * @param list<SchemaValidator\ValidationError> $errors
     */
    public static function asReported(array $errors): self
    {
        $separator = PHP_EOL.'  * ';
        $errorMessages = array_map(
            fn (SchemaValidator\ValidationError $error): string => sprintf(
                '[%s]: %s',
                $error->jsonPointer()->toString(),
                $error->message()->toString(),
            ),
            $errors,
        );

        return new self(
            sprintf('The configuration is invalid: %s%s', $separator, implode($separator, $errorMessages)),
            1643113965
        );
    }
}
