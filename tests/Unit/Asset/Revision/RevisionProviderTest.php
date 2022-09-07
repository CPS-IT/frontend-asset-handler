<?php

declare(strict_types=1);

/*
 * This file is part of the Composer package "cpsit/frontend-asset-handler".
 *
 * Copyright (C) 2021 Elias Häußler <e.haeussler@familie-redlich.de>
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

namespace CPSIT\FrontendAssetHandler\Tests\Unit\Asset\Revision;

use CPSIT\FrontendAssetHandler\Asset\Definition\Source;
use CPSIT\FrontendAssetHandler\Asset\Definition\Target;
use CPSIT\FrontendAssetHandler\Asset\Revision\Revision;
use CPSIT\FrontendAssetHandler\Asset\Revision\RevisionProvider;
use CPSIT\FrontendAssetHandler\Exception\UnsupportedDefinitionException;
use CPSIT\FrontendAssetHandler\Tests\Unit\ClientMockTrait;
use CPSIT\FrontendAssetHandler\Tests\Unit\ContainerAwareTestCase;
use CPSIT\FrontendAssetHandler\Tests\Unit\Fixtures\Classes\DummyAssetDefinition;
use Exception;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Filesystem\Filesystem;

/**
 * RevisionProviderTest.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class RevisionProviderTest extends ContainerAwareTestCase
{
    use ClientMockTrait;

    private RevisionProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RevisionProvider($this->getPreparedClient(), $this->container->get(Filesystem::class));
    }

    /**
     * @test
     */
    public function getRevisionThrowsExceptionForUnsupportedAssetDefinition(): void
    {
        $definition = new DummyAssetDefinition([]);

        $this->expectException(UnsupportedDefinitionException::class);
        $this->expectExceptionCode(1624636359);
        $this->expectExceptionMessageMatches('/^The given asset definition "[^"]+" is not supported\.$/');

        $this->subject->getRevision($definition);
    }

    /**
     * @test
     */
    public function getRevisionReturnsSourceRevisionOfCustomRevisionUrl(): void
    {
        $definition = new Source(['revision-url' => 'https://www.example.com']);

        $response = new Response();
        $stream = $response->getBody();
        $stream->write('1234567890');
        $stream->rewind();
        $this->mockHandler->append($response);

        $expected = new Revision('1234567890');
        $actual = $this->subject->getRevision($definition);

        self::assertInstanceOf(Revision::class, $actual);
        self::assertEquals($expected, $actual);
        $this->assertLastRequestMatchesUrl('https://www.example.com');
    }

    /**
     * @test
     */
    public function getRevisionReturnsSourceRevisionFromRevisionUrl(): void
    {
        $definition = new Source([
            'type' => 'dummy',
            'environment' => 'latest',
            'revision-url' => 'https://www.example.com/assets/{environment}/revision.txt',
        ]);

        $response = new Response();
        $stream = $response->getBody();
        $stream->write('1234567890');
        $stream->rewind();
        $this->mockHandler->append($response);

        $expected = new Revision('1234567890');
        $actual = $this->subject->getRevision($definition);

        self::assertInstanceOf(Revision::class, $actual);
        self::assertEquals($expected, $actual);
        $this->assertLastRequestMatchesUrl('https://www.example.com/assets/latest/revision.txt');
    }

    /**
     * @test
     */
    public function getRevisionReturnsNullIfAllRevisionUrlsAreErroneous(): void
    {
        $definition = new Source([
            'type' => 'dummy',
            'environment' => 'latest',
            'revision-url' => 'https://www.example.com/assets/{environment}/revision.txt',
        ]);

        // Mock exception for revision URL
        $this->mockHandler->append(new Exception('dummy'));

        self::assertNull($this->subject->getRevision($definition));
    }

    /**
     * @test
     */
    public function getRevisionReturnsNullIfNoRevisionUrlIsGiven(): void
    {
        $definition = new Source([
            'type' => 'dummy',
            'environment' => 'latest',
        ]);

        self::assertNull($this->subject->getRevision($definition));
    }

    /**
     * @test
     */
    public function getRevisionReturnsTargetRevisionDerivedFromLocalRevisionFile(): void
    {
        $definition = new Target([
            'type' => 'dummy',
            'path' => 'tests/Unit/Fixtures/AssetFiles',
            'revision-file' => 'revision.txt',
        ]);

        $expected = new Revision('1234567890');
        $actual = $this->subject->getRevision($definition);

        self::assertInstanceOf(Revision::class, $actual);
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function getRevisionReturnsNullIfLocalRevisionFileDoesNotExist(): void
    {
        $definition = new Target([
            'type' => 'dummy',
            'path' => 'foo',
        ]);

        self::assertNull($this->subject->getRevision($definition));
    }
}
