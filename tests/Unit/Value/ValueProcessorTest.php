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

use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\EnvironmentVariablesTrait;
use CPSIT\FrontendAssetHandler\Value\ValueProcessor;
use PHPUnit\Framework\Attributes\Test;

/**
 * ValueProcessorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ValueProcessorTest extends ContainerAwareTestCase
{
    use EnvironmentVariablesTrait;

    private ValueProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backUpEnvironmentVariables();

        $this->subject = $this->container->get(ValueProcessor::class);
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

        $this->setEnvironmentVariable('baz', 'baz');
        $this->setEnvironmentVariable('hello', 'hello');

        self::assertSame($expected, $this->subject->process($array));
    }

    #[Test]
    public function processSingleValueProcessesGivenValue(): void
    {
        $this->setEnvironmentVariable('foo', 'baz');

        self::assertSame('baz', $this->subject->processSingleValue('%env(foo)%'));
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
    }
}
