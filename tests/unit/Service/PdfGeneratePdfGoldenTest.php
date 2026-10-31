<?php

/**
 * Golden test for the mPDF path of PdfService: what lands in the PDF for each option set.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Every option set through generatePdfFromHtml(), compared with JSON recorded
 * from the implementation before generatePdf() was split.
 *
 * @covers \OCA\Filinq\Service\PdfService
 */
class PdfGeneratePdfGoldenTest extends TestCase {

	/**
	 * The option sets.
	 *
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		return [
			'defaults' => [[]],
			'a title' => [['title' => 'Besluit']],
			'an empty title' => [['title' => '']],
			'an author' => [['author' => 'Gemeente Zuiddrecht']],
			'a numeric author' => [['author' => 42]],
			'pdfa' => [['pdfa' => true]],
			'pdfa as a string' => [['pdfa' => 'true']],
			'pdfa with title and case' => [['pdfa' => true, 'title' => 'Besluit', 'caseReference' => 'Z-2026-1']],
			'pdfa with a numeric case' => [['pdfa' => true, 'caseReference' => 7]],
			'pdfa without a case' => [['pdfa' => true, 'title' => 'Besluit', 'author' => 'Anna']],
			'case without pdfa' => [['caseReference' => 'Z-2026-1', 'title' => 'Besluit']],
			'crop marks' => [['cropMarks' => true]],
			'crop marks as a string' => [['cropMarks' => 'yes']],
			'crop marks with pdfa' => [['cropMarks' => true, 'pdfa' => true]],
			'landscape A3 with margins' => [['format' => 'A3', 'orientation' => 'L', 'margin' => ['top' => 5, 'left' => 30]]],
			'pdfa landscape' => [['pdfa' => true, 'format' => 'A5', 'orientation' => 'L']],
			'accessible without a renderer' => [['accessible' => true, 'title' => 'Besluit']],
			'accessible as a string' => [['accessible' => 'true', 'title' => 'Besluit']],
			'an unknown page format' => [['format' => 'Z9']],
		];
	}//end scenarios()

	/**
	 * The fingerprint of the PDF (or the failure and the log) matches the recorded one.
	 *
	 * @param array<string, mixed> $options The PDF options.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testFingerprint(array $options): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line, with the exception's class.
			 *
			 * @param mixed             $level   The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed>      $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$exception = $context['exception'] ?? null;
				$context['exception'] = $exception === null ? null : $exception::class;
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$service = new class($logger, $this->createMock(TemplateRenderer::class)) extends PdfService {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $calls = [];

			/**
			 * Record the call and mark the HTML, so the prefix shows in the PDF.
			 *
			 * @param string $format      The format.
			 * @param string $orientation The orientation.
			 *
			 * @return string
			 */
			public function buildPrintCss(string $format, string $orientation): string {
				$this->calls[] = ['buildPrintCss', $format, $orientation];
				return parent::buildPrintCss($format, $orientation) . '<p>print css</p>';
			}

			/**
			 * Record the call and mark the HTML, so the wrapper shows in the PDF.
			 *
			 * @param string $html The HTML.
			 *
			 * @return string
			 */
			public function buildCropMarksHtml(string $html): string {
				$this->calls[] = ['buildCropMarksHtml', sha1($html)];
				return '<p>crop marks</p>' . parent::buildCropMarksHtml($html);
			}
		};
		$html = '<html><body><h1>Besluit</h1><table><thead><tr><th>Kop</th></tr></thead>'
			. '<tbody><tr><td>Regel</td></tr></tbody></table><p class="no-print">Niet printen</p></body></html>';

		try {
			$trace = ['pdf' => $this->fingerprint($service->generatePdfFromHtml($html, $options))];
		} catch (Exception $e) {
			$trace = ['error' => [$e::class, $e->getMessage(), $e->getCode(), $e->getPrevious() === null ? null : $e->getPrevious()::class]];
		}

		$trace['calls'] = $service->calls;
		$trace['logs'] = $logger->lines;

		$path = __DIR__ . '/golden/pdf-generate-pdf.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testFingerprint()

	/**
	 * What the PDF says about itself, without the time stamps and ids that change per run.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return array<string, mixed>
	 */
	private function fingerprint(string $pdf): array {
		$info = [];
		preg_match_all('#/(Title|Author|Creator|Keywords|Subject) \(((?:\\\\.|[^\\\\)])*)\)#s', $pdf, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$info[] = [$match[1], bin2hex($match[2])];
		}

		preg_match_all('#/BaseFont /(\S+)#', $pdf, $fonts);
		preg_match_all('#/MediaBox \[([^\]]*)\]#', $pdf, $boxes);
		preg_match_all('#pdfaid:(part|conformance)>([^<]*)<#', $pdf, $pdfa, PREG_SET_ORDER);

		$streams = [];
		preg_match_all('#/Filter /FlateDecode[^>]*>>\s*stream\r?\n(.*?)\r?\nendstream#s', $pdf, $raw);
		foreach ($raw[1] as $data) {
			$plain = @gzuncompress($data);
			$streams[] = $plain === false ? 'raw:' . strlen($data) : sha1($plain);
		}

		return [
			'pages' => preg_match_all('#/Type /Page\b#', $pdf),
			'info' => $info,
			'fonts' => $fonts[1],
			'boxes' => array_values(array_unique($boxes[1])),
			'pdfa' => array_map(static fn (array $m): string => $m[1] . '=' . $m[2], $pdfa),
			'streams' => $streams,
		];
	}//end fingerprint()
}//end class
