<?php

/**
 * The Anonymise document operation: intake only, counts per type and never
 * an entity value, and the prohibition gate fails the run rather than being
 * bypassed (REQ-DDFLO-004).
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

use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests AnonymizeDocumentOperation::execute().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\Operation\AnonymizeDocumentOperation
 * @uses   \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @uses   \OCA\Filinq\Flow\FlowOperationOutcome
 * @uses   \OCA\Filinq\Service\FileEntityStatsService
 * @uses   \OCA\Filinq\Exception\ProhibitionGateException
 */
class AnonymizeDocumentOperationTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * @var AnonymizationServiceInterface&MockObject
	 */
	private AnonymizationServiceInterface $anonymization;

	/**
	 * Set up: the commit path must never be reached.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->anonymization = $this->createMock(AnonymizationServiceInterface::class);
		$this->anonymization->expects($this->never())->method('anonymizeDocument');
		$this->anonymization->expects($this->never())->method('runProhibitionGate');

	}//end setUp()

	/**
	 * Run the operation on file 77.
	 *
	 * @return FlowOperationOutcome The outcome.
	 */
	private function execute(): FlowOperationOutcome {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(77);

		return (new AnonymizeDocumentOperation($this->l10n(), $this->urls(), $this->idleTrigger(), $this->anonymization))
			->execute($file, [], $this->createMock(Folder::class));

	}//end execute()

	/**
	 * Detection lands the document in review; the summary counts per type and holds no entity value.
	 *
	 * @return void
	 */
	public function testIntakeCountsPerTypeAndHoldsNoEntityValue(): void {
		$this->anonymization->expects($this->once())->method('extractAndDetectEntities')->with(77)->willReturn(
			[
				'entities' => [
					['entityType' => 'person', 'text' => 'J. de Vries'],
					['entityType' => 'bsn', 'text' => '123456782'],
					['entityType' => 'person', 'text' => 'P. Jansen'],
				],
				'entityCount' => 3,
			]
		);
		$this->anonymization->method('absoluteProhibitionViolations')->with(77)->willReturn([]);

		$outcome = $this->execute();

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame(['entityCounts' => ['BSN' => 1, 'PERSON' => 2], 'entityCount' => 3, 'reviewRequired' => true], $outcome->resultSummary);
		$encoded = (string) json_encode([$outcome->resultSummary, $outcome->errorMessage, $outcome->reason]);
		$this->assertStringNotContainsString('J. de Vries', $encoded);
		$this->assertStringNotContainsString('123456782', $encoded);

	}//end testIntakeCountsPerTypeAndHoldsNoEntityValue()

	/**
	 * An absolute prohibition fails the run with the gate reason; the counts are kept, nothing is overridden.
	 *
	 * @return void
	 */
	public function testAProhibitionFailsTheRun(): void {
		$this->anonymization->method('extractAndDetectEntities')->willReturn(['entities' => [['entityType' => 'PERSON', 'text' => 'X']]]);
		$this->anonymization->method('absoluteProhibitionViolations')->willReturn([['entityId' => 4]]);

		$outcome = $this->execute();

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('prohibition_gate', $outcome->reason);
		$this->assertStringContainsString('publication prohibition', (string) $outcome->errorMessage);
		$this->assertSame(['PERSON' => 1], $outcome->resultSummary['entityCounts']);

	}//end testAProhibitionFailsTheRun()

	/**
	 * A gate that cannot be asked counts as refusing: fail closed.
	 *
	 * @return void
	 */
	public function testAnUnaskableGateFailsClosed(): void {
		$this->anonymization->method('extractAndDetectEntities')->willReturn(['entities' => []]);
		$this->anonymization->method('absoluteProhibitionViolations')->willThrowException(new RuntimeException('policy store down'));

		$this->assertSame('prohibition_gate', $this->execute()->reason);

	}//end testAnUnaskableGateFailsClosed()

	/**
	 * The prohibition gate's exception fails the run with its message as the reason.
	 *
	 * @return void
	 */
	public function testTheGateExceptionIsRecordedNotBypassed(): void {
		$this->anonymization->method('extractAndDetectEntities')->willThrowException(
			new ProhibitionGateException(message: 'Anonymisation prohibition gate blocked the request.')
		);

		$outcome = $this->execute();

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('prohibition_gate', $outcome->reason);
		$this->assertSame('Anonymisation prohibition gate blocked the request.', $outcome->errorMessage);

	}//end testTheGateExceptionIsRecordedNotBypassed()

	/**
	 * A detection failure fails the run without leaking the engine message.
	 *
	 * @return void
	 */
	public function testADetectionFailureFailsTheRun(): void {
		$this->anonymization->method('extractAndDetectEntities')->willThrowException(new RuntimeException('chunk "J. de Vries" failed'));

		$outcome = $this->execute();

		$this->assertSame('detection_failed', $outcome->reason);
		$this->assertStringNotContainsString('de Vries', (string) $outcome->errorMessage);

	}//end testADetectionFailureFailsTheRun()
}//end class
