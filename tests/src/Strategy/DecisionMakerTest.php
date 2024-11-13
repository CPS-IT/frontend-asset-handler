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

namespace CPSIT\FrontendAssetHandler\Tests\Strategy;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Strategy;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;

/**
 * DecisionMakerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DecisionMakerTest extends Tests\ContainerAwareTestCase
{
    private Tests\Fixtures\Classes\DummyRevisionProvider $revisionProvider;
    private Strategy\DecisionMaker $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revisionProvider = $this->container->get(Tests\Fixtures\Classes\DummyRevisionProvider::class);
        $this->subject = new Strategy\DecisionMaker($this->revisionProvider);
    }

    #[Test]
    public function decideReturnsFetchNewStrategyIfSourceRevisionCannotBeDetermined(): void
    {
        $source = new Asset\Definition\Source([]);
        $target = new Asset\Definition\Target([]);

        self::assertEquals(Strategy\Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertNull($source->getRevision());
        self::assertNull($target->getRevision());
    }

    #[Test]
    public function decideReturnsFetchNewStrategyIfTargetRevisionCannotBeDetermined(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', null];

        $source = new Asset\Definition\Source([]);
        $target = new Asset\Definition\Target([]);

        self::assertEquals(Strategy\Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertNull($target->getRevision());
    }

    #[Test]
    public function decideReturnsUseExistingStrategyIfSourceAndTargetRevisionsAreEqual(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', '1234567890'];

        $source = new Asset\Definition\Source([]);
        $target = new Asset\Definition\Target([]);

        self::assertEquals(Strategy\Strategy::UseExisting, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertSame('1234567890', $target->getRevision()?->get());
    }

    #[Test]
    public function decideReturnsFetchNewStrategyIfSourceAndTargetRevisionsAreNotEqual(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', '0987654321'];

        $source = new Asset\Definition\Source([]);
        $target = new Asset\Definition\Target([]);

        self::assertEquals(Strategy\Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertSame('0987654321', $target->getRevision()?->get());
    }
}
