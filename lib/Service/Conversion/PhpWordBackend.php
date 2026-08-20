<?php

/**
 * PhpWord Conversion Backend
 *
 * Reads Word-family inputs via PhpOffice\PhpWord, renders them to HTML
 * with PhpWord's HTML writer, then hands that HTML to PdfService for
 * mPDF rendering. Covers DOC (MsDoc, limited fidelity), DOCX (Word2007),
 * ODT (ODText), RTF, and HTML — all the Word-family formats DocuDesk
 * needs to redact in the no-Office-app tier. Spreadsheet and
 * presentation formats are deliberately out of scope (see design D7).
 *
 * Routing PhpWord → HTML → PdfService (rather than PhpWord's own
 * PdfWriter) unifies the mPDF setup with PdfService's PDF/A-3b config
 * and reuses PdfService's writable temp-dir handling — PhpWord's
 * PdfWriter hardcodes mPDF's tempDir under the vendor directory, which
 * isn't writable in our container layout.
 *
 * Reached when the OfficeAppBackend declined the input (no Office app
 * installed, or convert failed). Lower fidelity than a real Office
 * engine — installs that care should configure an Office app and the
 * cascade will route there first.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Conversion;

use OCA\DocuDesk\Exception\ConversionFailedException;
use OCA\DocuDesk\Service\PdfService;
use OCP\Files\File;
use OCP\IAppConfig;
use OCP\ITempManager;
use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Converts DOC/DOCX/ODT/RTF/HTML to PDF via PhpWord-HTML + PdfService.
 *
 * PhpWord's HTML writer produces the intermediate representation;
 * PdfService renders that HTML to mPDF-emitted PDF/A-3b with its own
 * writable temp-dir handling. Inputs that PhpWord can't read raise
 * ConversionFailedException for the cascade to fall through.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Conversion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class PhpWordBackend implements ConversionBackendInterface {

	/**
	 * App config key controlling whether this backend is attempted.
	 * Default true; tenants disable for testing or forced fall-through.
	 */
	private const ENABLED_KEY = 'docudesk.conversion.backends.phpword_enabled';

	/**
	 * App identifier used for IAppConfig reads/writes.
	 */
	private const APP_ID = 'docudesk';

	/**
	 * PhpWord reader name to use per extension. PhpWord's IOFactory
	 * normally auto-detects, but explicit dispatch sidesteps the auto-
	 * detect heuristic on edge cases (e.g. RTF files with no leading
	 * magic bytes).
	 *
	 * @var array<string, string>
	 */
	private const READER_BY_EXT = [
		'doc' => 'MsDoc',
		'docx' => 'Word2007',
		'odt' => 'ODText',
		'rtf' => 'RTF',
		'html' => 'HTML',
		'htm' => 'HTML',
	];

	/**
	 * Seam over PhpWord's reader/writer construction.
	 *
	 * @var PhpWordIo
	 */
	private readonly PhpWordIo $phpWordIo;

	/**
	 * Constructor.
	 *
	 * @param IAppConfig $appConfig Tenant configuration provider.
	 * @param ITempManager $tempManager Provides Nextcloud-managed temp paths.
	 * @param PdfService $pdfService Renders the HTML produced by PhpWord to PDF/A-3b.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param PhpWordIo|null $phpWordIo Seam over PhpWord reader/writer construction;
	 *                                  autowired in production, defaulted here so
	 *                                  existing call sites stay source-compatible.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ITempManager $tempManager,
		private readonly PdfService $pdfService,
		private readonly LoggerInterface $logger,
		?PhpWordIo $phpWordIo = null,
	) {
		$this->phpWordIo = ($phpWordIo ?? new PhpWordIo());

	}//end __construct()

	/**
	 * Backend identifier surfaced in the 422 body's `conversionAttempts[].name`.
	 *
	 * @return string Identifier surfaced in 422 attempt records.
	 */
	public function name(): string {
		return 'phpword';
	}//end name()

	/**
	 * Available iff the tenant flag is set AND the PhpWord library is
	 * actually present at runtime (autoload should always make it so
	 * once composer require lands, but defensively check).
	 *
	 * @return bool
	 */
	public function isAvailable(): bool {
		$value = $this->appConfig->getValueString(self::APP_ID, self::ENABLED_KEY, 'true');
		if ($value === 'false') {
			return false;
		}

		return class_exists(IOFactory::class);
	}//end isAvailable()

	/**
	 * Declare whether PhpWord can read the source format.
	 *
	 * @param string $mimeType Source MIME.
	 * @param string $extension Source extension (lowercased, no dot).
	 *
	 * @return bool True for Word-family formats PhpWord can read.
	 */
	public function canHandle(string $mimeType, string $extension): bool {
		if (isset(self::READER_BY_EXT[$extension]) === true) {
			return true;
		}

		$mimeMap = [
			'application/msword' => true,
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => true,
			'application/vnd.oasis.opendocument.text' => true,
			'application/rtf' => true,
			'text/rtf' => true,
			'text/html' => true,
			'application/xhtml+xml' => true,
		];

		return isset($mimeMap[$mimeType]);
	}//end canHandle()

	/**
	 * Convert via PhpWord-HTML + PdfService. PhpWord reads the source
	 * into its document model; the HTML writer's getContent() yields
	 * an HTML string that PdfService renders to PDF/A-3b through mPDF.
	 *
	 * @param File $source Source file node.
	 *
	 * @return File Newly written PDF file node.
	 *
	 * @throws ConversionFailedException On read/render failure.
	 */
	public function convert(File $source): File {
		$name = $source->getName();
		$extension = $this->extractExtension(name: $name);
		$readerName = $this->resolveReaderName(extension: $extension);
		$sourceTmp = $this->materialiseSource(source: $source, extension: $extension);

		$html = $this->renderHtml(sourceTmp: $sourceTmp, readerName: $readerName);
		$pdfBytes = $this->renderPdf(html: $html, name: $name);

		$parent = $source->getParent();
		$outputName = $this->stripExtension(name: $name) . '.pdf';
		if ($parent->nodeExists($outputName) === true) {
			$parent->get($outputName)->delete();
		}

		return $parent->newFile($outputName, $pdfBytes);
	}//end convert()

	/**
	 * Return the lowercased extension of $name without the leading dot.
	 *
	 * @param string $name File name, with or without an extension.
	 *
	 * @return string Lowercased extension, or an empty string when the name
	 *                carries no dot.
	 */
	private function extractExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return '';
		}

		return strtolower(substr($name, ($dotPos + 1)));
	}//end extractExtension()

	/**
	 * Map a source extension onto the PhpWord reader that handles it.
	 *
	 * @param string $extension Lowercased extension without the dot.
	 *
	 * @return string PhpWord reader short name.
	 *
	 * @throws ConversionFailedException When no reader is mapped for $extension.
	 */
	private function resolveReaderName(string $extension): string {
		$readerName = self::READER_BY_EXT[$extension] ?? null;
		if ($readerName === null) {
			throw new ConversionFailedException(
				message: 'PhpWord backend reached convert() for unsupported extension ' . $extension,
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => false,
						'reason' => 'No PhpWord reader mapped for .' . $extension,
					],
				]
			);
		}

		return $readerName;
	}//end resolveReaderName()

	/**
	 * Write the source bytes to a Nextcloud-managed temp file.
	 *
	 * PhpWord readers operate on file paths, not streams, so the node's
	 * content has to be materialised on disk first.
	 *
	 * @param File $source Source file node.
	 * @param string $extension Lowercased extension, used for the temp suffix.
	 *
	 * @return string Absolute path of the temp file holding the source bytes.
	 *
	 * @throws ConversionFailedException When the node yields no readable content,
	 *                                   or no temp file could be allocated.
	 */
	private function materialiseSource(File $source, string $extension): string {
		// ITempManager::getTemporaryFile() returns string|false — false when
		// the temp directory is not writable. Failing closed here beats
		// handing `false` to file_put_contents() and writing to './'.
		$sourceTmp = $this->tempManager->getTemporaryFile('.' . $extension);
		if (is_string($sourceTmp) === false) {
			throw new ConversionFailedException(
				message: 'PhpWord backend could not allocate a temporary file.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'getTemporaryFile returned false',
					],
				]
			);
		}

		$bytes = $source->getContent();
		if (is_string($bytes) === false) {
			throw new ConversionFailedException(
				message: 'PhpWord backend could not read source content.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'getContent returned non-string',
					],
				]
			);
		}

		file_put_contents($sourceTmp, $bytes);

		return $sourceTmp;
	}//end materialiseSource()

	/**
	 * Parse the source with PhpWord and render it to the HTML intermediate.
	 *
	 * @param string $sourceTmp Path of the materialised source document.
	 * @param string $readerName PhpWord reader short name.
	 *
	 * @return string Non-empty HTML, with PhpWord's `@page` rules stripped.
	 *
	 * @throws ConversionFailedException On read failure, writer failure, or empty output.
	 */
	private function renderHtml(string $sourceTmp, string $readerName): string {
		try {
			$phpWord = $this->phpWordIo->load($sourceTmp, $readerName);
		} catch (Throwable $e) {
			throw new ConversionFailedException(
				message: 'PhpWord could not read source (' . $readerName . '): ' . $e->getMessage(),
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'reader load failed: ' . $e->getMessage(),
					],
				],
				previous: $e
			);
		}

		try {
			$html = $this->stripAtPageRules(html: $this->phpWordIo->toHtml($phpWord));
		} catch (Throwable $e) {
			throw new ConversionFailedException(
				message: 'PhpWord HTML writer failed: ' . $e->getMessage(),
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'HTML writer getContent failed: ' . $e->getMessage(),
					],
				],
				previous: $e
			);
		}

		if ($html === '') {
			throw new ConversionFailedException(
				message: 'PhpWord HTML writer produced empty output.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'HTML writer getContent returned empty',
					],
				]
			);
		}

		return $html;
	}//end renderHtml()

	/**
	 * Render the HTML intermediate to PDF/A-3b bytes.
	 *
	 * Mirrors MpdfBackend: request PDF/A-3b output and a known page format so
	 * the docx PDF carries the same normalization print CSS
	 * (PdfService::buildPrintCss) and archival container as every other
	 * anonymised output. Without these options the renderer skips the PDF/A +
	 * print-CSS branch entirely, leaving the docx PDF non-conformant and
	 * un-normalized.
	 *
	 * @param string $html HTML produced by PhpWord's HTML writer.
	 * @param string $name Source file name, used to derive the PDF title.
	 *
	 * @return string Non-empty PDF bytes.
	 *
	 * @throws ConversionFailedException On renderer failure or empty output.
	 */
	private function renderPdf(string $html, string $name): string {
		try {
			$pdfBytes = $this->pdfService->generatePdfFromHtml(
				html: $html,
				options: [
					'pdfa' => true,
					'format' => 'A4',
					'title' => $this->stripExtension(name: $name),
				]
			);
		} catch (Throwable $e) {
			throw new ConversionFailedException(
				message: 'PdfService failed to render PhpWord-HTML: ' . $e->getMessage(),
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'PdfService::generatePdfFromHtml failed: ' . $e->getMessage(),
					],
				],
				previous: $e
			);
		}//end try

		if ($pdfBytes === '') {
			throw new ConversionFailedException(
				message: 'PdfService returned empty PDF bytes.',
				attempts: [
					[
						'name' => $this->name(),
						'available' => true,
						'supports' => true,
						'reason' => 'generatePdfFromHtml returned empty string',
					],
				]
			);
		}

		return $pdfBytes;
	}//end renderPdf()

	/**
	 * Strip CSS `@page` rules emitted by PhpWord's HTML writer.
	 *
	 * PhpWord emits a per-section `@page pageN { size: A4 portrait;
	 * margin-*: ...; }` rule. mPDF's CSS parser mishandles that
	 * construct and degenerates into one character per page (the
	 * same root cause noted in PdfService::buildPrintCss). Page
	 * geometry is already set by mPDF's config (format / orientation
	 * / margin_* in PdfService::buildMpdfConfig), so the rule is
	 * redundant — dropping it is safe.
	 *
	 * @param string $html HTML output from PhpWord's HTML writer.
	 *
	 * @return string Same HTML with all `@page` rules removed.
	 */
	private function stripAtPageRules(string $html): string {
		return preg_replace('/@page[^{]*\{[^}]*\}/s', '', $html) ?? $html;
	}//end stripAtPageRules()

	/**
	 * Return $name without its trailing `.ext`.
	 *
	 * @param string $name File name with extension.
	 *
	 * @return string
	 */
	private function stripExtension(string $name): string {
		$dotPos = strrpos($name, '.');
		if ($dotPos === false) {
			return $name;
		}

		return substr($name, 0, $dotPos);
	}//end stripExtension()
}//end class
