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
use CPSIT\FrontendAssetHandler\Provider;
use CPSIT\FrontendAssetHandler\Tests;
use Symfony\Component\Console;

/**
 * SourceConfigStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class SourceConfigStepTest extends Tests\Unit\ContainerAwareTestCase
{
    use InitializationRequestTrait;
    use Tests\Unit\InteractiveConsoleInputTrait;

    private Console\Output\BufferedOutput $output;
    private Config\Initialization\Step\SourceConfigStep $subject;
    private Config\Initialization\InitializationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new Console\Output\BufferedOutput();
        $this->subject = $this->container->get(Config\Initialization\Step\SourceConfigStep::class);
        $this->subject->setOutput($this->output);
        $this->request = $this->createRequest($this->subject);
    }

    /**
     * @test
     */
    public function executeUsesDefaultSourceTypeIfUserEntersNothing(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'https://www.example.com', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Provider\HttpFileProvider::getName(),
            $this->request->getConfig()['frontend-assets'][0]['source']['type'],
        );
    }

    /**
     * @test
     */
    public function executeShowsErrorIfGivenSourceTypeIsNotSupported(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['foo', '', 'https://www.example.com', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Provider\HttpFileProvider::getName(),
            $this->request->getConfig()['frontend-assets'][0]['source']['type'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Value "foo" is invalid', $output);
    }

    /**
     * @test
     */
    public function executeAsksForPlaceholdersInGivenSourceUrl(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', 'https://www.example.com/{environment}/{foo}', 'foo', '', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'https://www.example.com/{environment}/{foo}',
            $this->request->getConfig()['frontend-assets'][0]['source']['url'],
        );
        self::assertSame(
            'foo',
            $this->request->getConfig()['frontend-assets'][0]['source']['foo'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('URL placeholder "foo" (optional)', $output);
    }

    /**
     * @test
     */
    public function executeAsksForPlaceholdersInGivenSourceRevisionUrl(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(
            ['', 'https://www.example.com/', 'https://www.example.com/{revision-file}', 'revision.txt', ''],
            $input,
        );

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'https://www.example.com/{revision-file}',
            $this->request->getConfig()['frontend-assets'][0]['source']['revision-url'],
        );
        self::assertSame(
            'revision.txt',
            $this->request->getConfig()['frontend-assets'][0]['source']['revision-file'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Revision URL placeholder "revision-file" (optional)', $output);
    }

    /**
     * @test
     */
    public function executeShowErrorIfGivenSourceConfigExtraIsNotValidJson(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(
            ['', 'https://www.example.com/', '', 'foo', ''],
            $input,
        );

        self::assertTrue($this->subject->execute($this->request));

        $output = $this->output->fetch();

        self::assertStringContainsString('JSON is invalid.', $output);
    }

    /**
     * @test
     */
    public function executeMergesSourceConfigExtraWithAdditionalVariables(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(
            ['', 'https://www.example.com/{foo}', 'foo', 'https://www.example.com/{baz}', 'baz', '{"hello":"world"}'],
            $input,
        );

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'https://www.example.com/{foo}',
            $this->request->getConfig()['frontend-assets'][0]['source']['url'],
        );
        self::assertSame(
            'https://www.example.com/{baz}',
            $this->request->getConfig()['frontend-assets'][0]['source']['revision-url'],
        );
        self::assertSame(
            'foo',
            $this->request->getConfig()['frontend-assets'][0]['source']['foo'],
        );
        self::assertSame(
            'baz',
            $this->request->getConfig()['frontend-assets'][0]['source']['baz'],
        );
        self::assertSame(
            'world',
            $this->request->getConfig()['frontend-assets'][0]['source']['hello'],
        );
    }
}
