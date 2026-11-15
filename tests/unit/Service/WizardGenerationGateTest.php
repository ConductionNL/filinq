<?php

/**
 * Unit tests for WizardGenerationGate.
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

use OCA\Filinq\Exception\WizardRefused;
use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardGenerationGate;
use OCA\Filinq\Service\Wizard\WizardRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The gate a wizard-driven generation passes before a document is rendered.
 *
 * @covers \OCA\Filinq\Service\Wizard\WizardGenerationGate
 *
 * @uses \OCA\Filinq\Exception\WizardRefused
 */
class WizardGenerationGateTest extends TestCase {

	/**
	 * The repository double.
	 *
	 * @var WizardRepository&MockObject
	 */
	private WizardRepository $repository;

	/**
	 * The answers double.
	 *
	 * @var WizardAnswers&MockObject
	 */
	private WizardAnswers $answers;

	/**
	 * The gate under test.
	 *
	 * @var WizardGenerationGate
	 */
	private WizardGenerationGate $gate;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->repository = $this->createMock(WizardRepository::class);
		$this->answers = $this->createMock(WizardAnswers::class);
		$this->gate = new WizardGenerationGate($this->repository, $this->answers);
	}//end setUp()

	/**
	 * Assert that a check is refused with the given message and errors.
	 *
	 * @param callable             $check   The check.
	 * @param string               $message The expected message.
	 * @param array<string, mixed> $errors  The expected errors.
	 *
	 * @return void
	 */
	private function assertRefused(callable $check, string $message, array $errors): void {
		try {
			$check();
			self::fail('Expected the generation to be refused.');
		} catch (WizardRefused $refusal) {
			self::assertSame($message, $refusal->getMessage());
			self::assertSame(422, $refusal->getCode());
			self::assertSame($errors, $refusal->getErrors());
		}
	}//end assertRefused()

	/**
	 * A context that is not an array looks up the empty id and is refused as unknown.
	 *
	 * @return void
	 */
	public function testAnUnknownWizardIsRefused(): void {
		$this->repository->expects(self::once())->method('find')->with('')->willReturn(null);
		$this->answers->expects(self::never())->method('errors');

		$this->assertRefused(
			fn () => $this->gate->check(templateId: 'tpl-1', dataRefs: [], context: 'wizard-1'),
			'The wizard of this run was not found',
			['wizardId' => 'Unknown wizard.']
		);
	}//end testAnUnknownWizardIsRefused()

	/**
	 * A wizard of another template, or an inactive one, is refused.
	 *
	 * @return void
	 */
	public function testAWizardOfAnotherTemplateOrInactiveIsRefused(): void {
		$this->repository->method('find')->with('wizard-1')->willReturnOnConsecutiveCalls(
			['templateId' => 'tpl-2'],
			['templateId' => 'tpl-1', 'active' => false],
		);

		foreach ([1, 2] as $ignored) {
			$this->assertRefused(
				fn () => $this->gate->check(templateId: 'tpl-1', dataRefs: [], context: ['wizardId' => 'wizard-1']),
				'The wizard is not the active wizard of this template',
				['wizardId' => 'Not the active wizard of this template.']
			);
		}
	}//end testAWizardOfAnotherTemplateOrInactiveIsRefused()

	/**
	 * Incomplete answers are refused with the answer errors.
	 *
	 * @return void
	 */
	public function testIncompleteAnswersAreRefused(): void {
		$wizard = ['templateId' => 'tpl-1'];
		$this->repository->method('find')->willReturn($wizard);
		$this->answers->expects(self::once())->method('errors')
			->with($wizard, [], ['ref-1'])->willReturn(['name' => 'Required.']);
		$this->answers->expects(self::never())->method('translate');

		$this->assertRefused(
			fn () => $this->gate->check(templateId: 'tpl-1', dataRefs: ['ref-1'], context: ['wizardId' => 'w', 'answers' => 'name=x']),
			'The wizard answers are not complete',
			['name' => 'Required.']
		);
	}//end testIncompleteAnswersAreRefused()

	/**
	 * Complete answers of the active wizard pass and answer only the translated wizard context.
	 *
	 * @return void
	 */
	public function testCompleteAnswersPassWithTheWizardContext(): void {
		$wizard = ['templateId' => 'tpl-1', 'active' => true];
		$given = ['name' => 'Ada'];
		$this->repository->method('find')->with('w')->willReturn($wizard);
		$this->answers->method('errors')->with($wizard, $given, [])->willReturn([]);
		$this->answers->expects(self::once())->method('translate')->with($wizard, $given)
			->willReturn(['wizardContext' => ['applicant' => 'Ada'], 'data' => ['ignored' => true]]);

		self::assertSame(
			['wizardContext' => ['applicant' => 'Ada']],
			$this->gate->check(templateId: 'tpl-1', dataRefs: [], context: ['wizardId' => 'w', 'answers' => $given])
		);
	}//end testCompleteAnswersPassWithTheWizardContext()
}//end class
