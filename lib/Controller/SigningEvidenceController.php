<?php

/**
 * Signing Evidence Controller
 *
 * Download or file the sealed evidence document of a finished signing request.
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
use OCA\Filinq\Service\SigningEvidenceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * "Bewijsdocument downloaden (pdf)" and "Kopie naar dossier" (REQ-DDWMK-006).
 *
 * The request is read scoped to the caller (initiator or signer); an admin
 * reads unscoped, as on the signing request page. Not found and not yours
 * are one 404.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
 */
class SigningEvidenceController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app id.
	 * @param IRequest $request The request.
	 * @param SigningEvidenceService $evidence Builds the evidence document.
	 * @param IUserSession $userSession The caller.
	 * @param IGroupManager $groupManager Admin check.
	 * @param LoggerInterface $logger Logs failures.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly SigningEvidenceService $evidence,
		private readonly IUserSession $userSession,
		private readonly IGroupManager $groupManager,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Download the sealed evidence PDF.
	 *
	 * @param string $id The signing request id.
	 *
	 * @return Response
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function download(string $id): Response {
		$uid = $this->userSession->getUser()?->getUID();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		return $this->guarded(
			action: function () use ($id, $uid): Response {
				$result = $this->evidence->download(requestId: $id, callerScope: $this->scope(uid: $uid), userId: $uid);
				return new DataDownloadResponse($result['pdf'], $result['fileName'], 'application/pdf');
			}
		);

	}//end download()

	/**
	 * Write the sealed evidence PDF next to the signed document.
	 *
	 * @param string $id The signing request id.
	 *
	 * @return Response
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-completed-signing-transaction-yields-one-sealed-evidence-document-req-ddwmk-006
	 */
	#[NoAdminRequired]
	public function copy(string $id): Response {
		$uid = $this->userSession->getUser()?->getUID();
		if ($uid === null) {
			return $this->notAuthenticated(message: 'Not authenticated');
		}

		return $this->guarded(
			action: fn (): Response => new JSONResponse(
				$this->evidence->copyToDossier(requestId: $id, callerScope: $this->scope(uid: $uid), userId: $uid),
				Http::STATUS_CREATED
			)
		);

	}//end copy()

	/**
	 * '' (unscoped) for an admin, else the uid.
	 *
	 * @param string $uid The caller.
	 *
	 * @return string
	 */
	private function scope(string $uid): string {
		if ($this->groupManager->isAdmin($uid) === true) {
			return '';
		}

		return $uid;

	}//end scope()

	/**
	 * Run an action, mapping refusals to their status.
	 *
	 * @param callable(): Response $action The action.
	 *
	 * @return Response
	 */
	private function guarded(callable $action): Response {
		try {
			return $action();
		} catch (WaarmerkRefusedException $e) {
			return new JSONResponse(array_merge(['error' => $e->getMessage()], $e->getDetails()), $e->getStatus());
		} catch (Throwable $e) {
			$this->logger->error('Filinq: signing evidence request failed', ['reason' => get_class($e), 'message' => $e->getMessage()]);
			return new JSONResponse(['error' => 'The evidence document could not be made'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end guarded()
}//end class
