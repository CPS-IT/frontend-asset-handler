<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2023 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests;

use Symfony\Component\Filesystem;

/**
 * FunctionExecutorTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait FunctionExecutorTrait
{
    private function executeInDirectory(callable $function, ?string $directory = null, bool $cleanUp = true): void
    {
        $filesystem = new Filesystem\Filesystem();
        $cwd = getcwd();

        // Fail if cwd cannot be determined
        if (false === $cwd) {
            self::fail('Unable to determine current working directory.');
        }

        // Go to temporary directory
        $directory ??= $this->createTemporaryDirectory();
        chdir($directory);

        // Execute function
        try {
            $function();
        } finally {
            // Go back to original location
            chdir($cwd);

            // Remove temporary directory
            if ($cleanUp && $filesystem->exists($directory)) {
                $filesystem->remove($directory);
            }
        }
    }

    private function createTemporaryDirectory(): string
    {
        $filesystem = new Filesystem\Filesystem();
        $temporaryDirectory = $filesystem->tempnam(sys_get_temp_dir(), 'asset_handler_test_');
        $filesystem->remove($temporaryDirectory);
        $filesystem->mkdir($temporaryDirectory);

        return $temporaryDirectory;
    }
}
