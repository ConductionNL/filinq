<?php

/**
 * OCR runs with the admin's settings, records what it found, never the text,
 * and names why when it cannot run.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

use OCA\Filinq\Service\Ocr\OcrRunService;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

/**
 * OcrRunService and OcrService::processNode().
 */
class OcrRunServiceTest extends TestCase {

	use OcrDoubles;

	/**
	 * A run records confidence, settings, length and trigger, and not the text.
	 *
	 * @return void
	 */
	public function testARunRecordsWhatItFoundButNotTheText(): void {
		$outcome = $this->ocrRunService($this->ocrService())->run(file: $this->ocrFile(), trigger: 'manual');

		$this->assertTrue($outcome['processed']);
		$this->assertSame('Jan Jansen, BSN 111222333', $outcome['text']);
		$this->assertSame(812004, $outcome['result']['fileId']);
		$this->assertSame(91.4, $outcome['result']['confidence']);
		$this->assertSame('nld+eng', $outcome['result']['languages']);
		$this->assertSame(300, $outcome['result']['dpi']);
		$this->assertSame(25, $outcome['result']['textLength']);
		$this->assertSame('manual', $outcome['result']['triggeredBy']);
		$this->assertSame('tesseract 5.3.0', $outcome['result']['engineVersion']);
		$this->assertSame('2026-09-29T10:00:00+00:00', $outcome['result']['ocrProcessedAt']);
		$this->assertStringNotContainsString('111222333', (string) json_encode($this->ocrWritten));

	}//end testARunRecordsWhatItFoundButNotTheText()

	/**
	 * Custom languages are applied and recorded; so is the DPI.
	 *
	 * @return void
	 */
	public function testCustomLanguagesAndDpiAreAppliedAndRecorded(): void {
		$ocr = $this->ocrService(config: ['ocr.default_languages' => 'nld+deu', 'ocr_dpi' => '200']);
		$ocr->expects($this->once())->method('extractTextFromPdf')
			->with($this->anything(), 'nld+deu', 200)
			->willReturn(['text' => 'Grüße', 'confidence' => 80.0]);

		$outcome = $this->ocrRunService($ocr)->run(file: $this->ocrFile(), trigger: 'fallback');

		$this->assertSame('nld+deu', $outcome['result']['languages']);
		$this->assertSame(200, $outcome['result']['dpi']);
		$this->assertSame('fallback', $outcome['result']['triggeredBy']);

	}//end testCustomLanguagesAndDpiAreAppliedAndRecorded()

	/**
	 * An image goes to the image path, never through the PDF rasteriser.
	 *
	 * @return void
	 */
	public function testAnImageGoesStraightToTesseract(): void {
		$ocr = $this->ocrService();
		$ocr->expects($this->never())->method('extractTextFromPdf');
		$ocr->expects($this->once())->method('extractTextFromImage')
			->willReturn(['text' => 'foto', 'confidence' => 70.0]);

		$outcome = $this->ocrRunService($ocr)->run(file: $this->ocrFile(mimeType: 'image/png'), trigger: 'manual');

		$this->assertTrue($outcome['processed']);

	}//end testAnImageGoesStraightToTesseract()

	/**
	 * A second run updates the file's one row.
	 *
	 * @return void
	 */
	public function testARerunUpdatesTheSameRow(): void {
		$service = $this->ocrRunService($this->ocrService());
		$first = $service->run(file: $this->ocrFile(), trigger: 'manual');
		$second = $service->run(file: $this->ocrFile(), trigger: 'fallback');

		$this->assertSame($first['result']['uuid'], $second['result']['uuid']);
		$this->assertCount(1, $this->ocrRows);

	}//end testARerunUpdatesTheSameRow()

	/**
	 * Each reason OCR cannot run is named, and nothing is stored.
	 *
	 * @return void
	 */
	public function testEveryRefusalIsNamedAndStoresNothing(): void {
		$cases = [
			OcrRunService::SKIP_DISABLED => [$this->ocrService(config: ['ocr_enabled' => '0']), 'application/pdf'],
			OcrRunService::SKIP_NO_TESSERACT => [$this->ocrService(tesseract: false), 'application/pdf'],
			OcrRunService::SKIP_NOT_CANDIDATE => [$this->ocrService(), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
			OcrRunService::SKIP_NO_TEXT => [$this->ocrService(text: "  \n"), 'image/tiff'],
		];

		foreach ($cases as $reason => [$ocr, $mimeType]) {
			$outcome = $this->ocrRunService($ocr)->run(file: $this->ocrFile(mimeType: $mimeType), trigger: 'manual');

			$this->assertFalse($outcome['processed'], $reason);
			$this->assertSame($reason, $outcome['reason']);
		}

		$this->assertSame([], $this->ocrWritten);

	}//end testEveryRefusalIsNamedAndStoresNothing()

	/**
	 * Every payload the service writes validates against the real schema fragment.
	 *
	 * @return void
	 */
	public function testEveryPayloadItWritesValidatesAgainstTheRegister(): void {
		$service = $this->ocrRunService($this->ocrService(confidence: 104.0));
		$service->run(file: $this->ocrFile(), trigger: 'manual');
		$service->run(file: $this->ocrFile(id: 5, mimeType: 'image/jpeg'), trigger: 'fallback');

		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../../lib/Settings/filinq_register.json'), true);
		$schema = $register['components']['schemas']['ocrResult'];
		$this->assertContains('ocrResult', $register['components']['registers']['filinq']['schemas']);
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(
			['type' => 'object', 'required' => $schema['required'], 'properties' => $properties, 'additionalProperties' => false]
		);

		$this->assertCount(2, $this->ocrWritten);
		foreach ($this->ocrWritten as $payload) {
			$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
			$message = '';
			if ($result->isValid() === false) {
				$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
			}

			$this->assertTrue($result->isValid(), $message);
		}

	}//end testEveryPayloadItWritesValidatesAgainstTheRegister()
}//end class
