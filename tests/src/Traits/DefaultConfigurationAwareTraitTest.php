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

namespace CPSIT\FrontendAssetHandler\Tests\Traits;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * DefaultConfigurationAwareTraitTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DefaultConfigurationAwareTraitTest extends TestCase
{
    private Tests\Fixtures\Classes\DefaultConfigurationAwareTraitTestClass $subject;

    protected function setUp(): void
    {
        $this->subject = new Tests\Fixtures\Classes\DefaultConfigurationAwareTraitTestClass([
            'foo' => 'foo',
            'baz' => null,
        ]);
    }

    #[Test]
    public function applyDefaultConfigurationMergesDefaultConfigurationWithAssetDefinition(): void
    {
        $assetDefinition = new Asset\Definition\Source([
            'baz' => 'baz',
        ]);

        $this->subject->runApplyDefaultConfiguration($assetDefinition);

        $expected = [
            'type' => 'http',
            'baz' => 'baz',
            'foo' => 'foo',
        ];

        self::assertSame($expected, $assetDefinition->getConfig());
    }

    #[Test]
    public function validateAssetDefinitionDoesNothingIfAssetDefinitionIsValid(): void
    {
        $assetDefinition = new Asset\Definition\Source([
            'baz' => 'baz',
        ]);

        $this->expectNotToPerformAssertions();

        $this->subject->runValidateAssetDefinition($assetDefinition);
    }

    #[Test]
    public function validateAssetDefinitionThrowsExceptionIfAssetDefinitionIsInvalid(): void
    {
        $assetDefinition = new Asset\Definition\Source([]);

        $this->expectExceptionObject(Exception\MissingConfigurationException::forKey('baz'));

        $this->subject->runValidateAssetDefinition($assetDefinition);
    }
}
