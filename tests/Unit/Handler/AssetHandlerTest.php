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
use CPSIT\FrontendAssetHandler\Asset\Revision\RevisionProvider;
use CPSIT\FrontendAssetHandler\Exception\AssetHandlerFailedException;
use CPSIT\FrontendAssetHandler\Handler\AssetHandler;
use CPSIT\FrontendAssetHandler\Processor\ProcessorFactory;
use CPSIT\FrontendAssetHandler\Strategy\Strategy;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyDecisionMaker;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyProcessor;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyProvider;
use Exception;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ServiceLocator;

use function sprintf;

/**
 * AssetHandlerTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetHandlerTest extends ContainerAwareTestCase
{
    private DummyProvider $provider;
    private DummyProcessor $processor;
    private DummyDecisionMaker $decisionMaker;
    private AssetHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $processorFactory = new ProcessorFactory(
            new ServiceLocator([
                'dummy' => fn (): DummyProcessor => new DummyProcessor(),
                'existing' => fn (): DummyProcessor => new DummyProcessor(),
                'invalid' => fn (): Exception => new Exception(),
            ])
        );
        $processorFactory->setOutput(new NullOutput());

        $this->subject = new AssetHandler(
            $this->provider = new DummyProvider(),
            $this->processor = new DummyProcessor(),
            $processorFactory,
            $this->decisionMaker = new DummyDecisionMaker($this->container->get(RevisionProvider::class))
        );
    }

    /**
     * @test
     */
    public function handleReturnsAssetForSelfDecidedStrategy(): void
    {
        $this->decisionMaker->expectedStrategy = Strategy::UseExisting;

        $source = new Source([]);
        $target = new Target([]);

        /** @var ExistingAsset $actual */
        $actual = $this->subject->handle($source, $target);

        self::assertInstanceOf(ExistingAsset::class, $actual);
        self::assertSame($source, $actual->getSource());
        self::assertSame($target, $actual->getTarget());
        self::assertSame('foo', $actual->getTargetPath());
    }

    /**
     * @test
     */
    public function handleThrowsExceptionIfProviderErrors(): void
    {
        $source = new Source(['environment' => 'foo']);
        $target = new Target([]);

        $exception = new Exception();
        $this->provider->expectedExceptions[] = $exception;

        $this->expectExceptionObject($exception);

        $this->subject->handle($source, $target, Strategy::FetchNew);
    }

    /**
     * @test
     */
    public function handleThrowsExceptionIfProcessedTargetPathIsInvalid(): void
    {
        $source = new Source(['foo' => 'baz']);
        $target = new Target(['foo' => 'baz']);

        $this->expectException(AssetHandlerFailedException::class);
        $this->expectExceptionCode(1623861520);
        $this->expectExceptionMessage(sprintf('Processing of asset from source "%s" to target "%s" failed.', $source, $target));

        $this->processor->shouldReturnValidPath = false;
        $this->subject->handle($source, $target, Strategy::FetchNew);
    }

    /**
     * @test
     */
    public function handleReturnsProcessedAsset(): void
    {
        $source = new Source(['foo' => 'baz']);
        $target = new Target(['foo' => 'baz']);

        /** @var ProcessedAsset $actual */
        $actual = $this->subject->handle($source, $target, Strategy::FetchNew);

        self::assertInstanceOf(ProcessedAsset::class, $actual);
        self::assertSame($source, $actual->getSource());
        self::assertSame($target, $actual->getTarget());
        self::assertSame('foo', $actual->getProcessedTargetPath());
    }
}
