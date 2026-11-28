<?php

/**
 * The Run OCR operation: delegates to the OCR run service with triggeredBy
 * flow, fails flagged when OCR is off or missing, skips a non-candidate, and
 * never records the recognised text (REQ-DDFLO-005).
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

use OCA\Filinq\Flow\Operation\RunOcrOperation;
use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests RunOcrOperation::execute().
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @covers \OCA\Filinq\Flow\Operation\RunOcrOperation
 * @uses   \OCA\Filinq\Flow\Operation\AbstractFileOperation
 * @uses   \OCA\Filinq\Flow\FlowOperationOutcome
 */
class RunOcrOperationTest extends TestCase {
	use FlowOperationDoubles;

	/**
	 * Run the operation over an OCR service answering $result.
	 *
	 * @param array<string, mixed> $result The OCR run's answer.
	 *
	 * @return \OCA\Filinq\Flow\FlowOperationOutcome The outcome.
	 */
	private function runWith(array $result): \OCA\Filinq\Flow\FlowOperationOutcome {
		$file = $this->createMock(File::class);
		/** @var OcrRunServiceInterface&MockObject $ocr */
		$ocr = $this->createMock(OcrRunServiceInterface::class);
		$ocr->expects($this->once())->method('run')->with($file, 'flow')->willReturn($result);

		$operation = new RunOcrOperation($this->l10n(), $this->urls(), $this->idleTrigger(), $ocr);

		return $operation->execute($file, [], $this->createMock(Folder::class));

	}//end runWith()

	/**
	 * A recognised scan succeeds with confidence and length, and the text is nowhere on the outcome.
	 *
	 * @return void
	 */
	public function testARecognisedScanSucceedsWithoutItsText(): void {
		$outcome = $this->runWith(
			[
				'processed' => true,
				'reason' => null,
				'text' => 'Bezwaarschrift van J. de Vries',
				'result' => ['confidence' => 88.2, 'textLength' => 3120, 'triggeredBy' => 'flow'],
			]
		);

		$this->assertSame('succeeded', $outcome->status);
		$this->assertSame(['confidence' => 88.2, 'textLength' => 3120], $outcome->resultSummary);
		$this->assertStringNotContainsString('de Vries', (string) json_encode([$outcome->resultSummary, $outcome->errorMessage]));

	}//end testARecognisedScanSucceedsWithoutItsText()

	/**
	 * OCR turned off fails the run with ocr_disabled, never silently.
	 *
	 * @return void
	 */
	public function testDisabledOcrFailsFlagged(): void {
		$outcome = $this->runWith(['processed' => false, 'reason' => 'ocr_disabled', 'text' => '', 'result' => null]);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('ocr_disabled', $outcome->reason);
		$this->assertSame('Text recognition is turned off on this server', $outcome->errorMessage);

	}//end testDisabledOcrFailsFlagged()

	/**
	 * Tesseract missing fails the run with tesseract_unavailable.
	 *
	 * @return void
	 */
	public function testMissingTesseractFailsFlagged(): void {
		$outcome = $this->runWith(['processed' => false, 'reason' => 'tesseract_unavailable', 'text' => '', 'result' => null]);

		$this->assertSame('failed', $outcome->status);
		$this->assertSame('tesseract_unavailable', $outcome->reason);

	}//end testMissingTesseractFailsFlagged()

	/**
	 * A file OCR is not offered for is skipped, not failed.
	 *
	 * @return void
	 */
	public function testANonCandidateIsSkipped(): void {
		$outcome = $this->runWith(['processed' => false, 'reason' => 'not_candidate', 'text' => '', 'result' => null]);

		$this->assertSame('skipped', $outcome->status);
		$this->assertSame('not_candidate', $outcome->reason);
		$this->assertNull($outcome->errorMessage);

	}//end testANonCandidateIsSkipped()

	/**
	 * No text recovered, and an engine failure, both fail the run.
	 *
	 * @return void
	 */
	public function testNoTextAndEngineFailureFail(): void {
		$this->assertSame('no_text_recovered', $this->runWith(['processed' => false, 'reason' => 'no_text_recovered', 'text' => '', 'result' => null])->reason);
		$failed = $this->runWith(['processed' => false, 'reason' => 'ocr_failed', 'text' => '', 'result' => null]);
		$this->assertSame('failed', $failed->status);
		$this->assertSame('Text recognition failed', $failed->errorMessage);

	}//end testNoTextAndEngineFailureFail()
}//end class
