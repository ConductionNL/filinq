<?php

/**
 * Redaction Output Controller
 *
 * The HTTP surface of what leaves the building: marking a detection run
 * checked, composing a publication list out of redacted copies, and the
 * conditions a gated download waits on.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\AnonymizeRequestServiceInterface;
use OCA\Filinq\Kernel\Contracts\DownloadAgreementGateInterface;
use OCA\Filinq\Kernel\Contracts\PublicationListComposerInterface;
use OCA\Filinq\Kernel\Contracts\RedactionOutputGuardInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Controller for the review mark, the composed list and the download gate.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */
class RedactionOutputController extends Controller {
	use AnswersNotAuthenticated;

	/**
	 * How many documents one status read may ask about.
	 */
	private const MAX_STATUS_IDS = 100;

	/**
	 * Constructor.
	 *
	 * @param string                  $appName     The app name.
	 * @param IRequest                $request     The request.
	 * @param RedactionOutputGuardInterface    $guard       The human review gate.
	 * @param PublicationListComposerInterface $composer    The publication list.
	 * @param DownloadAgreementGateInterface   $agreements  The download conditions.
	 * @param IUserSession            $userSession The current session.
	 * @param AnonymizeRequestServiceInterface $access      The per-file access check.
	 * @param IL10N                   $l10n        The translator.
	 * @param LoggerInterface         $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly RedactionOutputGuardInterface $guard,
		private readonly PublicationListComposerInterface $composer,
		private readonly DownloadAgreementGateInterface $agreements,
		private readonly IUserSession $userSession,
		private readonly AnonymizeRequestServiceInterface $access,
		private readonly IL10N $l10n,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Record that this person checked this detection run.
	 *
	 * 🔴 THE CHECKER IS THE SESSION, NEVER THE PAYLOAD. Taking `checkedBy` from
	 * the request would let a caller sign an approval in somebody else's name,
	 * which turns the accountability record into the opposite of one.
	 *
	 * @param int                              $fileId   The document.
	 * @param array<int, array<string, mixed>> $entities The entities this run found.
	 * @param string                           $note     What the checker wants recorded.
	 *
	 * @return JSONResponse The stored mark, or the refusal.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-2
	 *
	 * @no-admin-idor-exempt AnonymizeRequestService::verifyFileAccess() refuses a file outside the
	 * caller's user folder before anything is written, and the mark is saved with OpenRegister RBAC on
	 * (redactionReviewMark create: filinq-woo-officers, filinq-policy-admins).
	 */
	#[NoAdminRequired]
	public function markChecked(int $fileId, array $entities = [], string $note = ''): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('A check is signed by a person, and this request has none.')],
				statusCode: Http::STATUS_UNAUTHORIZED
			);
		}

		$denied = $this->access->verifyFileAccess(fileId: $fileId);
		if ($denied !== null) {
			return new JSONResponse(data: $denied['body'], statusCode: $denied['status']);
		}

		try {
			$mark = $this->guard->markChecked(
				fileId: $fileId,
				entities: $entities,
				checkedBy: $user->getUID(),
				note: $note
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'The review mark could not be recorded',
				['fileId' => $fileId, 'exception' => $e]
			);
			return new JSONResponse(
				data: ['error' => $this->l10n->t('The check could not be recorded, so the document is still not checked. Try again.')],
				statusCode: Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse(data: $mark);

	}//end markChecked()

	/**
	 * The latest review mark of each listed document the caller can open.
	 *
	 * A document the caller cannot open is left out, the same as one nobody
	 * checked, so the answer says nothing about files outside their reach.
	 *
	 * @param string $fileIds Comma separated file ids.
	 *
	 * @return JSONResponse `{marks: {fileId: {checkedBy, checkedAt, entityCount}}}`.
	 *
	 * @spec openspec/changes/anonymise-asks-for-the-review-mark/tasks.md#task-3
	 *
	 * @no-admin-idor-exempt every requested id passes AnonymizeRequestService::verifyFileAccess() (the
	 * caller's user folder) before its mark is read; ids the caller cannot read are dropped.
	 */
	#[NoAdminRequired]
	public function checkedStatus(string $fileIds = ''): JSONResponse {
		if ($this->userSession->getUser() === null) {
			return $this->notAuthenticated(message: $this->l10n->t('Not authenticated'));
		}

		$ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $fileIds)))));
		if (count($ids) > self::MAX_STATUS_IDS) {
			return new JSONResponse(
				data: ['error' => $this->l10n->t('Ask about at most %s documents at a time.', [self::MAX_STATUS_IDS])],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$readable = array_values(
			array_filter(
				$ids,
				fn (int $id): bool => $this->access->verifyFileAccess(fileId: $id) === null
			)
		);

		return new JSONResponse(data: ['marks' => $this->guard->marksFor(fileIds: $readable)]);

	}//end checkedStatus()

	/**
	 * Compose a publication list over one saved view.
	 *
	 * @param string $view  The view id or slug.
	 * @param string $title What the list is called.
	 *
	 * @return JSONResponse The list, or what is not ready.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 *
	 * @no-admin-idor-exempt the view is resolved by OpenRegister ViewService::find() with the session user
	 * as owner (own or public views only), and its records are read with RBAC on (SavedViewReader).
	 */
	#[NoAdminRequired]
	public function composeList(string $view, string $title = ''): JSONResponse {
		$composed = $this->composer->compose(view: $view, title: $title);

		if (isset($composed['refused']) === true) {
			return new JSONResponse(data: $composed, statusCode: Http::STATUS_CONFLICT);
		}

		return new JSONResponse(data: $composed);

	}//end composeList()

	/**
	 * The conditions this document is gated on, and whether they are met.
	 *
	 * @param string $document The document.
	 *
	 * @return JSONResponse The decision and the terms.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 *
	 * @no-admin-idor-exempt caller-scoped. The acceptance is looked up for the session user only
	 * (personAsking()), and the terms come from the downloadAgreement schema with OpenRegister RBAC on.
	 */
	#[NoAdminRequired]
	public function agreement(string $document): JSONResponse {
		return new JSONResponse(
			data: $this->agreements->check(document: $document, person: $this->personAsking())
		);

	}//end agreement()

	/**
	 * Accept the conditions, and say whether the file may now be served.
	 *
	 * @param string $document The document.
	 * @param string $version  The version the reader was shown.
	 *
	 * @return JSONResponse The decision.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 *
	 * @no-admin-idor-exempt caller-scoped. It records an acceptance for the session user only
	 * (personAsking()) and cannot act for anybody else.
	 */
	#[NoAdminRequired]
	public function acceptAgreement(string $document, string $version): JSONResponse {
		$decision = $this->agreements->accept(
			document: $document,
			person: $this->personAsking(),
			version: $version
		);

		if (($decision['mayDownload'] ?? false) === false) {
			return new JSONResponse(data: $decision, statusCode: Http::STATUS_CONFLICT);
		}

		return new JSONResponse(data: $decision);

	}//end acceptAgreement()

	/**
	 * Who is asking, as the acceptance records them.
	 *
	 * @return string The uid, or an empty string when there is no session.
	 *
	 * @spec exclude Session helper behind the agreement endpoints.
	 */
	private function personAsking(): string {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return '';
		}

		return $user->getUID();

	}//end personAsking()
}//end class
