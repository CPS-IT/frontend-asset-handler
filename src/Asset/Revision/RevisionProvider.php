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

namespace CPSIT\FrontendAssetHandler\Asset\Revision;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Traits;
use Generator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Filesystem;

/**
 * RevisionProvider.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
class RevisionProvider
{
    use Traits\TargetPathBuilderTrait;

    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly Filesystem\Filesystem $filesystem,
    ) {}

    public function getRevision(Asset\Definition\AssetDefinition $definition): ?Revision
    {
        if ($definition instanceof Asset\Definition\Source) {
            return $this->provideRemoteRevision($definition);
        }
        if ($definition instanceof Asset\Definition\Target) {
            return $this->provideLocalRevision($definition);
        }

        throw Exception\UnsupportedDefinitionException::create($definition);
    }

    protected function provideRemoteRevision(Asset\Definition\Source $source): ?Revision
    {
        foreach ($this->getPossibleRevisionUrls($source) as $revisionUrl) {
            try {
                $response = $this->client->request('GET', $revisionUrl, $this->getRequestOptions());
                $revision = trim($response->getBody()->getContents());

                return new Revision($revision);
            } catch (\Exception) {
                // Intended fallthrough.
            }
        }

        return null;
    }

    protected function provideLocalRevision(Asset\Definition\Target $target): ?Revision
    {
        $targetPath = $this->buildTargetPath($target);
        $revisionFile = $target->getRevisionFile();
        $revisionFilePath = Filesystem\Path::join($targetPath, $revisionFile);

        if ($this->filesystem->exists($revisionFilePath)) {
            $revision = trim((string) file_get_contents($revisionFilePath));

            return new Revision($revision);
        }

        return null;
    }

    /**
     * @return Generator<string>
     */
    protected function getPossibleRevisionUrls(Asset\Definition\Source $source): Generator
    {
        if (null === $source->getRevisionUrl()) {
            return;
        }

        $config = $source->getConfig();
        $config = array_map(Helper\StringHelper::urlEncode(...), $config);

        yield Helper\StringHelper::interpolate($source->getRevisionUrl(), $config);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequestOptions(): array
    {
        return [
            RequestOptions::CONNECT_TIMEOUT => 3,
        ];
    }
}
