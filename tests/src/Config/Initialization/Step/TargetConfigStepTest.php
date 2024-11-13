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

namespace CPSIT\FrontendAssetHandler\Tests\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Processor;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console;

/**
 * TargetConfigStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class TargetConfigStepTest extends Tests\ContainerAwareTestCase
{
    use InitializationRequestTrait;
    use Tests\InteractiveConsoleInputTrait;

    private Console\Output\BufferedOutput $output;
    private Config\Initialization\Step\TargetConfigStep $subject;
    private Config\Initialization\InitializationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new Console\Output\BufferedOutput();
        $this->subject = $this->container->get(Config\Initialization\Step\TargetConfigStep::class);
        $this->subject->setOutput($this->output);
        $this->request = $this->createRequest($this->subject);
    }

    #[Test]
    public function executeUsesDefaultTargetTypeIfUserEntersNothing(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'foo', 'baz', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Processor\FileArchiveProcessor::getName(),
            $this->request->getConfig()['frontend-assets'][0]['target']['type'],
        );
    }

    #[Test]
    public function executeShowsErrorIfGivenTargetTypeIsNotSupported(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['foo', '', 'foo', 'baz', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Processor\FileArchiveProcessor::getName(),
            $this->request->getConfig()['frontend-assets'][0]['target']['type'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Value "foo" is invalid', $output);
    }

    #[Test]
    public function executeAsksForBaseArchivePathForTargetTypeArchive(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'foo', 'baz', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'baz',
            $this->request->getConfig()['frontend-assets'][0]['target']['base'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Base archive path', $output);
    }

    #[Test]
    public function executeDoesNotAskForBaseArchivePathIfTargetTypeIsNotArchive(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs([Processor\ExistingAssetProcessor::getName(), 'foo', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertArrayNotHasKey('base', $this->request->getConfig()['frontend-assets'][0]['target']);

        $output = $this->output->fetch();

        self::assertStringNotContainsString('Base archive path', $output);
    }

    #[Test]
    public function executeShowErrorIfGivenTargetConfigExtraIsNotValidJson(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'foo', 'baz', '', 'foo', ''], $input);

        self::assertTrue($this->subject->execute($this->request));

        $output = $this->output->fetch();

        self::assertStringContainsString('JSON is invalid.', $output);
    }

    #[Test]
    public function executeMergesTargetConfigExtraWithAdditionalVariables(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'foo', 'baz', '', '{"foo":"baz"}'], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'baz',
            $this->request->getConfig()['frontend-assets'][0]['target']['base'],
        );
        self::assertSame(
            'baz',
            $this->request->getConfig()['frontend-assets'][0]['target']['foo'],
        );
    }
}
