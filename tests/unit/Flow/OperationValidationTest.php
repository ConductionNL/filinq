<?php

/**
 * Validation, identity and scope of the four Filinq Flow operations: a malformed
 * rule is refused at save time (REQ-DDFLO-001).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Flow
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Flow;

use OCA\Filinq\Flow\FlowOperationTrigger;
use OCA\Filinq\Flow\Operation\AbstractFileOperation;
use OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation;
use OCA\Filinq\Flow\Operation\ConvertToPdfaOperation;
use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCA\Filinq\Flow\Operation\RunValidationOperation;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;
use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\Validation\ValidationProfileResolver;
use OCP\EventDispatcher\GenericEvent;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * Tests the operation contract shared by the four operations.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @covers \OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation
 * @covers \OCA\Filinq\Flow\Operation\RunOcrOperation
 * @covers \OCA\Filinq\Flow\Operation\ConvertToPdfaOperation
 * @covers \OCA\Filinq\Flow\Operation\RunValidationOperation
 */
class OperationValidationTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * The four operations, keyed by their run key.
	 *
	 * @param FlowOperationTrigger|null $trigger The trigger, idle by default.
	 *
	 * @return array<string, AbstractFileOperation> The operations.
	 */
	private function operations(?FlowOperationTrigger $trigger=null): array {
		$trigger = ($trigger ?? $this->idleTrigger());
		$profiles = $this->createMock(ValidationProfileResolver::class);
		$profiles->method('documentTypes')->willReturn(['besluit', 'default']);

		return [
			'anonymize' => new AnonymizeDocumentOperation(
				$this->l10n(), $this->urls(), $trigger, $this->createMock(AnonymizationServiceInterface::class)
			),
			'ocr' => new RunOcrOperation($this->l10n(), $this->urls(), $trigger, $this->createMock(OcrRunServiceInterface::class)),
			'pdfa' => new ConvertToPdfaOperation(
				$this->l10n(),
				$this->urls(),
				$trigger,
				$this->createMock(Pdfa3ConversionServiceInterface::class),
				$this->createMock(PdfConversionService::class),
				$this->createMock(FileUploadService::class)
			),
			'validate' => new RunValidationOperation(
				$this->l10n(), $this->urls(), $trigger, $this->createMock(DocumentValidationServiceInterface::class), $profiles
			),
		];

	}//end operations()

	/**
	 * Each operation names itself, binds to the file entity and offers itself in both scopes only.
	 *
	 * @return void
	 */
	public function testEveryOperationIsAFileOperationInTheAdminAndThePersonalScope(): void {
		$names = [];
		foreach ($this->operations() as $key => $operation) {
			$this->assertSame($key, $operation->key());
			$this->assertSame('OCA\WorkflowEngine\Entity\File', $operation->getEntityId());
			$this->assertTrue($operation->isAvailableForScope(IManager::SCOPE_ADMIN));
			$this->assertTrue($operation->isAvailableForScope(IManager::SCOPE_USER));
			$this->assertFalse($operation->isAvailableForScope(7));
			$this->assertSame('/apps/filinq/img/app-dark.svg', $operation->getIcon());
			$this->assertNotSame('', $operation->getDescription());
			$names[] = $operation->getDisplayName();
		}

		$this->assertSame(['Anonymise document', 'Run OCR', 'Convert to PDF/A', 'Run validation checks'], $names);

	}//end testEveryOperationIsAFileOperationInTheAdminAndThePersonalScope()

	/**
	 * The option-less operations accept no options and an empty object, and refuse anything else.
	 *
	 * @return void
	 */
	public function testTheOptionlessOperationsRefuseOptions(): void {
		$operations = $this->operations();
		foreach (['anonymize', 'ocr'] as $key) {
			$operations[$key]->validateOperation('rule', [], '');
			$operations[$key]->validateOperation('rule', [], '{}');
			foreach (['{"documentType":"besluit"}', 'not json', '[1,2]', '"text"'] as $malformed) {
				try {
					$operations[$key]->validateOperation('rule', [], $malformed);
					$this->fail($key . ' accepted ' . $malformed);
				} catch (UnexpectedValueException $e) {
					$this->assertNotSame('', $e->getMessage());
				}
			}
		}

	}//end testTheOptionlessOperationsRefuseOptions()

	/**
	 * A document type that is not a profile key is refused at save time.
	 *
	 * @return void
	 */
	public function testAnUnknownDocumentTypeIsRefused(): void {
		$this->expectException(UnexpectedValueException::class);
		$this->expectExceptionMessage('No validation profile exists for document type "brief"');

		$this->operations()['validate']->validateOperation('rule', [], '{"documentType":"brief"}');

	}//end testAnUnknownDocumentTypeIsRefused()

	/**
	 * A known document type, the default, or none are all accepted.
	 *
	 * @return void
	 */
	public function testAKnownDocumentTypeIsAccepted(): void {
		$validate = $this->operations()['validate'];
		$validate->validateOperation('rule', [], '{"documentType":"besluit"}');
		$validate->validateOperation('rule', [], '{"documentType":"default"}');
		$validate->validateOperation('rule', [], '');

		$this->assertSame(['documentType' => 'besluit'], $validate->decodeConfig('{"documentType":"besluit"}'));

	}//end testAKnownDocumentTypeIsAccepted()

	/**
	 * Validation takes only a textual document type.
	 *
	 * @return void
	 */
	public function testValidationRefusesOtherOptionsAndNonTextTypes(): void {
		$validate = $this->operations()['validate'];
		foreach (['{"documentType":"besluit","targetFolder":"x"}', '{"documentType":5}'] as $malformed) {
			try {
				$validate->validateOperation('rule', [], $malformed);
				$this->fail('accepted ' . $malformed);
			} catch (UnexpectedValueException $e) {
				$this->assertNotSame('', $e->getMessage());
			}
		}

	}//end testValidationRefusesOtherOptionsAndNonTextTypes()

	/**
	 * A target folder must stay inside the owner's tree.
	 *
	 * @return void
	 */
	public function testTheTargetFolderCannotLeaveTheOwnersTree(): void {
		$pdfa = $this->operations()['pdfa'];
		$pdfa->validateOperation('rule', [], '{"targetFolder":"Archief"}');
		$pdfa->validateOperation('rule', [], '{"targetFolder":"Archief/2026"}');
		$pdfa->validateOperation('rule', [], '');

		foreach (['{"targetFolder":"../other"}', '{"targetFolder":"a/../../b"}', '{"targetFolder":"a\\\\b"}', '{"targetFolder":3}', '{"folder":"x"}'] as $malformed) {
			try {
				$pdfa->validateOperation('rule', [], $malformed);
				$this->fail('accepted ' . $malformed);
			} catch (UnexpectedValueException $e) {
				$this->assertNotSame('', $e->getMessage());
			}
		}

	}//end testTheTargetFolderCannotLeaveTheOwnersTree()

	/**
	 * onEvent hands the event to the trigger and does nothing else.
	 *
	 * @return void
	 */
	public function testOnEventOnlyHandsTheEventToTheTrigger(): void {
		$trigger = $this->createMock(FlowOperationTrigger::class);
		$operation = $this->operations(trigger: $trigger)['ocr'];
		$event = new GenericEvent();
		$matcher = $this->createMock(IRuleMatcher::class);

		$trigger->expects($this->once())->method('trigger')->with($operation, '\OCP\Files::postCreate', $event, $matcher);

		$operation->onEvent('\OCP\Files::postCreate', $event, $matcher);

	}//end testOnEventOnlyHandsTheEventToTheTrigger()
}//end class
