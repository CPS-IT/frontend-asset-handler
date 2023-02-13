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

use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\VersionTransformer;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * VersionTransformerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VersionTransformerTest extends TestCase
{
    private VersionTransformer $subject;

    protected function setUp(): void
    {
        $this->subject = new VersionTransformer('1.0.0');
    }

    /**
     * @param array{version?: false} $config
     */
    #[Test]
    #[DataProvider('fromArrayThrowsExceptionIfVersionIsMissingOrInvalidDataProvider')]
    public function fromArrayThrowsExceptionIfVersionIsMissingOrInvalid(array $config): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "version" is missing or invalid.');

        /* @phpstan-ignore-next-line */
        VersionTransformer::fromArray($config);
    }

    #[Test]
    public function fromArrayReturnsTransformerInstance(): void
    {
        self::assertEquals($this->subject, VersionTransformer::fromArray(['version' => '1.0.0']));
    }

    #[Test]
    public function toArrayReturnsArrayWithValue(): void
    {
        self::assertSame(['version' => '1.0.0'], $this->subject->toArray());
    }

    #[Test]
    public function transformReturnsStaticValue(): void
    {
        self::assertSame('1.0.0', $this->subject->transform('baz'));
    }

    /**
     * @return \Generator<string, array{array{version?: false}}>
     */
    public static function fromArrayThrowsExceptionIfVersionIsMissingOrInvalidDataProvider(): Generator
    {
        yield 'no version' => [[]];
        yield 'invalid version' => [['value' => false]];
    }
}
