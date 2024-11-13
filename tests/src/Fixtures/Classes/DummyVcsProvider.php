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

namespace CPSIT\FrontendAssetHandler\Tests\Fixtures\Classes;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Vcs;

use function array_shift;

/**
 * DummyVcsProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
final class DummyVcsProvider implements Vcs\DeployableVcsProviderInterface
{
    /**
     * @var list<list<Vcs\Dto\Deployment>>
     */
    public array $expectedDeployments = [];

    public function withVcs(Asset\Definition\Vcs $vcs): static
    {
        return clone $this;
    }

    public static function getName(): string
    {
        return 'dummy';
    }

    public function getSourceUrl(): string
    {
        return 'https://example.com/assets.git';
    }

    public function getLatestRevision(?string $environment = null): ?Asset\Revision\Revision
    {
        return null;
    }

    public function hasRevision(Asset\Revision\Revision $revision): bool
    {
        return false;
    }

    public function getActiveDeployments(): array
    {
        if ([] === $this->expectedDeployments) {
            return [];
        }

        return array_shift($this->expectedDeployments);
    }
}
