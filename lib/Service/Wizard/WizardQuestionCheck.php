<?php

/**
 * What is wrong with one wizard question
 *
 * The per-question half of the save-time check: the key, the label, the type,
 * what that type needs (choices, a register and schema, a readable data path)
 * and the condition. {@see WizardDefinitionValidator} runs it per question and
 * collects the errors by key.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

/**
 * Structural validation of one wizard question.
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 */
class WizardQuestionCheck {

	/**
	 * The question types a wizard may use.
	 *
	 * @var string[]
	 */
	public const TYPES = ['text', 'choice', 'date', 'registerObject'];

	/**
	 * What is wrong with one question.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param string               $key      Its trimmed key.
	 * @param array<string, true>  $seen     The keys of the questions before it.
	 *
	 * @return string|null The error, or null.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function error(array $question, string $key, array $seen): ?string {
		if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', $key) !== 1) {
			return 'A question needs a key of letters, digits, - or _, starting with a letter.';
		}

		if (isset($seen[$key]) === true) {
			return "The key \"{$key}\" is used twice.";
		}

		if (trim((string) ($question['label'] ?? '')) === '') {
			return 'A question needs a label.';
		}

		$type = (string) ($question['type'] ?? '');
		if (in_array($type, self::TYPES, true) === false) {
			return 'The type must be text, choice, date or registerObject.';
		}

		$typeError = $this->typeError(question: $question, type: $type);
		if ($typeError !== null) {
			return $typeError;
		}

		return $this->conditionError(condition: ($question['condition'] ?? null), seen: $seen);

	}//end error()

	/**
	 * What a question of this type is missing.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param string               $type     Its type.
	 *
	 * @return string|null The error, or null.
	 */
	private function typeError(array $question, string $type): ?string {
		if ($type === 'choice') {
			$choicesError = $this->choicesError(choices: ($question['choices'] ?? null));
			if ($choicesError !== null) {
				return $choicesError;
			}
		}

		if ($type === 'registerObject'
			&& (trim((string) ($question['register'] ?? '')) === '' || trim((string) ($question['schema'] ?? '')) === '')
		) {
			return 'A register object question needs a register and a schema.';
		}

		$path = trim((string) ($question['mapsTo'] ?? ''));
		if ($path !== '' && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $path) !== 1) {
			return 'The data path must be names joined by dots, such as applicant.name.';
		}

		return null;

	}//end typeError()

	/**
	 * What is wrong with a choice question's choices.
	 *
	 * @param mixed $choices The choices, if any.
	 *
	 * @return string|null The error, or null.
	 */
	private function choicesError(mixed $choices): ?string {
		if (is_array($choices) === false || $choices === []) {
			return 'A choice question needs choices.';
		}

		foreach ($choices as $choice) {
			if (is_array($choice) === false || trim((string) ($choice['value'] ?? '')) === '' || trim((string) ($choice['label'] ?? '')) === '') {
				return 'Every choice needs a value and a label.';
			}
		}

		return null;

	}//end choicesError()

	/**
	 * What is wrong with a condition.
	 *
	 * @param mixed               $condition The condition, if any.
	 * @param array<string, true> $seen      The keys of the questions before it.
	 *
	 * @return string|null The error, or null.
	 */
	private function conditionError(mixed $condition, array $seen): ?string {
		if ($condition === null || $condition === []) {
			return null;
		}

		if (is_array($condition) === false) {
			return 'The condition is not readable.';
		}

		$onKey = trim((string) ($condition['questionKey'] ?? ''));
		if ($onKey === '' || isset($seen[$onKey]) === false) {
			return "The condition refers to \"{$onKey}\", which is not an earlier question.";
		}

		if (in_array((string) ($condition['operator'] ?? ''), WizardConditions::OPERATORS, true) === false) {
			return 'The condition operator must be equals, notEquals or answered.';
		}

		return null;

	}//end conditionError()
}//end class
