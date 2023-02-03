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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Command;

use CPSIT\FrontendAssetHandler\Asset;
use CPSIT\FrontendAssetHandler\Command;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Strategy;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console;

use function dirname;

/**
 * FetchAssetsCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FetchAssetsCommandTest extends Tests\Unit\CommandTesterAwareTestCase
{
    use Tests\Unit\FunctionExecutorTrait;

    private Tests\Unit\Fixtures\Classes\DummyHandler $handler;
    private Asset\ProcessedAsset $processedAsset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeCommandTester(
            dirname(__DIR__).'/Fixtures/JsonFiles/assets.json',
            [
                Processor\ExistingAssetProcessor::class => $this->container->get(Tests\Unit\Fixtures\Classes\DummyProcessor::class),
                Asset\Revision\RevisionProvider::class => $this->container->get(Tests\Unit\Fixtures\Classes\DummyRevisionProvider::class),
            ],
        );

        $this->handler = $this->container->get(Tests\Unit\Fixtures\Classes\DummyHandler::class);
        $this->processedAsset = new Asset\ProcessedAsset(
            new Asset\Definition\Source([]),
            new Asset\Definition\Target([]),
            'foo',
        );
    }

    #[Test]
    public function executeThrowsExceptionIfGivenBranchIsMissing(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedEnvironmentException::forMissingVCS());

        $this->executeInDirectory(
            function () {
                $command = $this->container->get(Command\FetchAssetsCommand::class);
                $commandTester = new Console\Tester\CommandTester($command);

                $commandTester->execute([]);
            }
        );
    }

    #[Test]
    public function executeThrowsExceptionIfGivenBranchIsInvalid(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedEnvironmentException::forInvalidEnvironment('   '));

        $this->commandTester->execute([
            'branch' => '   ',
        ]);
    }

    #[Test]
    public function executeFailsIfAssetsCannotBeDownloaded(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = Exception\DownloadFailedException::create('foo', 'baz');

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Assets successfully downloaded to foo.', $output);
        self::assertStringContainsString('An error occurred while downloading "foo" to "baz".', $output);
        self::assertStringContainsString('Command finished with errors.', $output);
    }

    #[Test]
    public function executeFallsBackToLatestAssetsIfAssetsCannotBeDownloadedAndFailsafeOptionIsGiven(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = Exception\DownloadFailedException::create('foo', 'baz');
        $this->handler->returnQueue[] = $this->processedAsset;

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
                '--failsafe' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Error while fetching assets, falling back to latest assets.', $output);
        self::assertStringContainsString('Assets successfully downloaded to foo.', $output);
    }

    #[Test]
    public function executeFallsBackToLatestAssetsAndPassesOnSelectedStrategy(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = Exception\DownloadFailedException::create('foo', 'baz');
        $this->handler->returnQueue[] = $this->processedAsset;

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
                '--failsafe' => true,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(0, $exitCode);
        self::assertSame(Strategy\Strategy::FetchExisting, $this->handler->lastStrategy);
    }

    #[Test]
    public function executeSucceedsWithWarningsIfAssetsAreAlreadyDownloaded(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = new Asset\ExistingAsset(
            new Asset\Definition\Source([]),
            new Asset\Definition\Target([]),
            'foo',
            new Asset\Revision\Revision('1234567890'),
        );

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'Assets of revision 1234567 are already downloaded. Use -f to re-download them.',
            $output,
        );
        self::assertStringContainsString('Command finished with warnings.', $output);
    }

    #[Test]
    public function executeFailsWithAdditionalWarningIfAssetsAreAlreadyDownloaded(): void
    {
        $this->handler->returnQueue[] = Exception\DownloadFailedException::create('foo', 'baz');
        $this->handler->returnQueue[] = new Asset\ExistingAsset(
            new Asset\Definition\Source([]),
            new Asset\Definition\Target([]),
            'foo',
            new Asset\Revision\Revision('1234567890'),
        );

        $exitCodeFails = $this->commandTester->execute(
            [
                'branch' => 'main',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );

        self::assertSame(1, $exitCodeFails);

        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('An error occurred while downloading "foo" to "baz".', $output);
        self::assertStringContainsString(
            'Assets of revision 1234567 are already downloaded. Use -f to re-download them.',
            $output,
        );
        self::assertStringContainsString('Command finished with errors and warnings.', $output);
    }

    #[Test]
    public function executeFailsIfAssetsCannotBeProcessed(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(1, $exitCode);
        self::assertStringContainsString(
            'Error while fetching assets: The asset handler was unable to handle this asset source.',
            $output,
        );
    }

    #[Test]
    public function executeFetchesAndProcessesExistingAssets(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = $this->processedAsset;

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Project branch: main', $output);
        self::assertStringContainsString('Asset environment: stable', $output);
        self::assertStringContainsString('Processing of asset definition #1', $output);
        self::assertStringContainsString('Processing of asset definition #2', $output);
        self::assertStringContainsString('Assets successfully downloaded to foo.', $output);
    }

    #[Test]
    public function executeSuccessfullyFetchesAndProcessesAllAssets(): void
    {
        $this->handler->returnQueue[] = $this->processedAsset;
        $this->handler->returnQueue[] = $this->processedAsset;

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Project branch: main', $output);
        self::assertStringContainsString('Asset environment: stable', $output);
        self::assertStringContainsString('Processing of asset definition #1', $output);
        self::assertStringContainsString('Processing of asset definition #2', $output);
        self::assertStringContainsString('Assets successfully downloaded to foo.', $output);
    }

    protected static function getCoveredCommand(): string
    {
        return Command\FetchAssetsCommand::class;
    }
}
