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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Console\Output;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console as SymfonyConsole;

/**
 * TrackableOutputTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TrackableOutputTest extends TestCase
{
    private Tests\Unit\BufferedConsoleOutput $output;
    private Console\Output\TrackableOutput $subject;

    protected function setUp(): void
    {
        $this->output = new Tests\Unit\BufferedConsoleOutput();
        $this->subject = new Console\Output\TrackableOutput($this->output);
    }

    /**
     * @test
     */
    public function startProgressThrowsExceptionIfParentOutputIsNotSupported(): void
    {
        $output = new SymfonyConsole\Output\NullOutput();
        $subject = new Console\Output\TrackableOutput($output);

        $this->expectExceptionObject(Exception\IOException::forUnsupportedOutput($output));

        $subject->startProgress('foo');
    }

    /**
     * @test
     */
    public function startProgressStartsProgressAndReturnsTrackableProgress(): void
    {
        $actual = $this->subject->startProgress('Do something...');

        self::assertInstanceOf(Console\Output\Progress\TrackableProgress::class, $actual);
        self::assertSame('Do something... ', $this->output->fetch());
    }
}
