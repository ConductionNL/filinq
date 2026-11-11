<?php

/**
 * PDF responses
 *
 * The PDF/A-3 download, the typed refusal and the error status the PDF endpoints answer with.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Exception\Pdfa3ConversionException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Builds the PDF/A-3 download with its composition headers and the typed
 * error response for a refused conversion.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/pdfa3-conversion/spec.md
 */
final class PdfResponses {

	/**
	 * The PDF/A-3 download with its composition headers.
	 *
	 * Callers such as dossiq's archival pipeline read the composition
	 * metadata ({checksumSha256, paginas}) off these headers without a second
	 * round trip. THE `X-Docudesk-*` HEADER NAMES STAY ACROSS THE FILINQ RENAME:
	 * they are a cross-app response contract, and a renamed header is not an
	 * error to a consumer but an absent header, so the integration degrades
	 * silently. Rename them only together with the consumers; the same applies
	 * to the `X-Docudesk-File-*` headers in DocumentController.
	 *
	 * @param array<string, mixed> $result   The conversion result.
	 * @param string               $filename The download file name.
	 *
	 * @return DataDownloadResponse The download.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function pdfa3Download(array $result, string $filename): DataDownloadResponse {
		$response = new DataDownloadResponse(
			data: $result['content'],
			filename: $filename,
			contentType: 'application/pdf'
		);
		$response->addHeader('X-Docudesk-Pdfa3-Checksum-Sha256', $result['checksumSha256']);
		$response->addHeader('X-Docudesk-Pdfa3-Pages', (string)$result['pages']);
		$response->addHeader('X-Docudesk-Pdfa3-Conformance', $result['conformance']);
		$response->addHeader('X-Docudesk-Pdfa3-Verified', (string) ($result['verified'] ?? 'skipped'));

		return $response;

	}//end pdfa3Download()

	/**
	 * The typed error response for a conversion the service refused: its
	 * message, reason and admin hint, under its HTTP-range code, else 500.
	 *
	 * @param Pdfa3ConversionException $exception The refusal.
	 *
	 * @return JSONResponse The error with its reason and admin hint.
	 *
	 * @spec openspec/specs/pdfa3-conversion/spec.md
	 */
	public function pdfa3Refusal(Pdfa3ConversionException $exception): JSONResponse {
		return new JSONResponse(
			data: [
				'error' => $exception->getMessage(),
				'reason' => $exception->getReason(),
				'adminHint' => $exception->getAdminHint(),
			],
			statusCode: $this->clampStatusCode(code: $exception->getCode())
		);

	}//end pdfa3Refusal()

	/**
	 * Clamp an exception code to a valid HTTP status range, defaulting
	 * to 500 for anything outside [400, 599].
	 *
	 * @param int $code Candidate status code.
	 *
	 * @return int Valid HTTP status code.
	 *
	 * @spec openspec/specs/pdf-generation/spec.md
	 *
	 * @psalm-suppress InvalidArgument $statusCode is clamped to int<400, 599>; Psalm wants the literal HTTP status union.
	 */
	public function clampStatusCode(int $code): int {
		if ($code >= 400 && $code < 600) {
			return $code;
		}

		return Http::STATUS_INTERNAL_SERVER_ERROR;

	}//end clampStatusCode()
}//end class
