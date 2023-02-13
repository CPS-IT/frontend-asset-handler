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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Handler\AssetHandler;
use CPSIT\FrontendAssetHandler\Tests;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console;

/**
 * HandlerConfigStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class HandlerConfigStepTest extends Tests\Unit\ContainerAwareTestCase
{
    use InitializationRequestTrait;
    use Tests\Unit\InteractiveConsoleInputTrait;

    private Console\Output\BufferedOutput $output;
    private Config\Initialization\Step\HandlerConfigStep $subject;
    private Config\Initialization\InitializationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new Console\Output\BufferedOutput();
        $this->subject = $this->container->get(Config\Initialization\Step\HandlerConfigStep::class);
        $this->subject->setOutput($this->output);
        $this->request = $this->createRequest($this->subject);
    }

    #[Test]
    public function executeUsesDefaultHandlerTypeIfUserEntersNothing(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs([''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            AssetHandler::getName(),
            $this->request->getConfig()['frontend-assets'][0]['handler'],
        );
    }

    #[Test]
    public function executeShowsErrorIfGivenHandlerTypeIsNotSupported(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['foo'], $input);

        self::assertTrue($this->subject->execute($this->request));

        $output = $this->output->fetch();

        self::assertStringContainsString('Value "foo" is invalid', $output);
        self::assertSame(
            AssetHandler::getName(),
            $this->request->getConfig()['frontend-assets'][0]['handler'],
        );
    }
}
