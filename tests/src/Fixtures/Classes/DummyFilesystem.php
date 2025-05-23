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

namespace CPSIT\FrontendAssetHandler\Tests\Fixtures\Classes;

use Exception;
use SplFileInfo;
use Symfony\Component\Filesystem;
use Traversable;

/**
 * DummyFilesystem.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyFilesystem extends Filesystem\Filesystem
{
    /**
     * @var list<string>
     */
    public array $expectedExceptionStack = [];

    /**
     * @param string|iterable<string> $files
     *
     * @throws Exception
     */
    public function remove($files): void
    {
        if ($this->shouldThrowException('remove')) {
            throw new Exception('Dummy exception from Filesystem', 1628093163);
        }

        parent::remove($files);
    }

    /**
     * @param Traversable<SplFileInfo>|null $iterator
     * @param array<string, bool>           $options
     *
     * @throws Exception
     */
    public function mirror(string $originDir, string $targetDir, ?Traversable $iterator = null, array $options = []): void
    {
        if ($this->shouldThrowException('mirror')) {
            throw new Exception('Dummy exception from Filesystem', 1628093996);
        }

        parent::mirror($originDir, $targetDir, $iterator, $options);
    }

    private function shouldThrowException(string $methodName): bool
    {
        $nextMethod = reset($this->expectedExceptionStack);

        if ($nextMethod === $methodName) {
            array_shift($this->expectedExceptionStack);

            return true;
        }

        return false;
    }
}
