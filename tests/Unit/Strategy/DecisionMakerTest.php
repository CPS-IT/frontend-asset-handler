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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Strategy;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Strategy\DecisionMaker;
use CPSIT\FrontendAssetHandler\Strategy\Strategy;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyRevisionProvider;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DecisionMakerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class DecisionMakerTest extends ContainerAwareTestCase
{
    private DummyRevisionProvider $revisionProvider;
    private DecisionMaker $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revisionProvider = new DummyRevisionProvider(
            $this->container->get(ClientInterface::class),
            $this->container->get(Filesystem::class)
        );
        $this->subject = new DecisionMaker($this->revisionProvider);
    }

    /**
     * @test
     */
    public function decideReturnsFetchNewStrategyIfSourceRevisionCannotBeDetermined(): void
    {
        $source = new Source([]);
        $target = new Target([]);

        self::assertEquals(Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertNull($source->getRevision());
        self::assertNull($target->getRevision());
    }

    /**
     * @test
     */
    public function decideReturnsFetchNewStrategyIfTargetRevisionCannotBeDetermined(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', null];

        $source = new Source([]);
        $target = new Target([]);

        self::assertEquals(Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertNull($target->getRevision());
    }

    /**
     * @test
     */
    public function decideReturnsUseExistingStrategyIfSourceAndTargetRevisionsAreEqual(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', '1234567890'];

        $source = new Source([]);
        $target = new Target([]);

        self::assertEquals(Strategy::UseExisting, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertSame('1234567890', $target->getRevision()?->get());
    }

    /**
     * @test
     */
    public function decideReturnsFetchNewStrategyIfSourceAndTargetRevisionsAreNotEqual(): void
    {
        $this->revisionProvider->expectedRevisions = ['1234567890', '0987654321'];

        $source = new Source([]);
        $target = new Target([]);

        self::assertEquals(Strategy::FetchNew, $this->subject->decide($source, $target));
        self::assertSame('1234567890', $source->getRevision()?->get());
        self::assertSame('0987654321', $target->getRevision()?->get());
    }
}
