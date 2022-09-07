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

namespace CPSIT\FrontendAssetHandler\Processor;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Traits;
use Symfony\Component\Filesystem;

use function assert;

/**
 * ExistingAssetProcessor.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExistingAssetProcessor implements ProcessorInterface, ChattyInterface
{
    use Traits\OutputAwareTrait;
    use Traits\TargetPathBuilderTrait;

    public function __construct(
        private readonly Asset\Revision\RevisionProvider $revisionProvider,
        private readonly Filesystem\Filesystem $filesystem,
    ) {
    }

    public static function getName(): string
    {
        return 'existing';
    }

    public function processAsset(Asset\Asset $asset): string
    {
        $target = $asset->getTarget();
        $targetPath = $this->getAssetPath($asset);

        assert($target instanceof Asset\Definition\Target);

        // This processor assumes that the target path already exists,
        // otherwise it is not useful to use this processor
        if (!$this->filesystem->exists($targetPath)) {
            throw Exception\FilesystemFailureException::forMissingPath($targetPath);
        }

        // Try to get revision from target
        $target['revision'] = $revision = $target->getRevision() ?? $this->revisionProvider->getRevision($target);
        if (null !== $revision) {
            $this->output->writeln(sprintf('Frontend revision: <info>%s</info>', $revision->get()));
        }

        return $targetPath;
    }

    public function getAssetPath(Asset\Asset $asset): string
    {
        // Early return if the target is missing
        if (null === $asset->getTarget()) {
            throw Exception\UnsupportedAssetException::create($asset);
        }

        return $this->buildTargetPath($asset->getTarget());
    }
}
