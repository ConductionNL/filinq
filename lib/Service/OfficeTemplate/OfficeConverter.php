<?php

/**
 * Office converter
 *
 * Turns a filled DOCX into the requested output and an uploaded ODT into
 * the DOCX it is filled from. LibreOffice (headless) does the work; without
 * it a PDF still comes out through PhpWord's reader and mPDF, while HTML and
 * ODT fail with the reason the format matrix reports.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\Conversion\PhpWordIo;
use OCA\Filinq\Service\PdfService;

/**
 * Converts office template sources and filled documents.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
 */
class OfficeConverter {

	/**
	 * Constructor.
	 *
	 * @param LibreOfficeHeadlessBackend $libreOffice The soffice backend.
	 * @param PhpWordIo                  $phpWord     Reads a DOCX to HTML when soffice is missing.
	 * @param PdfService                 $pdfService  Writes that HTML as PDF (mPDF).
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LibreOfficeHeadlessBackend $libreOffice,
		private readonly PhpWordIo $phpWord,
		private readonly PdfService $pdfService,
	) {

	}//end __construct()

	/**
	 * Whether LibreOffice can be used now.
	 *
	 * @return bool True when it can.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
	 */
	public function libreOfficeAvailable(): bool {
		return $this->libreOffice->isAvailable();

	}//end libreOfficeAvailable()

	/**
	 * The DOCX of an uploaded ODT.
	 *
	 * @param string $odtBytes The ODT.
	 *
	 * @return string The DOCX.
	 *
	 * @throws OfficeTemplateRefused 503 when LibreOffice cannot convert it.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 */
	public function odtToDocx(string $odtBytes): string {
		try {
			return $this->libreOffice->convertOffice(bytes: $odtBytes, fromExtension: 'odt', toExtension: 'docx');
		} catch (ConversionFailedException $e) {
			throw new OfficeTemplateRefused(message: 'An ODT template is converted to DOCX by LibreOffice, which failed: ' . $e->getMessage(), reason: 'conversion', code: 503);
		}

	}//end odtToDocx()

	/**
	 * A filled DOCX in the requested format.
	 *
	 * @param string $docxBytes The filled DOCX.
	 * @param string $format    pdf, docx, odf or html.
	 *
	 * @return string The output bytes.
	 *
	 * @throws Exception 503 when the format needs LibreOffice and it is unavailable.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-7
	 */
	public function output(string $docxBytes, string $format): string {
		if ($format === 'docx') {
			return $docxBytes;
		}

		if ($format === 'pdf') {
			return $this->pdf(docxBytes: $docxBytes);
		}

		$target = ['html' => 'html', 'odf' => 'odt'][$format] ?? null;
		if ($target === null) {
			throw new Exception(message: 'Unsupported format ' . $format . ' for an office template', code: 400);
		}

		try {
			return $this->libreOffice->convertOffice(bytes: $docxBytes, fromExtension: 'docx', toExtension: $target);
		} catch (ConversionFailedException $e) {
			throw new Exception(message: $e->getMessage(), code: 503, previous: $e);
		}

	}//end output()

	/**
	 * The HTML of a filled DOCX, for a preview: LibreOffice when it is there,
	 * otherwise PhpWord's reader.
	 *
	 * @param string $docxBytes The filled DOCX.
	 *
	 * @return string The HTML.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function previewHtml(string $docxBytes): string {
		$html = $this->viaLibreOffice(work: fn (): string => $this->libreOffice->convertOffice(bytes: $docxBytes, fromExtension: 'docx', toExtension: 'html'));

		return $html ?? $this->phpWordHtml(docxBytes: $docxBytes);

	}//end previewHtml()

	/**
	 * A PDF of a filled DOCX: tagged through LibreOffice, else through
	 * PhpWord and mPDF.
	 *
	 * @param string $docxBytes The filled DOCX.
	 *
	 * @return string The PDF.
	 */
	private function pdf(string $docxBytes): string {
		$pdf = $this->viaLibreOffice(work: fn (): string => $this->libreOffice->convertTagged(bytes: $docxBytes, extension: 'docx', pdfa: false));

		return $pdf ?? $this->pdfService->generatePdfFromHtml(html: $this->phpWordHtml(docxBytes: $docxBytes));

	}//end pdf()

	/**
	 * The result of a LibreOffice call, or null when LibreOffice is missing
	 * or fails, so the caller can fall back.
	 *
	 * @param callable $work The call.
	 *
	 * @return string|null The bytes.
	 */
	private function viaLibreOffice(callable $work): ?string {
		if ($this->libreOffice->isAvailable() === false) {
			return null;
		}

		try {
			return $work();
		} catch (ConversionFailedException) {
			return null;
		}

	}//end viaLibreOffice()

	/**
	 * PhpWord's HTML of a DOCX.
	 *
	 * @param string $docxBytes The DOCX.
	 *
	 * @return string The HTML.
	 */
	private function phpWordHtml(string $docxBytes): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq_docx_');
		file_put_contents($path, $docxBytes);
		try {
			return $this->phpWord->toHtml(document: $this->phpWord->load(path: $path, readerName: 'Word2007'));
		} finally {
			unlink($path);
		}

	}//end phpWordHtml()
}//end class
