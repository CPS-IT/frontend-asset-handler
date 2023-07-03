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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Environment\Transformer;

use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\StaticTransformer;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * StaticTransformerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class StaticTransformerTest extends TestCase
{
    private StaticTransformer $subject;

    protected function setUp(): void
    {
        $this->subject = new StaticTransformer('foo');
    }

    /**
     * @param array{value?: false} $config
     */
    #[Test]
    #[DataProvider('fromArrayThrowsExceptionIfValueIsMissingOrInvalidDataProvider')]
    public function fromArrayThrowsExceptionIfValueIsMissingOrInvalid(array $config): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "value" is missing or invalid.');

        StaticTransformer::fromArray($config);
    }

    #[Test]
    public function fromArrayReturnsTransformerInstance(): void
    {
        self::assertEquals($this->subject, StaticTransformer::fromArray(['value' => 'foo']));
    }

    #[Test]
    public function toArrayReturnsArrayWithValue(): void
    {
        self::assertSame(['value' => 'foo'], $this->subject->toArray());
    }

    #[Test]
    public function transformReturnsStaticValue(): void
    {
        self::assertSame('foo', $this->subject->transform('baz'));
    }

    /**
     * @return Generator<string, array{array{value?: false}}>
     */
    public static function fromArrayThrowsExceptionIfValueIsMissingOrInvalidDataProvider(): Generator
    {
        yield 'no value' => [[]];
        yield 'invalid value' => [['value' => false]];
    }
}
