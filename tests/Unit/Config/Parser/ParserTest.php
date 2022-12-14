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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Parser;

use CPSIT\FrontendAssetHandler\Config\Config;
use CPSIT\FrontendAssetHandler\Config\Parser\Parser;
use CPSIT\FrontendAssetHandler\Config\Parser\ParserInstructions;
use CPSIT\FrontendAssetHandler\Exception\InvalidConfigurationException;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use Generator;

/**
 * ParserTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ParserTest extends ContainerAwareTestCase
{
    private Parser $subject;
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Parser::class);
        $this->config = new Config([
            'frontend-assets' => [
                [
                    'source' => [
                        'type' => 'foo',
                        'url' => '%env(FOO)%',
                    ],
                    'target' => [
                        'type' => 'foo',
                        'path' => 'foo',
                    ],
                    'environments' => [
                        'map' => [
                            'foo' => 'foo',
                        ],
                    ],
                ],
                [
                    'source' => [
                        'type' => 'baz',
                        'url' => '%env(BAZ)%',
                    ],
                    'target' => [
                        'type' => 'baz',
                        'path' => 'baz',
                    ],
                    'vcs' => [
                        'type' => 'baz',
                        'baz' => 'baz',
                    ],
                ],
            ],
        ], 'foo');
    }

    /**
     * @test
     */
    public function parseThrowsExceptionIfConfigIsInvalid(): void
    {
        $config = new Config([], 'foo');
        $instructions = new ParserInstructions($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1643113965);

        $this->subject->parse($instructions);
    }

    /**
     * @test
     *
     * @dataProvider parseReturnsUnprocessedConfigDataProvider
     *
     * @param list<string>                                                   $requiredKeys
     * @param array<string, array<int, array<string, array<string, mixed>>>> $expected
     */
    public function parseReturnsUnprocessedConfig(array $requiredKeys, array $expected): void
    {
        $instructions = new ParserInstructions($this->config);
        $instructions->processValues(false);

        foreach ($requiredKeys as $requiredKey) {
            $instructions->requireKey($requiredKey);
        }

        self::assertSame($expected, $this->subject->parse($instructions)->asArray());
    }

    /**
     * @test
     */
    public function parseReturnsProcessedConfig(): void
    {
        $instructions = new ParserInstructions($this->config);

        putenv('FOO=foo');
        putenv('BAZ=baz');

        $expected = [
            'frontend-assets' => [
                [
                    'source' => [
                        'type' => 'foo',
                        'url' => 'foo',
                    ],
                    'target' => [
                        'type' => 'foo',
                        'path' => 'foo',
                    ],
                    'environments' => [
                        'map' => [
                            'foo' => 'foo',
                        ],
                    ],
                ],
                [
                    'source' => [
                        'type' => 'baz',
                        'url' => 'baz',
                    ],
                    'target' => [
                        'type' => 'baz',
                        'path' => 'baz',
                    ],
                    'vcs' => [
                        'type' => 'baz',
                        'baz' => 'baz',
                    ],
                ],
            ],
        ];

        self::assertSame($expected, $this->subject->parse($instructions)->asArray());

        putenv('FOO');
        putenv('BAZ');
    }

    /**
     * @return \Generator<string, array{list<string>, array<string, array<int, array<string, array<string, mixed>>>>}>
     */
    public function parseReturnsUnprocessedConfigDataProvider(): Generator
    {
        yield 'no required keys' => [
            [],
            [
                'frontend-assets' => [
                    [
                        'source' => [
                            'type' => 'foo',
                            'url' => '%env(FOO)%',
                        ],
                        'target' => [
                            'type' => 'foo',
                            'path' => 'foo',
                        ],
                        'environments' => [
                            'map' => [
                                'foo' => 'foo',
                            ],
                        ],
                    ],
                    [
                        'source' => [
                            'type' => 'baz',
                            'url' => '%env(BAZ)%',
                        ],
                        'target' => [
                            'type' => 'baz',
                            'path' => 'baz',
                        ],
                        'vcs' => [
                            'type' => 'baz',
                            'baz' => 'baz',
                        ],
                    ],
                ],
            ],
        ];
        yield 'required keys "source" and "target' => [
            [
                'source',
                'target',
            ],
            [
                'frontend-assets' => [
                    [
                        'source' => [
                            'type' => 'foo',
                            'url' => '%env(FOO)%',
                        ],
                        'target' => [
                            'type' => 'foo',
                            'path' => 'foo',
                        ],
                    ],
                    [
                        'source' => [
                            'type' => 'baz',
                            'url' => '%env(BAZ)%',
                        ],
                        'target' => [
                            'type' => 'baz',
                            'path' => 'baz',
                        ],
                    ],
                ],
            ],
        ];
        yield 'required key "environments"' => [
            [
                'environments',
            ],
            [
                'frontend-assets' => [
                    [
                        'environments' => [
                            'map' => [
                                'foo' => 'foo',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        yield 'required key "vcs"' => [
            [
                'vcs',
            ],
            [
                'frontend-assets' => [
                    1 => [
                        'vcs' => [
                            'type' => 'baz',
                            'baz' => 'baz',
                        ],
                    ],
                ],
            ],
        ];
    }
}
