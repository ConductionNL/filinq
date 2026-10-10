<?php

/**
 * Pdf Stamp Service
 *
 * Stamps a text on every page of a PDF: across the page, at its foot, or both.
 * Built for sibling apps that must not serve a paper without the reader's name
 * on it, starting with decidiq's personal watermark on confidential meeting
 * papers.
 *
 * Every page is imported with FPDI on the same loop the PDF/A-3 conversion
 * uses, with `adjustPageSize`, so each page keeps its own size and orientation.
 * The result is a new PDF; the input bytes are never changed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use Mpdf\Mpdf;
use OCA\Filinq\Exception\PdfStampRefusedException;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Throwable;

/**
 * Stamps a text on every page of a PDF.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
 */
class PdfStampService {

	/**
	 * App config key holding the largest PDF, in bytes, that is stamped.
	 */
	public const MAX_BYTES_KEY = 'stamp_max_bytes';

	/**
	 * Default limit: 50 MB.
	 */
	public const DEFAULT_MAX_BYTES = 52428800;

	/**
	 * The longest stamp text accepted, in characters.
	 */
	public const MAX_TEXT_LENGTH = 200;

	/**
	 * The placements a caller may ask for.
	 *
	 * @var array<int, string>
	 */
	public const PLACEMENTS = ['diagonal', 'footer', 'both'];

	/**
	 * Opacity of the text across the page.
	 */
	private const DIAGONAL_ALPHA = 0.15;

	/**
	 * Font size of the foot line, in points.
	 */
	private const FOOTER_FONT_SIZE = 8;

	/**
	 * Distance of the last foot line from the bottom edge, in millimetres.
	 */
	private const FOOTER_BOTTOM_MM = 8;

	/**
	 * Height of one foot line, in millimetres.
	 */
	private const FOOTER_LINE_MM = 3.5;

	/**
	 * Left margin of the foot line, in millimetres.
	 */
	private const FOOTER_LEFT_MM = 10;

	/**
	 * FPDI stream-reader seam.
	 *
	 * @var PdfStreamReaderFactory
	 */
	private readonly PdfStreamReaderFactory $streamReaderFactory;

	/**
	 * Constructor.
	 *
	 * @param PdfService                  $pdfService          Builds the configured mPDF instance.
	 * @param IAppConfig                  $appConfig           Holds the size limit.
	 * @param LoggerInterface             $logger              Logs stamping failures.
	 * @param PdfStreamReaderFactory|null $streamReaderFactory FPDI stream-reader seam.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly IAppConfig $appConfig,
		private readonly LoggerInterface $logger,
		?PdfStreamReaderFactory $streamReaderFactory = null,
	) {
		$this->streamReaderFactory = ($streamReaderFactory ?? new PdfStreamReaderFactory());

	}//end __construct()

	/**
	 * Stamp a text on every page of a PDF and return the new PDF.
	 *
	 * @param string               $pdf     The PDF bytes; never changed.
	 * @param string               $text    Plain text, at most 200 characters; lines split on a newline.
	 * @param array<string, mixed> $options `placement`: diagonal, footer or both (default both).
	 *
	 * @return string The stamped PDF bytes.
	 *
	 * @throws InvalidArgumentException When the text or the placement is not accepted.
	 * @throws PdfStampRefusedException When the file is not a PDF, encrypted, too large, or stamping failed.
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-1
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-1-2
	 */
	public function stamp(string $pdf, string $text, array $options = []): string {
		$placement = (string)($options['placement'] ?? 'both');
		$lines = $this->validateText(text: $text);
		if (in_array($placement, self::PLACEMENTS, true) === false) {
			throw new InvalidArgumentException(
				'Unknown placement "' . $placement . '"; use one of ' . implode(', ', self::PLACEMENTS) . '.'
			);
		}

		$this->assertStampable(pdf: $pdf);

		try {
			$mpdf = $this->pdfService->createMpdfInstance();
			$pageCount = $mpdf->setSourceFile(file: $this->streamReaderFactory->fromString($pdf));
			for ($page = 1; $page <= $pageCount; $page++) {
				$mpdf->AddPage();
				$mpdf->useTemplate(tpl: $mpdf->importPage(pageNumber: $page), adjustPageSize: true);
				$this->stampPage(mpdf: $mpdf, lines: $lines, placement: $placement);
			}

			return $mpdf->Output(name: '', dest: 'S');
		} catch (CrossReferenceException $e) {
			if ($e->getCode() === CrossReferenceException::ENCRYPTED) {
				throw new PdfStampRefusedException(
					refusalCode: PdfStampRefusedException::ENCRYPTED,
					message: 'This PDF is encrypted and cannot be stamped.',
					previous: $e
				);
			}

			throw $this->failed(e: $e);
		} catch (Throwable $e) {
			throw $this->failed(e: $e);
		}//end try

	}//end stamp()

	/**
	 * Split and check the stamp text.
	 *
	 * @param string $text The stamp text.
	 *
	 * @return array<int, string> The non-empty lines.
	 *
	 * @throws InvalidArgumentException When the text is empty or longer than 200 characters.
	 */
	private function validateText(string $text): array {
		if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
			throw new InvalidArgumentException(
				'The stamp text is longer than ' . self::MAX_TEXT_LENGTH . ' characters.'
			);
		}

		$lines = array_values(
			array_filter(
				array_map('trim', preg_split('/\r\n|\r|\n/', $text)),
				static fn (string $line): bool => $line !== ''
			)
		);
		if ($lines === []) {
			throw new InvalidArgumentException('The stamp text is empty.');
		}

		return $lines;

	}//end validateText()

	/**
	 * Refuse a file that is too large, not a PDF, or encrypted, before parsing it.
	 *
	 * @param string $pdf The input bytes.
	 *
	 * @return void
	 *
	 * @throws PdfStampRefusedException With the matching code.
	 */
	private function assertStampable(string $pdf): void {
		$limit = (int)$this->appConfig->getValueString(
			'filinq',
			self::MAX_BYTES_KEY,
			(string)self::DEFAULT_MAX_BYTES
		);
		if ($limit > 0 && strlen($pdf) > $limit) {
			throw new PdfStampRefusedException(
				refusalCode: PdfStampRefusedException::TOO_LARGE,
				message: sprintf('This PDF is %d bytes; the limit for stamping is %d bytes.', strlen($pdf), $limit)
			);
		}

		if (str_contains(substr($pdf, 0, 1024), '%PDF-') === false) {
			throw new PdfStampRefusedException(
				refusalCode: PdfStampRefusedException::NOT_A_PDF,
				message: 'This file is not a PDF.'
			);
		}

		// An encryption dictionary in the trailer: FPDI cannot import the
		// pages, and serving the file unstamped is exactly what must not happen.
		if (preg_match('#/Encrypt\s*\d+\s+\d+\s+R#', $pdf) === 1) {
			throw new PdfStampRefusedException(
				refusalCode: PdfStampRefusedException::ENCRYPTED,
				message: 'This PDF is encrypted and cannot be stamped.'
			);
		}

	}//end assertStampable()

	/**
	 * Write the text on the current page.
	 *
	 * @param Mpdf               $mpdf      The document, on the page just imported.
	 * @param array<int, string> $lines     The stamp lines.
	 * @param string             $placement diagonal, footer or both.
	 *
	 * @return void
	 */
	private function stampPage(Mpdf $mpdf, array $lines, string $placement): void {
		if ($placement === 'diagonal' || $placement === 'both') {
			$mpdf->watermark(implode(' - ', $lines), 45, 96, self::DIAGONAL_ALPHA);
		}

		if ($placement === 'footer' || $placement === 'both') {
			$mpdf->SetFont('', '', self::FOOTER_FONT_SIZE);
			$mpdf->SetTextColor(90, 90, 90);
			$count = count($lines);
			foreach ($lines as $index => $line) {
				$y = ($mpdf->h - self::FOOTER_BOTTOM_MM - (($count - 1 - $index) * self::FOOTER_LINE_MM));
				$mpdf->WriteText(self::FOOTER_LEFT_MM, $y, $line);
			}

			$mpdf->SetTextColor(0, 0, 0);
		}

	}//end stampPage()

	/**
	 * The refusal for a stamping failure, logged.
	 *
	 * @param Throwable $e The underlying failure.
	 *
	 * @return PdfStampRefusedException The `failed` refusal.
	 */
	private function failed(Throwable $e): PdfStampRefusedException {
		$this->logger->error('[PdfStampService] Stamping failed', ['exception' => $e]);

		return new PdfStampRefusedException(
			refusalCode: PdfStampRefusedException::FAILED,
			message: 'The PDF could not be stamped: ' . $e->getMessage(),
			previous: $e
		);

	}//end failed()
}//end class
