<?php

/**
 * MPDF Conversion Backend
 *
 * Handles HTML and plain-TXT inputs directly via mPDF, reusing
 * `PdfService::generatePdfFromHtml` so PDF/A-3b configuration stays
 * in lockstep with the print-preview rendering path.
 *
 * Last cascade tier before EML / 422; intended for installs without
 * any Office app integration and without phpoffice/phpword coverage
 * (HTML and TXT are PhpWord-readable too, but the direct mPDF path is
 * lighter and preferred when the input doesn't need Word/Office
 * processing).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\StripsFileExtension;
use OCP\Files\File;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Renders HTML directly and wraps plain text in a minimal HTML envelope
 * before rendering. PDF/A-3b output is delegated to the existing
 * PdfService for consistency with print-preview.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 */
class MpdfBackend implements ConversionBackendInterface {
	use StripsFileExtension;

	/**
	 * The failure builder, built on first use.
	 *
	 * @var BackendFailures|null
	 */
	private ?BackendFailures $failures = null;

	/**
	 * App config key controlling whether this backend is attempted.
	 * Default true; tenants disable for testing or to force fall-through.
	 */
	private const ENABLED_KEY = 'filinq.conversion.backends.mpdf_enabled';

	/**
	 * App identifier used for IAppConfig reads/writes.
	 */
	private const APP_ID = 'filinq';

	/**
	 * Constructor.
	 *
	 * @param PdfService $pdfService PDF generator shared with print-preview.
	 * @param IAppConfig $appConfig Tenant configuration provider.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Identifier surfaced in the 422 body's `conversionAttempts[].backend`.
	 *
	 * @return string
	 */
	public function name(): string {
		return 'mpdf';
	}//end name()

	/**
	 * Whether the backend is enabled in tenant config. mPDF is in-process
	 * and always available at the runtime level; the flag exists for
	 * cascade testing and to let tenants force fall-through.
	 *
	 * @return bool
	 */
	public function isAvailable(): bool {
		$value = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
		return $value !== 'false';
	}//end isAvailable()

	/**
	 * HTML and plain-text inputs only. Spreadsheet/presentation/Word
	 * formats are claimed by other backends earlier in the cascade.
	 *
	 * @param string $mimeType Source MIME type.
	 * @param string $extension Lowercased extension without dot.
	 *
	 * @return bool
	 */
	public function canHandle(string $mimeType, string $extension): bool {
		$htmlMimes = [
			'text/html',
			'application/xhtml+xml',
		];
		$textMimes = [
			'text/plain',
			'text/markdown',
		];

		if (in_array($mimeType, $htmlMimes, true) === true
			|| $extension === 'html'
			|| $extension === 'htm'
			|| $extension === 'xhtml'
		) {
			return true;
		}

		if (in_array($mimeType, $textMimes, true) === true
			|| $extension === 'txt'
			|| $extension === 'md'
			|| $extension === 'markdown'
		) {
			return true;
		}

		return false;
	}//end canHandle()

	/**
	 * Convert the source. For TXT inputs, wrap the body in a minimal
	 * `<pre>` envelope so mPDF preserves whitespace; for HTML the body
	 * passes through unchanged. Output is written beside the source.
	 *
	 * @param File $source Source file node.
	 *
	 * @return File Newly written PDF file node.
	 *
	 * @throws ConversionFailedException When the PDF emission fails.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	public function convert(File $source): File {
		$name = $source->getName();
		$html = $this->htmlOf(name: $name, content: $this->readContent(source: $source));
		$pdfBinary = $this->renderOrFail(source: $source, html: $html);

		// Write PDF beside the source. If a file with the target name
		// already exists, delete it first — this is a fresh conversion,
		// not an incremental update.
		return (new PdfBesideSource())->write(source: $source, pdfBytes: $pdfBinary);
	}//end convert()

	/**
	 * The source's content, refusing a node that yields none.
	 *
	 * @param File $source Source file node.
	 *
	 * @return string The content.
	 *
	 * @throws ConversionFailedException When the content is not a string.
	 */
	private function readContent(File $source): string {
		$rawContent = $source->getContent();
		if (is_string($rawContent) === false) {
			throw $this->failures()->failed(
				failure: new BackendFailure(
					message: $this->name() . ' backend could not read source content.',
					reason: 'getContent returned non-string'
				)
			);
		}

		return $rawContent;

	}//end readContent()

	/**
	 * The HTML to render: plain text wrapped in a `<pre>` envelope, HTML as it is.
	 *
	 * @param string $name    Source file name.
	 * @param string $content Source content.
	 *
	 * @return string HTML document.
	 */
	private function htmlOf(string $name, string $content): string {
		$isPlainText = in_array(
			$this->extractExtension(name: $name),
			['txt', 'md', 'markdown'],
			true
		);

		if ($isPlainText === true) {
			return $this->wrapPlainTextAsHtml(text: $content);
		}

		return $content;

	}//end htmlOf()

	/**
	 * Render the HTML to PDF bytes, logging and wrapping a rendering failure.
	 *
	 * @param File   $source Source file node, for the log line and the title.
	 * @param string $html   HTML document.
	 *
	 * @return string PDF bytes.
	 *
	 * @throws ConversionFailedException When rendering throws.
	 */
	private function renderOrFail(File $source, string $html): string {
		try {
			return $this->renderPdf(html: $html, title: $this->stripExtension(name: $source->getName()));
		} catch (Throwable $e) {
			$this->logger->error(
				'[' . $this->name() . '] PDF rendering failed',
				[
					'source' => $source->getPath(),
					'exception' => get_class($e),
					'message' => $e->getMessage(),
				]
			);
			throw $this->failures()->failed(
				failure: new BackendFailure(
					message: $this->name() . ' rendering threw: ' . $e->getMessage(),
					reason: $this->name() . ' render exception: ' . $e->getMessage()
				),
				previous: $e
			);
		}//end try

	}//end renderOrFail()

	/**
	 * The failure builder for this backend, built on first use.
	 *
	 * @return BackendFailures The builder.
	 */
	private function failures(): BackendFailures {
		$this->failures ??= new BackendFailures(backendName: $this->name());
		return $this->failures;

	}//end failures()

	/**
	 * Render HTML to PDF/A-3b bytes through the shared PdfService.
	 *
	 * @param string $html  HTML document.
	 * @param string $title Document title.
	 *
	 * @return string PDF bytes.
	 *
	 * @spec openspec/specs/pdf-conversion/spec.md
	 */
	protected function renderPdf(string $html, string $title): string {
		return $this->pdfService->generatePdfFromHtml(
			html: $html,
			options: [
				'pdfa' => true,
				'format' => 'A4',
				'title' => $title,
			]
		);
	}//end renderPdf()

	/**
	 * Wrap a plain-text body in a minimal HTML envelope so mPDF emits
	 * the text with monospace formatting and preserved whitespace.
	 * The `<pre>` shape is faithful to the original layout.
	 *
	 * @param string $text UTF-8 plain text.
	 *
	 * @return string HTML document.
	 */
	private function wrapPlainTextAsHtml(string $text): string {
		$escaped = htmlspecialchars($text, (ENT_QUOTES | ENT_SUBSTITUTE), 'UTF-8');
		$preStyle = 'font-family:DejaVuSansMono,monospace; font-size:10pt; white-space:pre-wrap;';
		return sprintf(
			'<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><pre style="%s">%s</pre></body></html>',
			$preStyle,
			$escaped
		);

	}//end wrapPlainTextAsHtml()
}//end class
