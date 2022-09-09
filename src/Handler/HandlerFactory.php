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

namespace CPSIT\FrontendAssetHandler\Handler;

use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Traits;
use Symfony\Component\Console;
use Symfony\Component\DependencyInjection;

/**
 * HandlerFactory.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class HandlerFactory implements ChattyInterface
{
    use Traits\OutputAwareTrait;

    public function __construct(
        private readonly DependencyInjection\ServiceLocator $handlers,
    ) {
        $this->setOutput(new Console\Output\NullOutput());
    }

    /**
     * @throws Exception\UnsupportedClassException
     * @throws Exception\UnsupportedTypeException
     */
    public function get(string $type): HandlerInterface
    {
        if (!$this->has($type)) {
            throw Exception\UnsupportedTypeException::create($type);
        }

        // Fetch handler instance
        $handler = $this->handlers->get($type);

        // Validate handler instance
        if (!($handler instanceof HandlerInterface)) {
            throw Exception\UnsupportedClassException::create($handler::class);
        }

        // Pass output to handler
        if ($handler instanceof ChattyInterface) {
            $handler->setOutput($this->output);
        }

        return $handler;
    }

    public function has(string $type): bool
    {
        return $this->handlers->has($type);
    }
}
