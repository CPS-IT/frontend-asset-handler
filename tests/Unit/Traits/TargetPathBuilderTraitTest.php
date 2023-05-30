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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Traits;

use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\TargetPathBuilderTraitTestClass;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function dirname;

/**
 * TargetPathBuilderTraitTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TargetPathBuilderTraitTest extends TestCase
{
    private TargetPathBuilderTraitTestClass $subject;

    protected function setUp(): void
    {
        $this->subject = new TargetPathBuilderTraitTestClass();
    }

    #[Test]
    public function buildTargetPathThrowsExceptionIfPathIsNotDefined(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "path" is missing or invalid.');

        $this->subject->runBuildTargetPath(new Target([]));
    }

    #[Test]
    #[DataProvider('buildTargetPathThrowsExceptionIfPathIsNotDefinedDataProvider')]
    public function buildTargetPathReturnsTargetPath(string $path, string $expected): void
    {
        self::assertSame($expected, $this->subject->runBuildTargetPath(new Target(['path' => $path])));
    }

    /**
     * @return Generator<string, array{string, string}>
     */
    public static function buildTargetPathThrowsExceptionIfPathIsNotDefinedDataProvider(): Generator
    {
        yield 'absolute path' => ['/foo/baz', '/foo/baz'];
        yield 'relative path' => ['foo/baz', Path::join(getcwd() ?: dirname(__DIR__, 3), 'foo/baz')];
    }
}
