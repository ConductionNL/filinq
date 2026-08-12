<?php

/**
 * EML Preview Controller
 *
 * Streams a PDF/A-3b preview of the ORIGINAL (un-redacted) content of a
 * `message/rfc822` file, so the in-app file viewer can render an .eml the same
 * way it renders a PDF. The rendering is delegated to EmlPreviewService, which
 * reuses the anonymise-assembly pipeline with an empty entity set.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\Service\EmlPreviewService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Serves original-EML previews as PDF.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 */
class EmlPreviewController extends Controller {
	/**
	 * Constructor.
	 *
	 * @param string $appName App identifier.
	 * @param IRequest $request The current request.
	 * @param EmlPreviewService $emlPreviewService Renders the original EML to PDF.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param IUserSession $userSession Session user, for the file-access guard.
	 * @param IRootFolder $rootFolder Root folder, for the file-access guard.
	 * @param IL10N $l10n Translations for the guard responses.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly EmlPreviewService $emlPreviewService,
		private readonly LoggerInterface $logger,
		private readonly IUserSession $userSession,
		private readonly IRootFolder $rootFolder,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Verify the current user has access to the given file id.
	 *
	 * Resolves the file through the caller's OWN file tree — the same guard
	 * `AnonymizationController::verifyFileAccess()` applies to the sibling
	 * `extract` / `anonymize` endpoints under this URL prefix.
	 *
	 * Without this, the only thing separating a caller from another user's
	 * message is Nextcloud's session-scoped id resolution: the render path
	 * hands `$fileId` to OpenRegister's `FileService::getFileById()`, which
	 * calls `IRootFolder::getById()` at ROOT scope. That resolves against the
	 * session's mounts and so denies cross-user reads today, but it is an
	 * incidental protection, not an authorisation check — it disappears the
	 * moment the same code is reached without a session user (a background
	 * job, an `occ` command, any system-context call).
	 *
	 * A shared file still resolves: shares are mounted inside the user folder,
	 * so this denies exactly what is already denied and grants nothing new.
	 * Returns 404 rather than 403 so callers cannot probe for existence.
	 *
	 * @param int $fileId The Nextcloud file id to check.
	 *
	 * @return JSONResponse|null Null when access is granted, an error response otherwise.
	 */
	private function verifyFileAccess(int $fileId): ?JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Not authenticated')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		if (empty($nodes) === true || ($nodes[0] instanceof File) === false) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('File not found')],
				statusCode: Http::STATUS_NOT_FOUND
			);
		}

		return null;
	}//end verifyFileAccess()

	/**
	 * Stream a PDF/A-3b preview of the original (un-redacted) EML.
	 *
	 * The rendered PDF carries the ORIGINAL headers, body and attachments, so
	 * the caller's access to `$fileId` is verified before anything is
	 * rendered.
	 *
	 * @param int $fileId Nextcloud file id of the source .eml.
	 *
	 * @return DataDownloadResponse|JSONResponse PDF bytes, or a JSON error (401/404/422).
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function preview(int $fileId): DataDownloadResponse|JSONResponse {
		$accessError = $this->verifyFileAccess(fileId: $fileId);
		if ($accessError !== null) {
			return $accessError;
		}

		try {
			$pdf = $this->emlPreviewService->renderOriginalPreview(fileId: $fileId);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: 'EML preview failed for file ' . $fileId . ': ' . $e->getMessage(),
				context: ['fileId' => $fileId, 'exception' => $e]
			);
			return new JSONResponse(
				data: ['error' => 'Could not render EML preview: ' . $e->getMessage()],
				statusCode: 422
			);
		}

		return new DataDownloadResponse(
			data: $pdf,
			filename: 'eml-preview.pdf',
			contentType: 'application/pdf'
		);

	}//end preview()
}//end class
