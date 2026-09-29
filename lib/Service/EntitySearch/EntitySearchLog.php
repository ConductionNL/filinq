<?php

/**
 * The append-only processing log of the entity search (AVG article 30).
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
 * @spec openspec/changes/entity-search/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use OCP\AppFramework\Utility\ITimeFactory;
use Throwable;

/**
 * Writes one `entitySearchLog` row per search or detail view, before the
 * answer goes out. The row holds the sha256 digest of the query, never the
 * query: a logged BSN would itself be a new copy of personal data.
 *
 * Only this class writes the schema, and it only creates. The schema grants
 * no update or delete, and the write passes `_rbac: false` because the
 * searcher need not be allowed to read the log they add to.
 */
class EntitySearchLog {

	/**
	 * The log schema.
	 *
	 * @var string
	 */
	public const SCHEMA = 'entitySearchLog';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param ITimeFactory                  $time           The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * The digest the log keeps of a query.
	 *
	 * @param string $query The query as typed.
	 *
	 * @return string The sha256 of the lower-cased, trimmed query, '' for an empty one.
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.4
	 */
	public static function digest(string $query): string {
		$normal = mb_strtolower(trim($query));
		if ($normal === '') {
			return '';
		}

		return hash('sha256', $normal);

	}//end digest()

	/**
	 * Log a search.
	 *
	 * @param string $userId   The searcher.
	 * @param string $query    The query, of which only the digest is kept.
	 * @param string $type     The type filter.
	 * @param string $category The category filter.
	 * @param int    $results  How many entities matched.
	 *
	 * @return array<string, mixed> The row as written.
	 *
	 * @throws EntitySearchRefusedException When the row could not be written.
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.4
	 */
	public function search(string $userId, string $query, string $type, string $category, int $results): array {
		return $this->write(
			row: [
				'action' => 'search',
				'queryDigest' => self::digest(query: $query),
				'typeFilter' => mb_substr($type, 0, 64),
				'categoryFilter' => mb_substr($category, 0, 64),
				'resultCount' => $results,
				'performedBy' => $userId,
			]
		);

	}//end search()

	/**
	 * Log a detail view.
	 *
	 * @param string $userId      The searcher.
	 * @param string $entityUuid  The catalogue entity opened.
	 * @param int    $occurrences How many occurrences the view showed.
	 *
	 * @return array<string, mixed> The row as written.
	 *
	 * @throws EntitySearchRefusedException When the row could not be written.
	 *
	 * @spec openspec/changes/entity-search/tasks.md#task-2.4
	 */
	public function detail(string $userId, string $entityUuid, int $occurrences): array {
		return $this->write(
			row: [
				'action' => 'detail',
				'entityRef' => mb_substr($entityUuid, 0, 64),
				'occurrenceCount' => $occurrences,
				'performedBy' => $userId,
			]
		);

	}//end detail()

	/**
	 * Create the row, or refuse.
	 *
	 * @param array<string, mixed> $row The fields.
	 *
	 * @return array<string, mixed> The row as written.
	 *
	 * @throws EntitySearchRefusedException When OpenRegister refused it.
	 */
	private function write(array $row): array {
		$row['performedBy'] = mb_substr((string) $row['performedBy'], 0, 255);
		$row['performedAt'] = $this->time->getDateTime()->format(format: DATE_ATOM);
		try {
			$stored = $this->objectResolver->resolve()->saveObject(
				object: $row,
				register: IntakeRepository::REGISTER,
				schema: self::SCHEMA,
				_rbac: false
			);
		} catch (Throwable $e) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_LOG_UNAVAILABLE,
				message: 'The entity search log did not take the entry: ' . $e->getMessage()
			);
		}

		if ($stored === null) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_LOG_UNAVAILABLE,
				message: 'The entity search log returned nothing for the entry.'
			);
		}

		return $row;

	}//end write()
}//end class
