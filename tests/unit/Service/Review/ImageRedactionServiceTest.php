<?php

/**
 * Image content is never silent: an image-bearing file the seam cannot look
 * at is flagged on extract, found regions join the entity rows they belong
 * to, and an anonymise that did not burn the pixels is flagged pending.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-missing-image-capability-degrades-fail-flagged-never-fail-silent-req-ddimr-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\Review\ImageRedactionService;
use OCA\Filinq\Service\Review\ImageSeamGateway;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \OCA\Filinq\Service\Review\ImageRedactionService
 */
class ImageRedactionServiceTest extends TestCase {

	/**
	 * The seam.
	 *
	 * @var ImageSeamGateway&MockObject
	 */
	private ImageSeamGateway&MockObject $seam;

	protected function setUp(): void {
		parent::setUp();
		$this->seam = $this->createMock(ImageSeamGateway::class);

	}//end setUp()

	/**
	 * A file of a type.
	 *
	 * @param string $mime The MIME type.
	 *
	 * @return File The file.
	 */
	private function file(string $mime): File {
		$file = $this->createMock(File::class);
		$file->method('getMimeType')->willReturn($mime);

		return $file;

	}//end file()

	/**
	 * The service.
	 *
	 * @return ImageRedactionService The service.
	 */
	private function images(): ImageRedactionService {
		return new ImageRedactionService(seam: $this->seam);

	}//end images()

	public function testABornDigitalPdfHasNoImageLeg(): void {
		$this->seam->expects($this->never())->method('detect');
		$rows = [['type' => 'PERSON', 'value' => 'Jan', 'relationId' => 7]];

		$outcome = $this->images()->afterExtraction(file: $this->file(mime: 'application/pdf'), entities: $rows);

		$this->assertSame(['entities' => $rows, 'fields' => []], $outcome);

	}//end testABornDigitalPdfHasNoImageLeg()

	public function testAnImageWithoutTheSeamIsFlaggedSkipped(): void {
		$this->seam->method('available')->willReturn(false);

		$outcome = $this->images()->afterExtraction(file: $this->file(mime: 'image/jpeg'), entities: []);

		$this->assertSame(['imageDetectionSkipped' => ['reason' => 'backend_unavailable']], $outcome['fields']);

	}//end testAnImageWithoutTheSeamIsFlaggedSkipped()

	public function testAScanTheSeamCouldNotReadIsFlaggedWithItsReason(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('detect')->willReturn(
			['detected' => false, 'reason' => 'No OCR words with positions were provided for this file.', 'regions' => [], 'detectors' => [], 'objectDetection' => 'not-run', 'objectDetectionReason' => 'No region detector ran.']
		);

		$outcome = $this->images()->afterExtraction(file: $this->file(mime: 'application/pdf'), entities: [], ocrFields: ['ocr' => ['ran' => true]]);

		$this->assertSame(['reason' => 'not_detected', 'detail' => 'No OCR words with positions were provided for this file.'], $outcome['fields']['imageDetectionSkipped']);
		$this->assertSame('not-run', $outcome['fields']['objectDetection'], 'A run where no detector looked is never read as clean.');

	}//end testAScanTheSeamCouldNotReadIsFlaggedWithItsReason()

	public function testASeamFailureIsFlaggedNotThrown(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('detect')->willThrowException(new RuntimeException('timeout'));

		$outcome = $this->images()->afterExtraction(file: $this->file(mime: 'image/png'), entities: []);

		$this->assertSame(['reason' => 'backend_unavailable', 'detail' => 'timeout'], $outcome['fields']['imageDetectionSkipped']);

	}//end testASeamFailureIsFlaggedNotThrown()

	public function testRegionsJoinTheirRowsAndASignatureGetsItsOwn(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('detect')->willReturn(
			[
				'detected' => true,
				'reason' => null,
				'regions' => [
					['page' => 1, 'box' => ['x' => 0.1, 'y' => 0.2, 'w' => 0.3, 'h' => 0.05], 'entityType' => 'BSN', 'confidence' => 0.9, 'source' => 'ocr-text', 'relationId' => 7],
					['page' => 4, 'box' => ['x' => 0.61, 'y' => 0.78, 'w' => 0.24, 'h' => 0.09], 'entityType' => 'SIGNATURE', 'confidence' => 0.83, 'source' => 'anonymiq 1.0', 'relationId' => 9],
				],
				'detectors' => ['anonymiq' => ['status' => 'ran', 'reason' => null]],
				'objectDetection' => 'ran',
				'objectDetectionReason' => null,
			]
		);
		$rows = [['type' => 'BSN', 'value' => '111222333', 'relationId' => 7, 'skipAnonymization' => false]];

		$outcome = $this->images()->afterExtraction(file: $this->file(mime: 'application/pdf'), entities: $rows, ocrFields: ['ocr' => ['ran' => true]]);

		$this->assertSame(2, $outcome['fields']['imageRegionCount']);
		$this->assertSame('image', $outcome['entities'][0]['origin']);
		$this->assertSame([['page' => 1, 'x' => 0.1, 'y' => 0.2, 'w' => 0.3, 'h' => 0.05]], $outcome['entities'][0]['boxes']);
		$this->assertSame('111222333', $outcome['entities'][0]['value'], 'The row keeps its own fields.');
		$this->assertSame(
			['type' => 'SIGNATURE', 'value' => '', 'confidence' => 0.83, 'relationId' => 9, 'bases' => null, 'skipAnonymization' => false, 'origin' => 'image', 'boxes' => [['page' => 4, 'x' => 0.61, 'y' => 0.78, 'w' => 0.24, 'h' => 0.09]]],
			$outcome['entities'][1]
		);

	}//end testRegionsJoinTheirRowsAndASignatureGetsItsOwn()

	public function testTwoRegionsOfOneRelationShareTheRow(): void {
		$rows = $this->images()->attachRegions(
			entities: [],
			regions: [
				['page' => 1, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 0.1, 'h' => 0.1], 'entityType' => 'SIGNATURE', 'confidence' => 0.8, 'relationId' => 9],
				['page' => 2, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 0.1, 'h' => 0.1], 'entityType' => 'SIGNATURE', 'confidence' => 0.8, 'relationId' => 9],
			]
		);

		$this->assertCount(1, $rows);
		$this->assertSame([1, 2], array_column($rows[0]['boxes'], 'page'));

	}//end testTwoRegionsOfOneRelationShareTheRow()

	public function testABurnedImageReportsOnlyWhatWasBurned(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('decidedRegionCount')->willReturn(3);

		$this->assertSame(['imageRedactionPending' => false, 'burnedRegionCount' => 3], $this->images()->anonymizeFields(file: $this->file(mime: 'image/png')));

	}//end testABurnedImageReportsOnlyWhatWasBurned()

	public function testAPdfWithDecidedRegionsIsPending(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('decidedRegionCount')->willReturn(2);

		$this->assertSame(['imageRedactionPending' => true, 'burnedRegionCount' => 0], $this->images()->anonymizeFields(file: $this->file(mime: 'application/pdf')));

	}//end testAPdfWithDecidedRegionsIsPending()

	/**
	 * A PDF whose anonymise burns its decided regions reports them burned, not pending.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.4
	 *
	 * @return void
	 */
	public function testAPdfBurnedByTheAnonymiseCallReportsItsRegions(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('decidedRegionCount')->willReturn(2);
		$this->seam->method('burnsInsideAnonymise')->willReturn(true);

		$this->assertSame(
			expected: ['imageRedactionPending' => false, 'burnedRegionCount' => 2],
			actual: $this->images()->anonymizeFields(file: $this->file(mime: 'application/pdf'))
		);

	}//end testAPdfBurnedByTheAnonymiseCallReportsItsRegions()

	/**
	 * An image-bearing PDF without decided regions stays pending, even where the anonymise burns.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.4
	 *
	 * @return void
	 */
	public function testAnImageBearingPdfWithoutDecidedRegionsStaysPending(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('decidedRegionCount')->willReturn(0);
		$this->seam->method('burnsInsideAnonymise')->willReturn(true);

		$this->assertSame(
			expected: ['imageRedactionPending' => true, 'burnedRegionCount' => 0],
			actual: $this->images()->anonymizeFields(file: $this->file(mime: 'application/pdf'), ocrFields: ['ocr' => ['ran' => true]])
		);

	}//end testAnImageBearingPdfWithoutDecidedRegionsStaysPending()

	public function testAnImageWithoutTheSeamIsPending(): void {
		$this->seam->method('available')->willReturn(false);

		$this->assertSame(['imageRedactionPending' => true, 'burnedRegionCount' => 0], $this->images()->anonymizeFields(file: $this->file(mime: 'image/tiff')));

	}//end testAnImageWithoutTheSeamIsPending()

	public function testAPlainPdfCarriesNoImageFields(): void {
		$this->seam->method('available')->willReturn(true);
		$this->seam->method('decidedRegionCount')->willReturn(0);

		$this->assertSame([], $this->images()->anonymizeFields(file: $this->file(mime: 'application/pdf')));

	}//end testAPlainPdfCarriesNoImageFields()
}//end class
