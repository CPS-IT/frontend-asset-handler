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

namespace CPSIT\FrontendAssetHandler\Handler;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Strategy;

/**
 * AssetHandler.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetHandler implements HandlerInterface
{
    public function __construct(
        private readonly Provider\ProviderInterface $provider,
        private readonly Processor\ProcessorInterface $processor,
        private readonly Processor\ProcessorFactory $processorFactory,
        private readonly Strategy\DecisionMaker $decisionMaker,
    ) {
    }

    public function handle(
        Asset\Definition\Source $source,
        Asset\Definition\Target $target,
        Strategy\Strategy $strategy = null,
    ): Asset\ExistingAsset|Asset\ProcessedAsset {
        if (null === $strategy) {
            $strategy = $this->decisionMaker->decide($source, $target);
        }

        if (Strategy\Strategy::UseExisting === $strategy) {
            $asset = new Asset\Asset($source, $target);
            $processor = $this->processorFactory->get(Processor\ExistingAssetProcessor::getName());
            $targetPath = $processor->processAsset($asset);

            return new Asset\ExistingAsset($source, $target, $targetPath);
        }

        $asset = $this->provider->fetchAsset($source);
        $asset->setTarget($target);
        $processedTargetPath = $this->processor->processAsset($asset);

        if ('' === trim($processedTargetPath)) {
            throw Exception\AssetHandlerFailedException::create($asset);
        }

        return new Asset\ProcessedAsset($source, $target, $processedTargetPath);
    }
}
