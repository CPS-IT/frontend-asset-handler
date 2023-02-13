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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Handler;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\ExistingAsset;
use CPSIT\FrontendAssetHandler\Asset\ProcessedAsset;
use CPSIT\FrontendAssetHandler\Exception\AssetHandlerFailedException;
use CPSIT\FrontendAssetHandler\Handler\AssetHandler;
use CPSIT\FrontendAssetHandler\Processor\ExistingAssetProcessor;
use CPSIT\FrontendAssetHandler\Strategy\DecisionMaker;
use CPSIT\FrontendAssetHandler\Strategy\Strategy;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyDecisionMaker;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyProcessor;
use PHPUnit\Framework\Attributes\Test;

use function sprintf;

/**
 * AssetHandlerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetHandlerTest extends ContainerAwareTestCase
{
    private DummyProcessor $processor;
    private DummyDecisionMaker $decisionMaker;
    private AssetHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decisionMaker = $this->container->get(DummyDecisionMaker::class);
        $this->processor = $this->container->get(DummyProcessor::class);

        $this->container->set(DecisionMaker::class, $this->decisionMaker);
        $this->container->set(ExistingAssetProcessor::class, $this->processor);

        $this->subject = $this->container->get(AssetHandler::class);
    }

    #[Test]
    public function handleReturnsAssetForSelfDecidedStrategy(): void
    {
        $this->decisionMaker->expectedStrategy = Strategy::UseExisting;

        $source = new Source([]);
        $target = new Target(['type' => 'dummy']);

        /** @var ExistingAsset $actual */
        $actual = $this->subject->handle($source, $target);

        self::assertInstanceOf(ExistingAsset::class, $actual);
        self::assertSame($source, $actual->getSource());
        self::assertSame($target, $actual->getTarget());
        self::assertSame('foo', $actual->getTargetPath());
    }

    #[Test]
    public function handleThrowsExceptionIfProcessedTargetPathIsInvalid(): void
    {
        $source = new Source(['type' => 'dummy', 'foo' => 'baz']);
        $target = new Target(['type' => 'dummy', 'foo' => 'baz']);

        $this->expectException(AssetHandlerFailedException::class);
        $this->expectExceptionCode(1623861520);
        $this->expectExceptionMessage(sprintf('Processing of asset from source "%s" to target "%s" failed.', $source, $target));

        $this->processor->shouldReturnValidPath = false;
        $this->subject->handle($source, $target, Strategy::FetchNew);
    }

    #[Test]
    public function handleReturnsProcessedAsset(): void
    {
        $source = new Source(['type' => 'dummy', 'foo' => 'baz']);
        $target = new Target(['type' => 'dummy', 'foo' => 'baz']);

        /** @var ProcessedAsset $actual */
        $actual = $this->subject->handle($source, $target, Strategy::FetchNew);

        self::assertInstanceOf(ProcessedAsset::class, $actual);
        self::assertSame($source, $actual->getSource());
        self::assertSame($target, $actual->getTarget());
        self::assertSame('foo', $actual->getProcessedTargetPath());
    }
}
