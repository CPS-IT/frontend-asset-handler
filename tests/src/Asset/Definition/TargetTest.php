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

use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TargetTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TargetTest extends TestCase
{
    private Target $subject;

    protected function setUp(): void
    {
        $this->subject = new Target([]);
    }

    #[Test]
    public function getTypeReturnsType(): void
    {
        self::assertSame('archive', $this->subject->getType());

        $this->subject['type'] = 'foo';
        self::assertSame('foo', $this->subject->getType());
    }

    #[Test]
    public function getPathReturnsPathOrNull(): void
    {
        self::assertNull($this->subject->getPath());

        $this->subject['path'] = 'foo';
        self::assertSame('foo', $this->subject->getPath());
    }

    #[Test]
    public function getRevisionFileReturnsCustomRevisionFileOrDefaultRevisionFile(): void
    {
        self::assertSame(Target::DEFAULT_REVISION_FILE, $this->subject->getRevisionFile());

        $this->subject['revision-file'] = 'foo';
        self::assertSame('foo', $this->subject->getRevisionFile());
    }

    #[Test]
    public function getRevisionReturnsRevisionOrNull(): void
    {
        self::assertNull($this->subject->getRevision());

        $this->subject['revision'] = $revision = new Revision('1234567');
        self::assertSame($revision, $this->subject->getRevision());
    }
}
