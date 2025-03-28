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

namespace CPSIT\FrontendAssetHandler\Tests\Asset\Definition;

use CPSIT\FrontendAssetHandler\Tests\Fixtures\Classes\DummyAssetDefinition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * AssetDefinitionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetDefinitionTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $config;
    private DummyAssetDefinition $subject;

    protected function setUp(): void
    {
        $this->config = [
            'foo' => 'baz',
            'baz' => [
                'hello' => true,
                'world' => null,
            ],
        ];
        $this->subject = new DummyAssetDefinition($this->config);
    }

    #[Test]
    public function getConfigReturnsConfig(): void
    {
        self::assertSame($this->config, $this->subject->getConfig());
    }

    #[Test]
    public function subjectAllowsArrayAccess(): void
    {
        // offsetExists
        self::assertTrue(isset($this->subject['foo']));
        self::assertFalse(isset($this->subject['invalid-key']));

        // offsetGet
        self::assertSame('baz', $this->subject['foo']);
        self::assertSame(['hello' => true, 'world' => null], $this->subject['baz']);

        // offsetSet
        $this->subject['hello'] = 'world';
        self::assertSame('world', $this->subject['hello']);

        // offsetUnset
        unset($this->subject['hello']);
        self::assertFalse(isset($this->subject['hello']));
    }

    #[Test]
    public function subjectIsIterable(): void
    {
        self::assertSame([
            'foo' => 'baz',
            'baz' => [
                'hello' => true,
                'world' => null,
            ],
        ], iterator_to_array($this->subject->getIterator()));
    }

    #[Test]
    public function toStringReturnsJsonRepresentationOfConfig(): void
    {
        $json = $this->subject->__toString();

        self::assertJson($json);
        self::assertJsonStringEqualsJsonString(json_encode($this->config, JSON_THROW_ON_ERROR), $json);
    }
}
