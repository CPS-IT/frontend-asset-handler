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

namespace CPSIT\FrontendAssetHandler\Tests\Config\Parser;

use CPSIT\FrontendAssetHandler\Config\Config;
use CPSIT\FrontendAssetHandler\Config\Parser\ParserInstructions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ParserInstructionsTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ParserInstructionsTest extends TestCase
{
    private Config $config;
    private ParserInstructions $subject;

    protected function setUp(): void
    {
        $this->config = new Config([], 'foo');
        $this->subject = new ParserInstructions($this->config);
    }

    #[Test]
    public function getConfigReturnsConfig(): void
    {
        self::assertSame($this->config, $this->subject->getConfig());
    }

    #[Test]
    public function shouldProcessValuesReturnsTrueOnInitialState(): void
    {
        self::assertTrue($this->subject->shouldProcessValues());
    }

    #[Test]
    public function processValuesEnablesOrDisablesValueProcessing(): void
    {
        self::assertFalse($this->subject->processValues(false)->shouldProcessValues());
        self::assertTrue($this->subject->processValues(true)->shouldProcessValues());
    }

    #[Test]
    public function getRequiredKeysReturnsRequiredKeys(): void
    {
        self::assertSame([], $this->subject->getRequiredKeys());
    }

    #[Test]
    public function requireKeyAddsGivenKeyToListOfRequiredKeys(): void
    {
        $this->subject->requireKey('foo');

        self::assertSame(['foo'], $this->subject->getRequiredKeys());
    }

    #[Test]
    public function requireKeysDoesNotAddAKeyTwice(): void
    {
        $this->subject->requireKey('foo');
        $this->subject->requireKey('foo');

        self::assertSame(['foo'], $this->subject->getRequiredKeys());
    }
}
