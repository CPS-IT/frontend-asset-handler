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

namespace CPSIT\FrontendAssetHandler\Config\Initialization\Step;

use CPSIT\FrontendAssetHandler\ChattyInterface;
use CPSIT\FrontendAssetHandler\Config;
use CPSIT\FrontendAssetHandler\Console;
use CPSIT\FrontendAssetHandler\Exception;
use CPSIT\FrontendAssetHandler\Helper;
use CPSIT\FrontendAssetHandler\Traits;
use Symfony\Component\Console as SymfonyConsole;

use function array_diff;
use function array_keys;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;

/**
 * BaseStep.
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-3.0-or-later
 *
 * @internal
 */
abstract class BaseStep implements StepInterface, ChattyInterface
{
    use Traits\OutputAwareTrait;

    protected readonly Console\Input\Validator\ValidatorFactory $validatorFactory;
    protected readonly SymfonyConsole\Helper\QuestionHelper $questionHelper;

    public function __construct()
    {
        $this->validatorFactory = new Console\Input\Validator\ValidatorFactory();
        $this->questionHelper = new SymfonyConsole\Helper\QuestionHelper();
    }

    /**
     * @var array<string, Console\Input\Validator\ValidatorInterface>
     */
    protected array $validators = [];

    /**
     * @param string|non-empty-list<string>|null $validator
     */
    protected function createQuestion(
        string $label,
        mixed $default = null,
        string $alternative = null,
        string|array $validator = null,
    ): SymfonyConsole\Question\Question {
        $label = $this->decorateQuestionLabel($label, $default, $alternative);
        $question = new SymfonyConsole\Question\Question($label, $default);

        if (null === $validator) {
            return $question;
        }

        return $question->setValidator($this->createValidator($validator)->validate(...));
    }

    /**
     * @param array<string, string> $choices
     */
    protected function createChoiceQuestion(
        string $label,
        array $choices,
        mixed $default = null,
    ): SymfonyConsole\Question\ChoiceQuestion {
        $label = $this->decorateQuestionLabel($label, $default);

        return new SymfonyConsole\Question\ChoiceQuestion($label, $choices, $default);
    }

    protected function decorateQuestionLabel(string $label, mixed $default, string $alternative = null): string
    {
        $label = sprintf('▶ <info>%s</info>', $label);

        if (null !== $default) {
            $label .= ' ['.$default.($alternative ? '/'.$alternative : '').']';
        }

        return $label.': ';
    }

    protected function askBooleanQuestion(
        Config\Initialization\InitializationRequest $request,
        string $label,
    ): bool {
        $input = $this->getInput($request);
        $question = $this->createQuestion($label, 'Y', 'n');

        return str_starts_with(strtolower((string) $this->questionHelper->ask($input, $this->output, $question)), 'y');
    }

    /**
     * @param array<string, mixed>               $additionalVariables
     * @param string|non-empty-list<string>|null $validator
     */
    protected function askForPlaceholderVariables(
        Config\Initialization\InitializationRequest $request,
        mixed $string,
        string $label,
        array &$additionalVariables,
        mixed $default = null,
        string|array $validator = null,
    ): void {
        if (!is_string($string)) {
            return;
        }

        $placeholders = array_diff(
            Helper\StringHelper::extractPlaceholders($string),
            ['environment', 'revision', 'temp', 'cwd'],
            array_keys($request->getOptions()),
            array_keys($additionalVariables),
        );

        foreach ($placeholders as $placeholder) {
            $this->askForAdditionalVariable(
                $request,
                sprintf($label, $placeholder),
                $placeholder,
                $additionalVariables,
                $default,
                $validator,
            );
        }
    }

    /**
     * @param array<string, mixed>               $additionalVariables
     * @param string|non-empty-list<string>|null $validator
     */
    protected function askForAdditionalVariable(
        Config\Initialization\InitializationRequest $request,
        string $label,
        string $additionalVariable,
        array &$additionalVariables,
        mixed $default = null,
        string|array $validator = null,
    ): void {
        $additionalValue = $this->questionHelper->ask(
            $this->getInput($request),
            $this->output,
            $this->createQuestion($label, $default, validator: $validator),
        );

        if (null !== $additionalValue) {
            $additionalVariables[$additionalVariable] = $additionalValue;
        }
    }

    protected function getInput(Config\Initialization\InitializationRequest $request): SymfonyConsole\Input\InputInterface
    {
        return $request->getInput()
            ?? throw new SymfonyConsole\Exception\RuntimeException('Input cannot be determined.', 1663088092);
    }

    /**
     * @param string|non-empty-list<string> $type
     *
     * @throws Exception\UnsupportedTypeException
     */
    protected function createValidator(string|array $type): Console\Input\Validator\ValidatorInterface
    {
        if (is_array($type)) {
            return $this->validatorFactory->getForAll($type);
        }

        return $this->validatorFactory->get($type);
    }
}
