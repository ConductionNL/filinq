<?php

/**
 * The reading job moves an intake document through reading to read or
 * failed, keeps the text on the record, and writes only what the register
 * accepts.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-intake-ocr-on-arrival/tasks.md#task-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\BackgroundJob;

use OCA\Filinq\BackgroundJob\IntakeOcrJob;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Intake\IntakeReadingProgress;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\OcrService;
use OCA\Filinq\Tests\Unit\Service\Ocr\OcrDoubles;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * IntakeOcrJob.
 */
class IntakeOcrJobTest extends TestCase {

	use OcrDoubles;

	/**
	 * Every payload the job saved, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $saved = [];

	/**
	 * Text recognised: the text is on the record and the state is read.
	 *
	 * @return void
	 */
	public function testRecognisedTextIsKeptAndTheDocumentIsRead(): void {
		$this->runJob(ocr: $this->ocrService(text: '  Bezwaar tegen besluit Z-2026-114  '));

		$this->assertSame(['reading', 'read'], array_column($this->saved, 'readingState'));
		$this->assertSame('Bezwaar tegen besluit Z-2026-114', $this->saved[1]['contentText']);
		$this->assertSame('', $this->saved[1]['readingError']);

	}//end testRecognisedTextIsKeptAndTheDocumentIsRead()

	/**
	 * OCR off, no text, no file, an exception: failed, each with its reason.
	 *
	 * @return void
	 */
	public function testEveryFailureEndsInFailedWithAReason(): void {
		$cases = [
			IntakeOcrJob::REASON_UNAVAILABLE => [$this->ocrService(tesseract: false), true],
			IntakeOcrJob::REASON_NO_TEXT => [$this->ocrService(text: ' '), true],
			IntakeOcrJob::REASON_NO_FILE => [$this->ocrService(), false],
		];

		foreach ($cases as $reason => [$ocr, $fileExists]) {
			$this->saved = [];
			$this->runJob(ocr: $ocr, fileExists: $fileExists);

			$last = end($this->saved);
			$this->assertSame('failed', $last['readingState'], $reason);
			$this->assertSame($reason, $last['readingError']);
			$this->assertArrayNotHasKey('contentText', $last);
		}

		$this->saved = [];
		$broken = $this->ocrService();
		$broken->method('extractTextFromPdf')->willThrowException(new \RuntimeException('Imagick missing'));
		$this->runJob(ocr: $broken);
		$this->assertStringContainsString('Imagick missing', (string) end($this->saved)['readingError']);

	}//end testEveryFailureEndsInFailedWithAReason()

	/**
	 * Every payload the job writes validates against the real intakeDocument fragment.
	 *
	 * @return void
	 */
	public function testEveryPayloadValidatesAgainstTheRegister(): void {
		$this->runJob(ocr: $this->ocrService());
		$this->runJob(ocr: $this->ocrService(text: ''));

		$register = json_decode((string) file_get_contents(__DIR__ . '/../../../lib/Settings/filinq_register.json'), true);
		$schema = $register['components']['schemas']['intakeDocument'];
		$properties = [];
		foreach ($schema['properties'] as $name => $property) {
			unset($property['required'], $property['visible'], $property['order'], $property['facetable'], $property['x-enum-labels']);
			$properties[$name] = $property;
		}

		$json = (string) json_encode(['type' => 'object', 'required' => $schema['required'], 'properties' => $properties]);
		$this->assertCount(4, $this->saved);
		foreach ($this->saved as $payload) {
			$result = (new Validator())->validate(json_decode((string) json_encode($payload)), $json);
			$message = '';
			if ($result->isValid() === false) {
				$message = (string) json_encode((new ErrorFormatter())->format($result->error()));
			}

			$this->assertTrue($result->isValid(), $message);
		}

	}//end testEveryPayloadValidatesAgainstTheRegister()

	/**
	 * Run the job on intake-1 (file 4711).
	 *
	 * @param OcrService $ocr The engine.
	 * @param bool $fileExists Whether the file can be found.
	 *
	 * @return void
	 */
	private function runJob(OcrService $ocr, bool $fileExists = true): void {
		$stored = [
			'uuid' => 'intake-1',
			'channel' => 'scan',
			'status' => 'received',
			'file' => 4711,
			'fileName' => 'scan-0001.pdf',
			'readingState' => 'queued',
			'readingError' => '',
			'readingUpdatedAt' => '2026-09-29T08:00:00+00:00',
		];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('find')->willReturn($stored);
		$objects->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = '', ?string $uuid = null): array {
				$this->saved[] = $object;

				return $object + ['uuid' => $uuid];
			}
		);
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		$root = $this->createMock(IRootFolder::class);
		$root->method('getFirstNodeById')->willReturn($fileExists === true ? $this->ocrFile(id: 4711) : null);
		$time = $this->createMock(ITimeFactory::class);
		$time->method('getDateTime')->willReturn(new \DateTime('2026-09-29T08:05:00+00:00'));

		$job = new IntakeOcrJob(
			$time,
			new IntakeRepository($resolver, new NullLogger()),
			$ocr,
			$root,
			new IntakeReadingProgress(),
			new NullLogger()
		);

		$run = new \ReflectionMethod($job, 'run');
		$run->invoke($job, ['uuid' => 'intake-1', 'fileId' => 4711]);

	}//end runJob()
}//end class
