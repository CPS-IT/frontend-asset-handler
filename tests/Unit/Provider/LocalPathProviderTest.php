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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Provider;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\TemporaryAsset;
use CPSIT\FrontendAssetHandler\Exception\FilesystemFailureException;
use CPSIT\FrontendAssetHandler\Exception\MissingConfigurationException;
use CPSIT\FrontendAssetHandler\Helper\FilesystemHelper;
use CPSIT\FrontendAssetHandler\Provider\LocalPathProvider;
use CPSIT\FrontendAssetHandler\Tests\Unit\BufferedConsoleOutput;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

use function realpath;
use function sprintf;
use function sys_get_temp_dir;

/**
 * LocalPathProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class LocalPathProviderTest extends ContainerAwareTestCase
{
    private Source $source;
    private BufferedConsoleOutput $output;
    private LocalPathProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = new Source([
            'url' => '{temp}.tar.gz',
            'command' => 'tar -czf "{url}" -C "{cwd}/tests/Unit/Fixtures" "AssetFiles"',
        ]);
        $this->output = new BufferedConsoleOutput();
        $this->subject = new LocalPathProvider($this->container->get(Filesystem::class));
        $this->subject->setOutput($this->output);
    }

    /**
     * @test
     */
    public function fetchAssetRunsConfiguredCommandToGenerateAssetSourceFile(): void
    {
        $actual = $this->subject->fetchAsset($this->source);

        self::assertInstanceOf(TemporaryAsset::class, $actual);
        self::assertFileExists($actual->getTempFile());
        self::assertArchiveHasChild('AssetFiles/assets_0.zip', $actual->getTempFile());
        self::assertArchiveHasChild('AssetFiles/assets_1.tar', $actual->getTempFile());
        self::assertArchiveHasChild('AssetFiles/assets_2.tar.gz', $actual->getTempFile());
        self::assertArchiveHasChild('AssetFiles/revision.txt', $actual->getTempFile());
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfConfiguredCommandCannotBeExecutedSuccessfully(): void
    {
        $this->source['command'] = 'foo baz';

        $this->expectExceptionObject(FilesystemFailureException::forFailedCommandExecution('foo baz'));

        try {
            $this->subject->fetchAsset($this->source);
        } catch (Exception $exception) {
            self::assertMatchesRegularExpression('/foo: (command )?not found/', $this->output->fetch());

            throw $exception;
        }
    }

    /**
     * @test
     */
    public function fetchAssetThrowsExceptionIfSourceFileDoesNotExist(): void
    {
        unset($this->source['command']);

        $this->expectException(FilesystemFailureException::class);
        $this->expectExceptionMessageMatches('/^The file "[^"]+" is invalid or not supported\\.$/');
        $this->expectExceptionCode(1670866771);

        $this->subject->fetchAsset($this->source);
    }

    /**
     * @test
     */
    public function fetchAssetReturnsTemporaryAsset(): void
    {
        $actual = $this->subject->fetchAsset($this->source);

        self::assertInstanceOf(TemporaryAsset::class, $actual);
        self::assertFileExists($actual->getTempFile());
    }

    /**
     * @test
     */
    public function getAssetUrlThrowsExceptionIfSourceUrlIsNotConfigured(): void
    {
        unset($this->source['url']);

        $this->expectExceptionObject(MissingConfigurationException::forKey('source/url'));

        $this->subject->getAssetUrl($this->source);
    }

    /**
     * @test
     */
    public function getAssetUrlProperlyResolvesRootPathInAssetUrl(): void
    {
        $this->source['url'] = '{cwd}/foo.tar.gz';

        $expected = FilesystemHelper::getWorkingDirectory().'/foo.tar.gz';

        self::assertSame($expected, $this->subject->getAssetUrl($this->source));
    }

    /**
     * @test
     */
    public function getAssetUrlProperlyResolvesTemporaryFilenameInAssetUrl(): void
    {
        $expected = realpath(sys_get_temp_dir());

        self::assertIsString($expected);
        self::assertStringStartsWith($expected, $this->subject->getAssetUrl($this->source));
    }

    /**
     * @test
     */
    public function getAssetUrlCanHandleRelativePaths(): void
    {
        $this->source['url'] = 'foo.tar.gz';

        $expected = FilesystemHelper::getWorkingDirectory().'/foo.tar.gz';

        self::assertSame($expected, $this->subject->getAssetUrl($this->source));
    }

    private static function assertArchiveHasChild(string $path, string $archive): void
    {
        self::assertFileExists($archive, sprintf('Archive "%s" does not exist.', $archive));
        self::assertFileExists(
            'phar://'.$archive.'/'.$path,
            sprintf('Path "%s" does not exist in archive "%s".', $path, $archive)
        );
    }
}
