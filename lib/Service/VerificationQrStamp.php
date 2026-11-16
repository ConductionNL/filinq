<?php

/**
 * Draws a verification QR code and its link at the foot of a PDF's last page.
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
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Mpdf\Mpdf;
use OCA\Filinq\Service\Qr\QrCodeEncoder;
use RuntimeException;
use Throwable;

/**
 * Imports every page of a PDF and draws, at the bottom right of the last
 * page, a QR code of a link with the link written beside it.
 *
 * The QR is drawn as filled squares from {@see QrCodeEncoder}, so no QR
 * library is needed. The result is a new PDF; the input is never changed.
 *
 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
 */
class VerificationQrStamp {

	/**
	 * The side of one QR module, in millimetres.
	 *
	 * @var float
	 */
	public const MODULE_MM = 0.45;

	/**
	 * The light border around the QR, in modules (the four ISO 18004 asks for).
	 *
	 * @var int
	 */
	public const QUIET_MODULES = 4;

	/**
	 * The distance from the page edges, in millimetres.
	 *
	 * @var float
	 */
	private const MARGIN_MM = 6.0;

	/**
	 * The caption font size, in points.
	 *
	 * @var int
	 */
	private const CAPTION_POINTS = 7;

	/**
	 * Constructor.
	 *
	 * @param PdfService             $pdfService    Creates the mPDF instance.
	 * @param PdfStreamReaderFactory $streamReaders Reads the source PDF from memory.
	 * @param QrCodeEncoder          $encoder       Encodes the link.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly PdfStreamReaderFactory $streamReaders,
		private readonly QrCodeEncoder $encoder,
	) {
	}//end __construct()

	/**
	 * Whether bytes are a PDF this stamp can write on.
	 *
	 * @param string $content The bytes.
	 *
	 * @return bool True for an unencrypted PDF.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
	 */
	public function canStamp(string $content): bool {
		if (str_starts_with(ltrim(substr($content, 0, 1024)), '%PDF-') === false) {
			return false;
		}

		return preg_match('/\/Encrypt\s/', $content) !== 1;
	}//end canStamp()

	/**
	 * Stamp a link as a QR code with the link and a caption beside it.
	 *
	 * @param string $pdf     The PDF bytes.
	 * @param string $url     The link the QR encodes.
	 * @param string $caption The line written above the link.
	 *
	 * @return string The stamped PDF bytes.
	 *
	 * @throws RuntimeException When the bytes are not a stampable PDF or cannot be stamped.
	 *
	 * @spec openspec/changes/signature-verification-portal/tasks.md#task-2.4
	 */
	public function stamp(string $pdf, string $url, string $caption): string {
		if ($this->canStamp(content: $pdf) === false) {
			throw new RuntimeException('Only an unencrypted PDF can carry the verification QR code');
		}

		$matrix = $this->encoder->encode(text: $url);

		// The mPDF library switches mb_internal_encoding and restores it only on Output().
		$encoding = mb_internal_encoding();
		try {
			$mpdf = $this->importPages(pdf: $pdf);
			$this->drawStamp(mpdf: $mpdf, matrix: $matrix, url: $url, caption: $caption);

			return $mpdf->Output('', 'S');
		} catch (RuntimeException $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The verification QR code could not be stamped: ' . $e->getMessage(), code: 0, previous: $e);
		} finally {
			mb_internal_encoding($encoding);
		}
	}//end stamp()

	/**
	 * A new document holding every page of a PDF, positioned on its last page.
	 *
	 * @param string $pdf The PDF bytes.
	 *
	 * @return Mpdf The document.
	 *
	 * @throws RuntimeException When the PDF has no pages.
	 */
	private function importPages(string $pdf): Mpdf {
		$mpdf = $this->pdfService->createMpdfInstance();
		$pages = $mpdf->setSourceFile($this->streamReaders->fromString(content: $pdf));
		if ($pages < 1) {
			throw new RuntimeException('The PDF has no pages');
		}

		$mpdf->SetAutoPageBreak(false);
		for ($page = 1; $page <= $pages; $page++) {
			$mpdf->AddPage();
			$mpdf->useTemplate(tpl: $mpdf->importPage($page), adjustPageSize: true);
		}

		return $mpdf;
	}//end importPages()

	/**
	 * Draw the QR code at the bottom right of the current page, and the caption and link to its left.
	 *
	 * @param Mpdf                         $mpdf    The document, on its last page.
	 * @param array<int, array<int, bool>> $matrix  The QR modules.
	 * @param string                       $url     The link.
	 * @param string                       $caption The caption.
	 *
	 * @return void
	 */
	private function drawStamp(Mpdf $mpdf, array $matrix, string $url, string $caption): void {
		$modules = count($matrix);
		$side = (($modules + (2 * self::QUIET_MODULES)) * self::MODULE_MM);
		$left = ($mpdf->w - self::MARGIN_MM - $side);
		$top = ($mpdf->h - self::MARGIN_MM - $side);

		$mpdf->SetFillColor(255, 255, 255);
		$mpdf->Rect($left, $top, $side, $side, 'F');
		$mpdf->SetFillColor(0, 0, 0);
		$origin = (self::QUIET_MODULES * self::MODULE_MM);
		foreach ($matrix as $row => $columns) {
			foreach ($columns as $column => $dark) {
				if ($dark === true) {
					$mpdf->Rect(
						($left + $origin + ($column * self::MODULE_MM)),
						($top + $origin + ($row * self::MODULE_MM)),
						self::MODULE_MM,
						self::MODULE_MM,
						'F'
					);
				}
			}
		}

		$mpdf->SetFont('', '', self::CAPTION_POINTS);
		$mpdf->SetTextColor(60, 60, 60);
		$textRight = ($left - 2.0);
		$baseline = ($mpdf->h - self::MARGIN_MM - $origin);
		$mpdf->WriteText(($textRight - $mpdf->GetStringWidth($url)), $baseline, $url);
		$mpdf->WriteText(($textRight - $mpdf->GetStringWidth($caption)), ($baseline - 3.5), $caption);
	}//end drawStamp()
}//end class
