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
	public const TYPES = WizardQuestionCheck::TYPES;

	/**
	 * Constructor.
	 *
	 * @param WizardQuestionCheck $questionCheck What is wrong with one question.
	 * @param WizardBindingCheck  $bindingCheck  Warnings for questions that do not fit the template.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WizardQuestionCheck $questionCheck=new WizardQuestionCheck(),
		private readonly WizardBindingCheck $bindingCheck=new WizardBindingCheck(),
	) {

	}//end __construct()

	/**
	 * Check a definition.
	 *
	 * @param array<string, mixed>      $wizard          The definition as it will be saved.
	 * @param array<string, mixed>|null $template        The template it fronts, for the binding check.
	 * @param string[]|null             $boundProperties The property names of the template's bound schema, when known.
	 *
	 * @return array{errors: array<string, string>, warnings: string[]} Errors by question key (or field name) and warnings.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
	 */
	public function check(array $wizard, ?array $template=null, ?array $boundProperties=null): array {
		$errors = $this->fieldErrors(wizard: $wizard);

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
			$name = $this->nameOf(key: $key, index: $index);

			$error = $this->questionCheck->error(question: $question, key: $key, seen: $seen);
			if ($error !== null) {
				$errors[$name] = $error;
			}

			if ($key !== '') {
				$seen[$key] = true;
			}

			$warnings = array_merge(
				$warnings,
				$this->bindingCheck->warnings(question: $question, name: $name, template: $template, boundProperties: $boundProperties)
			);
		}

		$collisions = $this->bindingCheck->collisionWarnings(questions: array_values(array_filter($questions, 'is_array')));

		return ['errors' => $errors, 'warnings' => array_merge($warnings, $collisions)];

	}//end check()

	/**
	 * Errors for the wizard's own required fields.
	 *
	 * @param array<string, mixed> $wizard The definition.
	 *
	 * @return array<string, string> Errors by field name.
	 */
	private function fieldErrors(array $wizard): array {
		$errors = [];
		foreach (['name', 'templateId'] as $field) {
			if (trim((string) ($wizard[$field] ?? '')) === '') {
				$errors[$field] = 'Required.';
			}
		}

		return $errors;

	}//end fieldErrors()

	/**
	 * How an error names a question: its key, or its position when it has none.
	 *
	 * @param string $key   The trimmed key.
	 * @param int    $index The zero-based position.
	 *
	 * @return string The name.
	 */
	private function nameOf(string $key, int $index): string {
		if ($key === '') {
			return 'question ' . ($index + 1);
		}

		return $key;

	}//end nameOf()
}//end class
