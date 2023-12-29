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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Helper;

use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Tests;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process;

/**
 * VcsHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsHelperTest extends TestCase
{
    use Tests\Unit\EnvironmentVariablesTrait;
    use Tests\Unit\FunctionExecutorTrait;

    protected function setUp(): void
    {
        $this->backUpEnvironmentVariables();

        // Unset environment variables used in CI context to simulate
        // clean state for Git-related test scenarios
        $this->unsetEnvironmentVariable('GITHUB_ACTIONS');
        $this->unsetEnvironmentVariable('GITLAB_CI');
        $this->unsetEnvironmentVariable('CI_COMMIT_REF_NAME');
        $this->unsetEnvironmentVariable('FRONTEND_ASSETS_BRANCH');
    }

    #[Test]
    public function getCurrentBranchReturnsNullIfBranchCannotBeDetermined(): void
    {
        $this->executeInDirectory(
            static fn () => self::assertNull(Helper\VcsHelper::getCurrentBranch()),
        );
    }

    /**
     * @param array<string, string> $variables
     */
    #[Test]
    #[DataProvider('getCurrentBranchReturnsBranchNameFromEnvironmentVariablesDataProvider')]
    public function getCurrentBranchReturnsBranchNameFromEnvironmentVariables(array $variables, ?string $expected): void
    {
        $this->executeInDirectory(
            function () use ($variables, $expected) {
                // Create environment variables
                foreach ($variables as $name => $value) {
                    $this->setEnvironmentVariable($name, $value);
                }

                self::assertSame($expected, Helper\VcsHelper::getCurrentBranch());

                // Unset environment variables
                foreach (array_keys($variables) as $name) {
                    $this->unsetEnvironmentVariable($name);
                }
            },
        );
    }

    /**
     * @param array<string, string> $variables
     */
    #[Test]
    #[DataProvider('getCurrentBranchReturnsBranchNameFromCiVariablesDataProvider')]
    public function getCurrentBranchReturnsBranchNameFromCiVariables(array $variables, ?string $expected): void
    {
        if (false !== getenv('CI')) {
            self::markTestSkipped('Unable to execute this test in CI context.');
        }

        $this->executeInDirectory(
            function () use ($variables, $expected) {
                // Create environment variables
                foreach ($variables as $name => $value) {
                    $this->setEnvironmentVariable($name, $value);
                }

                self::assertSame($expected, Helper\VcsHelper::getCurrentBranch());

                // Unset environment variables
                foreach (array_keys($variables) as $name) {
                    $this->unsetEnvironmentVariable($name);
                }
            },
        );
    }

    #[Test]
    public function getCurrentBranchReturnsCurrentlyCheckedOutGitBranch(): void
    {
        $this->executeInDirectory(
            static function () {
                // Initialize Git repository
                $initProcess = new Process\Process(['git', 'init']);
                $initProcess->run();
                $newBranchProcess = new Process\Process(['git', 'checkout', '-b', 'test']);
                $newBranchProcess->run();

                self::assertSame('test', Helper\VcsHelper::getCurrentBranch());
            },
        );
    }

    /**
     * @return Generator<string, array{array<string, string>, string|null}>
     */
    public static function getCurrentBranchReturnsBranchNameFromEnvironmentVariablesDataProvider(): Generator
    {
        yield 'no relevant environment variables' => [[], null];
        yield 'relevant environment variables' => [['FRONTEND_ASSETS_BRANCH' => 'foo'], 'foo'];
    }

    /**
     * @return Generator<string, array{array<string, string>, string|null}>
     */
    public static function getCurrentBranchReturnsBranchNameFromCiVariablesDataProvider(): Generator
    {
        yield 'no CI context' => [[], null];
        yield 'faulty CI context' => [['GITLAB_CI' => 'true'], null];
        yield 'default CI context' => [['GITLAB_CI' => 'true', 'CI_COMMIT_REF_NAME' => 'foo'], 'foo'];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
    }
}
