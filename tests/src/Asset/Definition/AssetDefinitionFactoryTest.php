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

use CPSIT\FrontendAssetHandler\Asset\Definition\AssetDefinitionFactory;
use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\Definition\Vcs;
use CPSIT\FrontendAssetHandler\Asset\Environment\Environment;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\MapFactory;
use CPSIT\FrontendAssetHandler\Tests\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Vcs\GitlabVcsProvider;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * AssetDefinitionFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class AssetDefinitionFactoryTest extends ContainerAwareTestCase
{
    private AssetDefinitionFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new AssetDefinitionFactory($this->container->get(MapFactory::class));
    }

    /**
     * @param array{environments?: array<string, mixed>, source?: array<string, mixed>} $config
     */
    #[Test]
    #[DataProvider('buildSourceReturnsGeneratedSourceDataProvider')]
    public function buildSourceReturnsGeneratedSource(array $config, Source $expected): void
    {
        self::assertEquals($expected, $this->subject->buildSource($config, 'main'));
    }

    #[Test]
    public function buildTargetReturnsGeneratedTarget(): void
    {
        $target = new Target(['foo' => 'baz']);

        self::assertEquals($target, $this->subject->buildTarget(['target' => ['foo' => 'baz']]));
    }

    #[Test]
    public function buildVcsReturnsNullIfVcsConfigIsMissingOrInvalid(): void
    {
        self::assertNull($this->subject->buildVcs([], 'main'));
    }

    /**
     * @param array{environments?: array<string, mixed>, source?: array<string, mixed>, vcs?: array<string, mixed>} $config
     */
    #[Test]
    #[DataProvider('buildVcsReturnsGeneratedVcsDataProvider')]
    public function buildVcsReturnsGeneratedVcs(array $config, Vcs $expected): void
    {
        self::assertEquals($expected, $this->subject->buildVcs($config, 'main'));
    }

    /**
     * @return Generator<string, array{array{environments?: array<string, mixed>, source?: array<string, mixed>}, Source}>
     */
    public static function buildSourceReturnsGeneratedSourceDataProvider(): Generator
    {
        $buildSource = function (string $environment, bool $isVersion = false): Source {
            $source = new Source(['environment' => $environment]);

            if ($isVersion) {
                $source['version'] = $environment;
            }

            return $source;
        };

        yield 'no config' => [[], $buildSource(Environment::Stable->value)];
        yield 'custom map' => [
            [
                'environments' => [
                    'map' => [
                        'main' => 'baz',
                    ],
                ],
            ],
            $buildSource('baz'),
        ];
        yield 'custom map with merge' => [
            [
                'environments' => [
                    'map' => [
                        'foo' => 'baz',
                    ],
                    'merge' => true,
                ],
            ],
            $buildSource(Environment::Stable->value),
        ];
        yield 'version' => [
            [
                'source' => [
                    'version' => '1.0.0',
                ],
            ],
            $buildSource('1.0.0', true),
        ];
    }

    /**
     * @return Generator<string, array{array{environments?: array<string, mixed>, source?: array<string, mixed>, vcs?: array<string, mixed>}, Vcs}>
     */
    public static function buildVcsReturnsGeneratedVcsDataProvider(): Generator
    {
        $buildVcs = fn (string $environment): Vcs => new Vcs([
            'type' => GitlabVcsProvider::getName(),
            'environment' => $environment,
        ]);

        yield 'custom map' => [
            [
                'vcs' => [
                    'type' => GitlabVcsProvider::getName(),
                ],
                'environments' => [
                    'map' => [
                        'main' => 'baz',
                    ],
                ],
            ],
            $buildVcs('baz'),
        ];
        yield 'custom map with merge' => [
            [
                'vcs' => [
                    'type' => GitlabVcsProvider::getName(),
                ],
                'environments' => [
                    'map' => [
                        'foo' => 'baz',
                    ],
                    'merge' => true,
                ],
            ],
            $buildVcs(Environment::Stable->value),
        ];
        yield 'version' => [
            [
                'source' => [
                    'version' => '1.0.0',
                ],
                'vcs' => [
                    'type' => GitlabVcsProvider::getName(),
                ],
            ],
            $buildVcs('1.0.0'),
        ];
    }
}
