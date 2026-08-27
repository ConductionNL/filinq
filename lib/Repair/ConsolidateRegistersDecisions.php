<?php

/**
 * The pure decisions behind ConsolidateRegisters.
 *
 * A collaborator rather than static helpers, because the ruleset forbids static
 * access — and an object keeps these testable on their own. Nothing here
 * touches a database, a logger or a clock: they take plain arrays and scalars
 * and return plain arrays and scalars. That is what makes the DECISIONS of a
 * row-moving migration unit-testable while the INSERT/DELETE that follows them
 * is not.
 *
 * Every predicate in this class exists because getting it wrong is silent.
 * A table-name shape check that is slightly too loose does not throw, it
 * deletes somebody else's rows. A uuid diff that is slightly too generous does
 * not throw, it overwrites an object. So each one is small enough to read in
 * one sitting and each one has a test.
 *
 * @category  Repair
 * @package   OCA\Filinq\Repair
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

/**
 * Pure predicates for the five-into-one register consolidation.
 *
 * @spec exclude No canonical spec covers the consolidation of Filinq's five
 *  OpenRegister registers into one. Pointing this at an existing spec would
 *  report conformance to a requirement that says nothing about it.
 */
class ConsolidateRegistersDecisions {

	/**
	 * The marker every OpenRegister shard table carries in its name.
	 *
	 * MATCHED ON THE MARKER, NEVER ON A COMPUTED `oc_` PREFIX. The Nextcloud
	 * table prefix is an install-time setting (`dbtableprefix`), so hard-coding
	 * `oc_` is a guess. A prefix guess that misses does not error — it returns
	 * ZERO tables, which reads exactly like "this install has no objects" and
	 * would let this step report a successful consolidation having moved
	 * nothing at all.
	 *
	 * @var string
	 */
	public const TABLE_MARKER = 'openregister_table_';

	/**
	 * How many uuids may ride in a single IN clause.
	 *
	 * Bound low deliberately. Every database has a cap on bound parameters
	 * (SQLite's default is 999, MySQL's max_prepared_stmt_count is per
	 * connection), and a step that only ever runs on somebody else's install
	 * must not discover that cap there. Chunking is also the failure
	 * granularity: an insert-verify-delete cycle runs per chunk, so a
	 * mid-migration failure leaves whole chunks moved and whole chunks
	 * untouched, never a half-written row.
	 *
	 * @var int
	 */
	public const CHUNK_SIZE = 250;

	/**
	 * The regex a shard table name must match to be touched at all.
	 *
	 * Anchored at both ends and built from integers that have already been cast
	 * to `int` by the caller, so there is nothing here an attacker-controlled
	 * string could reach. This exists because a table name CANNOT be a bound
	 * parameter — it is interpolated into SQL — and interpolating anything that
	 * has not been shape-checked is how a repair step becomes an injection
	 * point.
	 *
	 * @param int $registerId The register id the table must belong to.
	 * @param int|null $schemaId The schema id, or null to accept any.
	 *
	 * @return string A PCRE pattern.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	private function tablePattern(int $registerId, ?int $schemaId = null): string {
		$schemaPart = '([0-9]+)';
		if ($schemaId !== null) {
			$schemaPart = '(' . $schemaId . ')';
		}

		return '/^[A-Za-z0-9_]+' . preg_quote(self::TABLE_MARKER, '/') . $registerId . '_' . $schemaPart . '$/';
	}//end tablePattern()

	/**
	 * Pick this register's shard tables out of a list of table names.
	 *
	 * The list must be one an `information_schema` query returned. This method
	 * only ever NARROWS it — it never composes a name — so a table this step
	 * goes on to address is a table the database said exists.
	 *
	 * TWO NAMES FOR ONE SCHEMA ID IS AMBIGUITY, NOT A CHOICE. On MySQL,
	 * `information_schema.tables` spans every database on the server, so an
	 * install sharing a server with a second Nextcloud (`nc_` beside `oc_`) can
	 * legitimately return two names matching the same (register, schema) pair.
	 * Guessing which one is "ours" from a prefix is exactly the guess this
	 * class refuses to make, so the pair is dropped and reported.
	 *
	 * @param array<int, string> $names Table names as the database reported them.
	 * @param int $registerId The register whose shards are wanted.
	 *
	 * @return array{tables: array<int, string>, ambiguous: array<int, int>}
	 *                                                                       `tables` is schema id => table name; `ambiguous` lists the schema ids
	 *                                                                       dropped because more than one name claimed them.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function shardTablesFor(array $names, int $registerId): array {
		$pattern = $this->tablePattern(registerId: $registerId);
		$tables = [];
		$seen = [];

		foreach ($names as $name) {
			$matches = [];
			if (preg_match($pattern, $name, $matches) !== 1) {
				continue;
			}

			$schemaId = (int)$matches[1];
			$seen[$schemaId] = ($seen[$schemaId] ?? 0) + 1;
			$tables[$schemaId] = $name;
		}

		$ambiguous = [];
		foreach ($seen as $schemaId => $count) {
			if ($count > 1) {
				$ambiguous[] = $schemaId;
				unset($tables[$schemaId]);
			}
		}

		ksort($tables);
		sort($ambiguous);

		return [
			'tables' => $tables,
			'ambiguous' => $ambiguous,
		];
	}//end shardTablesFor()

	/**
	 * Final gate before a table name is interpolated into SQL.
	 *
	 * Deliberately re-checks what shardTablesFor() already filtered on, against
	 * the exact (register, schema) pair this call is about. The two checks are
	 * not redundant: the first says "this is a shard table of register N", this
	 * one says "this is the shard table of register N and schema M, the one the
	 * caller believes it is holding". A step that moves rows between tables has
	 * exactly one chance to notice it is holding the wrong one.
	 *
	 * @param string $name The candidate table name.
	 * @param int $registerId The register the table must belong to.
	 * @param int $schemaId The schema the table must belong to.
	 *
	 * @return bool True when the name is safe to interpolate.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function isSafeTableName(string $name, int $registerId, int $schemaId): bool {
		return preg_match($this->tablePattern(registerId: $registerId, schemaId: $schemaId), $name) === 1;
	}//end isSafeTableName()

	/**
	 * Columns the source carries that the target does not.
	 *
	 * Both tables are generated from the SAME schema id, so in the ordinary
	 * case this is empty and that is what makes `INSERT INTO target SELECT ...
	 * FROM source` viable at all. It is checked anyway: MagicMapper ADDS a
	 * column when a schema property appears and never removes one, so a source
	 * table that was synced against a newer schema version than the target can
	 * genuinely carry a column the target lacks. Copying it silently drops that
	 * property from every migrated object — a data loss no count reconciles,
	 * because the ROWS all arrive.
	 *
	 * `_id` is excluded because it is deliberately not carried across: it is a
	 * per-table sequence, and the target assigns its own. `_uuid` is the
	 * identity that survives the move.
	 *
	 * @param array<int, string> $sourceColumns Columns of the source table.
	 * @param array<int, string> $targetColumns Columns of the target table.
	 *
	 * @return array<int, string> The unmatched source columns, sorted.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function missingColumns(array $sourceColumns, array $targetColumns): array {
		$missing = array_values(array_diff(
			$this->insertColumns(sourceColumns: $sourceColumns),
			array_map(static fn (string $column): string => strtolower($column), $targetColumns)
		));

		sort($missing);

		return $missing;
	}//end missingColumns()

	/**
	 * The columns an INSERT should carry, in a stable order.
	 *
	 * Lower-cased and de-duplicated so the comparison against the target's
	 * columns is not defeated by a database that reports identifiers in a
	 * different case than another one does.
	 *
	 * @param array<int, string> $sourceColumns Columns of the source table.
	 *
	 * @return array<int, string> Columns to carry, `_id` removed.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function insertColumns(array $sourceColumns): array {
		$columns = array_map(static fn (string $column): string => strtolower($column), $sourceColumns);
		$columns = array_values(array_unique(array_filter(
			$columns,
			static fn (string $column): bool => $column !== '' && $column !== '_id'
		)));

		sort($columns);

		return $columns;
	}//end insertColumns()

	/**
	 * Whether a column list is shaped like an OpenRegister shard table.
	 *
	 * `_uuid` is the identity every step of the move is keyed on and `_register`
	 * is the column the move exists to rewrite. A table missing either is not
	 * one of ours whatever its name says, and this step must not write to it.
	 *
	 * @param array<int, string> $columns The column list to check.
	 *
	 * @return bool True when both required columns are present.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function looksLikeShardTable(array $columns): bool {
		$columns = array_map(static fn (string $column): string => strtolower($column), $columns);

		return in_array('_uuid', $columns, true) === true
			&& in_array('_register', $columns, true) === true;
	}//end looksLikeShardTable()

	/**
	 * Split the source uuids into the ones that may move and the ones that may not.
	 *
	 * A uuid already present in the target is a CONFLICT, and this step refuses
	 * it rather than resolving it. Two objects sharing a uuid across the two
	 * tables can mean a previous run inserted and then failed to delete, or it
	 * can mean two genuinely different objects that were assigned the same
	 * identity. Those want opposite treatments and nothing available here tells
	 * them apart, so both rows are left exactly where they are and the conflict
	 * is logged. Merging is a decision about data, not a migration.
	 *
	 * @param array<int, string> $sourceUuids Uuids in the source table.
	 * @param array<int, string> $targetUuids Uuids already in the target table.
	 *
	 * @return array{movable: array<int, string>, conflicts: array<int, string>}
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function partitionUuids(array $sourceUuids, array $targetUuids): array {
		$source = array_values(array_unique(array_filter(
			$sourceUuids,
			static fn (string $uuid): bool => $uuid !== ''
		)));

		$movable = array_values(array_diff($source, $targetUuids));
		$conflicts = array_values(array_intersect($source, $targetUuids));

		return [
			'movable' => $movable,
			'conflicts' => $conflicts,
		];
	}//end partitionUuids()

	/**
	 * Cut a list of uuids into IN-clause sized batches.
	 *
	 * @param array<int, string> $items The uuids.
	 * @param int $size Maximum batch size.
	 *
	 * @return array<int, array<int, string>> The batches.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function chunk(array $items, int $size = self::CHUNK_SIZE): array {
		if ($items === []) {
			return [];
		}

		return array_chunk(array_values($items), max(1, $size));
	}//end chunk()

	/**
	 * Build the `?,?,?` placeholder list for an IN clause.
	 *
	 * Here rather than inline because a mismatch between the placeholder count
	 * and the bound parameters is the kind of error that only shows up at
	 * runtime, inside a repair step, on somebody else's install.
	 *
	 * @param int $count Number of bound parameters.
	 *
	 * @return string The placeholder list.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function placeholders(int $count): string {
		return implode(',', array_fill(0, max(0, $count), '?'));
	}//end placeholders()

	/**
	 * The SELECT list that rewrites `_register` while copying everything else.
	 *
	 * `_register` becomes a bound `?` — the one value the move changes — and
	 * every other column is copied through by name. Returning the expressions
	 * alongside the column list keeps the two in lockstep; building them at two
	 * call sites is how an INSERT ends up writing the right values into the
	 * wrong columns.
	 *
	 * @param array<int, string> $columns The columns to carry, from insertColumns().
	 *
	 * @return array<int, string> One SELECT expression per column, same order.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function selectExpressions(array $columns): array {
		$expressions = [];
		foreach ($columns as $column) {
			if ($column === '_register') {
				$expressions[] = '?';
				continue;
			}

			$expressions[] = $column;
		}

		return $expressions;
	}//end selectExpressions()

	/**
	 * Whether an insert reconciled, i.e. whether the target now holds the batch.
	 *
	 * The whole point of the insert-verify-delete ordering hangs on this
	 * returning false when it should. It compares what the target holds against
	 * what was asked for, not against what the driver's affected-row count
	 * said — a driver reporting `n` rows written is a claim, and the count
	 * queried back out of the target is an observation.
	 *
	 * @param int $expected How many uuids the batch contained.
	 * @param int|null $observed How many of them the target now holds, or null
	 *                           when the verification query itself failed.
	 *
	 * @return bool True only when every uuid in the batch was observed.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function reconciled(int $expected, ?int $observed): bool {
		if ($observed === null) {
			return false;
		}

		return $observed === $expected;
	}//end reconciled()

	/**
	 * Pull one string column out of a result set, dropping blanks.
	 *
	 * Defensive on purpose: a row with a null value must yield an empty string
	 * rather than a TypeError inside a repair step, where an escaping exception
	 * aborts the install and the app never enables — and then that empty string
	 * is DISCARDED rather than returned. Every caller here reads an identifier:
	 * a table name, a column name, a uuid. A blank one addresses nothing, and
	 * carrying it forward would put an empty string into an IN clause or, worse,
	 * into a name that gets interpolated into SQL.
	 *
	 * The upper-cased fallback is not decoration: some drivers report
	 * `information_schema` column labels in upper case, and reading only the
	 * lower-cased key there returns a list of blanks — which, before this
	 * dropped them, was a list of empty table names that looked like data.
	 *
	 * @param array<int, array<string, mixed>> $rows The result rows.
	 * @param string $column The column to pull.
	 *
	 * @return array<int, string> The non-blank values.
	 *
	 * @spec exclude No canonical spec covers the consolidation of Filinq's five
	 *  OpenRegister registers into one. Pointing this at an existing spec would
	 *  report conformance to a requirement that says nothing about it.
	 */
	public function column(array $rows, string $column): array {
		$values = array_map(
			static fn (array $row): string => (string)($row[$column] ?? $row[strtoupper($column)] ?? ''),
			$rows
		);

		return array_values(array_filter($values, static fn (string $value): bool => $value !== ''));
	}//end column()
}//end class
