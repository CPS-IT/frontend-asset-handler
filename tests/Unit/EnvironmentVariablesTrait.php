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

namespace CPSIT\FrontendAssetHandler\Tests\Unit;

use function getenv;

/**
 * EnvironmentVariablesTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait EnvironmentVariablesTrait
{
    /**
     * @var array<string, string>
     */
    private array $backedUpEnvironmentVariables = [];

    /**
     * @var array<string, string>
     */
    private array $additionalEnvironmentVariables = [];

    private function backUpEnvironmentVariables(): void
    {
        $this->backedUpEnvironmentVariables = getenv();
    }

    private function restoreEnvironmentVariables(): void
    {
        foreach ($this->additionalEnvironmentVariables as $key => $value) {
            $this->unsetEnvironmentVariable($key);
        }

        foreach ($this->backedUpEnvironmentVariables as $key => $value) {
            $this->setEnvironmentVariable($key, $value);
        }
    }

    private function setEnvironmentVariable(string $name, mixed $value): void
    {
        $this->additionalEnvironmentVariables[$name] = $value;

        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
    }

    private function unsetEnvironmentVariable(string $name): void
    {
        unset($this->additionalEnvironmentVariables[$name]);

        putenv($name);
        unset($_ENV[$name]);
    }
}
