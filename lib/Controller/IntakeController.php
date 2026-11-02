<?php

/**
 * Intake Controller
 *
 * The HTTP surface of the intake inbox: what is waiting, assign one document to
 * a record, reject one with a reason. Every refusal the service decides keeps
 * its status here, so a missing right answers 403 and a request that could
 * never be right answers 400.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\IntakeDetachmentServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeNotificationReachInterface;
use OCA\Filinq\Kernel\Contracts\IntakeRoutingServiceInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCA\Filinq\Kernel\Contracts\PartySuggestionServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Throwable;

/**
 * Controller for the documents waiting for a record.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeController extends Controller {
	use AnswersIntakeRefusals;
	use ReadsTypedRequestParameters;

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param IntakeServiceInterface $intake The intake inbox.
	 * @param IntakeDetachmentServiceInterface $detachment Returns a filed document to the worklist.
	 * @param IntakeRoutingServiceInterface $routing What a consuming app declared per record type.
	 * @param PartySuggestionServiceInterface $parties Party suggestions and the corrections corpus.
	 * @param IUserSession $userSession The current session.
	 * @param IntakeNotificationReachInterface $reach Whether a failed reading reaches anybody.
	 * @param IGroupManager $groups Counts the people in the notified group.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IntakeServiceInterface $intake,
		private readonly IntakeDetachmentServiceInterface $detachment,
		private readonly IntakeRoutingServiceInterface $routing,
		private readonly PartySuggestionServiceInterface $parties,
		private readonly IUserSession $userSession,
		private readonly IntakeNotificationReachInterface $reach,
		private readonly IGroupManager $groups,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * List the documents waiting for a clerk.
	 *
	 * @return JSONResponse The waiting documents.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$waiting = $this->intake->listWaiting();

			return new JSONResponse(
				data: [
					'results' => $waiting,
					'total' => count($waiting),
					'notificationReach' => $this->notificationReach(waiting: $waiting),
				],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end index()

	/**
	 * Whether the readingFailed notification reaches anybody, said on the inbox.
	 *
	 * Counts the failed readings in the worklist and the people in the group the
	 * rule addresses. A group that does not exist, or a backend that cannot
	 * count, reaches nobody.
	 *
	 * @param array<int, array<string, mixed>> $waiting The waiting documents.
	 *
	 * @return array<string, mixed> The reach, the failure count and the group name.
	 *
	 * @spec openspec/changes/archive/2026-10-09-intake-failure-reaches-someone/tasks.md#task-3.3
	 */
	private function notificationReach(array $waiting): array {
		$failed = 0;
		foreach ($waiting as $document) {
			if (($document['readingState'] ?? null) === 'failed') {
				$failed++;
			}
		}

		$members = 0;
		$group   = $this->groups->get(IntakeNotificationReachInterface::GROUP);
		if ($group !== null) {
			$counted = $group->count();
			if (is_int($counted) === true) {
				$members = $counted;
			}
		}

		$reach = $this->reach->describe(failureCount: $failed, groupMembers: $members);
		$reach['failureCount'] = $failed;
		$reach['group']        = IntakeNotificationReachInterface::GROUP;

		return $reach;

	}//end notificationReach()

	/**
	 * Assign one waiting document to a record.
	 *
	 * The request body names the target: `register`, `schema` and `id`, and
	 * `declaringApp` and `typeReference` for the app that owns the record type,
	 * each a string that defaults to ''. `withAttachments` says whether
	 * everything that arrived with the message goes along; it defaults to
	 * false. A target value that is not a string answers a bare 400, before
	 * the session is checked, as the dispatcher does for a typed argument.
	 *
	 * @param string $uuid The intake document.
	 *
	 * @return Response The assigned document, or the refusal.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	#[NoAdminRequired]
	public function assign(string $uuid): Response {
		$body = $this->typedRequestParameters(
			spec: [
				'register' => ['string', ''],
				'schema' => ['string', ''],
				'id' => ['string', ''],
				'declaringApp' => ['string', ''],
				'typeReference' => ['string', ''],
				'withAttachments' => ['bool', false],
			]
		);
		if ($body === null) {
			return $this->badRequest();
		}

		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$withAttachments = $body['withAttachments'];
		unset($body['withAttachments']);

		try {
			if ($withAttachments === true) {
				return new JSONResponse(
					data: $this->intake->assignWithAttachments(uuid: $uuid, target: $body),
					statusCode: Http::STATUS_OK
				);
			}

			return new JSONResponse(
				data: $this->intake->assign(uuid: $uuid, target: $body),
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end assign()

	/**
	 * Reject one waiting document.
	 *
	 * @param string $uuid The intake document.
	 * @param string $reason Why it does not belong here.
	 *
	 * @return JSONResponse The rejected document, or the refusal.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	#[NoAdminRequired]
	public function reject(string $uuid, string $reason = ''): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$document = $this->intake->reject(uuid: $uuid, reason: $reason);

			return new JSONResponse(data: $document, statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end reject()

	/**
	 * List the documents taken back off a record.
	 *
	 * @return JSONResponse The worklist.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	#[NoAdminRequired]
	public function detached(): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$worklist = $this->intake->listDetached();

			return new JSONResponse(
				data: ['results' => $worklist, 'total' => count($worklist)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end detached()

	/**
	 * Take one document off the record it is filed on.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $reason Why it does not belong there.
	 * @param string $documentName The document's name, for a file that never had an intake record.
	 *
	 * @return JSONResponse The intake document, now on the worklist.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	#[NoAdminRequired]
	public function detach(int $fileId, string $reason = '', string $documentName = ''): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$document = $this->detachment->detach(
				fileId: $fileId,
				reason: $reason,
				documentName: $documentName
			);

			return new JSONResponse(data: $document, statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end detach()

	/**
	 * Record what a clerk did with a party suggestion.
	 *
	 * @param string $sender The sender the suggestion was made for.
	 * @param string $decision One of `accepted`, `edited`, `rejected`.
	 * @param array<string, mixed> $suggested What was proposed.
	 * @param array<string, mixed> $accepted What was filed, empty on a rejection.
	 * @param string $intakeDocument The intake document it was shown on.
	 *
	 * @return JSONResponse The stored correction.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	#[NoAdminRequired]
	public function decideParty(
		string $sender = '',
		string $decision = '',
		array $suggested = [],
		array $accepted = [],
		string $intakeDocument = '',
	): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		if (in_array($decision, ['accepted', 'edited', 'rejected'], true) === false) {
			return new JSONResponse(
				data: ['error' => 'A party decision is accepted, edited or rejected.'],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$correction = $this->parties->recordDecision(
				sender: $sender,
				decision: $decision,
				suggested: $suggested,
				accepted: $accepted,
				intakeDocument: $intakeDocument
			);

			return new JSONResponse(data: $correction, statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end decideParty()

	/**
	 * Store what a consuming app declares about one of its record types.
	 *
	 * @param string $declaringApp The app making the declaration.
	 * @param string $typeReference The record type, in that app's vocabulary.
	 * @param string $routeTo The group inbound documents go to.
	 * @param bool $requiresAcceptance Whether that group has to accept them.
	 *
	 * @return JSONResponse The stored declaration.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) `$requiresAcceptance` is a
	 * field of the declaration the consuming app posts, not a mode this code
	 * chose. It is stored on the routing rule and read back from there; it does
	 * not branch this method at all, so there is no second responsibility here
	 * to split off.
	 *
	 * @spec openspec/changes/archive/2026-10-09-inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	#[NoAdminRequired]
	public function declareRouting(
		string $declaringApp = '',
		string $typeReference = '',
		string $routeTo = '',
		bool $requiresAcceptance = false,
	): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$declaration = $this->routing->declare(
				declaringApp: $declaringApp,
				typeReference: $typeReference,
				routeTo: $routeTo,
				requiresAcceptance: $requiresAcceptance
			);

			return new JSONResponse(data: $declaration, statusCode: Http::STATUS_OK);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end declareRouting()

	/**
	 * Refuse an anonymous caller.
	 *
	 * @return JSONResponse|null The refusal, or null when somebody is logged in.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function requireUser(): ?JSONResponse {
		if ($this->userSession->getUser() !== null) {
			return null;
		}

		return new JSONResponse(
			data: ['error' => 'You must be logged in to use the intake inbox.'],
			statusCode: Http::STATUS_UNAUTHORIZED
		);

	}//end requireUser()
}//end class
