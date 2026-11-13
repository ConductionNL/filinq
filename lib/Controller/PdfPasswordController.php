<?php

/**
 * PDF password controller
 *
 * POST api/documents/{fileId}/remove-password: unlock a password-protected PDF
 * the caller can open into a copy beside it and answer the copy's findings
 * (intake-pdf-password-removal REQ-PPR-001..003). The password is read from
 * the request body once and handed to the service; it is not logged.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\PdfPasswordRemovalService;
use OCA\Filinq\Service\SessionUserFile;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;

/**
 * Remove the password of a PDF into a copy.
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
 */
class PdfPasswordController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest                  $request The request.
	 * @param PdfPasswordRemovalService $removal Unlocks the PDF into a copy.
	 * @param SessionUserFile           $files   The signed-in user's own files.
	 * @param IL10N                     $l10n    Translations.
	 */
	public function __construct(
		IRequest $request,
		private readonly PdfPasswordRemovalService $removal,
		private readonly SessionUserFile $files,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);
	}//end __construct()

	/**
	 * Unlock one PDF the caller can open.
	 *
	 * 200 with the copy's id, name and findings; 404 for a file the caller
	 * cannot open; 400 without a password or for a file that is not a PDF;
	 * 422 for a wrong password; 409 for a final document; 503 without qpdf.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#1-service-and-route
	 */
	#[NoAdminRequired]
	public function remove(int $fileId): JSONResponse {
		// The session user's own folder: a file id from the payload is only a key into it.
		$file = $this->files->find(fileId: $fileId);
		if ($file === null) {
			return new JSONResponse(['error' => $this->l10n->t('File not found')], Http::STATUS_NOT_FOUND);
		}

		$password = $this->request->getParam('password', '');
		if (is_string($password) === false || $password === '') {
			return new JSONResponse(['error' => $this->l10n->t('Type the password of the PDF.')], Http::STATUS_BAD_REQUEST);
		}

		$result = $this->removal->unlock(file: $file, password: $password);
		unset($password);

		$t = fn (string $text): array => ['error' => $this->l10n->t($text)];

		return match ($result['outcome']) {
			PdfPasswordRemovalService::UNLOCKED => new JSONResponse(array_diff_key($result, ['outcome' => true])),
			PdfPasswordRemovalService::WRONG_PASSWORD => new JSONResponse($t('The password is not correct'), Http::STATUS_UNPROCESSABLE_ENTITY),
			PdfPasswordRemovalService::FINAL => new JSONResponse($t('This document is final and cannot be changed'), Http::STATUS_CONFLICT),
			PdfPasswordRemovalService::NO_TOOL => new JSONResponse($t('qpdf is not installed on this server'), Http::STATUS_SERVICE_UNAVAILABLE),
			default => $this->failed(message: (string)($result['message'] ?? '')),
		};
	}//end remove()

	/**
	 * The answer to a file that is not a PDF (400) or could not be unlocked (500).
	 *
	 * @param string $message The service's message.
	 *
	 * @return JSONResponse
	 */
	private function failed(string $message): JSONResponse {
		if ($message === 'This file is not a PDF.') {
			return new JSONResponse(['error' => $this->l10n->t('This file is not a PDF.')], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse(['error' => $this->l10n->t('The password could not be removed from this PDF.')], Http::STATUS_INTERNAL_SERVER_ERROR);
	}//end failed()
}//end class
