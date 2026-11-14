<?php

/**
 * Waarmerk Controller
 *
 * Seal, list, show and revoke waarmerken, and certify anonymisation runs.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
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

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\WaarmerkRefusedException;
use OCA\Filinq\Service\ProcessingCertificateService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCA\Filinq\Service\Waarmerk\SealedFileWriter;
use OCA\Filinq\Service\WaarmerkService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Authenticated waarmerk endpoints.
 *
 * Sealing needs read access to the file (the caller's Files or the object
 * that owns it). Listing and showing are scoped to the sealer unless the
 * caller is an admin, with one 404 for "absent" and "not yours". Revocation
 * is guarded in WaarmerkService::revoke(): admin or the sealer only.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
 */
class WaarmerkController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app id.
	 * @param IRequest $request The request.
	 * @param WaarmerkService $waarmerk Seals and revokes.
	 * @param ProcessingCertificateService $certificates Certifies anonymisation runs.
	 * @param ReadableFileResolver $files The caller's file access.
	 * @param SealedFileWriter $writer Writes the sealed file and its record.
	 * @param IUserSession $userSession The caller.
	 * @param IGroupManager $groupManager Admin check.
	 * @param LoggerInterface $logger Logs failures.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly WaarmerkService $waarmerk,
		private readonly ProcessingCertificateService $certificates,
		private readonly ReadableFileResolver $files,
		private readonly SealedFileWriter $writer,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List the caller's seals (all seals for an admin), optionally for one sealed file.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$uid = $this->uid();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$fileId = $this->request->getParam('documentFileId');
		$documentFileId = null;
		if (is_numeric($fileId) === true) {
			$documentFileId = (int) $fileId;
		}

		return $this->guarded(
			action: fn (): JSONResponse => new JSONResponse(
				['results' => $this->waarmerk->list(userId: $uid, isAdmin: $this->groupManager->isAdmin($uid), documentFileId: $documentFileId)]
			)
		);

	}//end index()

	/**
	 * One seal.
	 *
	 * @param string $id The seal's uuid.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	#[NoAdminRequired]
	public function show(string $id): JSONResponse {
		$uid = $this->uid();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		return $this->guarded(
			action: function () use ($id, $uid): JSONResponse {
				$seal = $this->waarmerk->show(uuid: $id, userId: $uid, isAdmin: $this->groupManager->isAdmin($uid));
				if ($seal === null) {
					return new JSONResponse(['error' => 'Waarmerk not found'], Http::STATUS_NOT_FOUND);
				}

				return new JSONResponse($seal);
			}
		);

	}//end show()

	/**
	 * Seal a PDF the caller can read; the sealed copy is written next to it.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	#[NoAdminRequired]
	public function seal(): JSONResponse {
		$uid = $this->uid();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		$node = $this->files->node(fileId: (int) $this->request->getParam('fileId', 0));
		if (($node instanceof File) === false) {
			return new JSONResponse(['error' => 'File not found'], Http::STATUS_NOT_FOUND);
		}

		return $this->guarded(
			action: function () use ($node, $uid): JSONResponse {
				$folder = $node->getParent();
				if (($folder instanceof Folder) === false) {
					throw new WaarmerkRefusedException(message: 'The file has no folder to write the sealed copy into', status: 422);
				}

				$name = SealedFileWriter::siblingName(sourceName: $node->getName(), suffix: 'gewaarmerkt');
				$artifact = $this->waarmerk->prepare(pdf: $node->getContent(), documentName: $name, userId: $uid);

				return new JSONResponse(
					array_merge($this->writer->write(folder: $folder, name: $name, artifact: $artifact), ['sourceFileId' => (int) $node->getId()]),
					Http::STATUS_CREATED
				);
			}
		);

	}//end seal()

	/**
	 * Revoke a seal, with a mandatory reason.
	 *
	 * @param string $id The seal's uuid.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-waarmerk-can-be-revoked-and-revocation-dominates-verification-req-ddwmk-005
	 */
	#[NoAdminRequired]
	public function revoke(string $id): JSONResponse {
		$uid = $this->uid();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		// The admin-or-sealer guard runs inside revoke(), before any write.
		return $this->guarded(
			action: fn (): JSONResponse => new JSONResponse(
				$this->waarmerk->revoke(
					uuid: $id,
					userId: $uid,
					isAdmin: $this->groupManager->isAdmin($uid),
					reason: (string) $this->request->getParam('reason', '')
				)
			)
		);

	}//end revoke()

	/**
	 * Certify an anonymisation run with a sealed processing certificate.
	 *
	 * @param string $linkId The anonymizationLink uuid.
	 *
	 * @return JSONResponse
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-anonymization-runs-can-be-certified-with-a-sealed-processing-certificate-req-ddwmk-003
	 */
	#[NoAdminRequired]
	public function certify(string $linkId): JSONResponse {
		$uid = $this->uid();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		return $this->guarded(
			action: fn (): JSONResponse => new JSONResponse($this->certificates->certify(linkId: $linkId, userId: $uid), Http::STATUS_CREATED)
		);

	}//end certify()

	/**
	 * The caller's uid, or null.
	 *
	 * @return string|null
	 */
	private function uid(): ?string {
		return $this->userSession->getUser()?->getUID();

	}//end uid()

	/**
	 * Run an action, mapping refusals to their status and anything else to 500.
	 *
	 * @param callable(): JSONResponse $action The action.
	 *
	 * @return JSONResponse
	 */
	private function guarded(callable $action): JSONResponse {
		try {
			return $action();
		} catch (WaarmerkRefusedException $e) {
			return new JSONResponse(array_merge(['error' => $e->getMessage()], $e->getDetails()), $e->getStatus());
		} catch (Throwable $e) {
			$this->logger->error('Filinq: waarmerk request failed', ['reason' => get_class($e), 'message' => $e->getMessage()]);
			return new JSONResponse(['error' => 'The waarmerk request failed'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end guarded()
}//end class
