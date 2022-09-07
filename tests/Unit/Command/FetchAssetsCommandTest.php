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

use CPSIT\FrontendAssetHandler\Command;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Tests;

/**
 * FetchAssetsCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class FetchAssetsCommandTest extends Tests\Unit\CommandTesterAwareTestCase
{
    private Tests\Unit\Fixtures\Classes\DummyProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = $this->container->get(Tests\Unit\Fixtures\Classes\DummyProvider::class);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfGivenBranchIsEmpty(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedEnvironmentException::forMissingVCS());

        $this->commandTester->execute([
            'branch' => '',
        ]);
    }

    /**
     * @test
     */
    public function executeThrowsExceptionIfGivenBranchIsInvalid(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedEnvironmentException::forInvalidEnvironment('   '));

        $this->commandTester->execute([
            'branch' => '   ',
        ]);
    }

    /**
     * @test
     */
    public function executeFailsIfAssetsCannotBeDownloaded(): void
    {
        $this->provider->expectedExceptions[] = Exception\DownloadFailedException::create('foo', 'baz');

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
        self::assertStringContainsString('Project branch: main', $output);
        self::assertStringContainsString('Processing of asset definition #1', $output);
        self::assertStringContainsString('An error occurred while downloading "foo" to "baz".', $output);
        self::assertStringContainsString('Processing of asset definition #2', $output);
        self::assertStringContainsString('An error occurred while downloading "foo" to "baz".', $output);
        self::assertStringContainsString('Asset environment: stable', $output);
        self::assertStringContainsString('Command finished with errors.', $output);
    }

    /**
     * @test
     */
    public function executeFallsBackToLatestAssetsIfAssetsCannotBeDownloadedAndFailsafeOptionIsGiven(): void
    {
        $this->provider->expectedExceptions[] = Exception\DownloadFailedException::create('foo', 'baz');

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
        self::assertStringContainsString('Project branch: main', $output);
        self::assertStringContainsString('Processing of asset definition #1', $output);
        self::assertStringContainsString('Error while fetching assets, falling back to latest assets.', $output);
        self::assertStringContainsString('Processing of asset definition #2', $output);
        self::assertStringContainsString('Assets successfully downloaded', $output);
        self::assertStringContainsString('Asset environment: stable', $output);
    }

    protected static function getCoveredCommand(): string
    {
        return Command\FetchAssetsCommand::class;
    }
}
