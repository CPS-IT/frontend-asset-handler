<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2022 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Asset\Environment\Map;

use CPSIT\FrontendAssetHandler\Asset\Environment\Environment;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\PassthroughTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\StaticTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\TransformerInterface;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\VersionTransformer;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedClassException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedTypeException;

use function call_user_func;
use function is_a;
use function is_string;
use function trim;

/**
 * MapFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @api
 */
final readonly class MapFactory
{
    private const REGEX_PATTERN_VERSION = '/^v?\\d+\\.\\d+\\.\\d+$/';

    /**
     * @param array<string, class-string<TransformerInterface>> $transformers
     */
    public function __construct(
        private array $transformers,
    ) {}

    public static function createDefault(?string $version = null): Map
    {
        $slugTransformer = new SlugTransformer('{slug}');
        $latestTransformer = new StaticTransformer(Environment::Latest->value);
        $passthroughTransformer = new PassthroughTransformer();

        if (null !== $version && '' !== trim($version)) {
            $stableTransformer = new VersionTransformer($version);
        } else {
            $stableTransformer = $slugTransformer;
        }

        return new Map([
            new Pair('main', $stableTransformer),
            new Pair('master', $stableTransformer),
            new Pair('renovate/*', $latestTransformer),
            new Pair(self::REGEX_PATTERN_VERSION, $passthroughTransformer),
            new Pair('*', $slugTransformer),
        ]);
    }

    public static function createEmpty(): Map
    {
        return new Map([]);
    }

    /**
     * @param array<string, array{transformer?: string, options?: array<string, mixed>}|string> $config
     */
    public function createFromArray(array $config, ?string $version = null): Map
    {
        $pairs = [];

        foreach ($config as $inputPattern => $transformer) {
            $options = ['version' => $version];

            if (is_string($transformer)) {
                $type = StaticTransformer::getName();
                $options += ['value' => $transformer];
            } else {
                $type = $transformer['transformer'] ?? null;
                $options += $transformer['options'] ?? [];
            }

            if (null === $type || '' === trim($type)) {
                throw MissingConfigurationException::forKey($inputPattern.'/transformer');
            }

            $pairs[] = new Pair($inputPattern, $this->createTransformer($type, $options));
        }

        return new Map($pairs);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws UnsupportedClassException
     * @throws UnsupportedTypeException
     */
    private function createTransformer(string $type, array $options = []): TransformerInterface
    {
        if (!isset($this->transformers[$type])) {
            throw UnsupportedTypeException::create($type);
        }

        $className = $this->transformers[$type];

        if (!class_exists($className)) {
            throw UnsupportedClassException::create($className);
        }
        if (!is_a($className, TransformerInterface::class, true)) {
            throw UnsupportedClassException::create($className);
        }

        return call_user_func([$className, 'fromArray'], $options);
    }
}
