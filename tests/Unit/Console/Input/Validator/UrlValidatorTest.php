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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert;

/**
 * UrlValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class UrlValidatorTest extends TestCase
{
    private Console\Input\Validator\UrlValidator $subject;

    protected function setUp(): void
    {
        $this->subject = new Console\Input\Validator\UrlValidator();
    }

    #[Test]
    public function validateDoesNothingIfValueIsNull(): void
    {
        self::assertNull($this->subject->validate(null));
    }

    #[Test]
    public function validateThrowsExceptionIfValueIsNotAString(): void
    {
        $this->expectExceptionObject(new Assert\InvalidArgumentException('Expected a string. Got: bool'));

        $this->subject->validate(false);
    }

    #[Test]
    public function validateThrowsExceptionIfFilteredValueIsNotAValidUrl(): void
    {
        $this->expectExceptionObject(new Assert\InvalidArgumentException('The given URL is invalid.'));

        $this->subject->validate('foo');
    }

    #[Test]
    public function validateReturnsValueOnValidUrl(): void
    {
        $url = 'https://www.example.com';

        self::assertSame($url, $this->subject->validate($url));
    }
}
