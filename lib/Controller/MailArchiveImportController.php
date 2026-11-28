<?php

/**
 * Mail archive import controller
 *
 * The api/mail-archive-imports routes start an import from a file the user can read,
 * list and read their own imports, map the folders to dossiers they may
 * write to and start importing, and cancel. An import is answered only to
 * the user who started it (design D6). The admin settings carry the per-run
 * limit and whether readpst is installed.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use InvalidArgumentException;
use OCA\Filinq\Service\MailArchive\MailArchiveImportService;
use OCA\Filinq\Service\MailArchive\MailArchiveSettings;
use OCA\Filinq\Settings\FilinqAdmin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Start, read, map and cancel mail archive imports.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-an-archivist-starts-an-import-from-a-file-in-nextcloud-req-mai-001
 */
class MailArchiveImportController extends Controller {

	/**
	 * Constructor.
	 *
	 * @param string                   $appName     The app id.
	 * @param IRequest                 $request     The request.
	 * @param MailArchiveImportService $imports     The imports.
	 * @param MailArchiveSettings      $settings    The per-run limit and readpst status.
	 * @param IUserSession             $userSession The session.
	 * @param LoggerInterface          $logger      The logger.
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly MailArchiveImportService $imports,
		private readonly MailArchiveSettings $settings,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * The user's imports, newest first.
	 *
	 * @return JSONResponse {results}.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[NoAdminRequired]
	public function index(): JSONResponse {
		return $this->answer(action: fn (string $userId): array => ['results' => $this->imports->mine(userId: $userId)]);

	}//end index()

	/**
	 * Start an import of a file the user can read; the answer lists its folders.
	 *
	 * @param int $fileId The archive's file id.
	 *
	 * @return JSONResponse The import (201), or 400 / 404.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[NoAdminRequired]
	public function create(int $fileId = 0): JSONResponse {
		if ($fileId <= 0) {
			return new JSONResponse(data: ['error' => 'file_required'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		return $this->answer(
			action: fn (string $userId): array => $this->imports->start(userId: $userId, fileId: $fileId),
			status: Http::STATUS_CREATED
		);

	}//end create()

	/**
	 * One of the user's imports, with its progress and failures.
	 *
	 * @param string $uuid The import.
	 *
	 * @return JSONResponse The import, or 404.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[NoAdminRequired]
	public function show(string $uuid): JSONResponse {
		return $this->answer(action: fn (string $userId): array => $this->imports->get(userId: $userId, uuid: $uuid));

	}//end show()

	/**
	 * Map the folders to dossiers and start importing.
	 *
	 * @param string            $uuid      The import.
	 * @param array<int, mixed> $folderMap Rows of {archiveFolder, dossier}.
	 *
	 * @return JSONResponse The import, or 400 / 403 / 404 / 409.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[NoAdminRequired]
	public function start(string $uuid, array $folderMap = []): JSONResponse {
		return $this->answer(action: fn (string $userId): array => $this->imports->map(userId: $userId, uuid: $uuid, folderMap: $folderMap));

	}//end start()

	/**
	 * Cancel an import that has not ended.
	 *
	 * @param string $uuid The import.
	 *
	 * @return JSONResponse The import, or 404 / 409.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[NoAdminRequired]
	public function cancel(string $uuid): JSONResponse {
		return $this->answer(action: fn (string $userId): array => $this->imports->cancel(userId: $userId, uuid: $uuid));

	}//end cancel()

	/**
	 * The per-run limit and whether readpst is installed.
	 *
	 * @return JSONResponse {messagesPerRun, readpstInstalled}.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function settings(): JSONResponse {
		return new JSONResponse(data: $this->settings->toArray());

	}//end settings()

	/**
	 * Store the per-run limit.
	 *
	 * @param int $messagesPerRun The limit; clamped to 1..2000.
	 *
	 * @return JSONResponse The stored settings.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-4
	 */
	#[AuthorizedAdminSetting(settings: FilinqAdmin::class)]
	public function updateSettings(int $messagesPerRun = MailArchiveSettings::DEFAULT_MESSAGES_PER_RUN): JSONResponse {
		return new JSONResponse(data: $this->settings->update(messagesPerRun: $messagesPerRun));

	}//end updateSettings()

	/**
	 * Run an action as the session user; a refusal keeps its status, anything else is a 500 with a generic body.
	 *
	 * @param callable $action The action, given the user id.
	 * @param int      $status The status of a success.
	 *
	 * @return JSONResponse The answer.
	 */
	private function answer(callable $action, int $status = Http::STATUS_OK): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(data: ['error' => 'not_authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		try {
			return new JSONResponse(data: $action($user->getUID()), statusCode: $status);
		} catch (InvalidArgumentException $e) {
			$code = $e->getCode();
			if (in_array($code, [400, 403, 404, 409], true) === false) {
				$code = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: $code);
		} catch (Throwable $e) {
			$this->logger->error('[MailArchiveImportController] request failed', ['exception' => $e->getMessage()]);

			return new JSONResponse(data: ['error' => 'failed'], statusCode: Http::STATUS_INTERNAL_SERVER_ERROR);
		}

	}//end answer()
}//end class
