<?php

/**
 * QR Code Service
 *
 * Thin wrapper around the vendored, dependency-free QR Code encoder
 * (OCA\DocuDesk\Vendor\KazuhikoArase\QrCode). Produces the visible
 * verification QR stamped onto signed/waarmerked PDFs and the SVG rendering
 * consumed by the public verification portal.
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

use OCA\DocuDesk\Vendor\KazuhikoArase\QRCode;
use RuntimeException;
use Throwable;

/**
 * Builds QR codes for the signature-verification-portal QR stamp.
 *
 * Uses error-correction level M (~15% recovery) — a reasonable balance
 * between resilience (photocopies, footer-scale printing) and symbol size
 * for a bounded-length `verify/{token}` URL.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/signature-verification-portal/design.md#d4
 */
class QrCodeService
{
    /**
     * Build a QR code for the given data string.
     *
     * @param string $data The data to encode (the absolute verify/{token} URL).
     *
     * @return QRCode The built, mask-optimised QR code (modules ready via
     *                {@see QRCode::isDark()} / {@see QRCode::getModuleCount()}).
     *
     * @throws RuntimeException When the data cannot be encoded (e.g. too long
     *                          for the QR version range the vendored encoder
     *                          resolves).
     *
     * @spec openspec/changes/signature-verification-portal/design.md#d4
     */
    public function build(string $data): QRCode
    {
        if (trim($data) === '') {
            throw new RuntimeException('Cannot build a QR code for empty data');
        }

        try {
            // QR_ERROR_CORRECT_LEVEL_M is a global constant defined by the
            // vendored encoder (lib/Vendor/KazuhikoArase/QrCode.php).
            return QRCode::getMinimumQRCode($data, QR_ERROR_CORRECT_LEVEL_M);
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to build QR code: '.$e->getMessage(), 0, $e);
        }

    }//end build()

    /**
     * Render a QR code for the given data as a standalone SVG string.
     *
     * Used by the public verification portal's API response (no GD/raster
     * dependency; the browser renders the vector directly).
     *
     * @param string $data       The data to encode.
     * @param int    $moduleSize Pixel size of one QR module in the SVG.
     *
     * @return string The SVG markup.
     *
     * @throws RuntimeException When the QR code cannot be built.
     *
     * @spec openspec/changes/signature-verification-portal/design.md#d4
     */
    public function toSvg(string $data, int $moduleSize=4): string
    {
        $qr = $this->build($data);

        ob_start();
        $qr->printSVG($moduleSize);
        $svg = ob_get_clean();

        return is_string($svg) ? $svg : '';

    }//end toSvg()
}//end class
