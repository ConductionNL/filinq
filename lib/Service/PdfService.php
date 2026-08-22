<?php

/**
 * PDF Service
 *
 * Shared service for generating PDF documents from Twig templates and data.
 * Uses mPDF for HTML-to-PDF conversion and delegates template rendering
 * to TemplateRenderer. Supports PDF/A-3b archival compliance mode.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/specs/print-preview/spec.md
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/specs/pdf-generation/spec.md
 * @spec openspec/changes/print-functionality/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Psr\Log\LoggerInterface;

/**
 * Service for generating PDF documents from Twig templates
 *
 * Supports standard PDF 1.4 output and PDF/A-3b archival compliance.
 * When PDF/A mode is enabled, fonts are embedded and print-optimized
 * CSS is injected automatically.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
class PdfService {
	/**
	 * Constructor for PdfService
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param TemplateRenderer $templateRenderer Template renderer for Twig
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly TemplateRenderer $templateRenderer,
	) {

	}//end __construct()

	/**
	 * Render a PDF from a Twig template string and data context
	 *
	 * @param string $templateContent Twig template content (HTML with Twig syntax)
	 * @param array $data Data context for template rendering
	 * @param array $options PDF configuration options:
	 *                       - format: Page size (A4, A3, Letter, Legal). Default: A4
	 *                       - orientation: P (portrait) or L (landscape). Default: P
	 *                       - margin: Array with top, right, bottom, left in mm. Default: 15
	 *                       - title: PDF document title metadata. Default: empty
	 *                       - pdfa: Enable PDF/A-3b compliance. Default: false
	 *                       - cropMarks: Add 3mm bleed and crop marks. Default: false
	 *                       - author: Author name for XMP metadata. Default: Filinq
	 *                       - caseReference: Case reference for XMP keywords. Default: empty
	 *
	 * @return string PDF binary content
	 *
	 * @throws Exception If Twig rendering or PDF generation fails
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 * @spec openspec/changes/print-functionality/tasks.md#task-1
	 */
	public function renderPdf(string $templateContent, array $data = [], array $options = []): string {
		$html = $this->templateRenderer->renderTemplate(
			templateContent: $templateContent,
			data: $data
		);

		return $this->generatePdf(html: $html, options: $options);
	}//end renderPdf()

	/**
	 * Render a Twig template to HTML without converting to PDF.
	 *
	 * Public wrapper around TemplateRenderer for callers that need the
	 * rendered HTML itself rather than a PDF — used by
	 * `PdfController::renderPdfA` to hand off to
	 * `Pdfa3ConversionService::convertHtml` when the request carries
	 * attachments or MDTO metadata, keeping Twig rendering centralised
	 * in one place rather than duplicated per caller.
	 *
	 * @param string $templateContent Twig template content (HTML with Twig syntax)
	 * @param array $data Data context for template rendering
	 *
	 * @return string Rendered HTML
	 *
	 * @throws Exception If Twig rendering fails
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function renderTemplateToHtml(string $templateContent, array $data = []): string {
		return $this->templateRenderer->renderTemplate(
			templateContent: $templateContent,
			data: $data
		);

	}//end renderTemplateToHtml()

	/**
	 * Generate a PDF from a raw HTML string (no Twig pre-processing).
	 *
	 * Public wrapper around the private generatePdf path for callers
	 * that already have rendered HTML — used by
	 * `Service\Conversion\MpdfBackend` (and any future conversion
	 * backend that wants the same PDF/A-3b configuration as
	 * print-preview without re-implementing it).
	 *
	 * @param string $html Pre-rendered HTML document body.
	 * @param array<string,mixed> $options PDF configuration options; same shape as
	 *                                     {@see renderPdf} (`format`, `orientation`,
	 *                                     `margin`, `title`, `pdfa`).
	 *
	 * @return string PDF binary content.
	 *
	 * @throws Exception When mPDF rendering fails.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	public function generatePdfFromHtml(string $html, array $options = []): string {
		return $this->generatePdf(html: $html, options: $options);
	}//end generatePdfFromHtml()

	/**
	 * Render HTML from a Twig template string and data context (for print preview)
	 *
	 * @param string $templateContent Twig template content (HTML with Twig syntax)
	 * @param array $data Data context for template rendering
	 * @param array $options Options for CSS injection:
	 *                       - format: Page size (A4, A3, Letter, Legal). Default: A4
	 *                       - orientation: P (portrait) or L (landscape). Default: P
	 *
	 * @return string Rendered HTML with print-optimized CSS injected
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	public function renderHtmlPreview(string $templateContent, array $data = [], array $options = []): string {
		$html = $this->templateRenderer->renderTemplate(
			templateContent: $templateContent,
			data: $data
		);

		$format = $options['format'] ?? 'A4';
		$orientation = $options['orientation'] ?? 'P';
		$printCss = $this->buildPrintCss(format: $format, orientation: $orientation);

		return $printCss . $html;
	}//end renderHtmlPreview()

	/**
	 * Build HTML with 3mm bleed area and crop marks injected around the content
	 *
	 * Wraps the content in a bleed container and adds SVG crop marks at all four
	 * corners. The bleed margin is 3mm on each side, making the effective printable
	 * area 6mm wider and taller than the nominal page size.
	 *
	 * @param string $html Rendered HTML document content
	 *
	 * @return string HTML with bleed CSS and crop mark SVGs prepended
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-1
	 */
	public function buildCropMarksHtml(string $html): string {
		$cropCss = '<style>
@media print {
    body { margin: 3mm; padding: 0; }
    .crop-mark-container {
        position: relative;
        margin: 3mm;
    }
    .crop-mark {
        position: absolute;
        width: 8mm;
        height: 8mm;
        overflow: visible;
    }
    .crop-mark-tl { top: -5mm; left: -5mm; }
    .crop-mark-tr { top: -5mm; right: -5mm; }
    .crop-mark-bl { bottom: -5mm; left: -5mm; }
    .crop-mark-br { bottom: -5mm; right: -5mm; }
}
</style>';

		$cropMarkSvgTl = '<svg class="crop-mark crop-mark-tl" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
			. '<line x1="12" y1="0" x2="12" y2="8" stroke="black" stroke-width="0.5"/>'
			. '<line x1="0" y1="12" x2="8" y2="12" stroke="black" stroke-width="0.5"/>'
			. '</svg>';
		$cropMarkSvgTr = '<svg class="crop-mark crop-mark-tr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
			. '<line x1="12" y1="0" x2="12" y2="8" stroke="black" stroke-width="0.5"/>'
			. '<line x1="16" y1="12" x2="24" y2="12" stroke="black" stroke-width="0.5"/>'
			. '</svg>';
		$cropMarkSvgBl = '<svg class="crop-mark crop-mark-bl" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
			. '<line x1="12" y1="16" x2="12" y2="24" stroke="black" stroke-width="0.5"/>'
			. '<line x1="0" y1="12" x2="8" y2="12" stroke="black" stroke-width="0.5"/>'
			. '</svg>';
		$cropMarkSvgBr = '<svg class="crop-mark crop-mark-br" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
			. '<line x1="12" y1="16" x2="12" y2="24" stroke="black" stroke-width="0.5"/>'
			. '<line x1="16" y1="12" x2="24" y2="12" stroke="black" stroke-width="0.5"/>'
			. '</svg>';

		$wrapper = '<div class="crop-mark-container">'
			. $cropMarkSvgTl . $cropMarkSvgTr . $cropMarkSvgBl . $cropMarkSvgBr
			. $html
			. '</div>';

		return $cropCss . $wrapper;
	}//end buildCropMarksHtml()

	/**
	 * Ensure the mPDF temp directory exists and is writable
	 *
	 * @param string $tempDir The temp directory path
	 *
	 * @return void
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function ensureTempDirectory(string $tempDir): void {
		if (file_exists(filename: $tempDir) === false) {
			mkdir(directory: $tempDir, permissions: 0777, recursive: true);
		}

		chmod(filename: $tempDir, permissions: 0777);

	}//end ensureTempDirectory()

	/**
	 * Build mPDF configuration array from options
	 *
	 * When PDF/A mode is enabled, configures mPDF for PDF/A-3b compliance
	 * with embedded DejaVu Sans fonts and automatic PDF/A metadata.
	 *
	 * @param string $tempDir The temp directory path
	 * @param array $options PDF configuration options
	 *
	 * @return array<string, mixed> mPDF configuration
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function buildMpdfConfig(string $tempDir, array $options): array {
		$margins = $options['margin'] ?? [];
		$isPdfA = ($options['pdfa'] ?? false) === true;

		$config = [
			'tempDir' => $tempDir,
			'format' => $options['format'] ?? 'A4',
			'orientation' => $options['orientation'] ?? 'P',
			'margin_top' => $margins['top'] ?? 15,
			'margin_right' => $margins['right'] ?? 15,
			'margin_bottom' => $margins['bottom'] ?? 15,
			'margin_left' => $margins['left'] ?? 15,
		];

		if ($isPdfA === true) {
			$config['PDFA'] = true;
			$config['PDFAauto'] = true;
			// Fix (2026-07-16): mPDF defaults PDFAversion to '1-B' when the
			// key is absent (see vendor/mpdf/mpdf/src/Config/ConfigVariables.php).
			// This class's docblock has always promised PDF/A-3b output, and
			// every caller (pdf#renderPdfA, print#downloadPdfA,
			// DocumentService's `pdfOptions.pdfa`) relies on that promise for
			// MDTO/archival compliance, but the version was never pinned —
			// output was silently PDF/A-1B. Pin explicitly so the emitted
			// XMP `pdfaid:part`/`pdfaid:conformance` markers actually say "3"/"B".
			$config['PDFAversion'] = '3-B';

			$fontDir = $this->getFontDirectory();
			if ($fontDir !== null) {
				$config['fontDir'] = [
					$fontDir,
				];
				$config['fontdata'] = [
					'dejavusans' => [
						'R' => 'DejaVuSans.ttf',
						'B' => 'DejaVuSans-Bold.ttf',
						'I' => 'DejaVuSans-Oblique.ttf',
						'BI' => 'DejaVuSans-BoldOblique.ttf',
					],
				];
				$config['default_font'] = 'dejavusans';
			}
		}//end if

		return $config;
	}//end buildMpdfConfig()

	/**
	 * Get the path to the bundled font directory
	 *
	 * Public so other services that build their own mPDF configuration
	 * (e.g. `Pdfa3ConversionService`, which needs the same embedded
	 * DejaVu Sans set for PDF/A-3 font-embedding compliance) can reuse
	 * this instead of re-deriving the path.
	 *
	 * @return string|null The font directory path, or null if not found
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function getFontDirectory(): ?string {
		$fontDir = dirname(path: __DIR__) . '/Fonts';
		if (is_dir(filename: $fontDir) === true) {
			return $fontDir;
		}

		return null;
	}//end getFontDirectory()

	/**
	 * Build print-optimized CSS for PDF/A and print preview output
	 *
	 * Generates a style block with @media print rules including page size,
	 * page-break-inside avoidance, and margin normalization.
	 *
	 * @param string $format Page format (A4, A3, Letter, Legal)
	 * @param string $orientation Page orientation (P for portrait, L for landscape)
	 *
	 * @return string HTML style block with print-optimized CSS
	 *
	 * @spec openspec/specs/print-preview/spec.md
	 */
	public function buildPrintCss(string $format, string $orientation): string {
		// Note on `@page`: this stylesheet intentionally omits the
		// `@page { size: ...; margin: ...; }` rule. mPDF's CSS parser
		// produced a degenerate page size (one character per page) when
		// `@page` was nested inside `@media print`, regardless of
		// whether `size: A4`, `size: A4 portrait`, or `size: A4
		// landscape` was emitted. Page dimensions are driven by the
		// mPDF config (`format` / `orientation` / `margin_*` in
		// `buildMpdfConfig`) instead, which mPDF interprets reliably.
		// The remaining rules are layout hints that don't touch page
		// dimensions.
		//
		// `$format` / `$orientation` remain part of the public contract
		// for callers that may key off them later; not consumed locally
		// since page sizing is delegated to the config path.
		unset($format, $orientation);

		return '<style>
@media print {
    body {
        margin: 0;
        padding: 0;
        font-family: "DejaVu Sans", sans-serif;
    }
    figure, img, pre, blockquote {
        page-break-inside: avoid;
    }
    /*
     * Let large data tables flow across pages instead of being crammed
     * onto one page. `page-break-inside: avoid` is applied to each ROW so
     * rows are never split mid-cell, while the table itself may break at
     * row boundaries. `thead { table-header-group }` makes mPDF repeat the
     * column header on every page the table spans.
     */
    tr {
        page-break-inside: avoid;
    }
    thead {
        display: table-header-group;
    }
    h1, h2, h3, h4, h5, h6 {
        page-break-after: avoid;
    }
    nav, .no-print {
        display: none;
    }
}
</style>
';

	}//end buildPrintCss()

	/**
	 * Create a configured mPDF instance for multi-pass assembly.
	 *
	 * Exposes the SAME mPDF configuration (temp dir, PDF/A-3b mode, font
	 * embedding, margins) that the single-pass `generatePdf` path uses, so
	 * callers that need to build a document across several `WriteHTML` /
	 * `AddPage` / FPDI-import passes (e.g. `EmlPdfAssemblyService`) stay in
	 * lockstep with print-preview's PDF/A-3b settings instead of
	 * re-implementing them.
	 *
	 * The returned instance has title/author/creator metadata applied when
	 * provided; the caller is responsible for writing content and calling
	 * `Output()`.
	 *
	 * @param array<string,mixed> $options PDF configuration options; same shape as
	 *                                     {@see renderPdf} (`format`, `orientation`,
	 *                                     `margin`, `title`, `pdfa`).
	 *
	 * @return Mpdf The configured mPDF instance.
	 *
	 * @throws Exception When mPDF cannot be instantiated.
	 */
	public function createMpdfInstance(array $options = []): Mpdf {
		$tempDir = '/tmp/mpdf';
		$this->ensureTempDirectory(tempDir: $tempDir);

		$config = $this->buildMpdfConfig(tempDir: $tempDir, options: $options);
		$title = $options['title'] ?? '';
		$isPdfA = ($options['pdfa'] ?? false) === true;

		try {
			$mpdf = new Mpdf(config: $config);

			if ($title !== '') {
				$mpdf->SetTitle($title);
			}

			if ($isPdfA === true) {
				$mpdf->SetAuthor('Filinq');
				$mpdf->SetCreator('Filinq PDF/A Generator');
			}

			return $mpdf;
		} catch (MpdfException $e) {
			$this->logger->error(
				message: 'mPDF instantiation failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);
			throw new Exception(
				message: 'PDF generation failed: ' . $e->getMessage(),
				code: 500,
				previous: $e
			);
		}//end try

	}//end createMpdfInstance()

	/**
	 * Build the print-optimised CSS prefix for an HTML fragment when PDF/A
	 * mode is in effect, mirroring the prefix `generatePdf` injects.
	 *
	 * Used by multi-pass callers that render fragments through a shared mPDF
	 * instance and want each fragment to carry the same print CSS the
	 * single-pass path applies.
	 *
	 * @param string $html HTML fragment.
	 * @param array<string,mixed> $options PDF options (`pdfa`, `format`, `orientation`).
	 *
	 * @return string The HTML fragment, prefixed with print CSS when `pdfa` is true.
	 */
	public function applyPrintCss(string $html, array $options = []): string {
		$isPdfA = ($options['pdfa'] ?? false) === true;
		if ($isPdfA === false) {
			return $html;
		}

		$format = $options['format'] ?? 'A4';
		$orientation = $options['orientation'] ?? 'P';
		return $this->buildPrintCss(format: $format, orientation: $orientation) . $html;
	}//end applyPrintCss()

	/**
	 * Generate a PDF from rendered HTML content
	 *
	 * Creates the mPDF temp directory if it does not exist,
	 * initializes mPDF with the given options, and returns the PDF binary.
	 * When PDF/A mode is enabled, injects print CSS and sets XMP metadata.
	 *
	 * @param string $html Rendered HTML content
	 * @param array $options PDF configuration options
	 *
	 * @return string PDF binary content
	 *
	 * @throws Exception If mPDF fails to generate the PDF
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 */
	private function generatePdf(string $html, array $options): string {
		$tempDir = '/tmp/mpdf';
		$this->ensureTempDirectory(tempDir: $tempDir);

		$config = $this->buildMpdfConfig(tempDir: $tempDir, options: $options);
		$title = $options['title'] ?? '';
		$isPdfA = ($options['pdfa'] ?? false) === true;

		if ($isPdfA === true) {
			$format = $options['format'] ?? 'A4';
			$orientation = $options['orientation'] ?? 'P';
			$printCss = $this->buildPrintCss(format: $format, orientation: $orientation);
			$html = $printCss . $html;
		}

		$author = (string)($options['author'] ?? 'Filinq');
		$caseReference = (string)($options['caseReference'] ?? '');
		$hasCropMarks = ($options['cropMarks'] ?? false) === true;

		if ($hasCropMarks === true) {
			$html = $this->buildCropMarksHtml(html: $html);
		}

		try {
			$mpdf = new Mpdf(config: $config);

			if ($title !== '') {
				$mpdf->SetTitle($title);
			}

			$mpdf->SetAuthor($author);

			if ($isPdfA === true) {
				$mpdf->SetCreator('Filinq PDF/A Generator');

				$keywords = 'Filinq';
				if ($caseReference !== '') {
					$keywords .= ' ' . $caseReference;
				}

				$mpdf->SetKeywords($keywords);
				$mpdf->SetSubject($title);
			}

			$mpdf->WriteHTML(html: $html);

			return $mpdf->Output(name: '', dest: \Mpdf\Output\Destination::STRING_RETURN);
		} catch (MpdfException $e) {
			$this->logger->error(
				message: 'mPDF generation failed: ' . $e->getMessage(),
				context: ['exception' => $e]
			);
			throw new Exception(
				message: 'PDF generation failed: ' . $e->getMessage(),
				code: 500,
				previous: $e
			);
		}//end try

	}//end generatePdf()
}//end class
