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

namespace CPSIT\FrontendAssetHandler\Tests\Console\Output\Progress;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function fclose;
use function fseek;
use function stream_get_contents;

/**
 * TrackableProgressTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TrackableProgressTest extends TestCase
{
    private Tests\BufferedConsoleOutput $output;
    private Console\Output\Progress\TrackableProgress $subject;

    protected function setUp(): void
    {
        $this->output = new Tests\BufferedConsoleOutput();
        $this->subject = new Console\Output\Progress\TrackableProgress($this->output, 'Do something... ');
    }

    #[Test]
    public function startStartsProgress(): void
    {
        self::assertFalse($this->subject->isRunning());

        $this->subject->start();

        self::assertTrue($this->subject->isRunning());
        self::assertSame('Do something... ', $this->fetchStreamOutput());
    }

    #[Test]
    public function writeDoesNothingIfProgressIsNotRunning(): void
    {
        $this->subject->write('foo');

        self::assertSame('', $this->fetchStreamOutput());
    }

    #[Test]
    public function writeWritesToProgressIfProgressIsRunning(): void
    {
        $this->subject->start();
        $this->subject->write('foo');

        self::assertStringContainsString('Do something... foo', $this->fetchStreamOutput());
    }

    #[Test]
    public function writelnDoesNothingIfProgressIsNotRunning(): void
    {
        $this->subject->writeln('foo');

        self::assertSame('', $this->fetchStreamOutput());
    }

    #[Test]
    public function writelnWritesToProgressIfProgressIsRunning(): void
    {
        $this->subject->start();
        $this->subject->writeln('foo');

        self::assertStringContainsString('Do something... foo'.PHP_EOL, $this->fetchStreamOutput());
    }

    #[Test]
    public function finishDoesNothingIfProgressIsNotRunning(): void
    {
        $this->subject->finish();

        self::assertSame('', $this->fetchStreamOutput());
    }

    #[Test]
    public function finishFinishesProgressIfProgressIsRunning(): void
    {
        $this->subject->start();
        $this->subject->finish();

        self::assertStringContainsString('Do something... Done', $this->fetchStreamOutput());
    }

    #[Test]
    public function failDoesNothingIfProgressIsNotRunning(): void
    {
        $this->subject->fail();

        self::assertSame('', $this->fetchStreamOutput());
    }

    #[Test]
    public function failFinishesProgressIfProgressIsRunning(): void
    {
        $this->subject->start();
        $this->subject->fail();

        self::assertStringContainsString('Do something... Failed', $this->fetchStreamOutput());
    }

    private function fetchStreamOutput(): string
    {
        fseek($this->output->getStream(), 0);

        $output = stream_get_contents($this->output->getStream());

        self::assertIsString($output);

        return $output;
    }

    protected function tearDown(): void
    {
        fclose($this->output->getStream());
    }
}
