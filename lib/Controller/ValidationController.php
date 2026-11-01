<?php

/**
 * Validation Controller
 *
 * On-demand document-validation endpoint. Resolves the file through the
 * requesting user's folder (IDOR-safe per ADR-005) and returns the verdict +
 * findings without persisting anything.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-validation-checks/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for on-demand document validation.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class ValidationController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param LoggerInterface $logger Logger.
	 * @param DocumentValidationServiceInterface $service The validation service.
	 * @param IRootFolder $rootFolder Root folder.
	 * @param IL10N $l10n Localisation.
	 * @param IUserSession $userSession User session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly DocumentValidationServiceInterface $service,
		private readonly IRootFolder $rootFolder,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Validate a file on demand without persisting.
	 *
	 * Body: {"fileId": int, "documentType"?: string}
	 *
	 * @return JSONResponse The verdict + findings, or an error.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 *
	 * @no-admin-idor-exempt the file is resolved through the caller's own user folder in the method body,
	 * and an id the caller cannot read answers 404.
	 */
	#[NoAdminRequired]
	public function validate(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		$fileId = (int)$this->request->getParam('fileId', 0);
		if ($fileId <= 0) {
			return $this->errorResponse(message: $this->l10n->t('fileId is required'), status: Http::STATUS_BAD_REQUEST);
		}

		$file = $this->resolveReadableFile(userId: $user->getUID(), fileId: $fileId);
		if ($file === null) {
			return $this->errorResponse(message: $this->l10n->t('File not found'), status: Http::STATUS_NOT_FOUND);
		}

		return $this->runValidation(file: $file, fileId: $fileId);

	}//end validate()

	/**
	 * Validate the file with the request's document type, answering 500 on failure.
	 *
	 * @param File $file The resolved file.
	 * @param int $fileId The requested file id, for the log.
	 *
	 * @return JSONResponse The verdict + findings, or a 500.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	private function runValidation(File $file, int $fileId): JSONResponse {
		try {
			$result = $this->service->validate(file: $file, record: [], documentType: $this->documentType());
			return new JSONResponse(data: $result, statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			$this->logger->error('Validation failed', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return $this->errorResponse(message: $this->l10n->t('Validation failed'), status: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end runValidation()

	/**
	 * The request's `documentType` as a string, or null when absent.
	 *
	 * @return string|null The document type.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	private function documentType(): ?string {
		$documentType = $this->request->getParam('documentType');
		if ($documentType === null) {
			return null;
		}

		return (string)$documentType;

	}//end documentType()

	/**
	 * Resolve a file id through the user's own folder. A file the user cannot
	 * read resolves to null, so the caller answers 404 without disclosure.
	 *
	 * @param string $userId The requesting user.
	 * @param int $fileId The requested file id.
	 *
	 * @return File|null The file, or null when it is not a readable file.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	private function resolveReadableFile(string $userId, int $fileId): ?File {
		$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
			return null;
		}

		return $nodes[0];

	}//end resolveReadableFile()

	/**
	 * A JSON error response with one message.
	 *
	 * @param string $message The error message.
	 * @param int $status The HTTP status.
	 *
	 * @return JSONResponse The response.
	 *
	 * @spec openspec/specs/document-validation-checks/spec.md
	 */
	private function errorResponse(string $message, int $status): JSONResponse {
		return new JSONResponse(data: ['error' => $message], statusCode: $status);

	}//end errorResponse()
}//end class
