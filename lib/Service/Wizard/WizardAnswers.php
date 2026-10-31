<?php

/**
 * Answers of a wizard run, checked and turned into a generate request
 *
 * The server half of a run: which visible questions are unanswered or
 * answered wrongly, and how the answers become the existing generate
 * contract. A register object answer becomes one dataRef, so
 * DataResolverService fetches it exactly as a hand-written dataRef; a text,
 * choice or date answer lands in adHocData at its dotted `mapsTo` path, and
 * ad-hoc data wins over resolved data (DCS-005). Hidden answers are dropped.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

/**
 * Answer validation and translation for wizard runs.
 */
class WizardAnswers {

	/**
	 * Constructor.
	 *
	 * @param WizardConditions $conditions The skip logic.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WizardConditions $conditions,
	) {

	}//end __construct()

	/**
	 * The errors of a set of answers, by question key.
	 *
	 * @param array<string, mixed>             $wizard   The wizard definition.
	 * @param array<string, mixed>             $answers  The answers by question key.
	 * @param array<int, array<string, mixed>> $dataRefs The dataRefs of the request.
	 *
	 * @return array<string, string> Question key => what is wrong. Empty when the answers are good.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
	 */
	public function errors(array $wizard, array $answers, array $dataRefs): array {
		$questions = $this->questions(wizard: $wizard);
		$visible = array_flip($this->conditions->visibleKeys(questions: $questions, answers: $answers));
		$errors = [];
		foreach ($questions as $question) {
			$key = (string) ($question['key'] ?? '');
			if (isset($visible[$key]) === false) {
				continue;
			}

			$answer = ($answers[$key] ?? null);
			if ($this->conditions->isAnswered(answer: $answer) === false) {
				if (($question['required'] ?? false) === true) {
					$errors[$key] = 'This question needs an answer.';
				}

				continue;
			}

			$error = $this->answerError(question: $question, answer: $answer, dataRefs: $dataRefs);
			if ($error !== null) {
				$errors[$key] = $error;
			}
		}//end foreach

		return $errors;

	}//end errors()

	/**
	 * Turn the answers into dataRefs, adHocData and the wizard context.
	 *
	 * @param array<string, mixed> $wizard  The wizard definition, with `uuid` and `version` when stored.
	 * @param array<string, mixed> $answers The answers by question key.
	 *
	 * @return array{dataRefs: array<int, array<string, string>>, adHocData: array<string, mixed>,
	 *     wizardContext: array<string, mixed>}
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-3
	 */
	public function translate(array $wizard, array $answers): array {
		$questions = $this->questions(wizard: $wizard);
		$visible = array_flip($this->conditions->visibleKeys(questions: $questions, answers: $answers));
		$dataRefs = [];
		$adHocData = [];
		$kept = [];
		foreach ($questions as $question) {
			$key = (string) ($question['key'] ?? '');
			$answer = ($answers[$key] ?? null);
			if (isset($visible[$key]) === false || $this->conditions->isAnswered(answer: $answer) === false) {
				continue;
			}

			$kept[$key] = $answer;
			if (($question['type'] ?? '') === 'registerObject') {
				$dataRefs[] = [
					'register' => (string) ($question['register'] ?? ''),
					'schema' => (string) ($question['schema'] ?? ''),
					'id' => (string) $answer,
				];
				continue;
			}

			$path = trim((string) ($question['mapsTo'] ?? ''));
			if ($path !== '') {
				$this->setPath(data: $adHocData, path: $path, value: $answer);
			}
		}//end foreach

		return [
			'dataRefs' => $dataRefs,
			'adHocData' => $adHocData,
			'wizardContext' => [
				'wizardId' => (string) ($wizard['uuid'] ?? ''),
				'wizardVersion' => (string) ($wizard['version'] ?? ''),
				'answers' => $kept,
			],
		];

	}//end translate()

	/**
	 * The suggested answer of one question, or null.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param array<string, string> $ref     The entry object.
	 * @param array<string, mixed> $data     The resolved data, keyed by schema.
	 *
	 * @return mixed The suggestion.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-5
	 */
	public function suggestion(array $question, array $ref, array $data): mixed {
		if (($question['type'] ?? '') === 'registerObject') {
			$fits = (string) ($question['register'] ?? '') === $ref['register'] && (string) ($question['schema'] ?? '') === $ref['schema'];
			if ($fits === false) {
				return null;
			}

			return $ref['id'];
		}

		$path = trim((string) ($question['mapsTo'] ?? ''));
		if ($path === '') {
			return null;
		}

		$value = $this->readPath(data: $data, path: $path);
		if ($value === null) {
			// A path may also be written relative to the entry object.
			$value = $this->readPath(data: (array) ($data[$ref['schema']] ?? []), path: $path);
		}

		if (is_scalar($value) === false) {
			return null;
		}

		return $value;

	}//end suggestion()

	/**
	 * Read a dotted path from nested data.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path, such as `aanvrager.naam`.
	 *
	 * @return mixed The value, or null when the path does not resolve.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-5
	 */
	private function readPath(array $data, string $path): mixed {
		$value = $data;
		foreach (explode('.', $path) as $segment) {
			if (is_array($value) === false || array_key_exists($segment, $value) === false) {
				return null;
			}

			$value = $value[$segment];
		}

		return $value;

	}//end readPath()

	/**
	 * Write a value at a dotted path, creating the levels on the way.
	 *
	 * @param array<string, mixed> $data  The data, changed in place.
	 * @param string               $path  The dotted path.
	 * @param mixed                $value The value.
	 *
	 * @return void
	 */
	private function setPath(array &$data, string $path, mixed $value): void {
		$segments = explode('.', $path);
		$leaf     = (string) array_pop($segments);
		$node     = &$data;
		foreach ($segments as $segment) {
			if (isset($node[$segment]) === false || is_array($node[$segment]) === false) {
				$node[$segment] = [];
			}

			$node = &$node[$segment];
		}

		$node[$leaf] = $value;

	}//end setPath()

	/**
	 * What is wrong with one given answer.
	 *
	 * @param array<string, mixed>             $question The question.
	 * @param mixed                            $answer   The answer, known to be given.
	 * @param array<int, array<string, mixed>> $dataRefs The request's dataRefs.
	 *
	 * @return string|null The error, or null when the answer is good.
	 */
	private function answerError(array $question, mixed $answer, array $dataRefs): ?string {
		if (is_scalar($answer) === false) {
			return 'This answer must be text.';
		}

		$type = (string) ($question['type'] ?? 'text');
		$text = (string) $answer;
		if ($type === 'choice') {
			$values = array_map(static fn ($choice): string => (string) ($choice['value'] ?? ''), (array) ($question['choices'] ?? []));
			if (in_array($text, $values, true) === false) {
				return 'This answer is not one of the choices.';
			}
		}

		if ($type === 'date' && $this->isIsoDate(answer: $text) === false) {
			return 'This answer is not a date (YYYY-MM-DD).';
		}

		if ($type === 'registerObject' && $this->hasDataRef(question: $question, id: $text, dataRefs: $dataRefs) === false) {
			return 'The chosen object is missing from the request.';
		}

		return null;

	}//end answerError()

	/**
	 * Whether the request carries the dataRef of a picked object.
	 *
	 * @param array<string, mixed>             $question The register object question.
	 * @param string                           $id       The picked id.
	 * @param array<int, array<string, mixed>> $dataRefs The request's dataRefs.
	 *
	 * @return bool True when present.
	 */
	private function hasDataRef(array $question, string $id, array $dataRefs): bool {
		foreach ($dataRefs as $ref) {
			if ((string) ($ref['id'] ?? '') === $id
				&& (string) ($ref['register'] ?? '') === (string) ($question['register'] ?? '')
				&& (string) ($ref['schema'] ?? '') === (string) ($question['schema'] ?? '')
			) {
				return true;
			}
		}

		return false;

	}//end hasDataRef()

	/**
	 * Whether an answer is an ISO 8601 date or date-time.
	 *
	 * @param string $answer The answer.
	 *
	 * @return bool True for YYYY-MM-DD, optionally followed by a time.
	 */
	private function isIsoDate(string $answer): bool {
		if (preg_match('/^\d{4}-\d{2}-\d{2}(T.+)?$/', $answer) !== 1) {
			return false;
		}

		[$year, $month, $day] = explode('-', substr($answer, 0, 10));

		return checkdate((int) $month, (int) $day, (int) $year);

	}//end isIsoDate()

	/**
	 * The questions of a wizard as a list.
	 *
	 * @param array<string, mixed> $wizard The wizard.
	 *
	 * @return array<int, array<string, mixed>> The questions.
	 */
	private function questions(array $wizard): array {
		return array_values(array_filter((array) ($wizard['questions'] ?? []), 'is_array'));

	}//end questions()
}//end class
