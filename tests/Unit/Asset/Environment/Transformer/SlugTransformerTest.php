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

use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SlugTransformerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SlugTransformerTest extends TestCase
{
    private SlugTransformer $subject;

    protected function setUp(): void
    {
        $this->subject = new SlugTransformer('fe-{slug}');
    }

    #[Test]
    public function constructorThrowsExceptionIfPatternIsNotValid(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "pattern" is missing or invalid.');

        new SlugTransformer('foo');
    }

    /**
     * @param array{pattern?: string|false} $config
     */
    #[Test]
    #[DataProvider('fromArrayThrowsExceptionIfPatternIsNotValidDataProvider')]
    public function fromArrayThrowsExceptionIfPatternIsNotValid(array $config): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "pattern" is missing or invalid.');

        /* @phpstan-ignore-next-line */
        SlugTransformer::fromArray($config);
    }

    /**
     * @param array{pattern?: string|null} $config
     */
    #[Test]
    #[DataProvider('fromArrayReturnsTransformerInstanceDataProvider')]
    public function fromArrayReturnsTransformerInstance(array $config, SlugTransformer $expected): void
    {
        /* @phpstan-ignore-next-line */
        self::assertEquals($expected, SlugTransformer::fromArray($config));
    }

    #[Test]
    public function toArrayReturnsArrayWithPattern(): void
    {
        self::assertSame(['pattern' => 'fe-{slug}'], $this->subject->toArray());
    }

    #[Test]
    public function transformReturnsInputValueAsSlug(): void
    {
        self::assertSame('fe-feature-foo-baz', $this->subject->transform('feature/foo-baz'));
    }

    /**
     * @return Generator<string, array{array{pattern?: string|false}}>
     */
    public static function fromArrayThrowsExceptionIfPatternIsNotValidDataProvider(): Generator
    {
        yield 'empty pattern' => [['pattern' => '']];
        yield 'invalid pattern' => [['pattern' => 'foo']];
        yield 'false-typed pattern' => [['pattern' => false]];
    }

    /**
     * @return Generator<string, array{array{pattern?: string|null}, SlugTransformer}>
     */
    public static function fromArrayReturnsTransformerInstanceDataProvider(): Generator
    {
        $defaultTransformer = new SlugTransformer();

        yield 'no pattern' => [[], $defaultTransformer];
        yield 'null pattern' => [['pattern' => null], $defaultTransformer];
        yield 'custom pattern' => [['pattern' => 'fe-{slug}'], new SlugTransformer('fe-{slug}')];
    }
}
