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

namespace CPSIT\FrontendAssetHandler\Tests\Unit;

use const PHP_EOL;

use Symfony\Component\Console;

/**
 * InteractiveConsoleInputTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait InteractiveConsoleInputTrait
{
    /**
     * @param list<string> $inputs
     */
    private static function setInputs(array $inputs, Console\Input\StreamableInputInterface $input): void
    {
        $input->setStream(self::createStream($inputs));
    }

    /**
     * This code snippet is originally taken from Symfony's source code.
     * See license at https://github.com/symfony/symfony/blob/6.1/LICENSE.
     *
     * (c) Fabien Potencier <fabien@symfony.com>
     *
     * @param list<string> $inputs
     *
     * @return resource
     *
     * @see https://github.com/symfony/symfony/blob/6.1/src/Symfony/Component/Console/Tester/TesterTrait.php
     */
    private static function createStream(array $inputs)
    {
        $stream = fopen('php://memory', 'r+');

        self::assertIsResource($stream);

        foreach ($inputs as $input) {
            fwrite($stream, $input.PHP_EOL);
        }

        rewind($stream);

        return $stream;
    }
}
