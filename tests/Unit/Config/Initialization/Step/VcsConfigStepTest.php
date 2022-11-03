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
use CPSIT\FrontendAssetHandler\Tests;
use CPSIT\FrontendAssetHandler\Vcs;
use Symfony\Component\Console;

/**
 * VcsConfigStepTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class VcsConfigStepTest extends Tests\Unit\ContainerAwareTestCase
{
    use InitializationRequestTrait;
    use Tests\Unit\InteractiveConsoleInputTrait;

    private Console\Output\BufferedOutput $output;
    private Config\Initialization\Step\VcsConfigStep $subject;
    private Config\Initialization\InitializationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new Console\Output\BufferedOutput();
        $this->subject = $this->container->get(Config\Initialization\Step\VcsConfigStep::class);
        $this->subject->setOutput($this->output);
        $this->request = $this->createRequest($this->subject);
    }

    /**
     * @test
     */
    public function executeDoesNothingIfUserSkipsConfiguration(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['no'], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame([], $this->request->getConfig()->asArray());

        $output = $this->output->fetch();

        self::assertStringContainsString('Add VCS configuration?', $output);
    }

    /**
     * @test
     */
    public function executeSkipsUserConfirmationIfVcsTypeRequestOptionIsGiven(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['', '', 'foo', '123', ''], $input);

        $this->request->setOption('vcs-type', Vcs\GitlabVcsProvider::getName());

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Vcs\GitlabVcsProvider::getName(),
            $this->request->getConfig()['frontend-assets'][0]['vcs']['type'],
        );

        $output = $this->output->fetch();

        self::assertStringNotContainsString('Add VCS configuration?', $output);
    }

    /**
     * @test
     */
    public function executeShowsErrorIfGivenTargetTypeIsNotSupported(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', 'foo', Vcs\GitlabVcsProvider::getName(), '', 'foo', '123', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            Vcs\GitlabVcsProvider::getName(),
            $this->request->getConfig()['frontend-assets'][0]['vcs']['type'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Value "foo" is invalid', $output);
    }

    /**
     * @test
     */
    public function executeAsksForBaseUrlForVcsTypeGitlab(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GitlabVcsProvider::getName(), 'https://www.example.com', 'foo', '123', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'https://www.example.com',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['base-url'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Base URL', $output);
    }

    /**
     * @test
     */
    public function executeAsksForAccessTokenForVcsTypeGitlab(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GitlabVcsProvider::getName(), '', 'foo', '123', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'foo',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['access-token'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Access token', $output);
    }

    /**
     * @test
     */
    public function executeAsksForProjectIdForVcsTypeGitlab(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GitlabVcsProvider::getName(), '', 'foo', '123', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            123,
            $this->request->getConfig()['frontend-assets'][0]['vcs']['project-id'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Project ID', $output);
    }

    /**
     * @test
     */
    public function executeAsksForAccessTokenForVcsTypeGithub(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GithubVcsProvider::getName(), 'foo', 'foo/baz', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'foo',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['access-token'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Access token', $output);
    }

    /**
     * @test
     */
    public function executeAsksForRepositoryForVcsTypeGithub(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GithubVcsProvider::getName(), 'foo', 'foo/baz', ''], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'foo/baz',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['repository'],
        );

        $output = $this->output->fetch();

        self::assertStringContainsString('Repository (<owner>/<name>)', $output);
    }

    /**
     * @test
     */
    public function executeShowErrorIfGivenVcsConfigExtraIsNotValidJson(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GitlabVcsProvider::getName(), '', 'foo', '123', 'foo', ''], $input);

        self::assertTrue($this->subject->execute($this->request));

        $output = $this->output->fetch();

        self::assertStringContainsString('JSON is invalid.', $output);
    }

    /**
     * @test
     */
    public function executeMergesVcsConfigExtraWithAdditionalVariables(): void
    {
        $input = $this->request->getInput();

        self::assertInstanceOf(Console\Input\StreamableInputInterface::class, $input);

        self::setInputs(['yes', Vcs\GitlabVcsProvider::getName(), '', 'foo', '123', '{"foo":"baz"}'], $input);

        self::assertTrue($this->subject->execute($this->request));
        self::assertSame(
            'https://gitlab.com',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['base-url'],
        );
        self::assertSame(
            'foo',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['access-token'],
        );
        self::assertSame(
            123,
            $this->request->getConfig()['frontend-assets'][0]['vcs']['project-id'],
        );
        self::assertSame(
            'baz',
            $this->request->getConfig()['frontend-assets'][0]['vcs']['foo'],
        );
    }
}
