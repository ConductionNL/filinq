<?php

/**
 * Unit tests for WizardQuestionCheck
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Wizard\WizardQuestionCheck;
use PHPUnit\Framework\TestCase;

/**
 * Every error one wizard question can have, in the order they are checked.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class WizardQuestionCheckTest extends TestCase {

	private const CHOICES_MISSING = 'A choice question needs choices.';

	private const CHOICE_INCOMPLETE = 'Every choice needs a value and a label.';

	private const REGISTER_MISSING = 'A register object question needs a register and a schema.';

	private const PATH_UNREADABLE = 'The data path must be names joined by dots, such as applicant.name.';

	private const OPERATOR_UNKNOWN = 'The condition operator must be equals, notEquals or answered.';

	/**
	 * The error for a question with the key `q`, after an earlier question `a`.
	 *
	 * @param array<string, mixed> $question The question
	 *
	 * @return string|null
	 */
	private function errorOf(array $question): ?string {
		return (new WizardQuestionCheck())->error($question, 'q', ['a' => true]);

	}//end errorOf()

	/**
	 * The key, the label and the type are checked first, in that order.
	 *
	 * @return void
	 */
	public function testKeyLabelAndTypeComeFirst(): void {
		$check = new WizardQuestionCheck();

		$this->assertSame(
			'A question needs a key of letters, digits, - or _, starting with a letter.',
			$check->error(['label' => 'x', 'type' => 'text'], '1bad', [])
		);
		$this->assertSame('The key "a" is used twice.', $check->error(['label' => ''], 'a', ['a' => true]));
		$this->assertSame('A question needs a label.', $this->errorOf(['label' => '  ', 'type' => 'nope']));
		$this->assertSame('The type must be text, choice, date or registerObject.', $this->errorOf(['label' => 'x', 'type' => 'nope']));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'text']));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'date', 'mapsTo' => 'applicant.name']));

	}//end testKeyLabelAndTypeComeFirst()

	/**
	 * A choice question needs a list of choices that each have a value and a label.
	 *
	 * @return void
	 */
	public function testChoiceQuestionsNeedCompleteChoices(): void {
		$this->assertSame(self::CHOICES_MISSING, $this->errorOf(['label' => 'x', 'type' => 'choice']));
		$this->assertSame(self::CHOICES_MISSING, $this->errorOf(['label' => 'x', 'type' => 'choice', 'choices' => []]));
		$this->assertSame(self::CHOICES_MISSING, $this->errorOf(['label' => 'x', 'type' => 'choice', 'choices' => 'ja']));

		$incomplete = [
			['ja'],
			[['value' => ' ', 'label' => 'Ja']],
			[['value' => 'ja', 'label' => '']],
			[['value' => 'ja', 'label' => 'Ja'], 'nee'],
		];
		foreach ($incomplete as $choices) {
			$this->assertSame(self::CHOICE_INCOMPLETE, $this->errorOf(['label' => 'x', 'type' => 'choice', 'choices' => $choices]));
		}

		$complete = ['label' => 'x', 'type' => 'choice', 'choices' => [['value' => 'ja', 'label' => 'Ja']], 'mapsTo' => '1x'];
		$this->assertSame(self::PATH_UNREADABLE, $this->errorOf($complete));
		$complete['mapsTo'] = ' ';
		$this->assertNull($this->errorOf($complete));

	}//end testChoiceQuestionsNeedCompleteChoices()

	/**
	 * A register object question needs a register and a schema; any type needs a readable path.
	 *
	 * @return void
	 */
	public function testRegisterObjectQuestionsAndDataPaths(): void {
		$this->assertSame(self::REGISTER_MISSING, $this->errorOf(['label' => 'x', 'type' => 'registerObject', 'schema' => 's']));
		$this->assertSame(self::REGISTER_MISSING, $this->errorOf(['label' => 'x', 'type' => 'registerObject', 'register' => 'r', 'schema' => ' ']));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'registerObject', 'register' => 'r', 'schema' => 's']));
		$this->assertSame(self::PATH_UNREADABLE, $this->errorOf(['label' => 'x', 'type' => 'text', 'mapsTo' => 'a..b']));
		$this->assertSame(self::PATH_UNREADABLE, $this->errorOf(['label' => 'x', 'type' => 'text', 'schema' => '', 'mapsTo' => 'a b']));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'text', 'mapsTo' => '_a.b_2']));

	}//end testRegisterObjectQuestionsAndDataPaths()

	/**
	 * A condition must point at an earlier question and use a known operator; an empty one is fine.
	 *
	 * @return void
	 */
	public function testConditionsReferToAnEarlierQuestion(): void {
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => null]));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => []]));
		$this->assertSame('The condition is not readable.', $this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => 'a']));
		$this->assertSame(
			'The condition refers to "", which is not an earlier question.',
			$this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => ['operator' => 'equals']])
		);
		$this->assertSame(
			'The condition refers to "b", which is not an earlier question.',
			$this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => ['questionKey' => ' b ', 'operator' => 'equals']])
		);
		$this->assertSame(self::OPERATOR_UNKNOWN, $this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => ['questionKey' => 'a']]));
		$this->assertNull($this->errorOf(['label' => 'x', 'type' => 'text', 'condition' => ['questionKey' => 'a', 'operator' => 'answered']]));

		$badChoice = ['label' => 'x', 'type' => 'choice', 'choices' => [], 'condition' => 'a'];
		$this->assertSame(self::CHOICES_MISSING, $this->errorOf($badChoice));

	}//end testConditionsReferToAnEarlierQuestion()
}//end class
