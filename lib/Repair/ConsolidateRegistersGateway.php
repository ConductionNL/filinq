<?php

/**
 * The only place ConsolidateRegisters touches the database.
 *
 * WHY THE SQL LIVES IN ITS OWN CLASS. This is the riskiest step in the app: it
 * DELETEs rows, and it interpolates table names into statements because a table
 * name cannot be a bound parameter. Reviewing that safely means reading every
 * statement the migration can issue — and the value of a file you can read
 * end-to-end for exactly that is the reason these nine methods are not mixed in
 * with the orchestration that calls them. Every statement this migration is
 * capable of executing is in this file, and there are no others.
 *
 * TWO RULES HOLD THROUGHOUT.
 *
 *  - EVERY VALUE IS BOUND. The new register id and every uuid ride as `?`
 *    parameters. The ONLY thing interpolated is a table name, and only after
 *    ConsolidateRegisters has shape-checked it against its own (register,
 *    schema) pair and confirmed it came out of an `information_schema` result.
 *  - EVERY READ RETURNS NULL ON FAILURE. Not an empty array. A query that could
 *    not run and a query that found nothing must not look alike, because "found
 *    nothing" is what authorises the caller to conclude there is nothing to
 *    move — and concluding that over an unreadable table is exactly how a
 *    migration reports success having moved nothing.
 *
 * Nothing here decides anything. It reads, it writes, and it reports failure
 * honestly; the decisions live in ConsolidateRegistersDecisions and the
 * sequencing in ConsolidateRegisters.
 *
 * @category  Repair
 * @package   OCA\Filinq\Repair
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://conduction.nl
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Reads and writes the OpenRegister shard tables on behalf of the repair step.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq's five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */
class ConsolidateRegistersGateway {

	/**
	 * Constructor.
	 *
	 * @param IDBConnection $db Database connection.
	 * @param LoggerInterface $logger Logger.
	 * @param ConsolidateRegistersDecisions $decisions The pure predicates.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function __construct(
		private readonly IDBConnection $db,
		private readonly LoggerInterface $logger,
		private readonly ConsolidateRegistersDecisions $decisions = new ConsolidateRegistersDecisions(),
	) {
	}//end __construct()

	/**
	 * Copy one batch across, rewriting `_register` on the way.
	 *
	 * The table names are interpolated because a table name cannot be bound;
	 * both have passed isSafeTableName() for their own (register, schema) pair
	 * and both came out of an `information_schema` result. Every VALUE — the
	 * new register id and every uuid — is bound.
	 *
	 * @param string $sourceTable Source table name.
	 * @param string $targetTable Target table name.
	 * @param int $targetId The new register id.
	 * @param array<int, string> $columns Columns to carry.
	 * @param array<int, string> $batch The uuids in this batch.
	 *
	 * @return bool True when the statement executed.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function insertBatch(
		string $sourceTable,
		string $targetTable,
		int $targetId,
		array $columns,
		array $batch,
	): bool {
		$select = $this->decisions->selectExpressions(columns: $columns);
		$sql = 'INSERT INTO `' . $targetTable . '` (' . implode(', ', $columns) . ') '
			. 'SELECT ' . implode(', ', $select) . ' FROM `' . $sourceTable . '` '
			. 'WHERE _uuid IN (' . $this->decisions->placeholders(count: count($batch)) . ')';

		$params = [];
		foreach ($columns as $column) {
			if ($column === '_register') {
				$params[] = (string)$targetId;
			}
		}

		$params = array_merge($params, array_values($batch));

		try {
			$this->db->executeStatement($sql, $params);
		} catch (Throwable $e) {
			$this->logger->warning(
				'ConsolidateRegisters: could not copy a batch into the target table.',
				['targetTable' => $targetTable, 'exception' => $e->getMessage()]
			);
			return false;
		}

		return true;
	}//end insertBatch()

	/**
	 * Remove one batch from the source table.
	 *
	 * Scoped to the exact uuids that were just confirmed present in the target.
	 * Never `DELETE FROM source WHERE _uuid IN (SELECT ... FROM target)`: that
	 * form would also delete the CONFLICTING rows, which are precisely the ones
	 * this step promised to leave alone.
	 *
	 * @param string $table The source table.
	 * @param array<int, string> $uuids The uuids to remove.
	 *
	 * @return bool True when the statement executed.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function deleteBatch(string $table, array $uuids): bool {
		$sql = 'DELETE FROM `' . $table . '` '
			. 'WHERE _uuid IN (' . $this->decisions->placeholders(count: count($uuids)) . ')';

		try {
			$this->db->executeStatement($sql, array_values($uuids));
		} catch (Throwable $e) {
			$this->logger->warning(
				'ConsolidateRegisters: could not delete the migrated rows from the source table.',
				['table' => $table, 'exception' => $e->getMessage()]
			);
			return false;
		}

		return true;
	}//end deleteBatch()

	/**
	 * Run one read, or report that nothing is known.
	 *
	 * EVERY READ IN THIS STEP GOES THROUGH HERE, and that is the point. The
	 * `null` return is the whole of property 4: a query that failed and a query
	 * that returned no rows must not look alike, because "no rows" authorises
	 * the migration to conclude there is nothing to move — and concluding that
	 * over an unreadable table is exactly how a migration reports success having
	 * moved nothing. One place to get that right beats five places to get it
	 * wrong.
	 *
	 * @param string $sql The query.
	 * @param array<int, mixed> $params Bound parameters.
	 * @param string $failure The warning to log when it fails.
	 * @param array<string, mixed> $context Extra log context.
	 *
	 * @return array<int, array<string, mixed>>|null Rows, or null when unreadable.
	 */
	private function fetchRows(string $sql, array $params, string $failure, array $context = []): ?array {
		try {
			return $this->db->executeQuery($sql, $params)->fetchAll();
		} catch (Throwable $e) {
			$this->logger->warning($failure, array_merge($context, ['exception' => $e->getMessage()]));
			return null;
		}
	}//end fetchRows()

	/**
	 * Read one column out of one query, preserving the failure/empty distinction.
	 *
	 * @param string $sql The query.
	 * @param array<int, mixed> $params Bound parameters.
	 * @param string $column The column to pull.
	 * @param string $failure The warning to log when it fails.
	 * @param array<string, mixed> $context Extra log context.
	 *
	 * @return array<int, string>|null The values, or null when unreadable.
	 */
	private function readColumn(
		string $sql,
		array $params,
		string $column,
		string $failure,
		array $context = [],
	): ?array {
		$rows = $this->fetchRows(sql: $sql, params: $params, failure: $failure, context: $context);
		if ($rows === null) {
			return null;
		}

		return $this->decisions->column(rows: $rows, column: $column);
	}//end readColumn()

	/**
	 * The register ids this step cares about, keyed by slug.
	 *
	 * Returns null on a read failure. An empty map says "this install has none
	 * of these registers", which is a completed migration or a fresh install;
	 * null says "the register table could not be read", which is neither.
	 *
	 * @return array<string, int>|null
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function registerIds(): ?array {
		$slugs = array_merge(ConsolidateRegisters::SOURCE_SLUGS, [ConsolidateRegisters::TARGET_SLUG]);

		$rows = $this->fetchRows(
			sql: 'SELECT id, slug FROM `*PREFIX*openregister_registers` '
				. 'WHERE slug IN (' . $this->decisions->placeholders(count: count($slugs)) . ')',
			params: $slugs,
			failure: 'ConsolidateRegisters: could not read the register table; stopping.'
		);

		if ($rows === null) {
			return null;
		}

		$ids = [];
		foreach ($rows as $row) {
			$slug = (string)($row['slug'] ?? '');
			$id = (int)($row['id'] ?? 0);
			if ($slug !== '' && $id > 0) {
				$ids[$slug] = $id;
			}
		}

		return $ids;
	}//end registerIds()

	/**
	 * Every table name on this connection that carries the shard-table marker.
	 *
	 * The LIKE pattern is bound and deliberately loose — `_` is a single-char
	 * wildcard in LIKE, which only widens it — because the authoritative filter
	 * is the anchored PCRE in ConsolidateRegistersDecisions, not the SQL. What
	 * matters is that the names come FROM the database rather than being
	 * composed here, and that the match is on the `openregister_table_` marker
	 * rather than on a guessed `oc_` prefix.
	 *
	 * On a database with no `information_schema` this query fails, which
	 * returns null and stops the step: "cannot see the install" is not
	 * "the install is empty".
	 *
	 * @return array<int, string>|null Table names, or null when unreadable.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function tableNames(): ?array {
		return $this->readColumn(
			sql: 'SELECT table_name FROM information_schema.tables WHERE table_name LIKE ?',
			params: ['%' . ConsolidateRegistersDecisions::TABLE_MARKER . '%'],
			column: 'table_name',
			failure: 'ConsolidateRegisters: could not list tables; stopping. Nothing was moved.'
		);
	}//end tableNames()

	/**
	 * The columns of one table, as the database reports them.
	 *
	 * @param string $table The table name.
	 *
	 * @return array<int, string>|null Column names, or null when unreadable.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function columnsOf(string $table): ?array {
		return $this->readColumn(
			sql: 'SELECT column_name FROM information_schema.columns WHERE table_name = ?',
			params: [$table],
			column: 'column_name',
			failure: 'ConsolidateRegisters: could not read table columns.',
			context: ['table' => $table]
		);
	}//end columnsOf()

	/**
	 * Every uuid held by one shard table.
	 *
	 * @param string $table The table name, already shape-checked.
	 *
	 * @return array<int, string>|null Uuids, or null when unreadable.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function uuidsIn(string $table): ?array {
		return $this->readColumn(
			sql: 'SELECT _uuid FROM `' . $table . '`',
			params: [],
			column: '_uuid',
			failure: 'ConsolidateRegisters: could not read the source table; refusing the pair. An unreadable '
				. 'table is not an empty one, and treating it as empty is how a migration deletes nothing '
				. 'and reports success.',
			context: ['table' => $table]
		);
	}//end uuidsIn()

	/**
	 * Which of these uuids the target table already holds.
	 *
	 * @param string $table The target table, already shape-checked.
	 * @param array<int, string> $uuids The uuids to look for.
	 *
	 * @return array<int, string>|null The subset present, or null when unreadable.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function uuidsPresent(string $table, array $uuids): ?array {
		$present = [];

		foreach ($this->decisions->chunk(items: $uuids) as $batch) {
			$found = $this->readColumn(
				sql: 'SELECT _uuid FROM `' . $table . '` '
					. 'WHERE _uuid IN (' . $this->decisions->placeholders(count: count($batch)) . ')',
				params: array_values($batch),
				column: '_uuid',
				failure: 'ConsolidateRegisters: could not read the target table; refusing the pair.',
				context: ['table' => $table]
			);

			if ($found === null) {
				return null;
			}

			$present = array_merge($present, $found);
		}

		return $present;
	}//end uuidsPresent()
}//end class
