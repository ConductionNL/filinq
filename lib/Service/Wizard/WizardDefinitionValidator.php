<?php

/**
 * Save-time checks of a wizard definition
 *
 * A wizard that cannot run correctly is refused when it is saved, with an
 * error per question: duplicate keys, a choice without choices, a register
 * object question without register and schema, and a condition on a question
 * that does not exist or comes later. When the template it fronts is bound to
 * a register and schema, a question that points elsewhere is a warning, not a
 * refusal: the template may still be bound later.
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
 * Structural validation of wizard definitions.
 */
class WizardDefinitionValidator {

	/**
	 * The question types a wizard may use.
	 *
	 * @var string[]
	 */
	public const TYPES = ['text', 'choice', 'date', 'registerObject'];

	/**
	 * Check a definition.
	 *
	 * @param array<string, mixed>      $wizard        The definition as it will be saved.
	 * @param array<string, mixed>|null $template      The template it fronts, for the binding check.
	 * @param string[]|null             $boundProperties The property names of the template's bound schema, when known.
	 *
	 * @return array{errors: array<string, string>, warnings: string[]} Errors by question key (or field name) and warnings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function check(array $wizard, ?array $template=null, ?array $boundProperties=null): array {
		$errors = [];
		foreach (['name', 'templateId'] as $field) {
			if (trim((string) ($wizard[$field] ?? '')) === '') {
				$errors[$field] = 'Required.';
			}
		}

		$questions = ($wizard['questions'] ?? null);
		if (is_array($questions) === false || $questions === []) {
			$errors['questions'] = 'A wizard needs at least one question.';
			return ['errors' => $errors, 'warnings' => []];
		}

		$seen = [];
		$warnings = [];
		foreach (array_values($questions) as $index => $question) {
			if (is_array($question) === false) {
				$question = [];
			}

			$key = trim((string) ($question['key'] ?? ''));
			$name = $key;
			if ($name === '') {
				$name = 'question ' . ($index + 1);
			}

			$error = $this->questionError(question: $question, key: $key, seen: $seen);
			if ($error !== null) {
				$errors[$name] = $error;
			}

			if ($key !== '') {
				$seen[$key] = true;
			}

			$warnings = array_merge($warnings, $this->bindingWarnings(question: $question, name: $name, template: $template, boundProperties: $boundProperties));
		}

		return ['errors' => $errors, 'warnings' => array_merge($warnings, $this->collisionWarnings(questions: array_values(array_filter($questions, 'is_array'))))];

	}//end check()

	/**
	 * Warnings for a scalar answer written under a picked object's key.
	 *
	 * Ad-hoc data wins per top-level key (DataResolverService merges with
	 * array_merge), so an answer mapped to `dossier.title` replaces the whole
	 * dossier a register object question picked. The author is told; the
	 * wizard still saves.
	 *
	 * @param array<int, array<string, mixed>> $questions The questions.
	 *
	 * @return string[] The warnings.
	 */
	private function collisionWarnings(array $questions): array {
		$picked = [];
		foreach ($questions as $question) {
			if (($question['type'] ?? '') === 'registerObject' && trim((string) ($question['schema'] ?? '')) !== '') {
				$picked[(string) $question['schema']] = (string) ($question['key'] ?? '');
			}
		}

		$warnings = [];
		foreach ($questions as $question) {
			$path = trim((string) ($question['mapsTo'] ?? ''));
			$top = explode('.', $path)[0];
			if (($question['type'] ?? '') !== 'registerObject' && $path !== '' && isset($picked[$top]) === true) {
				$warnings[] = "Question \"{$question['key']}\" writes into {$top}, which replaces the whole {$top} picked in question \"{$picked[$top]}\" in the template data.";
			}
		}

		return $warnings;

	}//end collisionWarnings()

	/**
	 * What is wrong with one question.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param string               $key      Its trimmed key.
	 * @param array<string, true>  $seen     The keys of the questions before it.
	 *
	 * @return string|null The error, or null.
	 */
	private function questionError(array $question, string $key, array $seen): ?string {
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

	}//end questionError()

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
			$choices = ($question['choices'] ?? null);
			if (is_array($choices) === false || $choices === []) {
				return 'A choice question needs choices.';
			}

			foreach ($choices as $choice) {
				if (is_array($choice) === false || trim((string) ($choice['value'] ?? '')) === '' || trim((string) ($choice['label'] ?? '')) === '') {
					return 'Every choice needs a value and a label.';
				}
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

		$on = trim((string) ($condition['questionKey'] ?? ''));
		if ($on === '' || isset($seen[$on]) === false) {
			return "The condition refers to \"{$on}\", which is not an earlier question.";
		}

		if (in_array((string) ($condition['operator'] ?? ''), WizardConditions::OPERATORS, true) === false) {
			return 'The condition operator must be equals, notEquals or answered.';
		}

		return null;

	}//end conditionError()

	/**
	 * Warnings for a question that does not fit the template's binding.
	 *
	 * @param array<string, mixed>      $question        The question.
	 * @param string                    $name            How to name it.
	 * @param array<string, mixed>|null $template        The template.
	 * @param string[]|null             $boundProperties The bound schema's property names.
	 *
	 * @return string[] The warnings.
	 */
	private function bindingWarnings(array $question, string $name, ?array $template, ?array $boundProperties): array {
		$register = trim((string) ($template['boundRegister'] ?? ''));
		$schema = trim((string) ($template['boundSchema'] ?? ''));
		if ($template === null || $schema === '') {
			return [];
		}

		if (($question['type'] ?? '') === 'registerObject') {
			$fits = (string) ($question['schema'] ?? '') === $schema
				&& ($register === '' || (string) ($question['register'] ?? '') === $register);
			if ($fits === true) {
				return [];
			}

			return ["Question \"{$name}\" picks from {$question['register']}/{$question['schema']}, not the template's {$register}/{$schema}."];
		}

		$path = trim((string) ($question['mapsTo'] ?? ''));
		if ($path === '' || $boundProperties === null) {
			return [];
		}

		$segments = explode('.', $path);
		$first = $segments[0];
		if ($first === $schema && count($segments) > 1) {
			$first = $segments[1];
		}

		if (in_array($first, $boundProperties, true) === true) {
			return [];
		}

		return ["Question \"{$name}\" maps to \"{$path}\", which the template's schema {$schema} does not have."];

	}//end bindingWarnings()
}//end class
