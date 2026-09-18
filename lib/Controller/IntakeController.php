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
use OCA\Filinq\Service\IntakeService;
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
	 * @param IUserSession $userSession The current session.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly IntakeService $intake,
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
	 *
	 * @return JSONResponse The assigned document, or the refusal.
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	#[NoAdminRequired]
	public function assign(string $uuid, string $register = '', string $schema = '', string $id = ''): JSONResponse {
		$unauthenticated = $this->requireUser();
		if ($unauthenticated !== null) {
			return $unauthenticated;
		}

		try {
			$document = $this->intake->assign(
				uuid: $uuid,
				target: ['register' => $register, 'schema' => $schema, 'id' => $id]
			);

			return new JSONResponse(data: $document, statusCode: Http::STATUS_OK);
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
