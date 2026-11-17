<?php

/**
 * extractAndDetectEntities() and the anonymise run carry the image leg:
 * a scan the seam cannot look at is flagged, found regions join the rows,
 * and a run that did not burn image content says so on its result.
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
 * @spec openspec/changes/image-redaction/specs/anonymization/spec.md#requirement-the-extract-response-carries-image-origin-entities-with-region-geometry-req-ddimr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Review;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\DocumentAnonymizeRunner;
use OCA\Filinq\Service\Ocr\OcrExtractionFallback;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Service\Review\ImageRedactionService;
use OCA\Filinq\Service\Review\ImageSeamGateway;
use OCA\Filinq\Tests\Unit\Service\BuildsAnonymizationService;
use OCA\Filinq\Tests\Unit\Service\Ocr\OcrDoubles;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Files\Service\FileExtractionOptions;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * @covers \OCA\Filinq\Service\AnonymizationService
 * @uses   \OCA\Filinq\Service\Review\ImageRedactionService
 */
class AnonymizationServiceImageLegTest extends TestCase {

	use BuildsAnonymizationService;
	use OcrDoubles;

	public function testAScanWithoutTheSeamIsFlaggedOnExtract(): void {
		$seam = $this->createMock(ImageSeamGateway::class);
		$seam->method('available')->willReturn(false);

		$result = $this->serviceOver(seam: $seam)->extractAndDetectEntities(fileId: 812004);

		$this->assertTrue($result['ocr']['ran']);
		$this->assertSame(['reason' => 'backend_unavailable'], $result['imageDetectionSkipped']);

	}//end testAScanWithoutTheSeamIsFlaggedOnExtract()

	public function testASignatureFoundOnAScanBecomesAReviewRow(): void {
		$seam = $this->createMock(ImageSeamGateway::class);
		$seam->method('available')->willReturn(true);
		$seam->method('detect')->willReturn(
			[
				'detected' => true,
				'reason' => null,
				'regions' => [['page' => 4, 'box' => ['x' => 0.61, 'y' => 0.78, 'w' => 0.24, 'h' => 0.09], 'entityType' => 'SIGNATURE', 'confidence' => 0.83, 'relationId' => 9]],
				'detectors' => [],
				'objectDetection' => 'ran',
				'objectDetectionReason' => null,
			]
		);

		$result = $this->serviceOver(seam: $seam)->extractAndDetectEntities(fileId: 812004);

		$this->assertArrayNotHasKey('imageDetectionSkipped', $result);
		$this->assertSame(1, $result['imageRegionCount']);
		$this->assertSame('SIGNATURE', $result['entities'][0]['type']);
		$this->assertSame('image', $result['entities'][0]['origin']);

	}//end testASignatureFoundOnAScanBecomesAReviewRow()

	public function testRegionsTheSeamStoresAreReadBackAsOrdinaryRows(): void {
		$detectedFirst = false;
		$seam = $this->createMock(ImageSeamGateway::class);
		$seam->method('available')->willReturn(true);
		$seam->method('detect')->willReturnCallback(
			static function () use (&$detectedFirst): array {
				$detectedFirst = true;

				return [
					'detected' => true,
					'reason' => null,
					'regions' => [['page' => 1, 'box' => ['x' => 0.1, 'y' => 0.2, 'w' => 0.3, 'h' => 0.05], 'entityType' => 'BSN', 'confidence' => 0.9, 'relationId' => 7]],
					'detectors' => [],
					'objectDetection' => 'not-run',
					'objectDetectionReason' => 'No region detector ran.',
				];
			}
		);
		$rows = static function () use (&$detectedFirst): array {
			return $detectedFirst === true ? [['entity_type' => 'BSN', 'entity_value' => '111222333', 'relation_id' => 7, 'confidence' => 0.9]] : [];
		};

		$result = $this->serviceOver(seam: $seam, rows: $rows)->extractAndDetectEntities(fileId: 812004);

		$this->assertCount(1, $result['entities'], 'The region joins the row the seam stored, once.');
		$this->assertSame('111222333', $result['entities'][0]['value']);
		$this->assertSame('image', $result['entities'][0]['origin']);
		$this->assertSame('not-run', $result['objectDetection']);

	}//end testRegionsTheSeamStoresAreReadBackAsOrdinaryRows()

	public function testWithoutTheImageLegTheResultIsUnchanged(): void {
		$result = $this->serviceOver(seam: null)->extractAndDetectEntities(fileId: 812004);

		$this->assertArrayNotHasKey('imageDetectionSkipped', $result);
		$this->assertArrayNotHasKey('imageRegionCount', $result);

	}//end testWithoutTheImageLegTheResultIsUnchanged()

	public function testAnAnonymiseThatDidNotBurnTheScanSaysSo(): void {
		$seam = $this->createMock(ImageSeamGateway::class);
		$seam->method('available')->willReturn(true);
		$seam->method('decidedRegionCount')->willReturn(2);
		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->method('run')->willReturn(['anonymizedFileId' => 812441, 'replacementCount' => 1]);
		$runner->expects($this->once())->method('recordImageRedaction')
			->with(812004, ['imageRedactionPending' => true, 'burnedRegionCount' => 0]);

		$result = $this->serviceOver(seam: $seam, runner: $runner)->anonymizeDocument(fileId: 812004, entities: []);

		$this->assertTrue($result['imageRedactionPending']);
		$this->assertSame(0, $result['burnedRegionCount']);
		$this->assertSame(812441, $result['anonymizedFileId']);

	}//end testAnAnonymiseThatDidNotBurnTheScanSaysSo()

	public function testAnAnonymiseWithoutImageContentRecordsNothing(): void {
		$seam = $this->createMock(ImageSeamGateway::class);
		$seam->method('available')->willReturn(true);
		$seam->method('decidedRegionCount')->willReturn(0);
		$runner = $this->createMock(DocumentAnonymizeRunner::class);
		$runner->method('run')->willReturn(['anonymizedFileId' => 812441]);
		$runner->expects($this->never())->method('recordImageRedaction');

		$result = $this->serviceOver(seam: $seam, runner: $runner)->anonymizeDocument(fileId: 812004, entities: []);

		$this->assertArrayNotHasKey('imageRedactionPending', $result);

	}//end testAnAnonymiseWithoutImageContentRecordsNothing()

	/**
	 * The service over a scan: OpenRegister extracts no text, so OCR runs.
	 *
	 * @param ImageSeamGateway|null        $seam   The seam, or null for no image leg.
	 * @param DocumentAnonymizeRunner|null $runner The anonymise runner, or the real one.
	 * @param callable|null                $rows   What the relation mapper reads back, when asked.
	 *
	 * @return AnonymizationService The service.
	 */
	private function serviceOver(?ImageSeamGateway $seam, ?DocumentAnonymizeRunner $runner = null, ?callable $rows = null): AnonymizationService {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturnCallback($rows ?? static fn (): array => []);
		$basis = $this->createMock(\OCA\Filinq\Service\LegalBasisProposalService::class);
		$basis->method('enrichEntitiesWithBases')->willReturnArgument(0);
		$file = $this->ocrFile();
		$fileService = new class ($file) {

			public function __construct(private readonly object $file) {
			}

			public function getFileById(int $fileId): object {
				return $this->file;
			}
		};
		$extractor = new class {

			public function extractFile(int $fileId, FileExtractionOptions $options = new FileExtractionOptions()): void {
			}

			public function getExtractedText(int $fileId): ?string {
				return null;
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $class) => match ($class) {
				'OCA\OpenRegister\Files\Service\TextExtractionService' => $extractor,
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				'OCA\OpenRegister\Service\FileService' => $fileService,
				'OCA\Filinq\Service\LegalBasisProposalService' => $basis,
				default => throw new \RuntimeException('Unknown service: ' . $class),
			}
		);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturn($file);
		$ocr = $this->ocrService();
		$repository = $this->ocrResultRepository();

		$deps = [
			'container' => $container,
			'appManager' => $apps,
			'ocrFallback' => new OcrExtractionFallback($ocr, $this->ocrRunServiceOver(ocr: $ocr, repository: $repository), $repository, $root, new NullLogger()),
		];
		if ($runner !== null) {
			$deps['anonymizeRunner'] = $runner;
		}

		if ($seam !== null) {
			$deps['images'] = new ImageRedactionService(seam: $seam);
		}

		return $this->makeAnonymizationServiceFrom(deps: $deps);

	}//end serviceOver()
}//end class
