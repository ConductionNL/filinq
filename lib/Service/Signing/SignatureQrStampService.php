<?php

/**
 * Signature QR Stamp Service
 *
 * Stamps a visible, scannable QR code (encoding the public verify/{token}
 * URL) onto every page of a signed or waarmerked PDF, so a printed or
 * downloaded copy remains checkable (LibreSign#2617, REQ-DDSVP-005).
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service\Signing
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

namespace OCA\DocuDesk\Service\Signing;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Vendor\KazuhikoArase\QRCode;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Overlays a footer QR stamp onto every page of a PDF via mPDF/FPDI.
 *
 * Reuses the mPDF instance already a composer dependency of this app (see
 * {@see PdfService::createMpdfInstance()}, already used for FPDI page-import
 * assembly by EmlPdfAssemblyService) — no new heavy dependency is added
 * (design.md D4). The QR itself is drawn as native PDF filled rectangles
 * (one per dark module) via {@see Mpdf::Rect()}, not a rasterised image, so
 * no GD extension is required either.
 *
 * Fail-soft by design: any failure to parse/stamp the source PDF logs a
 * warning and returns the ORIGINAL bytes unchanged (Risks/Trade-offs in
 * design.md: "cosmetic, opt-outable" — a stamping failure must never block
 * the signing completion that is already honest-completion-gated elsewhere).
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/signature-verification-portal/design.md#d4
 */
class SignatureQrStampService
{
    /**
     * Footer QR stamp size, in millimetres.
     */
    private const QR_SIZE_MM = 20.0;

    /**
     * Margin from the page edge to the QR stamp, in millimetres.
     */
    private const QR_MARGIN_MM = 8.0;

    /**
     * Constructor
     *
     * @param PdfService     $pdfService     PDF service (mPDF instance factory)
     * @param QrCodeService  $qrCodeService  QR code builder
     * @param LoggerInterface $logger        Logger
     *
     * @return void
     */
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly QrCodeService $qrCodeService,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Stamp a footer QR (encoding `$verifyUrl`) onto every page of `$pdfContent`.
     *
     * Fail-soft: any error returns the original bytes unchanged and logs a
     * warning — a QR-stamping failure must never block signing completion.
     *
     * @param string $pdfContent The PDF bytes to stamp.
     * @param string $verifyUrl  The absolute `verify/{token}` URL to encode.
     *
     * @return string The stamped PDF bytes, or the original bytes on failure.
     *
     * @spec openspec/changes/signature-verification-portal/design.md#d4
     */
    public function stampFooterQr(string $pdfContent, string $verifyUrl): string
    {
        if (trim($pdfContent) === '' || trim($verifyUrl) === '') {
            return $pdfContent;
        }

        try {
            $qr = $this->qrCodeService->build($verifyUrl);
        } catch (Throwable $e) {
            $this->logger->warning(
                'QR generation failed; shipping the signed artifact without a QR stamp: '.$e->getMessage()
            );
            return $pdfContent;
        }

        $tmpSource = tempnam(sys_get_temp_dir(), 'ddqr_src_');
        if ($tmpSource === false) {
            $this->logger->warning('Could not create a temp file for QR-stamp PDF import; shipping without a stamp.');
            return $pdfContent;
        }

        try {
            file_put_contents($tmpSource, $pdfContent);

            $mpdf      = $this->pdfService->createMpdfInstance(options: []);
            $pageCount = $mpdf->setSourceFile($tmpSource);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $this->importAndStampPage(mpdf: $mpdf, qr: $qr, pageNo: $pageNo);
            }

            $bytes = $mpdf->Output(name: '', dest: Destination::STRING_RETURN);

            return is_string($bytes) ? $bytes : $pdfContent;
        } catch (Throwable $e) {
            $this->logger->warning(
                'QR footer stamping failed; shipping the signed artifact without a visible QR: '.$e->getMessage()
            );
            return $pdfContent;
        } finally {
            if (file_exists($tmpSource) === true) {
                @unlink($tmpSource);
            }
        }

    }//end stampFooterQr()

    /**
     * Import one source page via FPDI at its native size, then draw the QR
     * footer stamp onto it.
     *
     * @param Mpdf   $mpdf   The shared mPDF instance (mid-assembly).
     * @param QRCode $qr     The built QR code.
     * @param int    $pageNo 1-based source page number.
     *
     * @return void
     */
    private function importAndStampPage(Mpdf $mpdf, QRCode $qr, int $pageNo): void
    {
        $tplId = $mpdf->importPage($pageNo);
        $size  = $mpdf->getTemplateSize($tplId);

        $width  = 210.0;
        $height = 297.0;
        if (is_array($size) === true && isset($size['width'], $size['height']) === true) {
            $width  = (float) $size['width'];
            $height = (float) $size['height'];
        }

        $orientation = ($width > $height) ? 'L' : 'P';

        // Preserve the source page's exact dimensions ('sheet-size') so the
        // stamped artifact remains a faithful 1:1 copy of the signed document.
        $mpdf->AddPageByArray(
            [
                'orientation' => $orientation,
                'sheet-size'  => [$width, $height],
            ]
        );
        $mpdf->useTemplate($tplId, 0, 0, $width, $height);

        $this->drawQrFooter(mpdf: $mpdf, qr: $qr, pageWidthMm: $width, pageHeightMm: $height);

    }//end importAndStampPage()

    /**
     * Draw the QR matrix as filled PDF rectangles in the page's bottom-right
     * footer corner, on a small white backing so it reads over dark content.
     *
     * @param Mpdf   $mpdf         The mPDF instance (current page).
     * @param QRCode $qr           The built QR code.
     * @param float  $pageWidthMm  Page width, millimetres.
     * @param float  $pageHeightMm Page height, millimetres.
     *
     * @return void
     */
    private function drawQrFooter(Mpdf $mpdf, QRCode $qr, float $pageWidthMm, float $pageHeightMm): void
    {
        $moduleCount = $qr->getModuleCount();
        if ($moduleCount <= 0) {
            return;
        }

        $moduleSizeMm = self::QR_SIZE_MM / $moduleCount;
        $x0           = $pageWidthMm - self::QR_SIZE_MM - self::QR_MARGIN_MM;
        $y0           = $pageHeightMm - self::QR_SIZE_MM - self::QR_MARGIN_MM;

        // White backing (with a small bleed) so the QR reads over any page content.
        $mpdf->SetFillColor(255, 255, 255);
        $mpdf->Rect($x0 - 1.5, $y0 - 1.5, self::QR_SIZE_MM + 3, self::QR_SIZE_MM + 3, 'F');

        $mpdf->SetFillColor(0, 0, 0);
        for ($row = 0; $row < $moduleCount; $row++) {
            for ($col = 0; $col < $moduleCount; $col++) {
                if ($qr->isDark($row, $col) === true) {
                    $mpdf->Rect(
                        $x0 + ($col * $moduleSizeMm),
                        $y0 + ($row * $moduleSizeMm),
                        $moduleSizeMm,
                        $moduleSizeMm,
                        'F'
                    );
                }
            }
        }

    }//end drawQrFooter()
}//end class
