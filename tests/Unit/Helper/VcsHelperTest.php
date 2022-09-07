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
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem;
use Symfony\Component\Process;

/**
 * VcsHelperTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsHelperTest extends TestCase
{
    private ?string $temporaryDirectory = null;

    /**
     * @var array<string, string>
     */
    private array $backedUpEnvironmentVariables = [];

    protected function setUp(): void
    {
        $this->backedUpEnvironmentVariables = getenv();

        // Unset environment variables used in CI context to simulate
        // clean state for Git-related test scenarios
        $this->unsetEnvironmentVariable('GITHUB_ACTIONS');
        $this->unsetEnvironmentVariable('GITLAB_CI');
        $this->unsetEnvironmentVariable('CI_COMMIT_REF_NAME');
        $this->unsetEnvironmentVariable('FRONTEND_ASSETS_BRANCH');
    }

    /**
     * @test
     */
    public function getCurrentBranchReturnsNullIfBranchCannotBeDetermined(): void
    {
        // Create temporary directory
        $temporaryDirectory = $this->createTemporaryDirectory();

        // Go to temporary directory
        $previousLocation = getcwd() ?: './';
        chdir($temporaryDirectory);

        self::assertNull(Helper\VcsHelper::getCurrentBranch());

        // Go back to original location
        chdir($previousLocation);
    }

    /**
     * @test
     *
     * @dataProvider getCurrentBranchReturnsBranchNameFromEnvironmentVariablesDataProvider
     *
     * @param array<string, string> $variables
     */
    public function getCurrentBranchReturnsBranchNameFromEnvironmentVariables(array $variables, ?string $expected): void
    {
        // Create temporary directory
        $temporaryDirectory = $this->createTemporaryDirectory();

        // Go to temporary directory
        $previousLocation = getcwd() ?: './';
        chdir($temporaryDirectory);

        // Create environment variables
        foreach ($variables as $name => $value) {
            $this->setEnvironmentVariable($name, $value);
        }

        self::assertSame($expected, Helper\VcsHelper::getCurrentBranch());

        // Unset environment variables
        foreach (array_keys($variables) as $name) {
            $this->unsetEnvironmentVariable($name);
        }

        // Go back to original location
        chdir($previousLocation);
    }

    /**
     * @test
     *
     * @dataProvider getCurrentBranchReturnsBranchNameFromCiVariablesDataProvider
     *
     * @param array<string, string> $variables
     */
    public function getCurrentBranchReturnsBranchNameFromCiVariables(array $variables, ?string $expected): void
    {
        if (false !== getenv('CI')) {
            $this->markTestSkipped('Unable to execute this test in CI context.');
        }

        // Create temporary directory
        $temporaryDirectory = $this->createTemporaryDirectory();

        // Go to temporary directory
        $previousLocation = getcwd() ?: './';
        chdir($temporaryDirectory);

        // Create environment variables
        foreach ($variables as $name => $value) {
            $this->setEnvironmentVariable($name, $value);
        }

        self::assertSame($expected, Helper\VcsHelper::getCurrentBranch());

        // Unset environment variables
        foreach (array_keys($variables) as $name) {
            $this->unsetEnvironmentVariable($name);
        }

        // Go back to original location
        chdir($previousLocation);
    }

    /**
     * @test
     */
    public function getCurrentBranchReturnsCurrentlyCheckedOutGitBranch(): void
    {
        // Create temporary directory
        $temporaryDirectory = $this->createTemporaryDirectory();

        // Go to temporary directory
        $previousLocation = getcwd() ?: './';
        chdir($temporaryDirectory);

        // Initialize Git repository
        $initProcess = new Process\Process(['git', 'init']);
        $initProcess->run();
        $newBranchProcess = new Process\Process(['git', 'checkout', '-b', 'test']);
        $newBranchProcess->run();

        self::assertSame('test', Helper\VcsHelper::getCurrentBranch());

        // Go back to original location
        chdir($previousLocation);
    }

    /**
     * @return \Generator<string, array{array<string, string>, string|null}>
     */
    public function getCurrentBranchReturnsBranchNameFromEnvironmentVariablesDataProvider(): Generator
    {
        yield 'no relevant environment variables' => [[], null];
        yield 'relevant environment variables' => [['FRONTEND_ASSETS_BRANCH' => 'foo'], 'foo'];
    }

    /**
     * @return \Generator<string, array{array<string, string>, string|null}>
     */
    public function getCurrentBranchReturnsBranchNameFromCiVariablesDataProvider(): Generator
    {
        yield 'no CI context' => [[], null];
        yield 'faulty CI context' => [['GITLAB_CI' => 'true'], null];
        yield 'default CI context' => [['GITLAB_CI' => 'true', 'CI_COMMIT_REF_NAME' => 'foo'], 'foo'];
    }

    protected function tearDown(): void
    {
        foreach ($this->backedUpEnvironmentVariables as $key => $value) {
            $this->setEnvironmentVariable($key, $value);
        }

        $this->removeTemporaryDirectory();
    }

    private function createTemporaryDirectory(): string
    {
        $filesystem = new Filesystem\Filesystem();
        $this->temporaryDirectory = $filesystem->tempnam(sys_get_temp_dir(), 'asset_handler_test_');
        $filesystem->remove($this->temporaryDirectory);
        $filesystem->mkdir($this->temporaryDirectory);

        return $this->temporaryDirectory;
    }

    private function removeTemporaryDirectory(): void
    {
        $filesystem = new Filesystem\Filesystem();

        if (null !== $this->temporaryDirectory && $filesystem->exists($this->temporaryDirectory)) {
            $filesystem->remove($this->temporaryDirectory);
        }
    }

    private function setEnvironmentVariable(string $name, mixed $value): void
    {
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
    }

    private function unsetEnvironmentVariable(string $name): void
    {
        putenv($name);
        unset($_ENV[$name]);
    }
}
