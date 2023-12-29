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
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Tests;
use CPSIT\FrontendAssetHandler\Vcs;
use Generator;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

use function dirname;
use function preg_quote;
use function sprintf;

/**
 * InspectAssetsCommandTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class InspectAssetsCommandTest extends Tests\Unit\CommandTesterAwareTestCase
{
    use Tests\Unit\EnvironmentVariablesTrait;
    use Tests\Unit\FunctionExecutorTrait;

    private Tests\Unit\Fixtures\Classes\DummyRevisionProvider $revisionProvider;
    private Tests\Unit\Fixtures\Classes\DummyVcsProvider $vcsProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backUpEnvironmentVariables();

        // Unset environment variables used in CI context to simulate
        // clean state for Git-related test scenarios
        $this->unsetEnvironmentVariable('GITHUB_ACTIONS');
        $this->unsetEnvironmentVariable('GITLAB_CI');
        $this->unsetEnvironmentVariable('CI_COMMIT_REF_NAME');
        $this->unsetEnvironmentVariable('FRONTEND_ASSETS_BRANCH');

        $this->revisionProvider = $this->container->get(Tests\Unit\Fixtures\Classes\DummyRevisionProvider::class);
        $this->vcsProvider = new Tests\Unit\Fixtures\Classes\DummyVcsProvider();

        $this->initializeCommandTester(
            dirname(__DIR__).'/Fixtures/JsonFiles/assets.json',
            [
                Asset\Revision\RevisionProvider::class => $this->revisionProvider,
                Processor\ProcessorFactory::class => new Processor\ProcessorFactory(
                    new DependencyInjection\ServiceLocator([
                        'dummy' => fn () => new Tests\Unit\Fixtures\Classes\DummyProcessor(),
                    ]),
                ),
                Provider\ProviderFactory::class => new Provider\ProviderFactory(
                    new DependencyInjection\ServiceLocator([
                        'dummy' => fn () => new Tests\Unit\Fixtures\Classes\DummyProvider(),
                    ]),
                ),
                Vcs\VcsProviderFactory::class => new Vcs\VcsProviderFactory(
                    new DependencyInjection\ServiceLocator([
                        'dummy' => fn () => $this->vcsProvider,
                    ]),
                ),
            ],
        );
    }

    #[Test]
    public function executeThrowsExceptionIfGivenBranchIsMissing(): void
    {
        $this->expectExceptionObject(Exception\UnsupportedEnvironmentException::forMissingVCS());

        $this->executeInDirectory(
            function () {
                $command = $this->container->get(Command\InspectAssetsCommand::class);
                $commandTester = new Console\Tester\CommandTester($command);

                $commandTester->execute([]);
            },
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
    public function executeDescribesAllAssets(): void
    {
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
        self::assertStringContainsString('Inspecting asset definition #1', $output);
        self::assertStringContainsString('Inspecting asset definition #2', $output);
        self::assertMatchesRegularExpression('/Asset environment\\s+stable/', $output);
        self::assertMatchesRegularExpression('/VCS revision\s+Unknown/', $output);
        self::assertMatchesRegularExpression('/VCS url\s+https:\/\/example\.com\/assets\.git/', $output);
        self::assertMatchesRegularExpression('/Source revision\s+Unknown/', $output);
        self::assertMatchesRegularExpression('/Source url\s+https:\/\/www\.example\.com/', $output);
        self::assertMatchesRegularExpression('/Target revision\s+Unknown \(\?\)/', $output);
        self::assertMatchesRegularExpression('/Target path\s+\/tmp/', $output);
    }

    /**
     * @param list<string|null> $revisions
     */
    #[Test]
    #[DataProvider('executeUsesDifferentRevisionDiffSymbolsDataProvider')]
    public function executeUsesDifferentRevisionDiffSymbols(array $revisions, string $expected): void
    {
        $this->revisionProvider->expectedRevisions = $revisions;

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
        self::assertMatchesRegularExpression(sprintf('/Target revision\s+%s/', preg_quote($expected)), $output);
    }

    #[Test]
    public function executeWaitsForActiveDeploymentsToFinish(): void
    {
        $this->vcsProvider->expectedDeployments = [
            [
                new Vcs\Dto\Deployment(
                    new Psr7\Uri('https://www.example.com'),
                    new Asset\Revision\Revision('1234567890'),
                ),
            ],
            [],
        ];

        $exitCode = $this->commandTester->execute(
            [
                'branch' => 'main',
                '--wait-for-deployments' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ],
        );
        $output = $this->commandTester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Waiting for the Frontend assets to be deployed ...', $output);
    }

    /**
     * @return Generator<string, array{list<string|null>, string}>
     */
    public static function executeUsesDifferentRevisionDiffSymbolsDataProvider(): Generator
    {
        $revision = '1234567890';
        $outdatedRevision = '0987654321';

        yield 'no source revision' => [[$revision, null], 'Unknown (?)'];
        yield 'no target revision' => [[null, $revision], 'Unknown (?)'];
        yield 'no revisions' => [[], 'Unknown (?)'];
        yield 'up to date revision' => [[$revision, $revision], '1234567890 (✓)'];
        yield 'outdated revision' => [[$revision, $outdatedRevision], '0987654321 (↓)'];
    }

    protected static function getCoveredCommand(): string
    {
        return Command\InspectAssetsCommand::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restoreEnvironmentVariables();
    }
}
