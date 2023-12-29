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

namespace CPSIT\FrontendAssetHandler\Command;

use CPSIT\FrontendAssetHandler\DependencyInjection;
use Symfony\Component\Console;

/**
 * ClearCacheCommand.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 */
final class ClearCacheCommand extends Console\Command\Command
{
    private const SUCCESSFUL = 0;

    public function __construct(
        private readonly DependencyInjection\Cache\ContainerCache $cache,
    ) {
        parent::__construct('clear-cache');
    }

    protected function configure(): void
    {
        $this->setDescription('Flush container caches if dependencies have changed.');
        $this->setAliases(['cc']);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $output->writeln(
            sprintf('Container path: <comment>%s</comment>', $this->cache->getPath()),
            Console\Output\OutputInterface::VERBOSITY_VERBOSE,
        );

        $this->cache->flushAll();

        $io = new Console\Style\SymfonyStyle($input, $output);
        $io->success('Container cache was successfully flushed.');

        return self::SUCCESSFUL;
    }
}
