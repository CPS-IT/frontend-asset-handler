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

namespace CPSIT\FrontendAssetHandler\Tests\Console\Input\Validator;

use CPSIT\FrontendAssetHandler\Console;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert;

/**
 * IntegerValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class IntegerValidatorTest extends TestCase
{
    private Console\Input\Validator\IntegerValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Console\Input\Validator\IntegerValidator();
    }

    #[Test]
    public function validateDoesNothingIfValueIsNull(): void
    {
        self::assertNull($this->subject->validate(null));
    }

    #[Test]
    public function validateReturnsValueIfItIsAnInteger(): void
    {
        self::assertSame(123, $this->subject->validate(123));
    }

    #[Test]
    public function validateThrowsExceptionIfValueIsNotNumeric(): void
    {
        $this->expectExceptionObject(new Assert\InvalidArgumentException('Expected a numeric. Got: bool'));

        $this->subject->validate(false);
    }

    #[Test]
    public function validateReturnsConvertedIntegerValueIfItIsANumericString(): void
    {
        self::assertSame(123, $this->subject->validate('123'));
    }
}
