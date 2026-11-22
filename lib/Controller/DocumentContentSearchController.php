<?php

/**
 * Document Content Search Controller
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Finds which of the caller's listed documents hold some words, for a list
 * that is not a record's (My documents).
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

use OCA\Filinq\Kernel\Contracts\ReadableFileResolverInterface;
use OCA\Filinq\Service\ContentSearchClient;
use OCA\Filinq\Service\ReadsCurrentUserId;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Serves `POST api/documents/content-search` (REQ-WCS-001, REQ-WCS-002, REQ-WCS-003).
 *
 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
 */
class DocumentContentSearchController extends Controller {
	use ReadsCurrentUserId;

	/**
	 * The most files one search may ask about.
	 *
	 * @var int
	 */
	public const MAX_FILES = 500;

	/**
	 * Constructor.
	 *
	 * @param string                        $appName     App name.
	 * @param IRequest                      $request     Request object.
	 * @param ContentSearchClient           $content     OpenRegister's scoped content query.
	 * @param ReadableFileResolverInterface $readable    Says which files the caller may read.
	 * @param IUserSession                  $userSession The person at the keyboard.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ContentSearchClient $content,
		private readonly ReadableFileResolverInterface $readable,
		private readonly IUserSession $userSession,
	) {
		parent::__construct(appName: $appName, request: $request);

	}//end __construct()

	/**
	 * Which of the given files hold the words.
	 *
	 * Only files the caller may read are asked about; any other id is left
	 * out before OpenRegister is asked, so a hit or an unread entry never
	 * names a file the caller cannot open.
	 *
	 * @param string           $words   The words to find.
	 * @param array<int, mixed> $fileIds The files of the caller's list.
	 *
	 * @return JSONResponse `{available, hits: [{fileId, rank, snippet}], unread: [fileId]}`;
	 *                      400 without words or with too many files; 401 without a session.
	 *
	 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
	 */
	#[NoAdminRequired]
	public function search(string $words = '', array $fileIds = []): JSONResponse {
		if ($this->currentUserId() === '') {
			return new JSONResponse(data: ['error' => 'Not authenticated'], statusCode: Http::STATUS_UNAUTHORIZED);
		}

		if (trim($words) === '') {
			return new JSONResponse(data: ['error' => 'Give the words to find'], statusCode: Http::STATUS_BAD_REQUEST);
		}

		if (count($fileIds) > self::MAX_FILES) {
			return new JSONResponse(
				data: ['error' => 'Ask about at most '.self::MAX_FILES.' files at once'],
				statusCode: Http::STATUS_BAD_REQUEST
			);
		}

		$answer = $this->content->search(words: trim($words), fileIds: $this->readableIds(fileIds: $fileIds));

		return new JSONResponse(data: $answer, statusCode: Http::STATUS_OK);

	}//end search()

	/**
	 * The given ids the caller may read, each once.
	 *
	 * @param array<int, mixed> $fileIds The ids as sent.
	 *
	 * @return array<int, int> The readable ids.
	 *
	 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
	 */
	private function readableIds(array $fileIds): array {
		$readable = [];
		foreach ($fileIds as $fileId) {
			if (is_numeric($fileId) === false) {
				continue;
			}

			$fileId = (int) $fileId;
			if ($fileId > 0 && isset($readable[$fileId]) === false && $this->readable->node(fileId: $fileId) !== null) {
				$readable[$fileId] = $fileId;
			}
		}

		return array_values($readable);

	}//end readableIds()
}//end class
