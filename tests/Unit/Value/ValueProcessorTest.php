<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Value;

use CPSIT\FrontendAssetHandler\Exception\UnsupportedClassException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedTypeException;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Value\ValueProcessor;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * ValueProcessorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ValueProcessorTest extends ContainerAwareTestCase
{
    private ValueProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(ValueProcessor::class);
    }

    #[Test]
    public function constructorThrowsExceptionIfGivenPlaceholderProcessorIsNotAnObject(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionCode(1628149629);

        /* @noinspection PhpParamsInspection */
        /* @phpstan-ignore-next-line */
        new ValueProcessor(['foo']);
    }

    #[Test]
    public function constructorThrowsExceptionIfGivenPlaceholderProcessorIsInvalid(): void
    {
        $this->expectException(UnsupportedClassException::class);
        $this->expectExceptionCode(1623911858);

        /* @noinspection PhpParamsInspection */
        /* @phpstan-ignore-next-line */
        new ValueProcessor([new stdClass()]);
    }

    #[Test]
    public function processProcessesGivenArrayRecursively(): void
    {
        $array = [
            'foo' => [
                'baz' => '%env(baz)%',
                'hello' => '%env(hello)%',
                'world' => [
                    'value' => 'foo',
                ],
            ],
        ];
        $expected = [
            'foo' => [
                'baz' => 'baz',
                'hello' => 'hello',
                'world' => [
                    'value' => 'foo',
                ],
            ],
        ];

        putenv('baz=baz');
        putenv('hello=hello');

        self::assertSame($expected, $this->subject->process($array));

        // Unset temporary environment variables
        putenv('baz');
        putenv('hello');
    }

    #[Test]
    public function processSingleValueProcessesGivenValue(): void
    {
        putenv('foo=baz');

        self::assertSame('baz', $this->subject->processSingleValue('%env(foo)%'));

        // Unset temporary environment variable
        putenv('foo');
    }
}
