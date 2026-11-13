<?php

/**
 * Unit tests for WizardDefinitionValidator.
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

use OCA\Filinq\Service\Wizard\WizardBindingCheck;
use OCA\Filinq\Service\Wizard\WizardDefinitionValidator;
use OCA\Filinq\Service\Wizard\WizardQuestionCheck;
use PHPUnit\Framework\TestCase;

/**
 * Errors and warnings for a whole wizard definition.
 *
 * @covers \OCA\Filinq\Service\Wizard\WizardDefinitionValidator
 *
 * @uses \OCA\Filinq\Service\Wizard\WizardQuestionCheck
 * @uses \OCA\Filinq\Service\Wizard\WizardBindingCheck
 */
class WizardDefinitionValidatorTest extends TestCase {

	/**
	 * A wizard without a name, template or questions reports all three and no warnings.
	 *
	 * @return void
	 */
	public function testAnEmptyWizardReportsTheRequiredFields(): void {
		$result = (new WizardDefinitionValidator())->check(wizard: ['name' => '  ', 'questions' => []]);

		self::assertSame(
			[
				'errors' => [
					'name' => 'Required.',
					'templateId' => 'Required.',
					'questions' => 'A wizard needs at least one question.',
				],
				'warnings' => [],
			],
			$result
		);
	}//end testAnEmptyWizardReportsTheRequiredFields()

	/**
	 * Questions that are not a list are refused like an empty list.
	 *
	 * @return void
	 */
	public function testQuestionsThatAreNotAListAreRefused(): void {
		$result = (new WizardDefinitionValidator())->check(wizard: ['name' => 'N', 'templateId' => 't', 'questions' => 'q1']);

		self::assertSame(['questions' => 'A wizard needs at least one question.'], $result['errors']);
	}//end testQuestionsThatAreNotAListAreRefused()

	/**
	 * A question's error is named by its key, or by its position when it has none; a reused key is caught.
	 *
	 * @return void
	 */
	public function testQuestionErrorsAreNamedByKeyOrPosition(): void {
		$wizard = [
			'name' => 'Intake',
			'templateId' => 'tpl-1',
			'questions' => [
				'x' => ['key' => 'applicant', 'label' => 'Applicant', 'type' => 'text'],
				'y' => ['label' => 'No key', 'type' => 'text'],
				'z' => ['key' => 'applicant', 'label' => 'Again', 'type' => 'text'],
				'w' => 'not a question',
			],
		];

		$result = (new WizardDefinitionValidator())->check(wizard: $wizard);

		self::assertSame(
			[
				'question 2' => 'A question needs a key of letters, digits, - or _, starting with a letter.',
				'applicant' => 'The key "applicant" is used twice.',
				'question 4' => 'A question needs a key of letters, digits, - or _, starting with a letter.',
			],
			$result['errors']
		);
		self::assertSame([], $result['warnings']);
	}//end testQuestionErrorsAreNamedByKeyOrPosition()

	/**
	 * Binding warnings come per question first, then the collision warnings.
	 *
	 * @return void
	 */
	public function testBindingAndCollisionWarningsAreCollected(): void {
		$wizard = [
			'name' => 'Intake',
			'templateId' => 'tpl-1',
			'questions' => [
				['key' => 'person', 'label' => 'Person', 'type' => 'registerObject', 'register' => 'crm', 'schema' => 'person'],
				['key' => 'nick', 'label' => 'Nickname', 'type' => 'text', 'mapsTo' => 'person.nick'],
			],
		];

		$result = (new WizardDefinitionValidator())->check(
			wizard: $wizard,
			template: ['boundRegister' => 'zaken', 'boundSchema' => 'zaak'],
			boundProperties: ['title'],
		);

		self::assertSame([], $result['errors']);
		self::assertSame(
			[
				"Question \"person\" picks from crm/person, not the template's zaken/zaak.",
				'Question "nick" maps to "person.nick", which the template\'s schema zaak does not have.',
				'Question "nick" writes into person, which replaces the whole person picked in question "person" in the template data.',
			],
			$result['warnings']
		);
	}//end testBindingAndCollisionWarningsAreCollected()

	/**
	 * The injected checks are the ones consulted.
	 *
	 * @return void
	 */
	public function testTheInjectedChecksAreConsulted(): void {
		$questionCheck = $this->createMock(WizardQuestionCheck::class);
		$questionCheck->expects(self::exactly(2))->method('error')
			->willReturnCallback(static fn (array $question, string $key, array $seen): ?string => $key === 'b' && isset($seen['a']) ? 'nope' : null);
		$bindingCheck = $this->createMock(WizardBindingCheck::class);
		$bindingCheck->expects(self::exactly(2))->method('warnings')->willReturn([]);
		$bindingCheck->expects(self::once())->method('collisionWarnings')->willReturn(['collision']);

		$result = (new WizardDefinitionValidator($questionCheck, $bindingCheck))->check(
			wizard: ['name' => 'N', 'templateId' => 't', 'questions' => [['key' => ' a '], ['key' => 'b']]]
		);

		self::assertSame(['errors' => ['b' => 'nope'], 'warnings' => ['collision']], $result);
	}//end testTheInjectedChecksAreConsulted()
}//end class
