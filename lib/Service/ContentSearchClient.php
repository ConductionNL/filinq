<?php

/**
 * Content Search Client
 *
 * The one filinq class that asks OpenRegister's scoped content query. filinq
 * reads no chunks, keeps no text and ranks nothing: it passes the words and
 * the file ids of a list, and gets back, per file the caller may read, the
 * rank and a snippet.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Asks OpenRegister which of a list's files hold the words, scoped to the caller.
 *
 * On an OpenRegister without the scoped query the answer says "not available"
 * instead of failing, so a list keeps its name search (REQ-WCS-003).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
 *
 * @psalm-type ContentHit = array{fileId: int, rank: float, snippet: string}
 * @psalm-type ContentAnswer = array{available: bool, hits: array<int, ContentHit>, unread: array<int, int>}
 */
class ContentSearchClient {

	/**
	 * The OpenRegister service that answers a content query scoped to one user.
	 */
	public const QUERY_SERVICE = 'OCA\\OpenRegister\\Service\\File\\ScopedContentSearchService';

	/**
	 * The method on that service: (string $words, int[] $fileIds, string $userId).
	 */
	public const QUERY_METHOD = 'searchFiles';

	/**
	 * Constructor.
	 *
	 * @param OpenRegisterAccess $openRegister Says whether OpenRegister is there and hands out its query service.
	 * @param IUserSession       $session      The caller the query is scoped to.
	 * @param LoggerInterface    $logger       Logs a query that failed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function __construct(
		private readonly OpenRegisterAccess $openRegister,
		private readonly IUserSession $session,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Which of the given files hold the words, for the caller.
	 *
	 * A hit for a file outside `$fileIds` is dropped: the list asked about its
	 * own files only, and nothing else may reach it (REQ-WCS-003). `unread`
	 * lists the given files OpenRegister holds no extracted text for
	 * (REQ-WCS-002).
	 *
	 * @param string          $words   The words to find.
	 * @param array<int, int> $fileIds The files of the list, the scope of the query.
	 *
	 * @return array The answer; `available` false when OpenRegister offers no scoped query.
	 *
	 * @psalm-return ContentAnswer
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function search(string $words, array $fileIds): array {
		$query = $this->query();
		$user  = $this->session->getUser();
		if ($query === null || $user === null) {
			return ['available' => false, 'hits' => [], 'unread' => []];
		}

		$scope = array_values(array_unique(array_map(intval(...), $fileIds)));
		$raw   = [];
		if (trim($words) !== '' && $scope !== []) {
			$raw = $this->ask(query: $query, words: $words, scope: $scope, userId: $user->getUID());
		}

		$inScope = array_flip($scope);
		return [
			'available' => true,
			'hits' => $this->hits(rows: $raw['results'] ?? $raw, inScope: $inScope),
			'unread' => $this->unread(fileIds: $raw['unextracted'] ?? [], inScope: $inScope),
		];

	}//end search()

	/**
	 * Whether this server offers the scoped content query.
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function isAvailable(): bool {
		return $this->query() !== null;

	}//end isAvailable()

	/**
	 * OpenRegister's query service, or null when this OpenRegister has none.
	 *
	 * @return object|null
	 */
	private function query(): ?object {
		if ($this->openRegister->apps->isEnabledForUser('openregister') === false) {
			return null;
		}

		// An OpenRegister without the service makes the container throw.
		try {
			$service = $this->openRegister->container->get(self::QUERY_SERVICE);
		} catch (Throwable) {
			return null;
		}

		if (is_object($service) === false || method_exists($service, self::QUERY_METHOD) === false) {
			return null;
		}

		return $service;

	}//end query()

	/**
	 * The query's raw answer; an empty answer when the query fails.
	 *
	 * @param object          $query  OpenRegister's query service.
	 * @param string          $words  The words.
	 * @param array<int, int> $scope  The files asked about.
	 * @param string          $userId The caller.
	 *
	 * @return array<mixed>
	 */
	private function ask(object $query, string $words, array $scope, string $userId): array {
		try {
			$raw = $query->{self::QUERY_METHOD}($words, $scope, $userId);
		} catch (Throwable $e) {
			$this->logger->warning('filinq: content search failed: ' . $e->getMessage(), ['exception' => $e]);
			return [];
		}

		if (is_array($raw) === false) {
			return [];
		}

		return $raw;

	}//end ask()

	/**
	 * The hits inside the scope, one per file, best rank first.
	 *
	 * A hit carries `fileId` (or `file_id`), `rank` and `snippet`.
	 *
	 * @param mixed           $rows    The query's hits.
	 * @param array<int, int> $inScope The files asked about, as keys.
	 *
	 * @return array<int, array{fileId: int, rank: float, snippet: string}>
	 */
	private function hits(mixed $rows, array $inScope): array {
		$hits = [];
		foreach ($this->rows(value: $rows) as $row) {
			$hit = [
				'fileId' => (int) ($row['fileId'] ?? $row['file_id'] ?? 0),
				'rank' => (float) ($row['rank'] ?? 0.0),
				'snippet' => (string) ($row['snippet'] ?? ''),
			];
			$known = $hits[$hit['fileId']]['rank'] ?? null;
			if (isset($inScope[$hit['fileId']]) === true && ($known === null || $known < $hit['rank'])) {
				$hits[$hit['fileId']] = $hit;
			}
		}

		usort($hits, static fn (array $left, array $right): int => $right['rank'] <=> $left['rank']);
		return $hits;

	}//end hits()

	/**
	 * The rows of a value that are arrays; none when it is not a list.
	 *
	 * @param mixed $value The value.
	 *
	 * @return array<int, array<mixed>>
	 */
	private function rows(mixed $value): array {
		if (is_array($value) === false) {
			return [];
		}

		return array_values(array_filter($value, is_array(...)));

	}//end rows()

	/**
	 * The unread files inside the scope.
	 *
	 * @param mixed           $fileIds The files the query says have no extracted text.
	 * @param array<int, int> $inScope The files asked about, as keys.
	 *
	 * @return array<int, int>
	 */
	private function unread(mixed $fileIds, array $inScope): array {
		if (is_array($fileIds) === false) {
			return [];
		}

		$unread = array_map(intval(...), $fileIds);
		return array_values(array_unique(array_filter($unread, static fn (int $fileId): bool => isset($inScope[$fileId]))));

	}//end unread()
}//end class
