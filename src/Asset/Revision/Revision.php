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

namespace CPSIT\FrontendAssetHandler\Asset\Revision;

use CPSIT\FrontendAssetHandler\Exception;
use Stringable;

use function strlen;

/**
 * Revision.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class Revision implements Stringable
{
    private const SHORT_LENGTH = 7;

    public function __construct(
        private readonly string $revision,
    ) {
        $this->validate();
    }

    public function get(): string
    {
        return $this->revision;
    }

    public function getShort(): string
    {
        return substr($this->revision, 0, self::SHORT_LENGTH);
    }

    public function equals(Revision $other): bool
    {
        return $this->revision === $other->get();
    }

    public function __toString(): string
    {
        return $this->revision;
    }

    private function validate(): void
    {
        if (strlen($this->revision) < self::SHORT_LENGTH) {
            throw Exception\InvalidRevisionException::create($this->revision);
        }
    }
}
