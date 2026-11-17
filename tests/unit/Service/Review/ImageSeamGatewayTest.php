<?php

/**
 * The gateway hands full-page regions to OpenRegister's image seam with the
 * seam's real region shape, and refuses when the seam is absent or fails.
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
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Exception\ScopeDecisionRefusedException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCA\Filinq\Service\Review\ImageSeamGateway;
use OCA\OpenRegister\Service\File\ImageDetection;
use OCA\OpenRegister\Service\File\ImageRedactionService;
use OCA\OpenRegister\Service\File\ImageRegion;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../../../stubs/OpenRegisterImageSeamStubs.php';

/**
 * @covers \OCA\Filinq\Service\Review\ImageSeamGateway
 * @uses   \OCA\Filinq\Exception\ScopeDecisionRefusedException
 */
class ImageSeamGatewayTest extends TestCase {

	/**
	 * The seam double.
	 *
	 * @var ImageRedactionService
	 */
	private ImageRedactionService $seam;

	protected function setUp(): void {
		parent::setUp();
		$this->seam = new ImageRedactionService();

	}//end setUp()

	/**
	 * A gateway whose locator resolves the seam, or fails to.
	 *
	 * @param bool $installed Whether OpenRegister resolves the seam.
	 *
	 * @return ImageSeamGateway The gateway.
	 */
	private function gateway(bool $installed = true): ImageSeamGateway {
		$locator = $this->createMock(OpenRegisterServiceLocator::class);
		if ($installed === true) {
			$locator->method('get')->with(ImageSeamGateway::SEAM_CLASS)->willReturn($this->seam);
		} else {
			$locator->method('get')->willThrowException(new RuntimeException('not available'));
		}

		return new ImageSeamGateway(locator: $locator);

	}//end gateway()

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
		$file->method('getId')->willReturn(812005);

		return $file;

	}//end file()

	public function testPdfPagesReachTheSeamAsFullPageRegions(): void {
		$output = $this->file(mime: 'application/pdf');
		$this->seam->answer = $output;
		$box = ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0];

		$result = $this->gateway()->burn(
			file: $this->file(mime: 'application/pdf'),
			regions: [['page' => 3, 'box' => $box], ['page' => 4, 'box' => $box]],
			bases: ['5.1.2.e', '5.1.1.c']
		);

		$this->assertSame($output, $result);
		$this->assertCount(2, $this->seam->burned);
		$this->assertContainsOnlyInstancesOf(ImageRegion::class, $this->seam->burned);
		$this->assertSame([3, 4], array_map(static fn (ImageRegion $r): ?int => $r->page, $this->seam->burned));
		$this->assertSame($box, $this->seam->burned[0]->box);
		$this->assertSame('WITHHELD', $this->seam->burned[0]->entityType);
		$this->assertSame(1.0, $this->seam->burned[0]->confidence);
		$this->assertSame('filinq-scope:5.1.2.e,5.1.1.c', $this->seam->burned[0]->source);

	}//end testPdfPagesReachTheSeamAsFullPageRegions()

	public function testAStandaloneImageRegionCarriesNoPage(): void {
		$this->seam->answer = $this->file(mime: 'image/png');

		$this->gateway()->burn(
			file: $this->file(mime: 'image/png'),
			regions: [['page' => 1, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0]]],
			bases: ['5.1.2.e']
		);

		$this->assertNull($this->seam->burned[0]->page);

	}//end testAStandaloneImageRegionCarriesNoPage()

	public function testASeamRefusalWritesNothing(): void {
		$this->seam->answer = new RuntimeException('burning image content inside application/pdf files is not supported yet.');

		$this->expectException(ScopeDecisionRefusedException::class);
		$this->expectExceptionMessage('OpenRegister could not burn these pages, so nothing was written: burning image content inside application/pdf files is not supported yet.');

		$this->gateway()->burn(
			file: $this->file(mime: 'application/pdf'),
			regions: [['page' => 1, 'box' => ['x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0]]],
			bases: ['5.1.2.e']
		);

	}//end testASeamRefusalWritesNothing()

	public function testWithoutOpenRegisterTheSeamIsAbsent(): void {
		$gateway = $this->gateway(installed: false);

		$this->assertFalse($gateway->available());
		$this->expectExceptionMessage("Page redaction needs OpenRegister's image seam, so nothing was written.");
		$gateway->burn(file: $this->file(mime: 'application/pdf'), regions: [], bases: ['5.1.2.e']);

	}//end testWithoutOpenRegisterTheSeamIsAbsent()

	public function testDetectionKeepsTheSeamsOutcome(): void {
		$this->seam->detection = new ImageDetection(
			detected: true,
			regions: [new ImageRegion(page: 4, box: ['x' => 0.61, 'y' => 0.78, 'w' => 0.24, 'h' => 0.09], entityType: 'SIGNATURE', confidence: 0.83, source: 'anonymiq 1.0', relationId: 9)],
			detectors: ['anonymiq' => ['status' => 'ran', 'reason' => null]]
		);

		$outcome = $this->gateway()->detect(file: $this->file(mime: 'application/pdf'));

		$this->assertTrue($outcome['detected']);
		$this->assertSame('ran', $outcome['objectDetection']);
		$this->assertSame('SIGNATURE', $outcome['regions'][0]['entityType']);
		$this->assertSame(9, $outcome['regions'][0]['relationId']);

	}//end testDetectionKeepsTheSeamsOutcome()

	public function testAnUndetectableFileSaysWhy(): void {
		$outcome = $this->gateway()->detect(file: $this->file(mime: 'image/png'));

		$this->assertFalse($outcome['detected']);
		$this->assertStringStartsWith('No OCR words', (string) $outcome['reason']);
		$this->assertSame('not-run', $outcome['objectDetection']);

	}//end testAnUndetectableFileSaysWhy()

	public function testDecidedRegionsAreCounted(): void {
		$box = ['x' => 0.0, 'y' => 0.0, 'w' => 0.1, 'h' => 0.1];
		$this->seam->decided = [
			new ImageRegion(page: null, box: $box, entityType: 'PERSON', confidence: 0.9, source: 'ocr-text'),
			new ImageRegion(page: null, box: $box, entityType: 'BSN', confidence: 0.9, source: 'ocr-text'),
		];

		$this->assertSame(2, $this->gateway()->decidedRegionCount(file: $this->file(mime: 'image/png')));
		$this->assertSame(0, $this->gateway(installed: false)->decidedRegionCount(file: $this->file(mime: 'image/png')));

	}//end testDecidedRegionsAreCounted()

	public function testDetectionWithoutTheSeamIsRefused(): void {
		$this->expectException(ScopeDecisionRefusedException::class);

		$this->gateway(installed: false)->detect(file: $this->file(mime: 'image/png'));

	}//end testDetectionWithoutTheSeamIsRefused()

	public function testWithTheSeamInstalledItIsAvailable(): void {
		$this->assertTrue($this->gateway()->available());

	}//end testWithTheSeamInstalledItIsAvailable()
}//end class
