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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Processor;

use CPSIT\FrontendAssetHandler\Asset\Asset;
use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\TemporaryAsset;
use CPSIT\FrontendAssetHandler\Exception\FilesystemFailureException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedAssetException;
use CPSIT\FrontendAssetHandler\Processor\FileArchiveProcessor;
use CPSIT\FrontendAssetHandler\Tests\Unit\BufferedConsoleOutput;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyFileArchiveProcessor;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyFilesystem;
use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function dirname;
use function sprintf;

/**
 * FileArchiveProcessorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FileArchiveProcessorTest extends TestCase
{
    private BufferedConsoleOutput $output;
    private DummyFilesystem $filesystem;
    private DummyFileArchiveProcessor $subject;

    protected function setUp(): void
    {
        $this->output = new BufferedConsoleOutput();
        $this->filesystem = new DummyFilesystem();

        $childProcessor = new FileArchiveProcessor($this->filesystem);
        $childProcessor->setOutput($this->output);

        $this->subject = new DummyFileArchiveProcessor($childProcessor);
    }

    #[Test]
    public function processAssetThrowsExceptionIfAssetIsNotTemporary(): void
    {
        $asset = new Asset(new Source([]));

        $this->expectException(UnsupportedAssetException::class);
        $this->expectExceptionCode(1623922009);

        $this->subject->processAsset($asset);
    }

    #[Test]
    public function processAssetThrowsExceptionIfTemporaryFileIsUnsupported(): void
    {
        $asset = new TemporaryAsset(new Source([]), 'foo.baz');

        $this->expectException(UnsupportedAssetException::class);
        $this->expectExceptionCode(1623922009);

        $this->subject->processAsset($asset);
    }

    #[Test]
    public function processAssetThrowsExceptionIfExtractionPreparationFails(): void
    {
        $this->filesystem->expectedExceptionStack = ['remove'];

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1628093163);

        try {
            $this->subject->processAsset(self::getTemporaryAsset());
        } catch (Exception $exception) {
            $output = $this->output->fetch();
            self::assertStringContainsString('Truncating target directory... Failed', $output);

            throw $exception;
        }
    }

    #[Test]
    public function processAssetThrowsExceptionIfArchiveExtractionFails(): void
    {
        $this->subject->shouldOpenInvalidArchive = true;

        $this->expectException(FilesystemFailureException::class);
        $this->expectExceptionCode(1624040854);

        try {
            $this->subject->processAsset(self::getTemporaryAsset());
        } catch (Exception $exception) {
            $output = $this->output->fetch();
            self::assertStringContainsString('Truncating target directory... Done', $output);
            self::assertStringContainsString('Extracting downloaded assets... Failed', $output);

            throw $exception;
        }
    }

    #[Test]
    public function processAssetThrowsExceptionIfFileMirroringFails(): void
    {
        $this->filesystem->expectedExceptionStack = ['mirror'];

        $this->expectException(Exception::class);
        $this->expectExceptionCode(1628093996);

        try {
            $this->subject->processAsset(self::getTemporaryAsset());
        } catch (Exception $exception) {
            $output = $this->output->fetch();
            self::assertStringContainsString('Truncating target directory... Done', $output);
            self::assertStringContainsString('Extracting downloaded assets... Done', $output);
            self::assertStringContainsString('Removing temporary files... Failed', $output);

            throw $exception;
        }
    }

    #[Test]
    #[DataProvider('processAssetExtractsArchiveDataProvider')]
    public function processAssetExtractsArchive(TemporaryAsset $asset): void
    {
        $targetPath = $asset->getTarget()?->getPath();
        self::assertNotNull($targetPath);

        // Ensure target path is empty
        if (is_dir($targetPath)) {
            $this->filesystem->remove($targetPath);
        }

        // Ensure decompressed files are removed
        if (str_ends_with($asset->getTempFile(), '.tar.gz')) {
            $decompressedFile = substr_replace($asset->getTempFile(), '.tar', -7);
            if (file_exists($decompressedFile)) {
                $this->filesystem->remove($decompressedFile);
            }
        }

        $this->subject->processAsset($asset);

        self::assertTrue($this->filesystem->exists($targetPath));
        self::assertTrue($this->filesystem->exists($targetPath.'/asset.txt'));

        $output = $this->output->fetch();
        self::assertStringContainsString('Truncating target directory... Done', $output);
        self::assertStringContainsString('Extracting downloaded assets... Done', $output);
        self::assertStringContainsString('Removing temporary files... Done', $output);
    }

    #[Test]
    public function getAssetPathThrowsExceptionIfAssetTargetIsNotDefined(): void
    {
        $asset = new Asset(new Source([]));

        $this->expectException(UnsupportedAssetException::class);
        $this->expectExceptionCode(1623922009);
        $this->expectExceptionMessage(
            sprintf('The asset with source "%s" and target "" is not supported.', $asset->getSource())
        );

        $this->subject->getAssetPath($asset);
    }

    #[Test]
    public function getAssetPathReturnsAssetsTargetPath(): void
    {
        $asset = new Asset(new Source([]), new Target(['path' => '/foo/baz']));

        self::assertSame('/foo/baz', $this->subject->getAssetPath($asset));
    }

    /**
     * @return Generator<string, array{TemporaryAsset}>
     */
    public static function processAssetExtractsArchiveDataProvider(): Generator
    {
        foreach (['zip', 'tar', 'tar.gz'] as $key => $type) {
            yield $type => [self::getTemporaryAsset($key, $type)];
        }
    }

    private static function getTemporaryAsset(int $index = 0, string $type = 'zip'): TemporaryAsset
    {
        $temporaryFile = sprintf(dirname(__DIR__).'/Fixtures/AssetFiles/assets_%d.%s', $index, $type);
        $source = new Source([]);
        $target = new Target([
            'path' => dirname(__DIR__, 3).'/.Build/var/tests/assets',
            'base' => '',
        ]);
        $asset = new TemporaryAsset($source, $temporaryFile);
        $asset->setTarget($target);

        return $asset;
    }
}
