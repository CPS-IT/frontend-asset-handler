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
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use CPSIT\FrontendAssetHandler\Exception\FilesystemFailureException;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedAssetException;
use CPSIT\FrontendAssetHandler\Processor\ExistingAssetProcessor;
use CPSIT\FrontendAssetHandler\Tests\Unit\BufferedConsoleOutput;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use PHPUnit\Framework\Attributes\Test;

use function dirname;

/**
 * ExistingAssetProcessorTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ExistingAssetProcessorTest extends ContainerAwareTestCase
{
    private BufferedConsoleOutput $output;
    private ExistingAssetProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new BufferedConsoleOutput();
        $this->subject = $this->container->get(ExistingAssetProcessor::class);
        $this->subject->setOutput($this->output);
    }

    #[Test]
    public function processAssetThrowsExceptionIfTargetPathDoesNotExist(): void
    {
        $asset = new Asset(new Source([]), new Target(['path' => 'foo']));

        $this->expectException(FilesystemFailureException::class);
        $this->expectExceptionCode(1624633845);
        $this->expectExceptionMessageMatches('/^The path "[^"]+foo" was expected to exist, but it does not\.$/');

        $this->subject->processAsset($asset);
    }

    #[Test]
    public function processAssetPrintsRevisionProvidedByAssetTarget(): void
    {
        $target = new Target([
            'path' => __DIR__,
            'revision' => new Revision('0987654321'),
        ]);
        $asset = new Asset(new Source([]), $target);

        self::assertSame(__DIR__, $this->subject->processAsset($asset));
        self::assertSame('Frontend revision: 0987654321', trim($this->output->fetch()));
    }

    #[Test]
    public function processAssetPrintsRevisionProvidedByRevisionFile(): void
    {
        $target = new Target([
            'path' => dirname(__DIR__).'/Fixtures/AssetFiles',
            'revision-file' => 'revision.txt',
        ]);
        $asset = new Asset(new Source([]), $target);

        self::assertSame(dirname(__DIR__).'/Fixtures/AssetFiles', $this->subject->processAsset($asset));
        self::assertSame('Frontend revision: 1234567890', trim($this->output->fetch()));
    }

    #[Test]
    public function processAssetDoesNotPrintRevisionIfNoRevisionIsAvailable(): void
    {
        $target = new Target([
            'path' => __DIR__,
        ]);
        $asset = new Asset(new Source([]), $target);

        self::assertSame(__DIR__, $this->subject->processAsset($asset));
        self::assertSame('', trim($this->output->fetch()));
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
}
