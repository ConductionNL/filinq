<?php

/**
 * Waiting Work Controller
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Waiting for me list on My documents: what waits for the person at the
 * keyboard, and why.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\ReadsCurrentUserId;
use OCA\Filinq\Kernel\Contracts\WaitingWorkServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Serves `GET api/documents/waiting` (REQ-SCR-006).
 *
 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
 */
class WaitingWorkController extends Controller {
	use ReadsCurrentUserId;

	/**
	 * Constructor.
	 *
	 * @param string             $appName     App name.
	 * @param IRequest           $request     Request object.
	 * @param WaitingWorkServiceInterface $waitingWork What waits for a person.
	 * @param IUserSession       $userSession The person at the keyboard.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly WaitingWorkServiceInterface $waitingWork,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * What waits for the caller: signatures and acceptances, routed intake
	 * documents, and their documents due for review.
	 *
	 * Every item is the caller's own: the signing folder is theirs, an intake
	 * document is listed only for a member of the group it was routed to,
	 * and a review only for the document's owner.
	 *
	 * @return JSONResponse `{results, total}`, or 401 without a session.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/tasks.md#2-documents
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		$userId = $this->currentUserId();
		if ($userId === '') {
			return new JSONResponse(data: ['error' => 'Not authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		$items = $this->waitingWork->listFor(userId: $userId);

		return new JSONResponse(data: ['results' => $items, 'total' => count($items)], statusCode: Http::STATUS_OK);

	}//end index()
}//end class
