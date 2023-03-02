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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Json;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Json;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;

/**
 * SchemaValidatorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
class SchemaValidatorTest extends Tests\Unit\ContainerAwareTestCase
{
    private Json\SchemaValidator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Json\SchemaValidator::class);
    }

    #[Test]
    public function validateReturnsFalseOnInvalidConfig(): void
    {
        $config = new Config\Config(['foo' => 'baz'], 'foo');

        self::assertFalse($this->subject->validate($config));
    }

    #[Test]
    public function validateReturnsTrueOnValidConfig(): void
    {
        $config = new Config\Config([
            'frontend-assets' => [
                [
                    'source' => [
                        'type' => 'baz',
                        'url' => 'baz',
                    ],
                    'target' => [
                        'type' => 'foo',
                        'path' => 'foo',
                    ],
                ],
            ],
        ], 'foo');

        self::assertTrue($this->subject->validate($config));
    }
}
