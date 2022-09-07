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

namespace CPSIT\FrontendAssetHandler\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message;

/**
 * ClientMockTrait.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
trait ClientMockTrait
{
    protected Handler\MockHandler $mockHandler;

    /**
     * @var array<int, array{request: Message\RequestInterface}>
     */
    protected array $requestContainer = [];

    protected function getPreparedClient(callable $middleware = null): Client
    {
        $this->mockHandler = new Handler\MockHandler();
        $this->requestContainer = [];

        $handlerStack = HandlerStack::create($this->mockHandler);
        $history = Middleware::history($this->requestContainer);
        $handlerStack->push($history);

        if (null !== $middleware) {
            $handlerStack->push($middleware);
        }

        return new Client(['handler' => $handlerStack]);
    }

    protected function assertLastRequestMatchesUrl(string $url): void
    {
        $lastRequest = $this->getLastRequest();

        self::assertSame($url, (string) $lastRequest->getUri());
    }

    protected function getLastRequest(): Message\RequestInterface
    {
        $lastRequest = $this->mockHandler->getLastRequest();

        self::assertInstanceOf(Message\RequestInterface::class, $lastRequest, 'Last mocked request is invalid');

        return $lastRequest;
    }

    protected function enqueueResponse(Message\ResponseInterface $response, int $times = 1): void
    {
        $this->mockHandler->append(...array_fill(0, $times, $response));
    }
}
