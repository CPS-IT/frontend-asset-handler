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
use CPSIT\FrontendAssetHandler\Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * ValidatorFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ValidatorFactoryTest extends TestCase
{
    private Console\Input\Validator\ValidatorFactory $subject;

    protected function setUp(): void
    {
        $this->subject = new Console\Input\Validator\ValidatorFactory();
    }

    #[Test]
    public function getThrowsExceptionIfGivenTypeIsNotSupported(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedTypeException::create('foo'));

        $this->subject->get('foo');
    }

    #[Test]
    #[DataProvider('getReturnsValidatorOfGivenTypeDataProvider')]
    public function getReturnsValidatorOfGivenType(
        string $type,
        Console\Input\Validator\ValidatorInterface $expected,
    ): void {
        self::assertEquals($expected, $this->subject->get($type));
    }

    #[Test]
    public function getForAllReturnsChainedValidator(): void
    {
        $expected = new Console\Input\Validator\ChainedValidator([
            new Console\Input\Validator\NotEmptyValidator(),
            new Console\Input\Validator\UrlValidator(),
        ]);

        self::assertEquals($expected, $this->subject->getForAll(['notEmpty', 'url']));
    }

    /**
     * @return Generator<string, array{string, Console\Input\Validator\ValidatorInterface}>
     */
    public static function getReturnsValidatorOfGivenTypeDataProvider(): Generator
    {
        yield 'integer validator' => ['integer', new Console\Input\Validator\IntegerValidator()];
        yield 'json validator' => ['json', new Console\Input\Validator\JsonValidator()];
        yield 'notEmpty validator' => ['notEmpty', new Console\Input\Validator\NotEmptyValidator()];
        yield 'url validator' => ['url', new Console\Input\Validator\UrlValidator()];
    }
}
