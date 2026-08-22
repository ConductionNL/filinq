<?php

/**
 * Version Controller
 *
 * Thin, authorized read/restore endpoints over a document's Nextcloud file
 * versions (`files_versions`). Every access is resolved through the requesting
 * user's folder inside the service, so the endpoints are IDOR-safe (ADR-005):
 * an inaccessible document yields 404 with no existence disclosure, and restore
 * requires write access. No Filinq-owned version storage is introduced.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Service\DocumentVersionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for document file-version listing, download and restore.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-versions/spec.md
 */
class VersionController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param LoggerInterface $logger Logger.
	 * @param DocumentVersionService $service The version service.
	 * @param IL10N $l10n Localisation.
	 * @param IUserSession $userSession User session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly LoggerInterface $logger,
		private readonly DocumentVersionService $service,
		private readonly IL10N $l10n,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List a document's Nextcloud file versions (newest first).
	 *
	 * Query params: `limit` (default 50), `offset` (default 0).
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return JSONResponse The versions, or a localised error.
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function index(int $fileId): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => $this->l10n->t('Not authenticated')], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		$limit = (int)($this->request->getParam('limit', 50));
		$offset = (int)($this->request->getParam('offset', 0));
		if ($limit <= 0) {
			$limit = 50;
		}

		try {
			$versions = $this->service->listVersions(fileId: $fileId, limit: $limit, offset: max(0, $offset));
			return new JSONResponse(data: ['versions' => $versions], statusCode: Http::STATUS_OK);
		} catch (ComparisonException $e) {
			return $this->errorResponse(exception: $e);
		} catch (Throwable $e) {
			$this->logger->error('Version listing failed', ['exception' => $e->getMessage()]);
			return new JSONResponse(data: ['error' => $this->l10n->t('Could not list versions')], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}//end try

	}//end index()

	/**
	 * Download (or open) the bytes of a specific version.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp (0 = current).
	 *
	 * @return DataDownloadResponse|JSONResponse The bytes, or a localised error.
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function download(int $fileId, int $versionTimestamp): DataDownloadResponse|JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => $this->l10n->t('Not authenticated')], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$bytes = $this->service->readVersion(fileId: $fileId, versionTimestamp: $versionTimestamp);
			$filename = 'version-' . $fileId . '-' . $versionTimestamp;
			return new DataDownloadResponse(data: $bytes, filename: $filename, contentType: 'application/octet-stream');
		} catch (ComparisonException $e) {
			return $this->errorResponse(exception: $e);
		} catch (Throwable $e) {
			$this->logger->error('Version download failed', ['exception' => $e->getMessage()]);
			return new JSONResponse(data: ['error' => $this->l10n->t('Could not download version')], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}//end try

	}//end download()

	/**
	 * Restore a prior version (requires write access).
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param int $versionTimestamp The version timestamp to restore.
	 *
	 * @return JSONResponse Success, or a localised error.
	 *
	 * @spec openspec/specs/document-versions/spec.md
	 */
	#[NoAdminRequired]
	public function restore(int $fileId, int $versionTimestamp): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return new JSONResponse(data: ['error' => $this->l10n->t('Not authenticated')], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->service->restoreVersion(fileId: $fileId, versionTimestamp: $versionTimestamp);
			return new JSONResponse(data: ['restored' => true], statusCode: Http::STATUS_OK);
		} catch (ComparisonException $e) {
			return $this->errorResponse(exception: $e);
		} catch (Throwable $e) {
			$this->logger->error('Version restore failed', ['exception' => $e->getMessage()]);
			return new JSONResponse(data: ['error' => $this->l10n->t('Could not restore version')], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}//end try

	}//end restore()

	/**
	 * Map a ComparisonException to a localised JSON error response.
	 *
	 * @param ComparisonException $exception The exception.
	 *
	 * @return JSONResponse The error response.
	 */
	private function errorResponse(ComparisonException $exception): JSONResponse {
		$message = $this->l10n->t('Document not found');
		if ($exception->getReason() === 'versions-unavailable') {
			$message = $this->l10n->t('File versions are not available on this instance');
		}

		return new JSONResponse(
			data: [
				'error' => $message,
				'reason' => $exception->getReason(),
			],
			statusCode: $exception->getStatusCode()
		);

	}//end errorResponse()
}//end class
