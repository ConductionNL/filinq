<?php

/**
 * Unit tests for WizardConditions.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Wizard\WizardConditions;
use PHPUnit\Framework\TestCase;

/**
 * Which wizard questions are asked, given the answers so far.
 *
 * @covers \OCA\Filinq\Service\Wizard\WizardConditions
 */
class WizardConditionsTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var WizardConditions
	 */
	private WizardConditions $conditions;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->conditions = new WizardConditions();
	}//end setUp()

	/**
	 * Questions without a condition, or with an empty or unreadable one, are always asked.
	 *
	 * @return void
	 */
	public function testUnconditionalQuestionsAreAsked(): void {
		$questions = [
			['key' => 'a'],
			['key' => 'b', 'condition' => []],
			['key' => 'c', 'condition' => 'a == yes'],
		];

		self::assertSame(['a', 'b', 'c'], $this->conditions->visibleKeys(questions: $questions, answers: []));
	}//end testUnconditionalQuestionsAreAsked()

	/**
	 * A condition on a later or unknown question, or with an unknown operator, is ignored.
	 *
	 * @return void
	 */
	public function testAnUnusableConditionIsIgnored(): void {
		$questions = [
			['key' => 'first', 'condition' => ['questionKey' => 'later', 'operator' => 'answered']],
			['key' => 'blank', 'condition' => ['questionKey' => '', 'operator' => 'answered']],
			['key' => 'odd', 'condition' => ['questionKey' => 'first', 'operator' => 'contains', 'value' => 'x']],
			['key' => 'later'],
		];

		self::assertSame(['first', 'blank', 'odd', 'later'], $this->conditions->visibleKeys(questions: $questions, answers: []));
	}//end testAnUnusableConditionIsIgnored()

	/**
	 * `equals` asks the question only when the earlier answer matches, comparing as text.
	 *
	 * @return void
	 */
	public function testEqualsComparesAsText(): void {
		$questions = [
			['key' => 'count'],
			['key' => 'flag'],
			['key' => 'onCount', 'condition' => ['questionKey' => 'count', 'operator' => 'equals', 'value' => '3']],
			['key' => 'onFlag', 'condition' => ['questionKey' => 'flag', 'operator' => 'equals', 'value' => 'true']],
			['key' => 'onFalse', 'condition' => ['questionKey' => 'flag', 'operator' => 'equals', 'value' => false]],
		];

		self::assertSame(
			['count', 'flag', 'onCount', 'onFlag'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['count' => 3, 'flag' => true])
		);
		self::assertSame(
			['count', 'flag', 'onFalse'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['count' => 4, 'flag' => false])
		);
	}//end testEqualsComparesAsText()

	/**
	 * `equals` on an unanswered question never matches, even an empty value.
	 *
	 * @return void
	 */
	public function testEqualsNeedsAnAnswer(): void {
		$questions = [
			['key' => 'name'],
			['key' => 'onEmpty', 'condition' => ['questionKey' => 'name', 'operator' => 'equals', 'value' => '']],
		];

		self::assertSame(['name'], $this->conditions->visibleKeys(questions: $questions, answers: ['name' => '']));
	}//end testEqualsNeedsAnAnswer()

	/**
	 * `notEquals` asks the question when the answer differs, unanswered included; arrays compare strictly.
	 *
	 * @return void
	 */
	public function testNotEqualsAsksWhenTheAnswerDiffers(): void {
		$questions = [
			['key' => 'kind'],
			['key' => 'tags'],
			['key' => 'notPermit', 'condition' => ['questionKey' => 'kind', 'operator' => 'notEquals', 'value' => 'permit']],
			['key' => 'otherTags', 'condition' => ['questionKey' => 'tags', 'operator' => 'notEquals', 'value' => ['a']]],
		];

		self::assertSame(
			['kind', 'tags', 'notPermit'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['tags' => ['a']])
		);
		self::assertSame(
			['kind', 'tags', 'otherTags'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['kind' => 'permit', 'tags' => ['b']])
		);
	}//end testNotEqualsAsksWhenTheAnswerDiffers()

	/**
	 * A question conditional on a hidden question is hidden too, whatever was answered.
	 *
	 * @return void
	 */
	public function testAQuestionOnAHiddenQuestionIsHidden(): void {
		$questions = [
			['key' => 'kind'],
			['key' => 'permitNo', 'condition' => ['questionKey' => 'kind', 'operator' => 'equals', 'value' => 'permit']],
			['key' => 'permitDate', 'condition' => ['questionKey' => 'permitNo', 'operator' => 'answered']],
		];

		self::assertSame(
			['kind'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['kind' => 'other', 'permitNo' => 'P-1'])
		);
		self::assertSame(
			['kind', 'permitNo', 'permitDate'],
			$this->conditions->visibleKeys(questions: $questions, answers: ['kind' => 'permit', 'permitNo' => 'P-1'])
		);
	}//end testAQuestionOnAHiddenQuestionIsHidden()

	/**
	 * Null, an empty list and blank text are unanswered; zero, false and text are answered.
	 *
	 * @return void
	 */
	public function testWhatCountsAsAnswered(): void {
		foreach ([null, [], '', "  \t"] as $empty) {
			self::assertFalse($this->conditions->isAnswered(answer: $empty));
		}

		foreach ([0, false, 'x', ['a']] as $given) {
			self::assertTrue($this->conditions->isAnswered(answer: $given));
		}
	}//end testWhatCountsAsAnswered()
}//end class
