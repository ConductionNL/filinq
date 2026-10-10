<?php

/**
 * Search OpenRegister's detected entities and see where one occurs.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EntitySearch
 *
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;

/**
 * The order is the control: the gate first, then the tenant scope, then the
 * read, then the log, and only then the answer. A lookup the log did not take
 * is refused, so nobody can look up a person without it being accounted for.
 */
class EntitySearchService {

	/**
	 * The largest page.
	 *
	 * @var int
	 */
	public const MAX_LIMIT = 100;

	/**
	 * The page size when none is asked for.
	 *
	 * @var int
	 */
	public const DEFAULT_LIMIT = 25;

	/**
	 * Constructor.
	 *
	 * @param EntitySearchGate  $gate        Who may search.
	 * @param EntityCatalogue   $catalogue   OpenRegister's entity catalogue.
	 * @param EntityOccurrences $occurrences Where an entity occurs.
	 * @param EntitySearchLog   $log         The processing log.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly EntitySearchGate $gate,
		private readonly EntityCatalogue $catalogue,
		private readonly EntityOccurrences $occurrences,
		private readonly EntitySearchLog $log,
	) {

	}//end __construct()

	/**
	 * Whether the caller may use the entity search at all. Reads nothing and logs nothing.
	 *
	 * @param string $userId The caller.
	 *
	 * @return array{allowed: true} The answer; a refusal is thrown.
	 *
	 * @throws EntitySearchRefusedException When the caller may not.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-3.1
	 */
	public function access(string $userId): array {
		$this->gate->assertMaySearch(userId: $userId);

		return ['allowed' => true];

	}//end access()

	/**
	 * Entities matching the query, logged before they are returned.
	 *
	 * @param string $userId   The caller.
	 * @param string $query    Case-insensitive substring of the value.
	 * @param string $type     Exact type, '' for any.
	 * @param string $category Exact category, '' for any.
	 * @param int    $limit    Page size.
	 * @param int    $offset   Page start.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int, limit: int, offset: int} The page.
	 *
	 * @throws EntitySearchRefusedException When refused, or when the log did not take the entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	public function search(string $userId, string $query, string $type, string $category, int $limit, int $offset): array {
		$this->gate->assertMaySearch(userId: $userId);
		$query = trim($query);
		$type = trim($type);
		$category = trim($category);
		if ($query === '' && $type === '' && $category === '') {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_INVALID,
				message: 'A search needs a value, a type or a category.'
			);
		}

		$limit = max(1, min(self::MAX_LIMIT, $limit));
		$offset = max(0, $offset);
		$scope = $this->catalogue->scope(isAdmin: $this->gate->isAdmin(userId: $userId));
		$page = $this->catalogue->search(scope: $scope, query: $query, type: $type, category: $category, limit: $limit, offset: $offset);
		$this->log->search(userId: $userId, query: $query, type: $type, category: $category, results: $page['total']);

		return ['results' => $page['results'], 'total' => $page['total'], 'limit' => $limit, 'offset' => $offset];

	}//end search()

	/**
	 * One entity and where it occurs, logged before it is returned.
	 *
	 * @param string $userId The caller.
	 * @param string $uuid   The entity uuid.
	 *
	 * @return array<string, mixed> The entity and its occurrences.
	 *
	 * @throws EntitySearchRefusedException When refused, not visible, or not logged.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
	 */
	public function detail(string $userId, string $uuid): array {
		$this->gate->assertMaySearch(userId: $userId);
		$scope = $this->catalogue->scope(isAdmin: $this->gate->isAdmin(userId: $userId));
		$found = $this->catalogue->find(scope: $scope, uuid: $uuid);
		if ($found === null) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_NOT_FOUND,
				message: 'No entity with that uuid is visible to the caller.'
			);
		}

		$occurrences = $this->occurrences->forUser(relations: $found['relations'], userId: $userId);
		$this->log->detail(userId: $userId, entityUuid: $uuid, occurrences: $occurrences['occurrenceCount']);

		return array_merge($found['entity'], $occurrences);

	}//end detail()
}//end class
