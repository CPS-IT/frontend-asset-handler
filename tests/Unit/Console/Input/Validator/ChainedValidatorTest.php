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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Console\Input\Validator;

use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\TestCase;

/**
 * ChainedValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ChainedValidatorTest extends TestCase
{
    private Console\Input\Validator\ChainedValidator $subject;
    private Tests\Unit\Fixtures\Classes\DummyValidator $firstValidator;
    private Tests\Unit\Fixtures\Classes\DummyValidator $secondValidator;

    protected function setUp(): void
    {
        $this->subject = new Console\Input\Validator\ChainedValidator([
            $this->firstValidator = new Tests\Unit\Fixtures\Classes\DummyValidator(),
            $this->secondValidator = new Tests\Unit\Fixtures\Classes\DummyValidator(),
        ]);
    }

    /**
     * @test
     */
    public function validateIteratesThroughAllChainedValidators(): void
    {
        self::assertFalse($this->firstValidator->hasBeenCalled);
        self::assertFalse($this->secondValidator->hasBeenCalled);

        $this->subject->validate('foo');

        self::assertTrue($this->firstValidator->hasBeenCalled);
        self::assertTrue($this->secondValidator->hasBeenCalled);
    }
}
