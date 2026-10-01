<?php

/**
 * Classification controller: the pending suggestions and a person's decision
 *
 * GET api/classification/pending lists the suggestions on files the caller
 * can reach; POST api/classification/{fileId}/confirm confirms (with
 * corrections in the body: documentType, correspondent, dossier) and POST
 * api/classification/{fileId}/reject rejects. Every decision resolves the
 * file in the caller's own folder first (ClassificationDecisionService), so
 * nobody decides on a document they cannot open.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Service\Classification\ClassificationDecisionService;
use OCA\Filinq\Service\Classification\ClassificationRefused;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Pending classification suggestions and their confirmation.
 *
 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
 */
class ClassificationController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                        $appName     The app id.
	 * @param IRequest                      $request     The request.
	 * @param ClassificationDecisionService $decisions   Lists and decides suggestions.
	 * @param IUserSession                  $userSession The session.
	 * @param LoggerInterface               $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ClassificationDecisionService $decisions,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The suggestions waiting for a person, on files the caller can reach.
	 *
	 * @return JSONResponse {results}.
	 *
	 * @no-admin-idor-exempt names no object; every row is filtered on the caller's own access to its file.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
	 */
	#[NoAdminRequired]
	public function pending(): JSONResponse {
		return $this->answer(action: fn (): array => ['results' => $this->decisions->pending(userId: $this->userId())]);

	}//end pending()

	/**
	 * The classification of one file, for the document card.
	 *
	 * @param int $fileId The file.
	 *
	 * @return JSONResponse The record, or 404 for a file the caller cannot open or without a record.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#3-2
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		// The per-object guard: the decision service resolves the file in the caller's own folder first.
		return $this->answer(
			action: function () use ($fileId): array {
				$record = $this->decisions->forFile(fileId: $fileId, userId: $this->userId());
				if ($record === null) {
					throw new ClassificationRefused(message: 'No suggestion for this file', code: Http::STATUS_NOT_FOUND);
				}

				return $record;
			}
		);

	}//end show()

	/**
	 * Confirm a suggestion, with corrections if the body carries any.
	 *
	 * @param int $fileId The file.
	 *
	 * @return JSONResponse The confirmed record, or 404/409/422.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
	 */
	#[NoAdminRequired]
	public function confirm(int $fileId): JSONResponse {
		$body = $this->request->getParams();
		$choices = array_intersect_key($body, array_flip(['documentType', 'correspondent', 'dossier']));

		// The per-object guard: the decision service resolves the file in the caller's own folder first.
		return $this->answer(action: fn (): array => $this->decisions->confirm(fileId: $fileId, userId: $this->userId(), choices: $choices));

	}//end confirm()

	/**
	 * Reject a suggestion.
	 *
	 * @param int $fileId The file.
	 *
	 * @return JSONResponse The rejected record, or 404/409.
	 *
	 * @spec openspec/changes/archive/2026-10-02-inbound-auto-classification/tasks.md#2-4
	 */
	#[NoAdminRequired]
	public function reject(int $fileId): JSONResponse {
		// The per-object guard: the decision service resolves the file in the caller's own folder first.
		return $this->answer(action: fn (): array => $this->decisions->reject(fileId: $fileId, userId: $this->userId()));

	}//end reject()

	/**
	 * The caller's user id.
	 *
	 * @return string The uid, '' without a session.
	 */
	private function userId(): string {
		return (string) $this->userSession->getUser()?->getUID();

	}//end userId()

	/**
	 * Run an action; a refusal keeps its status, anything else is a 500 with a generic body.
	 *
	 * @param callable $action The action.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action): JSONResponse {
		try {
			return new JSONResponse(data: $action());
		} catch (ClassificationRefused $e) {
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $e->getCode());
		} catch (Throwable $e) {
			$this->logger->error('[ClassificationController] request failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()
}//end class
