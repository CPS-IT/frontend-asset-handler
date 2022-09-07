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

$finder = \PhpCsFixer\Finder::create()
    ->ignoreVCSIgnored(true)
    ->in(__DIR__)
    ->append([__DIR__.'/bin/frontend-assets'])
;

$config = new \PhpCsFixer\Config();
$config->setFinder($finder);
$config->setRiskyAllowed(true);

return $config->setRules([
    '@PSR2' => true,
    '@Symfony' => true,
    'native_function_invocation' => true,
    'global_namespace_import' => ['import_classes' => true, 'import_functions' => true],
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
    'ordered_imports' => ['imports_order' => ['const', 'class', 'function']],
]);
