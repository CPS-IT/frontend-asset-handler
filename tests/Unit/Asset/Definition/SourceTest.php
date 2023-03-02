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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Definition;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * SourceTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SourceTest extends TestCase
{
    private Source $subject;

    protected function setUp(): void
    {
        $this->subject = new Source([]);
    }

    #[Test]
    public function getTypeReturnsType(): void
    {
        self::assertSame('http', $this->subject->getType());

        $this->subject['type'] = 'foo';
        self::assertSame('foo', $this->subject->getType());
    }

    #[Test]
    public function getEnvironmentReturnsEnvironmentOrNull(): void
    {
        self::assertNull($this->subject->getEnvironment());

        $this->subject['environment'] = 'foo';
        self::assertSame('foo', $this->subject->getEnvironment());
    }

    #[Test]
    public function getUrlReturnsCustomUrlOrNull(): void
    {
        self::assertNull($this->subject->getUrl());

        $this->subject['url'] = 'foo';
        self::assertSame('foo', $this->subject->getUrl());
    }

    #[Test]
    public function getRevisionUrlReturnsCustomRevisionUrlOrNull(): void
    {
        self::assertNull($this->subject->getRevisionUrl());

        $this->subject['revision-url'] = 'foo';
        self::assertSame('foo', $this->subject->getRevisionUrl());
    }

    #[Test]
    public function getRevisionReturnsRevisionOrNull(): void
    {
        self::assertNull($this->subject->getRevision());

        $this->subject['revision'] = $revision = new Revision('1234567');
        self::assertSame($revision, $this->subject->getRevision());
    }
}
