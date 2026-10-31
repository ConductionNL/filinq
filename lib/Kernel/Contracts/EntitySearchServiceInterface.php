<?php

/**
 * Contract of EntitySearchService
 *
 * The public surface of the EntitySearchService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Kernel\EntitySearchQuery;

/**
 * Contract of EntitySearchService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface EntitySearchServiceInterface {
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
	public function access(string $userId): array;

	/**
	 * Entities matching the query, logged before they are returned.
	 *
	 * @param string            $userId The caller.
	 * @param EntitySearchQuery $query  The value text, type and category filters, and page.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int, limit: int, offset: int} The page.
	 *
	 * @throws EntitySearchRefusedException When refused, or when the log did not take the entry.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	public function search(string $userId, EntitySearchQuery $query): array;

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
	public function detail(string $userId, string $uuid): array;
}//end interface
