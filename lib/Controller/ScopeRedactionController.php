<?php

/**
 * Scope redaction endpoints: a page, a range of pages or a whole document.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Service\Review\ScopeRedactionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Read, decide and commit scope decisions on a document the caller can open.
 *
 * Every endpoint first resolves the file in the caller's own folder and
 * answers 404 when it is not there, so no one decides on a file they cannot
 * open.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */
class ScopeRedactionController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param IRequest              $request     The request.
	 * @param IUserSession          $userSession The signed-in user.
	 * @param IRootFolder           $rootFolder  The file tree.
	 * @param ScopeRedactionService $scopes      Reads, validates and commits decisions.
	 * @param LoggerInterface       $logger      Logger for failures.
	 *
	 * @return void
	 */
	public function __construct(
		IRequest $request,
		private readonly IUserSession $userSession,
		private readonly IRootFolder $rootFolder,
		private readonly ScopeRedactionService $scopes,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: AppIdentity::APP_ID, request: $request);

	}//end __construct()

	/**
	 * The document's scope decisions, its page count and whether page scopes can be committed.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse {decisions, pageCount, pageScopesAvailable}, or 404.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
	 */
	#[NoAdminRequired]
	public function show(int $fileId): JSONResponse {
		// The per-object guard: the caller must be able to open the file in their own folder.
		$file = $this->reachableFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		return $this->answer(action: fn (): array => $this->scopes->state(file: $file));

	}//end show()

	/**
	 * Store one scope decision for the document.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse The stored decision (201), 400 when refused, or 404.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.1
	 */
	#[NoAdminRequired]
	public function decide(int $fileId): JSONResponse {
		// The per-object guard: the caller must be able to open the file in their own folder.
		$file = $this->reachableFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		return $this->answer(
			action: fn (): array => $this->scopes->decide(file: $file, input: $this->decisionInput(), userId: $this->userId()),
			status: Http::STATUS_CREATED
		);

	}//end decide()

	/**
	 * Commit a scope decision: burn its pages through the seam or withhold the document.
	 *
	 * @param int $fileId The document.
	 *
	 * @return JSONResponse The outcome, 400 when refused (nothing written), or 404.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	#[NoAdminRequired]
	public function commit(int $fileId): JSONResponse {
		// The per-object guard: the caller must be able to open the file in their own folder.
		$file = $this->reachableFile(fileId: $fileId);
		if ($file === null) {
			return $this->notFound();
		}

		return $this->answer(
			action: fn (): array => $this->scopes->commitOn(file: $file, input: $this->decisionInput(), userId: $this->userId())
		);

	}//end commit()

	/**
	 * The decision fields of the request body.
	 *
	 * @return array<string, mixed> scope, fromPage, toPage and bases.
	 */
	private function decisionInput(): array {
		return array_intersect_key($this->request->getParams(), array_flip(['scope', 'fromPage', 'toPage', 'bases']));

	}//end decisionInput()

	/**
	 * The file in the caller's own folder, or null.
	 *
	 * @param int $fileId The file.
	 *
	 * @return File|null The file.
	 */
	private function reachableFile(int $fileId): ?File {
		$userId = $this->userId();
		if ($userId === '') {
			return null;
		}

		try {
			$nodes = $this->rootFolder->getUserFolder($userId)->getById($fileId);
		} catch (Throwable) {
			return null;
		}

		$node = ($nodes[0] ?? null);
		if (($node instanceof File) === false) {
			return null;
		}

		return $node;

	}//end reachableFile()

	/**
	 * Run an action and answer with its result, a 400 refusal or a 500.
	 *
	 * @param callable $action The action.
	 * @param int      $status The status of a success.
	 *
	 * @return JSONResponse The response.
	 */
	private function answer(callable $action, int $status = Http::STATUS_OK): JSONResponse {
		try {
			return new JSONResponse(data: $action(), statusCode: $status);
		} catch (InvalidArgumentException $e) {
			// A refused decision (ScopeDecisionRefusedException): nothing was written.
			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			$this->logger->error(message: '[ScopeRedactionController] ' . $e->getMessage(), context: ['exception' => $e]);

			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()

	/**
	 * The 404 for a file the caller cannot open.
	 *
	 * @return JSONResponse The response.
	 */
	private function notFound(): JSONResponse {
		return new JSONResponse(data: ['error' => 'File not found'], statusCode: Http::STATUS_NOT_FOUND);

	}//end notFound()

	/**
	 * The signed-in user's id, '' when nobody is signed in.
	 *
	 * @return string The id.
	 */
	private function userId(): string {
		return (string) $this->userSession->getUser()?->getUID();

	}//end userId()
}//end class
