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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Environment\Map;

use CPSIT\FrontendAssetHandler\Asset\Environment\Environment;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\Map;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\MapFactory;
use CPSIT\FrontendAssetHandler\Asset\Environment\Map\Pair;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\PassthroughTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\SlugTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\StaticTransformer;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\TransformerInterface;
use CPSIT\FrontendAssetHandler\Asset\Environment\Transformer\VersionTransformer;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedClassException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedTypeException;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use Exception;
use Generator;

/**
 * MapFactoryTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class MapFactoryTest extends ContainerAwareTestCase
{
    private MapFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(MapFactory::class);
    }

    /**
     * @test
     *
     * @dataProvider createDefaultReturnsDefaultMapDataProvider
     */
    public function createDefaultReturnsDefaultMap(?string $version, Map $expected): void
    {
        self::assertEquals($expected, MapFactory::createDefault($version));
    }

    /**
     * @test
     */
    public function createEmptyReturnsEmptyMap(): void
    {
        self::assertEquals(new Map([]), MapFactory::createEmpty());
    }

    /**
     * @test
     */
    public function createFromArrayThrowsExceptionOnInvalidTransformerConfiguration(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "foo" is missing or invalid.');

        /* @phpstan-ignore-next-line */
        $this->subject->createFromArray(['foo' => null]);
    }

    /**
     * @test
     */
    public function createFromArrayThrowsExceptionOnInvalidTransformerType(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1623867663);
        $this->expectExceptionMessage('Configuration for key "foo/transformer" is missing or invalid.');

        $this->subject->createFromArray([
            'foo' => [
                'transformer' => '',
            ],
        ]);
    }

    /**
     * @test
     */
    public function createFromArrayThrowsExceptionOnMissingTransformerClass(): void
    {
        $this->expectException(UnsupportedClassException::class);
        $this->expectExceptionCode(1623911858);
        $this->expectExceptionMessage('The given class "Foo\\Baz" is either not available or not supported.');

        /* @phpstan-ignore-next-line */
        $subject = new MapFactory([
            'foo' => 'Foo\\Baz',
        ]);
        $subject->createFromArray([
            'foo' => [
                'transformer' => 'foo',
            ],
        ]);
    }

    /**
     * @test
     */
    public function createFromArrayThrowsExceptionOnInvalidTransformerClass(): void
    {
        $this->expectException(UnsupportedClassException::class);
        $this->expectExceptionCode(1623911858);
        $this->expectExceptionMessage('The given class "Exception" is either not available or not supported.');

        /* @phpstan-ignore-next-line */
        $subject = new MapFactory([
            'foo' => Exception::class,
        ]);
        $subject->createFromArray([
            'foo' => [
                'transformer' => 'foo',
            ],
        ]);
    }

    /**
     * @test
     */
    public function createFromArrayThrowsExceptionOnUnsupportedTransformerType(): void
    {
        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionCode(1624618683);
        $this->expectExceptionMessage('The given type "baz" is not supported by this factory.');

        $this->subject->createFromArray([
            'foo' => [
                'transformer' => 'baz',
            ],
        ]);
    }

    /**
     * @test
     */
    public function createFromArrayReturnsMapFromGivenConfigArray(): void
    {
        $config = [
            'main' => [
                'transformer' => StaticTransformer::getName(),
                'options' => [
                    'value' => Environment::Stable->value,
                ],
            ],
            'develop' => [
                'transformer' => PassthroughTransformer::getName(),
            ],
            '1.x-dev' => 'preview',
        ];

        $expected = new Map([
            new Pair('main', new StaticTransformer(Environment::Stable->value)),
            new Pair('develop', new PassthroughTransformer()),
            new Pair('1.x-dev', new StaticTransformer('preview')),
        ]);

        self::assertEquals($expected, $this->subject->createFromArray($config));
    }

    /**
     * @return Generator<string, array{string|null, Map}>
     */
    public function createDefaultReturnsDefaultMapDataProvider(): Generator
    {
        $latestTransformer = new StaticTransformer(Environment::Latest->value);
        $passthroughTransformer = new PassthroughTransformer();
        $defaultMap = fn (TransformerInterface $stableTransformer): Map => new Map([
            new Pair('main', $stableTransformer),
            new Pair('master', $stableTransformer),
            new Pair('develop', $latestTransformer),
            new Pair('release/*', $latestTransformer),
            new Pair('feature/*', new SlugTransformer('fe-{slug}')),
            new Pair('preview', $passthroughTransformer),
            new Pair('integration', $passthroughTransformer),
            new Pair('renovate/*', $latestTransformer),
            new Pair('/^v?\\d+\\.\\d+\\.\\d+$/', $passthroughTransformer),
        ]);

        yield 'no version' => [null, $defaultMap(new StaticTransformer(Environment::Stable->value))];
        yield 'with version' => ['1.0.0', $defaultMap(new VersionTransformer('1.0.0'))];
    }
}
