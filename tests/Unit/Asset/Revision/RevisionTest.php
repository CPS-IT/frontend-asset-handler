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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Revision;

use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use CPSIT\FrontendAssetHandler\Exception\InvalidRevisionException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * RevisionTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class RevisionTest extends TestCase
{
    private Revision $subject;

    protected function setUp(): void
    {
        $this->subject = new Revision('1234567890');
    }

    #[Test]
    public function constructorThrowsExceptionIfRevisionIsTooShort(): void
    {
        $this->expectException(InvalidRevisionException::class);
        $this->expectExceptionCode(1624639456);
        $this->expectExceptionMessage('The string "foo" is not a valid revision.');

        new Revision('foo');
    }

    #[Test]
    public function getReturnsFullRevision(): void
    {
        self::assertSame('1234567890', $this->subject->get());
    }

    #[Test]
    public function getShortReturnsShortRevision(): void
    {
        self::assertSame('1234567', $this->subject->getShort());
    }

    #[Test]
    public function equalComparesRevisionsCorrectly(): void
    {
        self::assertFalse($this->subject->equals(new Revision('1234567')));
        self::assertTrue($this->subject->equals(new Revision('1234567890')));
    }

    #[Test]
    public function toStringReturnsFullRevision(): void
    {
        self::assertSame('1234567890', (string) $this->subject);
    }
}
