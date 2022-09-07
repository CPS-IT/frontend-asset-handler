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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Vcs\Dto;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Vcs;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;

/**
 * DeploymentTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DeploymentTest extends TestCase
{
    private Vcs\Dto\Deployment $subject;

    protected function setUp(): void
    {
        $this->subject = new Vcs\Dto\Deployment(
            new Psr7\Uri('https://www.example.com'),
            new Asset\Revision\Revision('1234567890'),
        );
    }

    /**
     * @test
     */
    public function getUriReturnsUri(): void
    {
        self::assertEquals(new Psr7\Uri('https://www.example.com'), $this->subject->getUri());
    }

    /**
     * @test
     */
    public function getRevisionReturnsRevision(): void
    {
        self::assertEquals(new Asset\Revision\Revision('1234567890'), $this->subject->getRevision());
    }
}
