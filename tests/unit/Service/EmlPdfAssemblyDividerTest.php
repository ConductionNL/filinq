<?php

/**
 * Characterization tests for the divider and render calls of EmlPdfAssemblyService
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

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\EmlAttachmentRenderer;
use OCA\Filinq\Service\EmlPdfAssemblyService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins, per attachment shape, which divider variant is written with which index and size, and what is rendered.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EmlPdfAssemblyDividerTest extends TestCase {

	/**
	 * Every writeDivider and renderBytes call, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * Assemble a structure with a spying attachment renderer.
	 *
	 * @param array<int, object>    $attachments The attachments.
	 * @param array<string, string> $config      App config values.
	 * @param bool                  $renderFails Whether renderBytes throws.
	 *
	 * @return void
	 */
	private function assemble(array $attachments, array $config = [], bool $renderFails = false): void {
		$logger = $this->createMock(LoggerInterface::class);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($config[$key] ?? $default)
		);
		$templates = new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer());
		$pdfService = new PdfService($logger, $templates);

		$renderer = $this->getMockBuilder(EmlAttachmentRenderer::class)
			->setConstructorArgs([$pdfService, $templates, $appConfig, $logger])
			->onlyMethods(['writeDivider', 'renderBytes'])
			->getMock();
		$renderer->method('writeDivider')->willReturnCallback(
			function (...$arguments): void {
				$this->calls[] = array_merge(['divider'], array_slice($arguments, 1));
			}
		);
		$renderer->method('renderBytes')->willReturnCallback(
			function (...$arguments) use ($renderFails): void {
				$this->calls[] = ['render', $arguments[1], $arguments[2], $arguments[3]];
				if ($renderFails === true) {
					throw new \RuntimeException('broken');
				}
			}
		);

		$service = new EmlPdfAssemblyService($pdfService, $templates, $appConfig, $logger, null, $renderer);
		$service->assemble(result: $this->structure(attachments: $attachments));

	}//end assemble()

	/**
	 * A minimal redacted structure.
	 *
	 * @param array<int, object> $attachments The attachments.
	 *
	 * @return object
	 */
	private function structure(array $attachments): object {
		return (object)[
			'headers' => ['from' => 'a@example.org', 'to' => 'b@example.org', 'subject' => 'S', 'date' => '2026-10-01'],
			'body' => ['plain' => 'body', 'html' => null],
			'attachments' => $attachments,
			'inlineImages' => [],
		];

	}//end structure()

	/**
	 * One attachment.
	 *
	 * @param string      $name     File name.
	 * @param string      $mime     MIME type.
	 * @param string|null $redacted Redacted bytes.
	 * @param array       $extra    unsupported / nestedEml.
	 *
	 * @return object
	 */
	private function attachment(string $name, string $mime, ?string $redacted, array $extra = []): object {
		return (object)($extra + ['filename' => $name, 'mimeType' => $mime, 'redactedContent' => $redacted, 'unsupported' => false, 'nestedEml' => null]);

	}//end attachment()

	/**
	 * Every attachment shape writes its divider with its 1-based index and size.
	 *
	 * @return void
	 */
	public function testEveryShapeWritesItsDivider(): void {
		$nested = $this->structure(attachments: [$this->attachment('inner.txt', 'text/plain', 'hi')]);
		$this->assemble(
			attachments: [
				$this->attachment('u.bin', 'application/octet-stream', null, ['unsupported' => true]),
				$this->attachment('n.eml', 'message/rfc822', null, ['nestedEml' => $nested]),
				$this->attachment('d.eml', 'message/rfc822', null),
				$this->attachment('x.txt', 'text/plain', null),
				$this->attachment('big.txt', 'text/plain', str_repeat('x', 20)),
				$this->attachment('s.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'abc'),
				$this->attachment('ok.txt', 'text/plain', 'fine'),
			],
			config: ['filinq.conversion.eml.max_attachment_render_size_bytes' => '10']
		);

		$this->assertSame(
			[
				['divider', 1, 'u.bin', 'application/octet-stream', null, 'unsupported'],
				['divider', 2, 'n.eml', 'message/rfc822', null, 'default'],
				['divider', 1, 'inner.txt', 'text/plain', 2, 'default'],
				['render', 'hi', 'text/plain', 'inner.txt'],
				['divider', 3, 'd.eml', 'message/rfc822', null, 'depth_limit'],
				['divider', 4, 'x.txt', 'text/plain', null, 'unsupported'],
				['divider', 5, 'big.txt', 'text/plain', 20, 'too_large'],
				['divider', 6, 's.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 3, 'non_renderable'],
				['divider', 7, 'ok.txt', 'text/plain', 4, 'default'],
				['render', 'fine', 'text/plain', 'ok.txt'],
			],
			$this->calls
		);

	}//end testEveryShapeWritesItsDivider()

	/**
	 * With appending off a renderable attachment writes nothing; a failed render falls back to a divider.
	 *
	 * @return void
	 */
	public function testAppendOffAndRenderFailure(): void {
		$this->assemble(
			attachments: [$this->attachment('ok.txt', 'text/plain', 'fine')],
			config: ['filinq.conversion.eml.append_attachment_pages' => 'false']
		);
		$this->assertSame([], $this->calls);

		$this->assemble(attachments: [$this->attachment('ok.txt', 'text/plain', 'fine')], renderFails: true);
		$this->assertSame(
			[
				['divider', 1, 'ok.txt', 'text/plain', 4, 'default'],
				['render', 'fine', 'text/plain', 'ok.txt'],
				['divider', 1, 'ok.txt', 'text/plain', 4, 'render_failed'],
			],
			$this->calls
		);

	}//end testAppendOffAndRenderFailure()
}//end class
