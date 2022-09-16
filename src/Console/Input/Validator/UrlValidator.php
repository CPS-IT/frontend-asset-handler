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

namespace CPSIT\FrontendAssetHandler\Console\Input\Validator;

use Webmozart\Assert;

use function filter_var;

/**
 * UrlValidator.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class UrlValidator implements ValidatorInterface
{
    public function validate(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        Assert\Assert::stringNotEmpty($value);

        // Allow placeholders in URLs. Those will be replaced later by string interpolation.
        $normalizedUrl = preg_replace('/{[^}]+}/', 'placeholder', $value);

        Assert\Assert::notFalse(filter_var($normalizedUrl, FILTER_VALIDATE_URL), 'The given URL is invalid.');

        return $value;
    }
}
