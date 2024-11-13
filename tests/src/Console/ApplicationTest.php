<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2024 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests\Console;

use CPSIT\FrontendAssetHandler as Src;
use Generator;
use PHPUnit\Framework;
use Symfony\Component\Console\Tester\ApplicationTester;

use function dirname;

/**
 * ApplicationTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Console\Application::class)]
final class ApplicationTest extends Framework\TestCase
{
    private ApplicationTester $applicationTester;

    public function setUp(): void
    {
        $application = new Src\Console\Application();
        $application->setAutoExit(false);

        $this->applicationTester = new ApplicationTester($application);
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('applicationAddsConfigOptionToAllAvailableCommandsDataProvider')]
    public function applicationAddsConfigOptionToAllAvailableCommands(string $command): void
    {
        $this->applicationTester->run([$command, '--help']);

        self::assertStringContainsString('--config', $this->applicationTester->getDisplay());
    }

    #[Framework\Attributes\Test]
    public function applicationAddsCurrentVersionOutputToAssetCommands(): void
    {
        $this->applicationTester->run([
            'command' => 'config',
            '--config' => dirname(__DIR__).'/Fixtures/JsonFiles/assets.json',
        ]);

        self::assertStringContainsString(
            'Running Frontend Asset Handler dev-',
            $this->applicationTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function applicationAddsClearCacheCommand(): void
    {
        $this->applicationTester->run(['clear-cache']);

        self::assertStringContainsString(
            'Container cache was successfully flushed.',
            $this->applicationTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function applicationAddsAssetCommands(): void
    {
        $this->applicationTester->run([
            'command' => 'config',
            '--config' => dirname(__DIR__).'/Fixtures/JsonFiles/assets.json',
        ]);

        self::assertSame(2, $this->applicationTester->getStatusCode());
        self::assertStringContainsString(
            'The configuration path must not be empty.',
            $this->applicationTester->getDisplay(),
        );
    }

    #[Framework\Attributes\Test]
    public function listCommandImplicitlyAddsAssetCommands(): void
    {
        $this->applicationTester->run(['list']);

        $output = $this->applicationTester->getDisplay();

        self::assertStringContainsString('clear-cache', $output);
        self::assertStringContainsString('config', $output);
        self::assertStringContainsString('fetch', $output);
        self::assertStringContainsString('init', $output);
        self::assertStringContainsString('inspect', $output);
    }

    #[Framework\Attributes\Test]
    public function commandHelpImplicitlyAddsCommand(): void
    {
        $this->applicationTester->run(['fetch', '--help']);

        self::assertStringContainsString('fetch [options] [--] [<branch>]', $this->applicationTester->getDisplay());
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function applicationAddsConfigOptionToAllAvailableCommandsDataProvider(): Generator
    {
        yield 'clear-cache' => ['clear-cache'];
        yield 'config' => ['config'];
        yield 'fetch' => ['fetch'];
        yield 'init' => ['init'];
        yield 'inspect' => ['inspect'];
    }
}
