<?php

/**
 * Contract of PdfService
 *
 * The public surface of the PdfService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use Exception;
use Mpdf\Mpdf;

/**
 * Contract of PdfService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PdfServiceInterface {
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
	 *                       - accessible: Tagged PDF/UA output through LibreOffice, never mPDF. Default: false
	 *                       - lang: Document language for accessible output (else the template's, else the instance's)
	 *
	 * @return string PDF binary content
	 *
	 * @throws Exception If Twig rendering or PDF generation fails
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 * @spec openspec/changes/print-functionality/tasks.md#task-1
	 */
	public function renderPdf(string $templateContent, array $data = [], array $options = []): string;

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
	public function renderTemplateToHtml(string $templateContent, array $data = []): string;

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
	public function generatePdfFromHtml(string $html, array $options = []): string;

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
	public function renderHtmlPreview(string $templateContent, array $data = [], array $options = []): string;

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
	public function buildCropMarksHtml(string $html): string;

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
	public function getFontDirectory(): ?string;

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
	public function buildPrintCss(string $format, string $orientation): string;

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
	 *
	 * @spec openspec/specs/pdf-generation/spec.md#requirement-mpdf-temp-directory-management-req-pdf-04
	 */
	public function createMpdfInstance(array $options = []): Mpdf;

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
	public function applyPrintCss(string $html, array $options = []): string;
}//end interface
