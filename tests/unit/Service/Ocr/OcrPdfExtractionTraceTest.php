<?php

/**
 * Trace tests for OcrService::extractTextFromPdf: the Imagick calls, the
 * per-page OCR calls, the temp files, the log lines and the answer.
 *
 * Recorded on the implementation before the method was split into steps.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/ocr-document-scanning/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Ocr;

require_once __DIR__ . '/ImagickPdfDoubles.php';

use Exception;
use OCA\Filinq\Service\OcrService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

/**
 * The PDF OCR trace per scenario, compared with a recorded one.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Ocr
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class OcrPdfExtractionTraceTest extends TestCase {

	/**
	 * Restore the real extension check.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		ImagickPdfWorld::reset();
		parent::tearDown();

	}//end tearDown()

	/**
	 * The scenarios: tesseract available, imagick available, pages, per-page answers.
	 *
	 * @return array<string, array{0: bool, 1: bool, 2: int, 3: array<int, mixed>}> Name => [tesseract, imagick, pages, answers].
	 */
	public static function scenarios(): array {
		return [
			'no tesseract' => [false, true, 2, []],
			'no imagick' => [true, false, 2, []],
			'no pages' => [true, true, 0, []],
			'one page' => [true, true, 1, [['  Hallo  ', 91.25]]],
			'three pages' => [true, true, 3, [['Een', 90.0], ['', 0.0], ['Drie', 85.55]]],
			'the second page fails' => [true, true, 3, [['Een', 90.0], 'fail', ['Drie', 85.0]]],
		];
	}//end scenarios()

	/**
	 * The trace matches the recorded one.
	 *
	 * @param bool              $tesseract Whether Tesseract answers as available.
	 * @param bool              $imagick   Whether the Imagick extension answers as loaded.
	 * @param int               $pages     The page count.
	 * @param array<int, mixed> $answers   Per page: [text, confidence] or 'fail'.
	 *
	 * @return void
	 *
	 * @dataProvider scenarios
	 */
	public function testTrace(bool $tesseract, bool $imagick, int $pages, array $answers): void {
		ImagickPdfWorld::$extensionLoaded = $imagick;
		ImagickPdfWorld::$pages = $pages;
		$logger = new class extends AbstractLogger {
			/**
			 * The lines logged.
			 *
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array             $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$service = new class (
			$logger,
			$this->createMock(IAppConfig::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class)
		) extends OcrService {
			/**
			 * Whether Tesseract answers as available.
			 *
			 * @var bool
			 */
			public bool $tesseract = true;

			/**
			 * Per page: [text, confidence] or 'fail'.
			 *
			 * @var array<int, mixed>
			 */
			public array $answers = [];

			/**
			 * Tesseract as the scenario says.
			 *
			 * @return bool Available or not.
			 */
			public function isTesseractAvailable(): bool {
				ImagickPdfWorld::record(['isTesseractAvailable']);
				return $this->tesseract;
			}

			/**
			 * One page's OCR as the scenario says.
			 *
			 * @param string $filePath  The page image.
			 * @param string $languages The languages.
			 * @param int    $dpi       The DPI.
			 *
			 * @return array{text: string, confidence: float} The page's text and confidence.
			 */
			public function extractTextFromImage(string $filePath, string $languages = 'nld+eng', int $dpi = 300): array {
				ImagickPdfWorld::record(['extractTextFromImage', $filePath, file_exists($filePath), $languages, $dpi]);
				$answer = array_shift($this->answers);
				if ($answer === 'fail') {
					throw new Exception('page unreadable');
				}

				return ['text' => $answer[0], 'confidence' => $answer[1]];
			}
		};
		$service->tesseract = $tesseract;
		$service->answers = $answers;

		try {
			$outcome = ['result' => $service->extractTextFromPdf(filePath: '/data/scan.pdf', languages: 'deu+eng', dpi: 150)];
		} catch (Throwable $e) {
			$outcome = ['exception' => [$e::class, $e->getMessage(), ($e->getPrevious() !== null ? $e->getPrevious()->getMessage() : null)]];
		}

		$leftover = array_map(
			static fn (string $path): array => [basename($path), is_file($path), is_dir(dirname($path))],
			ImagickPdfWorld::$written
		);

		$trace = ['outcome' => $outcome, 'calls' => ImagickPdfWorld::$calls, 'logs' => $logger->lines, 'leftover' => $leftover];
		$this->assertSame($this->recorded(trace: $trace), json_decode((string)json_encode($trace), true));

	}//end testTrace()

	/**
	 * Without Tesseract an image is not read: one warning and the empty answer.
	 *
	 * @return void
	 */
	public function testImageWithoutTesseractIsSkipped(): void {
		$logger = $this->createMock(\Psr\Log\LoggerInterface::class);
		$logger->expects($this->once())->method('warning')->with('Tesseract is not available, skipping OCR');
		$service = new class (
			$logger,
			$this->createMock(IAppConfig::class),
			$this->createMock(IRootFolder::class),
			$this->createMock(IUserSession::class)
		) extends OcrService {
			/**
			 * Tesseract is absent.
			 *
			 * @return bool False.
			 */
			public function isTesseractAvailable(): bool {
				return false;
			}
		};

		$this->assertSame(['text' => '', 'confidence' => 0.0], $service->extractTextFromImage(filePath: '/data/page.png'));

	}//end testImageWithoutTesseractIsSkipped()

	/**
	 * The recorded trace of this scenario, recording it first when asked to.
	 *
	 * @param array<string, mixed> $trace The trace just made.
	 *
	 * @return array<string, mixed>|null The recorded trace.
	 */
	private function recorded(array $trace): ?array {
		$path = __DIR__ . '/../golden/ocr-pdf-extraction-trace.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = json_decode((string)json_encode($trace), true);
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		return ($recorded[$name] ?? null);

	}//end recorded()
}//end class
