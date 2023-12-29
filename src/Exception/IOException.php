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

namespace CPSIT\FrontendAssetHandler\Exception;

use RuntimeException;
use Symfony\Component\Console;

use function sprintf;

/**
 * IOException.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class IOException extends RuntimeException
{
    public static function forUnsupportedOutput(Console\Output\OutputInterface $output): self
    {
        return new self(
            sprintf('The output "%s" is not supported.', $output::class),
            1661872012,
        );
    }

    public static function forMissingOutputStream(): self
    {
        return new self('No output stream is available.', 1661873512);
    }

    public static function forUnprocessableOutputStream(): self
    {
        return new self('The output stream cannot be processed.', 1661873639);
    }
}
