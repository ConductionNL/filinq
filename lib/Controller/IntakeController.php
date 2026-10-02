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

use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\IntakeDetachmentService;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\Filinq\Service\IntakeService;
use OCA\Filinq\Service\PartySuggestionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
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

	/**
	 * Constructor.
	 *
	 * @param string $appName The app name.
	 * @param IRequest $request The request.
	 * @param IntakeService $intake The intake inbox.
	 * @param IntakeDetachmentService $detachment Returns a filed document to the worklist.
	 * @param IntakeRoutingService $routing What a consuming app declared per record type.
	 * @param PartySuggestionService $parties Party suggestions and the corrections corpus.
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IntakeService $intake,
		private readonly IntakeDetachmentService $detachment,
		private readonly IntakeRoutingService $routing,
		private readonly PartySuggestionService $parties,
		private readonly IUserSession $userSession,
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
				data: ['results' => $waiting, 'total' => count($waiting)],
				statusCode: Http::STATUS_OK
			);
		} catch (Throwable $e) {
			return $this->failure(error: $e);
		}

	}//end index()

	/**
	 * Assign one waiting document to a record.
	 *
	 * @param string $uuid The intake document.
	 * @param string $register The target register slug.
	 * @param string $schema The target schema slug.
	 * @param string $id The target object id.
	 * @param string $declaringApp App id of the app that owns the record type.
	 * @param string $typeReference That app's reference for the record type.
	 * @param bool $withAttachments Whether everything that arrived with the message goes along.
	 *
	 * @return JSONResponse The assigned document, or the refusal.
	 *
	 * @SuppressWarnings(PHPMD.BooleanArgumentFlag) `$withAttachments` is a field
	 * of the request body, not a mode this code chose. Nextcloud binds a
	 * controller parameter to the JSON key of the same name, so the SRP split
	 * phpmd asks for is a split into two ROUTES for one thing a clerk does:
	 * "file this letter, and the bijlagen that came with it". The service
	 * behind it carries no flag, see IntakeService::assignWithAttachments().
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	#[NoAdminRequired]
	public function assign(
		string $uuid,
		string $register = '',
		string $schema = '',
		string $id = '',
		string $declaringApp = '',
		string $typeReference = '',
		bool $withAttachments = false,
	): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		$target = [
			'register' => $register,
			'schema' => $schema,
			'id' => $id,
			'declaringApp' => $declaringApp,
			'typeReference' => $typeReference,
		];

		try {
			if ($withAttachments === true) {
				return new JSONResponse(
					data: $this->intake->assignWithAttachments(uuid: $uuid, target: $target),
					statusCode: Http::STATUS_OK
				);
			}

			return new JSONResponse(
				data: $this->intake->assign(uuid: $uuid, target: $target),
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
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

	/**
	 * Answer one failure with the status the service gave it.
	 *
	 * @param Throwable $error The failure.
	 *
	 * @return JSONResponse The refusal.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	private function failure(Throwable $error): JSONResponse {
		$status = Http::STATUS_INTERNAL_SERVER_ERROR;
		if ($error instanceof IntakeRefusedException) {
			$status = $error->getStatus();
		}

		return new JSONResponse(data: ['error' => $error->getMessage()], statusCode: $status);

	}//end failure()
}//end class
