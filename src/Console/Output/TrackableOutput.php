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

namespace CPSIT\FrontendAssetHandler\Console\Output;

use CPSIT\FrontendAssetHandler\Exception;
use Symfony\Component\Console as SymfonyConsole;

use function fopen;
use function rtrim;

/**
 * TrackableOutput.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TrackableOutput extends SymfonyConsole\Output\ConsoleOutput
{
    public function __construct(
        private readonly SymfonyConsole\Output\OutputInterface $output,
    ) {
        parent::__construct();
    }

    public function startProgress(string $text): Progress\TrackableProgress
    {
        if (!($this->output instanceof SymfonyConsole\Output\ConsoleOutputInterface)) {
            throw Exception\IOException::forUnsupportedOutput($this->output);
        }

        $progress = new Progress\TrackableProgress($this->output, rtrim($text).' ');
        $progress->start();

        return $progress;
    }

    // @codeCoverageIgnoreStart
    public function write($messages, bool $newline = false, int $options = self::OUTPUT_NORMAL): void
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, int $options = self::OUTPUT_NORMAL): void
    {
        $this->output->writeln($messages, $options);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(SymfonyConsole\Formatter\OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): SymfonyConsole\Formatter\OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }

    public function getStream()
    {
        if ($this->output instanceof SymfonyConsole\Output\StreamOutput) {
            return $this->output->getStream();
        }

        $stream = fopen('php://temp', 'w+');

        if (false === $stream) {
            throw Exception\IOException::forMissingOutputStream();
        }

        return $stream;
    }

    public function getOutput(): SymfonyConsole\Output\OutputInterface
    {
        return $this->output;
    }

    public function getErrorOutput(): SymfonyConsole\Output\OutputInterface
    {
        if ($this->output instanceof SymfonyConsole\Output\ConsoleOutputInterface) {
            return $this->output->getErrorOutput();
        }

        return $this->output;
    }

    public function setErrorOutput(SymfonyConsole\Output\OutputInterface $error): void
    {
        if ($this->output instanceof SymfonyConsole\Output\ConsoleOutputInterface) {
            $this->output->setErrorOutput($error);
        }
    }
    // @codeCoverageIgnoreEnd
}
