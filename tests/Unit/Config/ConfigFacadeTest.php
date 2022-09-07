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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;
use Symfony\Component\Filesystem;

use function dirname;
use function sys_get_temp_dir;

/**
 * ConfigFacadeTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ConfigFacadeTest extends Tests\Unit\ContainerAwareTestCase
{
    private Config\ConfigFacade $subject;
    private Filesystem\Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = $this->container->get(Config\ConfigFacade::class);
        $this->filesystem = new Filesystem\Filesystem();
    }

    /**
     * @test
     */
    public function loadLoadsConfigWithSupportedLoader(): void
    {
        $filePath = dirname(__DIR__).'/Fixtures/JsonFiles/assets.json';
        $expected = new Config\Config(
            [
                'frontend-assets' => [
                    [
                        'source' => [
                            'type' => 'dummy',
                            'url' => 'https://www.example.com/assets/{environment}.tar.gz',
                            'revision-url' => 'https://www.example.com/assets/{environment}/REVISION',
                        ],
                        'target' => [
                            'type' => 'dummy',
                            'path' => 'foo',
                        ],
                        'environments' => [
                            'map' => [
                                'foo' => 'foo',
                            ],
                        ],
                        'vcs' => [
                            'type' => 'dummy',
                            'foo' => 'foo',
                        ],
                    ],
                    [
                        'source' => [
                            'type' => 'dummy',
                            'url' => 'https://www.example.com/assets/{environment}.tar.gz',
                            'revision-url' => 'https://www.example.com/assets/{environment}/REVISION',
                        ],
                        'target' => [
                            'type' => 'dummy',
                            'path' => 'baz',
                        ],
                        'environments' => [
                            'map' => [
                                'baz' => 'baz',
                            ],
                        ],
                        'vcs' => [
                            'type' => 'dummy',
                            'baz' => 'baz',
                        ],
                    ],
                ],
            ],
            $filePath,
        );

        self::assertEquals($expected, $this->subject->load($filePath));
    }

    /**
     * @test
     */
    public function loadThrowsExceptionIfNoSupportedConfigLoaderIsAvailable(): void
    {
        $this->expectExceptionObject(Exception\UnprocessableConfigFileException::create(__FILE__));

        $this->subject->load(__FILE__);
    }

    /**
     * @test
     */
    public function writeWritesConfigWithSupportedLoader(): void
    {
        $filePath = $this->filesystem->tempnam(sys_get_temp_dir(), 'asset_handler_test_');
        $this->filesystem->rename($filePath, $filePath .= '.json');
        $this->filesystem->dumpFile($filePath, '{"frontend-assets":[]}');

        $config = new Config\Config(['frontend-assets' => ['foo' => 'baz']], $filePath);

        self::assertTrue($this->subject->write($config));
        self::assertJsonStringEqualsJsonFile($filePath, '{"frontend-assets":{"foo":"baz"}}');

        $this->filesystem->remove($filePath);
    }

    /**
     * @test
     */
    public function writeThrowsExceptionIfNoSupportedConfigWriterIsAvailable(): void
    {
        $config = new Config\Config([], __FILE__);

        $this->expectExceptionObject(Exception\UnprocessableConfigFileException::create(__FILE__));

        $this->subject->write($config);
    }
}
