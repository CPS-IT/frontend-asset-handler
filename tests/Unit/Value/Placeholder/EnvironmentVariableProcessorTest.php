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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Value\Placeholder;

use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Value\Placeholder\EnvironmentVariableProcessor;
use Generator;
use UnexpectedValueException;

use function putenv;

/**
 * EnvironmentVariableProcessorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class EnvironmentVariableProcessorTest extends ContainerAwareTestCase
{
    private EnvironmentVariableProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(EnvironmentVariableProcessor::class);
    }

    /**
     * @test
     *
     * @dataProvider canProcessTestsWhetherGivenPlaceholderContainsEnvironmentVariablePlaceholderDataProvider
     */
    public function canProcessTestsWhetherGivenPlaceholderContainsEnvironmentVariablePlaceholder(string $placeholder, bool $expected): void
    {
        self::assertSame($expected, $this->subject->canProcess($placeholder));
    }

    /**
     * @test
     */
    public function processThrowsExceptionIfGivenPlaceholderCannotBeProcessed(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionCode(1628147418);

        $this->subject->process('foo');
    }

    /**
     * @test
     */
    public function processThrowsExceptionIfRequiredEnvironmentVariableIsNotSet(): void
    {
        // Ensure environment variable is not set
        putenv('foo');

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionCode(1628147471);

        $this->subject->process('%env(foo)%');
    }

    /**
     * @test
     *
     * @dataProvider processReplacesEnvironmentPlaceholderWithEnvironmentVariableDataProvider
     */
    public function processReplacesEnvironmentPlaceholderWithEnvironmentVariable(string $placeholder, string $expected): void
    {
        putenv('foo=baz');
        putenv('baz=boo');
        putenv('dummy=New York');

        self::assertSame($expected, $this->subject->process($placeholder));

        // Unset temporary environment variable
        putenv('foo');
        putenv('baz');
        putenv('dummy');
    }

    /**
     * @return \Generator<string, array{string, bool}>
     */
    public function canProcessTestsWhetherGivenPlaceholderContainsEnvironmentVariablePlaceholderDataProvider(): Generator
    {
        yield 'empty string' => ['', false];
        yield 'static value' => ['foo', false];
        yield 'unsupported placeholder' => ['%foo%', false];
        yield 'uppercased env placeholder' => ['%ENV(foo)%', false];
        yield 'valid env placeholder' => ['%env(foo)%', true];
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public function processReplacesEnvironmentPlaceholderWithEnvironmentVariableDataProvider(): Generator
    {
        yield 'placeholder only' => ['%env(foo)%', 'baz'];
        yield 'one placeholder with surrounding text' => ['Hello, %env(foo)%!', 'Hello, baz!'];
        yield 'multiple placeholders with surrounding text' => [
            'Hello, %env(foo)% %env(baz)%! Welcome to %env(dummy)%.',
            'Hello, baz boo! Welcome to New York.',
        ];
    }
}
