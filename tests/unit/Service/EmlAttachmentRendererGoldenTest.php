<?php

/**
 * Golden test for the pages EmlAttachmentRenderer writes for attachments.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\EmlAttachmentRenderer;
use OCA\Filinq\Service\EmlStructureReader;
use OCA\Filinq\Service\EmlTemplateLoader;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\PhpWordHtmlRenderer;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Throwable;

/**
 * Every mPDF call and log line writeDivider() and renderBytes() make, per
 * attachment shape, compared with JSON recorded from the implementation that
 * took each attachment field as its own parameter. GOLDEN_RECORD=1 rewrites it.
 *
 * @covers \OCA\Filinq\Service\EmlAttachmentRenderer
 * @uses   \OCA\Filinq\Service\Charts\BarChartRenderer
 * @uses   \OCA\Filinq\Service\Charts\CartesianFrame
 * @uses   \OCA\Filinq\Service\Charts\ChartLabelFormatter
 * @uses   \OCA\Filinq\Service\Charts\ChartLegendRenderer
 * @uses   \OCA\Filinq\Service\Charts\ChartSvgRenderer
 * @uses   \OCA\Filinq\Service\Charts\LineChartRenderer
 * @uses   \OCA\Filinq\Service\Charts\PieChartRenderer
 * @uses   \OCA\Filinq\Service\Charts\TableCellFormatter
 * @uses   \OCA\Filinq\Service\Charts\TableHtmlRenderer
 * @uses   \OCA\Filinq\Service\EmlStructureReader
 * @uses   \OCA\Filinq\Service\EmlTemplateLoader
 * @uses   \OCA\Filinq\Service\PdfService
 * @uses   \OCA\Filinq\Service\TemplateRenderer
 * @uses   \OCA\Filinq\Service\Charts\CartesianChartRenderer
 *
 * @spec exclude characterization test of existing attachment rendering
 */
class EmlAttachmentRendererGoldenTest extends TestCase {

	/**
	 * The mPDF calls and log lines, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * Divider cases: the divider fields, the template setting, and whether WriteHTML throws.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: string, 2: bool}>
	 */
	public static function dividerCases(): array {
		$base = ['index' => 2, 'filename' => 'brief <1>.pdf', 'mimeType' => 'application/pdf', 'size' => 1234, 'variant' => 'default'];

		$cases = [];
		foreach (['default', 'non_renderable', 'too_large', 'unsupported', 'depth_limit', 'render_failed'] as $variant) {
			$cases['variant ' . $variant] = [['variant' => $variant] + $base, '', false];
		}

		$cases['no size'] = [['size' => null, 'index' => 7] + $base, '', false];
		$cases['template missing'] = [$base, 'eml/does-not-exist.twig', false];
		$cases['blank template setting'] = [$base, '   ', false];
		$cases['write fails'] = [['index' => 3] + $base, '', true];

		return $cases;

	}//end dividerCases()

	/**
	 * Render cases: the attachment bytes, MIME type and file name, and the PDF options.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string, 3: array<string, mixed>}>
	 */
	public static function renderCases(): array {
		return [
			'pdf, two pages' => ['%PDF-two', 'application/pdf', 'a.pdf', []],
			'png image' => ["\x89PNG", 'image/png', 'p.png', []],
			'jpeg image, pdfa' => ['jpg', 'image/jpeg', 'p.jpg', ['pdfa' => true, 'format' => 'A5', 'orientation' => 'L']],
			'plain text' => ["a <b> & 'c'\n", 'text/plain', 't.txt', []],
			'csv, pdfa' => ['x,y', 'text/csv', 't.csv', ['pdfa' => true]],
			'rtf is not text' => ['{\\rtf}', 'text/rtf', 'r.rtf', []],
			'calendar is not text' => ['BEGIN:VCALENDAR', 'text/calendar', 'c.ics', []],
			'word' => ['PK-docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'w.docx', []],
			'unknown' => ['?', 'application/zip', 'z.zip', []],
		];

	}//end renderCases()

	/**
	 * The divider page of one attachment.
	 *
	 * @param array<string, mixed> $divider     The index, file name, MIME type, size and variant.
	 * @param string               $template    The divider template setting.
	 * @param bool                 $writeFails  Whether WriteHTML throws.
	 *
	 * @dataProvider dividerCases
	 *
	 * @return void
	 */
	public function testDivider(array $divider, string $template, bool $writeFails): void {
		$mpdf = $this->recordingMpdf(writeFails: $writeFails);

		$this->renderer(template: $template)->writeDivider(mpdf: $mpdf, divider: $divider);

		$this->assertTrace(trace: $this->calls);

	}//end testDivider()

	/**
	 * The pages of one renderable attachment.
	 *
	 * @param string               $bytes    The attachment bytes.
	 * @param string               $mimeType The lowercased MIME type.
	 * @param string               $filename The file name.
	 * @param array<string, mixed> $options  The PDF options.
	 *
	 * @dataProvider renderCases
	 *
	 * @return void
	 */
	public function testRenderBytes(string $bytes, string $mimeType, string $filename, array $options): void {
		$mpdf = $this->recordingMpdf(writeFails: false);

		try {
			$this->renderer(template: '')->renderBytes(
				mpdf: $mpdf,
				attachment: ['bytes' => $bytes, 'mimeType' => $mimeType, 'filename' => $filename],
				options: $options
			);
		} catch (Throwable $e) {
			$this->calls[] = ['threw', get_class($e), $e->getMessage()];
		}

		$this->assertTrace(trace: $this->calls);

	}//end testRenderBytes()

	/**
	 * Compare the trace with the recorded one; GOLDEN_RECORD=1 rewrites it.
	 *
	 * @param array<int, array<int, mixed>> $trace The calls.
	 *
	 * @return void
	 */
	private function assertTrace(array $trace): void {
		$path = __DIR__ . '/golden/eml-attachment-renderer.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = $this->name() . ' ' . (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			ksort($recorded);
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);

	}//end assertTrace()

	/**
	 * The renderer with real template and PDF services, a logger and a Word renderer that record.
	 *
	 * @param string $template The divider template setting.
	 *
	 * @return EmlAttachmentRenderer
	 */
	private function renderer(string $template): EmlAttachmentRenderer {
		$logger = new class($this->calls) extends AbstractLogger {
			/**
			 * @param array<int, array<int, mixed>> $calls The shared trace.
			 */
			public function __construct(
				private array &$calls,
			) {
			}

			/**
			 * @param mixed                $level   The level.
			 * @param string|\Stringable   $message The message.
			 * @param array<string, mixed> $context The context.
			 */
			public function log($level, string|\Stringable $message, array $context = []): void {
				$this->calls[] = ['log', (string)$level, (string)$message, $context];
			}
		};

		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($template === '' ? $default : $template)
		);

		$word = $this->createMock(PhpWordHtmlRenderer::class);
		$word->method('renderToHtml')->willReturnCallback(
			static fn (string $bytes, string $mimeType, string $filename): string => '<p>word ' . $bytes . ' ' . $mimeType . ' ' . $filename . '</p>'
		);

		$templates = new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer());

		return new EmlAttachmentRenderer(
			new PdfService($logger, $templates),
			$templates,
			$appConfig,
			$logger,
			new EmlTemplateLoader(),
			new EmlStructureReader(),
			$word
		);

	}//end renderer()

	/**
	 * An mPDF double that records the page calls; an imported PDF has a portrait and a landscape page.
	 *
	 * @param bool $writeFails Whether WriteHTML throws.
	 *
	 * @return Mpdf
	 */
	private function recordingMpdf(bool $writeFails): Mpdf {
		$mpdf = $this->getMockBuilder(Mpdf::class)
			->disableOriginalConstructor()
			->onlyMethods(['AddPage', 'WriteHTML', 'setSourceFile', 'importPage', 'getTemplateSize', 'useTemplate'])
			->getMock();
		$mpdf->method('AddPage')->willReturnCallback(
			function (...$arguments): void {
				$this->calls[] = ['AddPage', $arguments];
			}
		);
		$mpdf->method('WriteHTML')->willReturnCallback(
			function (string $html) use ($writeFails): void {
				$this->calls[] = ['WriteHTML', $html];
				if ($writeFails === true) {
					throw new \RuntimeException('mpdf broke');
				}
			}
		);
		$mpdf->method('setSourceFile')->willReturnCallback(
			function (string $file): int {
				$this->calls[] = ['setSourceFile', (string)file_get_contents($file)];
				return 2;
			}
		);
		$mpdf->method('importPage')->willReturnCallback(
			function (int $page): string {
				$this->calls[] = ['importPage', $page];
				return 'tpl-' . $page;
			}
		);
		$mpdf->method('getTemplateSize')->willReturnCallback(
			static fn (string $tplId): array => ($tplId === 'tpl-2' ? ['width' => 297, 'height' => 210] : ['width' => 210, 'height' => 297])
		);
		$mpdf->method('useTemplate')->willReturnCallback(
			function (string $tplId): array {
				$this->calls[] = ['useTemplate', $tplId];
				return [];
			}
		);

		return $mpdf;

	}//end recordingMpdf()
}//end class
