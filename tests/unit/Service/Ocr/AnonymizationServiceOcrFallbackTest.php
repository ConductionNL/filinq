<?php

/**
 * A scan that OpenRegister extracted no text from gets OCR, and the extraction
 * result says when detection could not see it.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\AnonymizationService;
use OCA\Filinq\Service\Ocr\OcrExtractionFallback;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Tests\Unit\Service\BuildsAnonymizationService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCA\OpenRegister\Files\Service\FileExtractionOptions;
use OCA\OpenRegister\Files\Service\ProvidedTextRequest;
use OCA\OpenRegister\Files\Service\TextExtractionService;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * AnonymizationService::extractAndDetectEntities() over OcrExtractionFallback.
 */
class AnonymizationServiceOcrFallbackTest extends TestCase {

	use BuildsAnonymizationService;
	use OcrDoubles;

	/**
	 * A born-digital PDF skips OCR: the result is what it was before.
	 *
	 * @return void
	 */
	public function testABornDigitalPdfSkipsOcr(): void {
		$ocr = $this->ocrService();
		$ocr->expects($this->never())->method('extractTextFromPdf');

		$result = $this->serviceOver(ocr: $ocr, extractor: $this->extractorWith(text: 'Besluit op bezwaar van J. Jansen'))
			->extractAndDetectEntities(fileId: 812004);

		$this->assertArrayNotHasKey('ocr', $result);
		$this->assertArrayNotHasKey('ocrSkipped', $result);
		$this->assertArrayNotHasKey('ocrDetectionPending', $result);
		$this->assertSame([], $this->ocrWritten);

	}//end testABornDigitalPdfSkipsOcr()

	/**
	 * A scan on an OpenRegister without the seam: OCR runs, the result is
	 * recorded as a fallback run, and detection is flagged pending.
	 *
	 * @return void
	 */
	public function testASeamlessOpenRegisterDegradesFailFlagged(): void {
		$result = $this->serviceOver(ocr: $this->ocrService(), extractor: $this->extractorWith(text: null))
			->extractAndDetectEntities(fileId: 812004);

		$this->assertTrue($result['ocrDetectionPending']);
		$this->assertTrue($result['ocr']['ran']);
		$this->assertFalse($result['ocr']['ingested']);
		$this->assertSame(91.4, $result['ocr']['confidence']);
		$this->assertSame('fallback', $this->ocrWritten[0]['triggeredBy']);

	}//end testASeamlessOpenRegisterDegradesFailFlagged()

	/**
	 * A scan with Tesseract missing is flagged, not silent.
	 *
	 * @return void
	 */
	public function testAScanWithOcrUnavailableIsFlagged(): void {
		$result = $this->serviceOver(ocr: $this->ocrService(tesseract: false), extractor: $this->extractorWith(text: ''))
			->extractAndDetectEntities(fileId: 812004);

		$this->assertSame('tesseract_unavailable', $result['ocrSkipped']);
		$this->assertFalse($result['ocr']['ran']);

	}//end testAScanWithOcrUnavailableIsFlagged()

	/**
	 * With the seam OpenRegister gets the recovered text before the entities are read,
	 * as the ProvidedTextRequest OpenRegister 2.2 takes.
	 *
	 * @return void
	 */
	public function testWithTheSeamOpenRegisterGetsTheText(): void {
		$extractor = new class extends TextExtractionService {
			/**
			 * The file id and text of every provided-text call.
			 *
			 * @var array<int, array{0: int, 1: string}>
			 */
			public array $ingested = [];

			/**
			 * Record the request.
			 *
			 * @param ProvidedTextRequest $request The provided text.
			 *
			 * @return void
			 */
			public function extractFromProvidedText(ProvidedTextRequest $request): void {
				$this->ingested[] = [$request->fileId, $request->text];
			}
		};

		$result = $this->serviceOver(ocr: $this->ocrService(), extractor: $extractor)->extractAndDetectEntities(fileId: 812004);

		$this->assertSame(expected: [[812004, 'Jan Jansen, BSN 111222333']], actual: $extractor->ingested);
		$this->assertFalse(condition: $result['ocrDetectionPending']);
		$this->assertTrue(condition: $result['ocr']['ingested']);

	}//end testWithTheSeamOpenRegisterGetsTheText()

	/**
	 * An OpenRegister before 2.2 takes the file id and text as positional arguments.
	 *
	 * @return void
	 */
	public function testAPositionalSeamStillGetsTheText(): void {
		$extractor = new class {
			/**
			 * The file id and text of every provided-text call.
			 *
			 * @var array<int, array{0: int, 1: string}>
			 */
			public array $ingested = [];

			/**
			 * Extract nothing, in the positional shape.
			 *
			 * @param int $fileId The file id.
			 * @param bool $force Whether to re-extract.
			 * @param array<int, string>|null $entityTypes The entity types.
			 *
			 * @return void
			 */
			public function extractFile(int $fileId, bool $force = false, ?array $entityTypes = null): void {
			}

			/**
			 * No text: the file is a scan.
			 *
			 * @param int $fileId The file id.
			 *
			 * @return string|null Always null.
			 */
			public function getExtractedText(int $fileId): ?string {
				return null;
			}

			/**
			 * Record the positional call.
			 *
			 * @param int $fileId The file id.
			 * @param string $text The provided text.
			 *
			 * @return void
			 */
			public function extractFromProvidedText(int $fileId, string $text): void {
				$this->ingested[] = [$fileId, $text];
			}
		};

		$result = $this->serviceOver(ocr: $this->ocrService(), extractor: $extractor)->extractAndDetectEntities(fileId: 812004);

		$this->assertSame(expected: [[812004, 'Jan Jansen, BSN 111222333']], actual: $extractor->ingested);
		$this->assertFalse(condition: $result['ocrDetectionPending']);

	}//end testAPositionalSeamStillGetsTheText()

	/**
	 * Reopening a scan whose text had nowhere to go does not run Tesseract again.
	 *
	 * @return void
	 */
	public function testReopeningDoesNotRunOcrAgain(): void {
		$ocr = $this->ocrService();
		$ocr->expects($this->once())->method('extractTextFromPdf');
		$service = $this->serviceOver(ocr: $ocr, extractor: $this->extractorWith(text: null));

		$service->extractAndDetectEntities(fileId: 812004);
		$again = $service->extractAndDetectEntities(fileId: 812004);

		$this->assertTrue($again['ocrDetectionPending']);
		$this->assertSame(91.4, $again['ocr']['confidence']);

	}//end testReopeningDoesNotRunOcrAgain()

	/**
	 * An OpenRegister TextExtractionService without the provided-text seam,
	 * with a fixed extracted text.
	 *
	 * @param string|null $text What getExtractedText() answers.
	 *
	 * @return object The double.
	 */
	private function extractorWith(?string $text): object {
		return new class($text) {
			/**
			 * Constructor.
			 *
			 * @param string|null $text What getExtractedText() answers.
			 */
			public function __construct(private readonly ?string $text) {
			}

			/**
			 * Extract nothing.
			 *
			 * @param int $fileId The file id.
			 * @param FileExtractionOptions $options The options.
			 *
			 * @return void
			 */
			public function extractFile(int $fileId, FileExtractionOptions $options = new FileExtractionOptions()): void {
			}

			/**
			 * The fixed text.
			 *
			 * @param int $fileId The file id.
			 *
			 * @return string|null The text.
			 */
			public function getExtractedText(int $fileId): ?string {
				return $this->text;
			}
		};

	}//end extractorWith()

	/**
	 * The service with a real fallback over the given engine and extractor.
	 *
	 * @param OcrService $ocr The engine.
	 * @param object $extractor OpenRegister's extractor.
	 *
	 * @return AnonymizationService The service.
	 */
	private function serviceOver(OcrService $ocr, object $extractor): AnonymizationService {
		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturn([]);
		$basis = $this->createMock(\OCA\Filinq\Service\LegalBasisProposalService::class);
		$basis->method('enrichEntitiesWithBases')->willReturnArgument(0);

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			static fn (string $class) => match ($class) {
				'OCA\OpenRegister\Files\Service\TextExtractionService' => $extractor,
				'OCA\OpenRegister\Db\EntityRelationMapper' => $mapper,
				'OCA\Filinq\Service\LegalBasisProposalService' => $basis,
				default => throw new \RuntimeException('Unknown service: ' . $class),
			}
		);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturn($this->ocrFile());
		$repository = $this->ocrResultRepository();

		return $this->makeAnonymizationServiceFrom(
			deps: [
				'container' => $container,
				'appManager' => $apps,
				'ocrFallback' => new OcrExtractionFallback(
					$ocr,
					$this->ocrRunServiceOver(ocr: $ocr, repository: $repository),
					$repository,
					$root,
					new NullLogger()
				),
			]
		);

	}//end serviceOver()
}//end class
