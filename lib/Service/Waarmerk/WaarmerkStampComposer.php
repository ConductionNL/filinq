<?php

/**
 * Waarmerk Stamp Composer
 *
 * Renders the visible waarmerk stamp page and appends it to a PDF.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
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

namespace OCA\Filinq\Service\Waarmerk;

use Com\Tecnick\Barcode\Barcode;
use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Kernel\Contracts\PdfServiceInterface;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * The stamp page (mark, organisation, seal time, verification code, QR) and
 * its appending as the final page, never overlaying an existing one.
 *
 * The QR is generated locally (tc-lib-barcode, already shipped with
 * tc-lib-pdf); no external QR service is called.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class WaarmerkStampComposer {

	private const TEMPLATE = __DIR__ . '/../../Resources/templates/waarmerk/stamp.twig';

	/**
	 * Constructor.
	 *
	 * @param PdfServiceInterface $pdfService Renders Twig to PDF.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfServiceInterface $pdfService,
	) {

	}//end __construct()

	/**
	 * The source PDF with the stamp page appended.
	 *
	 * @param string $sourcePdf The PDF bytes to seal.
	 * @param array<string, string> $stamp organisation, sealedAt, verificationCode, verificationUrl, documentName.
	 *
	 * @return string The sealed PDF bytes.
	 *
	 * @throws WaarmerkRefusedException 422 when the source is not a readable PDF.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function compose(string $sourcePdf, array $stamp): string {
		if (str_starts_with(ltrim($sourcePdf), '%PDF-') === false) {
			throw new WaarmerkRefusedException(message: 'Only a PDF can be sealed', status: 422);
		}

		$stamp['qr'] = $this->qrDataUri(url: (string) ($stamp['verificationUrl'] ?? ''));
		$stampPdf = $this->pdfService->renderPdf(
			templateContent: (string) file_get_contents(self::TEMPLATE),
			data: ['stamp' => $stamp],
			options: ['title' => 'Waarmerk']
		);

		return $this->append(sourcePdf: $sourcePdf, stampPdf: $stampPdf);

	}//end compose()

	/**
	 * A QR code of the verification URL as an SVG data URI.
	 *
	 * @param string $url The public verification URL.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function qrDataUri(string $url): string {
		$svg = (new Barcode())->getBarcodeObj('QRCODE,M', $url, -4, -4, 'black', [0, 0, 0, 0])->getSvgCode();
		return 'data:image/svg+xml;base64,' . base64_encode($svg);

	}//end qrDataUri()

	/**
	 * Every page of the source, then every page of the stamp.
	 *
	 * @param string $sourcePdf The source bytes.
	 * @param string $stampPdf The stamp page bytes.
	 *
	 * @return string
	 *
	 * @throws WaarmerkRefusedException 422 when FPDI cannot read the source.
	 */
	private function append(string $sourcePdf, string $stampPdf): string {
		$source = (string) tempnam(sys_get_temp_dir(), 'filinq-seal-src-');
		$stamp = (string) tempnam(sys_get_temp_dir(), 'filinq-seal-stamp-');
		try {
			file_put_contents($source, $sourcePdf);
			file_put_contents($stamp, $stampPdf);
			$pdf = new Fpdi();
			foreach ([$source, $stamp] as $file) {
				$count = $pdf->setSourceFile($file);
				for ($page = 1; $page <= $count; $page++) {
					$template = $pdf->importPage($page);
					$size = $pdf->getTemplateSize($template);
					$pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
					$pdf->useTemplate($template);
				}
			}

			// @phpstan-ignore-next-line method.notFound (FPDF stubs are not loaded for static analysis).
			return (string) $pdf->Output('S');
		} catch (Throwable $e) {
			throw new WaarmerkRefusedException(message: 'The PDF could not be read to append the waarmerk page', status: 422, previous: $e);
		} finally {
			@unlink($source);
			@unlink($stamp);
		}//end try

	}//end append()
}//end class
