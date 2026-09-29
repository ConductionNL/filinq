<?php

/**
 * Pseudonymisation Controller
 *
 * The routes of reversible pseudonymisation: whether a redacted copy kept a key
 * and whether the caller may use it, and the restore itself. Both answer 404 for
 * a copy the caller cannot open, and the restore answers 403 to anyone outside
 * the allowed groups before it looks at anything else.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCA\Filinq\Service\Pseudonymisation\PseudonymRestoreService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Reversible pseudonymisation routes.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymisationController extends Controller {

	/**
	 * HTTP status per refusal. A caller outside the allowed groups gets the same
	 * 403 whether the list is empty, unreadable or simply lacks them.
	 *
	 * @var array<string, int>
	 */
	private const STATUS = [
		PseudonymRestoreRefusedException::REASON_NOT_ALLOWED => Http::STATUS_FORBIDDEN,
		PseudonymRestoreRefusedException::REASON_CONFIG_UNREADABLE => Http::STATUS_FORBIDDEN,
		PseudonymRestoreRefusedException::REASON_NOT_FOUND => Http::STATUS_NOT_FOUND,
		PseudonymRestoreRefusedException::REASON_NO_MAP => Http::STATUS_CONFLICT,
		PseudonymRestoreRefusedException::REASON_MAP_UNREADABLE => Http::STATUS_CONFLICT,
		PseudonymRestoreRefusedException::REASON_WRITE_FAILED => Http::STATUS_INTERNAL_SERVER_ERROR,
		PseudonymRestoreRefusedException::REASON_AUDIT_UNAVAILABLE => Http::STATUS_SERVICE_UNAVAILABLE,
	];

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param PseudonymRestoreService $restores Status and restore.
	 * @param IUserSession $userSession The signed-in user.
	 * @param IL10N $l10n Translations.
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PseudonymRestoreService $restores,
		private readonly IUserSession $userSession,
		private readonly IL10N $l10n,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Whether a redacted copy kept a key, and whether the caller may restore it.
	 *
	 * The service answers null unless the caller can open the copy in their own
	 * files, so a guessed file id learns nothing.
	 *
	 * @param int $fileId The redacted copy's file id.
	 *
	 * @return JSONResponse linkId, reversible, entryCount, mayRestore; 404 otherwise.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
	 */
	#[NoAdminRequired]
	public function status(int $fileId): JSONResponse {
		$status = $this->restores->status(anonymizedFileId: $fileId, userId: $this->userId());
		if ($status === null) {
			return new JSONResponse(['error' => $this->l10n->t('Document not found')], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($status);

	}//end status()

	/**
	 * Restore the original of one reversibly anonymised copy.
	 *
	 * NoAdminRequired because the allowed groups are not admins by definition;
	 * the gate inside the service is the authorisation, and it runs first.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 *
	 * @return JSONResponse The restored copy or the report; a refusal with its reason otherwise.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	#[NoAdminRequired]
	public function restore(string $linkId): JSONResponse {
		try {
			$result = $this->restores->restore(linkId: $linkId, userId: $this->userId());
		} catch (PseudonymRestoreRefusedException $refusal) {
			return $this->refused(refusal: $refusal);
		}

		return new JSONResponse($result);

	}//end restore()

	/**
	 * The signed-in user id, '' when nobody is signed in.
	 *
	 * @return string The user id.
	 */
	private function userId(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end userId()

	/**
	 * The response for a refusal: a neutral message and the reason code.
	 *
	 * @param PseudonymRestoreRefusedException $refusal The refusal.
	 *
	 * @return JSONResponse The response.
	 */
	private function refused(PseudonymRestoreRefusedException $refusal): JSONResponse {
		$reason = $refusal->getReason();
		$messages = [
			PseudonymRestoreRefusedException::REASON_NOT_ALLOWED => $this->l10n->t('You are not allowed to restore names.'),
			PseudonymRestoreRefusedException::REASON_CONFIG_UNREADABLE => $this->l10n->t('You are not allowed to restore names.'),
			PseudonymRestoreRefusedException::REASON_NOT_FOUND => $this->l10n->t('Document not found'),
			PseudonymRestoreRefusedException::REASON_NO_MAP => $this->l10n->t('This copy was anonymised without a key, so the names cannot be restored.'),
			PseudonymRestoreRefusedException::REASON_MAP_UNREADABLE => $this->l10n->t('The key of this copy could not be read, so the names cannot be restored.'),
			PseudonymRestoreRefusedException::REASON_WRITE_FAILED => $this->l10n->t('The restored copy could not be saved.'),
			PseudonymRestoreRefusedException::REASON_AUDIT_UNAVAILABLE => $this->l10n->t('Nothing was restored, because the audit trail could not record it. Try again later.'),
		];

		// The 403 bodies carry no reason: whether the list is empty or
		// unreadable is not the caller's business.
		$body = ['error' => ($messages[$reason] ?? $this->l10n->t('The names could not be restored.'))];
		$status = (self::STATUS[$reason] ?? Http::STATUS_INTERNAL_SERVER_ERROR);
		if ($status !== Http::STATUS_FORBIDDEN) {
			$body['reason'] = $reason;
		}

		return new JSONResponse($body, $status);

	}//end refused()
}//end class
