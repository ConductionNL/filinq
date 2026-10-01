<?php

/**
 * Skip logic of a wizard
 *
 * Which questions of a wizard are asked, given the answers so far. One
 * forward pass over the question order: a question without a condition is
 * asked; a question with a condition is asked when the earlier answer it
 * names satisfies the operator. A condition this class cannot evaluate makes
 * the question visible: asking too much is safe, silently skipping a question
 * a legal document needs is not. src/services/wizard.js applies the same rules
 * in the runner; the server's answer is the one that counts.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

/**
 * Forward-pass visibility of wizard questions.
 */
class WizardConditions {

	/**
	 * The operators a condition may use.
	 *
	 * @var string[]
	 */
	public const OPERATORS = ['equals', 'notEquals', 'answered'];

	/**
	 * The keys of the questions that are asked, in question order.
	 *
	 * A question whose condition names a question that is itself not asked is
	 * not asked either: its trigger has no answer that counts.
	 *
	 * @param array<int, array<string, mixed>> $questions The wizard's questions, in order.
	 * @param array<string, mixed>             $answers   The answers by question key.
	 *
	 * @return string[] The visible question keys.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
	 */
	public function visibleKeys(array $questions, array $answers): array {
		$visible = [];
		$earlier = [];
		foreach ($questions as $question) {
			$key = (string) ($question['key'] ?? '');
			if ($this->isAsked(question: $question, answers: $answers, earlier: $earlier, visible: $visible) === true) {
				$visible[$key] = true;
			}

			$earlier[$key] = true;
		}

		return array_keys($visible);

	}//end visibleKeys()

	/**
	 * Whether an answer counts as given.
	 *
	 * @param mixed $answer The answer.
	 *
	 * @return bool False for null, an empty string and an empty list.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-2
	 */
	public static function isAnswered(mixed $answer): bool {
		if ($answer === null || $answer === [] || (is_string($answer) === true && trim($answer) === '')) {
			return false;
		}

		return true;

	}//end isAnswered()

	/**
	 * Whether one question is asked.
	 *
	 * @param array<string, mixed> $question The question.
	 * @param array<string, mixed> $answers  The answers by key.
	 * @param array<string, true>  $earlier  The keys of the questions before it.
	 * @param array<string, true>  $visible  The keys asked so far.
	 *
	 * @return bool True when asked.
	 */
	private function isAsked(array $question, array $answers, array $earlier, array $visible): bool {
		$condition = ($question['condition'] ?? null);
		if ($condition === null || $condition === []) {
			return true;
		}

		if (is_array($condition) === false) {
			return true;
		}

		$on = (string) ($condition['questionKey'] ?? '');
		$operator = (string) ($condition['operator'] ?? '');
		if ($on === '' || isset($earlier[$on]) === false || in_array($operator, self::OPERATORS, true) === false) {
			// Fail safe: a condition that cannot be evaluated never hides a question.
			return true;
		}

		if (isset($visible[$on]) === false) {
			return false;
		}

		$answer = ($answers[$on] ?? null);
		$value = ($condition['value'] ?? null);

		return match ($operator) {
			'answered' => self::isAnswered(answer: $answer),
			'equals' => self::isAnswered(answer: $answer) === true && $this->same(answer: $answer, value: $value) === true,
			default => $this->same(answer: $answer, value: $value) === false,
		};

	}//end isAsked()

	/**
	 * Compare an answer with a condition value as text.
	 *
	 * @param mixed $answer The answer.
	 * @param mixed $value  The condition value.
	 *
	 * @return bool True when they read the same.
	 */
	private function same(mixed $answer, mixed $value): bool {
		if (is_scalar($answer) === false || is_scalar($value) === false) {
			return $answer === $value;
		}

		return $this->text(scalar: $answer) === $this->text(scalar: $value);

	}//end same()

	/**
	 * A scalar as text, booleans as true and false.
	 *
	 * @param bool|int|float|string $scalar The value.
	 *
	 * @return string The text.
	 */
	private function text(bool|int|float|string $scalar): string {
		return match ($scalar) {
			true => 'true',
			false => 'false',
			default => (string) $scalar,
		};

	}//end text()
}//end class
