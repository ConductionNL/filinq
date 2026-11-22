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

use OCP\App\IAppManager;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
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
	 * @param IAppManager        $apps      Says whether OpenRegister is installed.
	 * @param ContainerInterface $container Hands out OpenRegister's query service.
	 * @param IUserSession       $session   The caller the query is scoped to.
	 * @param LoggerInterface    $logger    Logs a query that failed.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function __construct(
		private readonly IAppManager $apps,
		private readonly ContainerInterface $container,
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
	 * @return array{available: bool, hits: array<int, array{fileId: int, rank: float, snippet: string}>, unread: array<int, int>} The answer; `available` false when OpenRegister offers no scoped query.
	 *
	 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md
	 */
	public function search(string $words, array $fileIds): array {
		$answer = ['available' => false, 'hits' => [], 'unread' => []];
		$query  = $this->query();
		$user   = $this->session->getUser();
		if ($query === null || $user === null) {
			return $answer;
		}

		$answer['available'] = true;
		$scope = array_values(array_unique(array_map(intval(...), $fileIds)));
		if (trim($words) === '' || $scope === []) {
			return $answer;
		}

		try {
			$raw = $query->{self::QUERY_METHOD}($words, $scope, $user->getUID());
		} catch (Throwable $e) {
			$this->logger->warning(
				'filinq: content search failed: ' . $e->getMessage(),
				['exception' => $e]
			);
			return $answer;
		}

		return $this->normalise(raw: is_array($raw) === true ? $raw : [], scope: $scope, answer: $answer);

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
		if ($this->apps->isEnabledForUser('openregister') === false) {
			return null;
		}

		// An OpenRegister without the service makes the container throw.
		try {
			$service = $this->container->get(self::QUERY_SERVICE);
		} catch (Throwable) {
			return null;
		}

		if (is_object($service) === false || method_exists($service, self::QUERY_METHOD) === false) {
			return null;
		}

		return $service;

	}//end query()

	/**
	 * The query's answer as hits and unread files, within the scope.
	 *
	 * The answer is either a list of hits or `{results, unextracted}`; a hit
	 * carries `fileId` (or `file_id`), `rank` and `snippet`. Hits are ordered
	 * by rank, highest first, one per file.
	 *
	 * @param array<mixed>                                                                                            $raw    The query's answer.
	 * @param array<int, int>                                                                                         $scope  The files asked about.
	 * @param array{available: bool, hits: array<int, array{fileId: int, rank: float, snippet: string}>, unread: array<int, int>} $answer The answer so far.
	 *
	 * @return array{available: bool, hits: array<int, array{fileId: int, rank: float, snippet: string}>, unread: array<int, int>}
	 */
	private function normalise(array $raw, array $scope, array $answer): array {
		$inScope = array_flip($scope);
		$rows    = $raw['results'] ?? $raw;
		$hits    = [];
		foreach (is_array($rows) === true ? $rows : [] as $row) {
			if (is_array($row) === false) {
				continue;
			}

			$fileId = (int) ($row['fileId'] ?? $row['file_id'] ?? 0);
			if (isset($inScope[$fileId]) === false) {
				continue;
			}

			$rank = (float) ($row['rank'] ?? 0.0);
			if (isset($hits[$fileId]) === true && $hits[$fileId]['rank'] >= $rank) {
				continue;
			}

			$hits[$fileId] = ['fileId' => $fileId, 'rank' => $rank, 'snippet' => (string) ($row['snippet'] ?? '')];
		}

		usort($hits, static fn (array $left, array $right): int => $right['rank'] <=> $left['rank']);
		$answer['hits'] = $hits;

		$unread = $raw['unextracted'] ?? [];
		foreach (is_array($unread) === true ? $unread : [] as $fileId) {
			if (isset($inScope[(int) $fileId]) === true) {
				$answer['unread'][] = (int) $fileId;
			}
		}

		$answer['unread'] = array_values(array_unique($answer['unread']));
		return $answer;

	}//end normalise()
}//end class
