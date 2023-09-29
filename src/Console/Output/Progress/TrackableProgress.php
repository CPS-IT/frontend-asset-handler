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

namespace CPSIT\FrontendAssetHandler\Console\Output\Progress;

use Symfony\Component\Console;

/**
 * TrackableProgress.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TrackableProgress
{
    private ?Console\Output\ConsoleSectionOutput $section = null;

    public function __construct(
        private readonly Console\Output\ConsoleOutputInterface $output,
        private readonly string $text,
    ) {}

    public function start(): void
    {
        if (null === $this->section) {
            $this->section = $this->output->section();
            $this->section->write($this->text);
        }
    }

    /**
     * @param string|list<string> $messages
     */
    public function write(string|array $messages): void
    {
        $this->section?->write($messages);
    }

    /**
     * @param string|list<string> $messages
     */
    public function writeln(string|array $messages): void
    {
        $this->section?->writeln($messages);
    }

    public function finish(): void
    {
        $this->section?->overwrite($this->text.'<info>Done</info>');
        $this->section = null;
    }

    public function fail(): void
    {
        $this->section?->overwrite($this->text.'<error>Failed</error>');
        $this->section = null;
    }

    public function isRunning(): bool
    {
        return null !== $this->section;
    }
}
