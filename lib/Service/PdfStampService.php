<?php

/**
 * Stamps a text on every page of a PDF.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfStampRefusal;
use OCP\IAppConfig;
use Throwable;

/**
 * Imports every page of a PDF and writes a text on it: across the page,
 * at its foot, or both.
 *
 * Each page keeps its own size and orientation (`useTemplate()` with
 * `adjustPageSize`). The result is a new PDF; the input is never changed
 * or stored. A file that is not a PDF, is encrypted, or is larger than the
 * `stamp_max_bytes` app setting (default 50 MB) is refused with its code.
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.1
 */
class PdfStampService {

	/**
	 * Placements a caller can ask for.
	 */
	public const PLACEMENTS = ['diagonal', 'footer', 'both'];

	/**
	 * The default size limit in bytes.
	 */
	public const DEFAULT_MAX_BYTES = 52428800;

	/**
	 * The app setting holding the size limit.
	 */
	public const SETTING_MAX_BYTES = 'stamp_max_bytes';

	/**
	 * The longest text, in characters.
	 */
	public const MAX_TEXT_LENGTH = 200;

	/**
	 * The default opacity of the text across the page.
	 */
	private const DEFAULT_OPACITY = 0.15;

	/**
	 * Footer font size in points, and the line height and bottom margin in mm.
	 */
	private const FOOTER_POINTS = 8;
	private const FOOTER_LINE   = 3.5;
	private const FOOTER_MARGIN = 6.0;
	private const FOOTER_LEFT   = 10.0;

	/**
	 * Constructor.
	 *
	 * @param PdfService             $pdfService    Builds a configured mPDF instance.
	 * @param PdfStreamReaderFactory $streamReaders Reads the PDF bytes.
	 * @param IAppConfig             $appConfig     Holds the size limit.
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly PdfStreamReaderFactory $streamReaders,
		private readonly IAppConfig $appConfig,
	) {
	}//end __construct()

	/**
	 * Stamp a text on every page of a PDF.
	 *
	 * @param string $pdf     The PDF bytes.
	 * @param string $text    Plain text, at most 200 characters, lines separated by a newline.
	 * @param array  $options placement: diagonal, footer or both (default both);
	 *                        opacity: 0-1 for the text across the page (default 0.15).
	 *
	 * @return string The stamped PDF bytes.
	 *
	 * @throws PdfStampRefusal When the file is not a PDF, encrypted, too large, or cannot be stamped.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1.1
	 */
	public function stamp(string $pdf, string $text, array $options = []): string {
		$this->refuseUnstampable(pdf: $pdf);
		$lines     = $this->lines(text: $text);
		$placement = (string) ($options['placement'] ?? 'both');
		if (in_array($placement, self::PLACEMENTS, true) === false) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::FAILED, reason: 'Unknown placement: ' . $placement);
		}

		// The mPDF library switches mb_internal_encoding and puts it back only on Output(),
		// so a stamp that fails halfway would leave it changed for the request.
		$encoding = mb_internal_encoding();
		try {
			return $this->stampPages(pdf: $pdf, lines: $lines, placement: $placement, options: $options);
		} finally {
			mb_internal_encoding($encoding);
		}
	}//end stamp()

	/**
	 * Import every page, stamp it, and answer the new PDF.
	 *
	 * @param string             $pdf       The PDF bytes.
	 * @param array<int, string> $lines     The text lines.
	 * @param string             $placement diagonal, footer or both.
	 * @param array              $options   The stamp options (opacity).
	 *
	 * @return string The stamped PDF bytes.
	 *
	 * @throws PdfStampRefusal With failed when the PDF cannot be read or stamped.
	 */
	private function stampPages(string $pdf, array $lines, string $placement, array $options): string {
		try {
			$mpdf = $this->pdfService->createMpdfInstance();
			$pages = $mpdf->setSourceFile($this->streamReaders->fromString(content: $pdf));
		} catch (Throwable $e) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::FAILED, reason: 'The PDF could not be read.', previous: $e);
		}

		if ($pages < 1) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::FAILED, reason: 'The PDF has no pages.');
		}

		try {
			if ($placement !== 'footer') {
				$mpdf->SetWatermarkText(implode(' · ', $lines), (float) ($options['opacity'] ?? self::DEFAULT_OPACITY));
				$mpdf->showWatermarkText = true;
			}

			$mpdf->SetAutoPageBreak(false);
			for ($page = 1; $page <= $pages; $page++) {
				$this->stampPage(mpdf: $mpdf, page: $page, lines: $lines, footer: $placement !== 'diagonal');
			}

			return $mpdf->Output('', 'S');
		} catch (Throwable $e) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::FAILED, reason: 'The PDF could not be stamped.', previous: $e);
		}
	}//end stampPages()

	/**
	 * Import one page at its own size and write the footer on it.
	 *
	 * @param Mpdf               $mpdf   The document being built.
	 * @param int                $page   The page number, from 1.
	 * @param array<int, string> $lines  The text lines.
	 * @param bool               $footer Whether to write the footer.
	 *
	 * @return void
	 */
	private function stampPage(Mpdf $mpdf, int $page, array $lines, bool $footer): void {
		$mpdf->AddPage();
		$template = $mpdf->importPage($page);
		$mpdf->useTemplate(tpl: $template, adjustPageSize: true);
		if ($footer === false) {
			return;
		}

		$mpdf->SetFont('', '', self::FOOTER_POINTS);
		$mpdf->SetTextColor(80, 80, 80);
		$top = ($mpdf->h - self::FOOTER_MARGIN - (self::FOOTER_LINE * (count($lines) - 1)));
		foreach ($lines as $index => $line) {
			$mpdf->WriteText(self::FOOTER_LEFT, ($top + (self::FOOTER_LINE * $index)), $line);
		}
	}//end stampPage()

	/**
	 * Refuse a file that is not a PDF, too large, or encrypted.
	 *
	 * @param string $pdf The bytes.
	 *
	 * @return void
	 *
	 * @throws PdfStampRefusal With not-a-pdf, too-large or encrypted.
	 */
	private function refuseUnstampable(string $pdf): void {
		$header = strpos(substr($pdf, 0, 1024), '%PDF-');
		if ($header === false) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::NOT_A_PDF, reason: 'The file is not a PDF.');
		}

		$limit = $this->appConfig->getValueInt('filinq', self::SETTING_MAX_BYTES, self::DEFAULT_MAX_BYTES);
		if ($limit > 0 && strlen($pdf) > $limit) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::TOO_LARGE, reason: 'The PDF is larger than ' . $limit . ' bytes.');
		}

		if (preg_match('#/Encrypt[\s/<\d]#', $pdf) === 1) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::ENCRYPTED, reason: 'The PDF is encrypted.');
		}
	}//end refuseUnstampable()

	/**
	 * The text as plain lines: control characters dropped, at most 200 characters in all.
	 *
	 * @param string $text The text.
	 *
	 * @return array<int, string> The non-empty lines.
	 *
	 * @throws PdfStampRefusal With failed when nothing is left to stamp.
	 */
	private function lines(string $text): array {
		$plain = (string) preg_replace('/[^\P{C}\n]/u', '', str_replace(["\r\n", "\r"], "\n", $text));
		$plain = mb_substr(trim($plain), 0, self::MAX_TEXT_LENGTH);
		$lines = array_values(array_filter(array_map('trim', explode("\n", $plain)), static fn (string $line): bool => $line !== ''));
		if ($lines === []) {
			throw new PdfStampRefusal(refusalCode: PdfStampRefusal::FAILED, reason: 'There is no text to stamp.');
		}

		return $lines;
	}//end lines()
}//end class
