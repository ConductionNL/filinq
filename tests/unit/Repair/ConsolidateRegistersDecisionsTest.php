<?php

/**
 * Tests for the pure decisions behind the five-into-one register consolidation.
 *
 * WHY THESE ARE SEPARATE FROM THE STEP'S OWN TESTS. Every predicate here is one
 * a row-moving migration gets exactly one chance to get right, and each of them
 * fails SILENTLY when it is wrong: a table-name pattern that is a shade too
 * loose deletes somebody else's rows, a uuid partition that is a shade too
 * generous overwrites an object, a column comparison that is case-sensitive on
 * a database that upper-cases identifiers refuses every pair and reports a
 * clean run. None of those throw. Testing them against a live-ish database
 * double would exercise them through four layers of SQL; testing them here
 * exercises the decision itself.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Repair;

use OCA\Filinq\Repair\ConsolidateRegistersDecisions;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the predicates that authorise every write the step performs.
 */
class ConsolidateRegistersDecisionsTest extends TestCase {

	private ConsolidateRegistersDecisions $decisions;

	/**
	 * Fresh decisions per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->decisions = new ConsolidateRegistersDecisions();
	}//end setUp()

	/**
	 * The table pattern matches ANY prefix, because the prefix is configurable.
	 *
	 * This is the single most important assertion in the file. Nextcloud's
	 * `dbtableprefix` is an install-time setting: hard-coding `oc_` is a guess,
	 * and a prefix guess that misses returns zero tables — which reads exactly
	 * like "this install has no objects" and lets the step report a successful
	 * consolidation having moved nothing.
	 *
	 * @return void
	 */
	public function testTheTableNameMatchIsOnTheMarkerNotAGuessedPrefix(): void {
		foreach (['oc_', 'nc_', 'weird1_prefix_'] as $prefix) {
			$this->assertTrue(
				$this->decisions->isSafeTableName(
					name: $prefix . 'openregister_table_66_227',
					registerId: 66,
					schemaId: 227
				),
				sprintf('prefix "%s" must be accepted', $prefix)
			);
		}

		// THE ONE PREFIX NOT ACCEPTED IS THE EMPTY ONE, and that is a deliberate
		// trade-off rather than an oversight. The shape check requires at least
		// one prefix character, so an install configured with `dbtableprefix`
		// set to '' has its pairs SKIPPED AND LOGGED — incomplete, which is
		// recoverable, rather than matched by a pattern loose enough to accept a
		// bare `openregister_table_*` from anywhere. Pinned here so that the day
		// somebody widens the pattern, they do it on purpose.
		$this->assertFalse(
			$this->decisions->isSafeTableName(
				name: 'openregister_table_66_227',
				registerId: 66,
				schemaId: 227
			)
		);
	}//end testTheTableNameMatchIsOnTheMarkerNotAGuessedPrefix()

	/**
	 * Names that are not exactly this (register, schema) pair are refused.
	 *
	 * The suffixed and infixed cases matter most: an unanchored pattern would
	 * accept `oc_openregister_table_66_227_backup` and `oc_openregister_table_
	 * 660_2270`, and the step interpolates this name straight into a DELETE.
	 *
	 * @return void
	 */
	public function testNamesOutsideTheExactPairAreRefused(): void {
		$refused = [
			'oc_openregister_table_66_227_backup',
			'oc_openregister_table_660_2270',
			'oc_openregister_table_66_2270',
			'oc_openregister_table_6_227',
			'oc_openregister_table_66_227 ; DROP TABLE oc_users',
			'oc_openregister_objects',
			'oc_users',
			'',
		];

		foreach ($refused as $name) {
			$this->assertFalse(
				$this->decisions->isSafeTableName(name: $name, registerId: 66, schemaId: 227),
				sprintf('"%s" must NOT be accepted', $name)
			);
		}
	}//end testNamesOutsideTheExactPairAreRefused()

	/**
	 * Shard tables are picked out of a table list by register, keyed by schema.
	 *
	 * @return void
	 */
	public function testShardTablesAreSelectedByRegisterAndKeyedBySchema(): void {
		$names = [
			'oc_openregister_table_66_227',
			'oc_openregister_table_66_228',
			'oc_openregister_table_6_5023',
			'oc_openregister_registers',
			'oc_filecache',
		];

		$result = $this->decisions->shardTablesFor(names: $names, registerId: 66);

		$this->assertSame(
			[227 => 'oc_openregister_table_66_227', 228 => 'oc_openregister_table_66_228'],
			$result['tables']
		);
		$this->assertSame([], $result['ambiguous']);
	}//end testShardTablesAreSelectedByRegisterAndKeyedBySchema()

	/**
	 * Two prefixes claiming one pair is ambiguity, and ambiguity is dropped.
	 *
	 * One database server can host two Nextcloud installs with different table
	 * prefixes, and on MySQL `information_schema.tables` spans both. Choosing
	 * between `oc_` and `nc_` by preferring one is a guess about which install
	 * the rows belong to, and the guess is unrecoverable once the DELETE runs.
	 *
	 * @return void
	 */
	public function testTwoPrefixesClaimingOnePairAreDroppedNotPicked(): void {
		$result = $this->decisions->shardTablesFor(
			names: ['oc_openregister_table_66_227', 'nc_openregister_table_66_227'],
			registerId: 66
		);

		$this->assertSame([], $result['tables'], 'neither may be chosen');
		$this->assertSame([227], $result['ambiguous']);
	}//end testTwoPrefixesClaimingOnePairAreDroppedNotPicked()

	/**
	 * `_id` never travels; every other column does.
	 *
	 * `_id` is a per-table sequence and the target assigns its own. Carrying it
	 * across would collide with rows the target already holds.
	 *
	 * @return void
	 */
	public function testTheSourceIdColumnIsNeverCarriedAcross(): void {
		$columns = $this->decisions->insertColumns(
			sourceColumns: ['_id', '_uuid', '_register', '_schema', 'name']
		);

		$this->assertNotContains('_id', $columns);
		$this->assertContains('_uuid', $columns);
		$this->assertContains('_register', $columns);
		$this->assertContains('name', $columns);
	}//end testTheSourceIdColumnIsNeverCarriedAcross()

	/**
	 * A source column the target lacks is a refusal, not a silent drop.
	 *
	 * MagicMapper ADDS a column when a schema property appears and never
	 * removes one, so a source synced against a newer schema version than the
	 * target genuinely can carry a column the target has not got. Copying
	 * anyway drops that property from every migrated object while every ROW
	 * arrives — a data loss no count reconciles.
	 *
	 * @return void
	 */
	public function testAColumnTheTargetLacksIsReported(): void {
		$missing = $this->decisions->missingColumns(
			sourceColumns: ['_id', '_uuid', '_register', 'name', 'checkedOn'],
			targetColumns: ['_id', '_uuid', '_register', 'name']
		);

		$this->assertSame(['checkedon'], $missing);
	}//end testAColumnTheTargetLacksIsReported()

	/**
	 * Identical column sets produce no complaint, whatever the case or order.
	 *
	 * Databases disagree about identifier case in `information_schema`. A
	 * case-sensitive comparison would report every column missing on one of
	 * them, refusing every pair while reporting a clean run.
	 *
	 * @return void
	 */
	public function testColumnComparisonIgnoresCaseAndOrder(): void {
		$this->assertSame(
			[],
			$this->decisions->missingColumns(
				sourceColumns: ['NAME', '_UUID', '_register'],
				targetColumns: ['_register', 'name', '_uuid', 'extra_target_only']
			)
		);
	}//end testColumnComparisonIgnoresCaseAndOrder()

	/**
	 * A table without `_uuid` or `_register` is not one of ours.
	 *
	 * @return void
	 */
	public function testATableMissingTheIdentityOrRegisterColumnIsNotOurs(): void {
		$this->assertTrue($this->decisions->looksLikeShardTable(columns: ['_uuid', '_register', 'name']));
		$this->assertFalse($this->decisions->looksLikeShardTable(columns: ['_register', 'name']));
		$this->assertFalse($this->decisions->looksLikeShardTable(columns: ['_uuid', 'name']));
		$this->assertFalse($this->decisions->looksLikeShardTable(columns: []));
	}//end testATableMissingTheIdentityOrRegisterColumnIsNotOurs()

	/**
	 * A uuid already in the target is a conflict, and conflicts do not move.
	 *
	 * @return void
	 */
	public function testUuidsAlreadyInTheTargetAreConflictsNotMovable(): void {
		$split = $this->decisions->partitionUuids(
			sourceUuids: ['a', 'b', 'c'],
			targetUuids: ['b', 'z']
		);

		$this->assertSame(['a', 'c'], $split['movable']);
		$this->assertSame(['b'], $split['conflicts']);
	}//end testUuidsAlreadyInTheTargetAreConflictsNotMovable()

	/**
	 * Empty and blank uuids never become movable.
	 *
	 * @return void
	 */
	public function testBlankUuidsAreNeverMovable(): void {
		$split = $this->decisions->partitionUuids(sourceUuids: ['', 'a', ''], targetUuids: []);

		$this->assertSame(['a'], $split['movable']);
	}//end testBlankUuidsAreNeverMovable()

	/**
	 * Reconciliation is false when the verification query itself failed.
	 *
	 * This is property 4 in miniature: `null` means "nothing is known", and
	 * "nothing is known" must never authorise the DELETE that follows.
	 *
	 * @return void
	 */
	public function testAnUnknownObservationNeverReconciles(): void {
		$this->assertFalse($this->decisions->reconciled(expected: 0, observed: null));
		$this->assertFalse($this->decisions->reconciled(expected: 3, observed: null));
		$this->assertFalse($this->decisions->reconciled(expected: 3, observed: 2));
		$this->assertTrue($this->decisions->reconciled(expected: 3, observed: 3));
	}//end testAnUnknownObservationNeverReconciles()

	/**
	 * Batching keeps every uuid exactly once.
	 *
	 * @return void
	 */
	public function testChunkingLosesNothing(): void {
		$items = array_map(static fn (int $i): string => 'uuid-' . $i, range(1, 7));

		$batches = $this->decisions->chunk(items: $items, size: 3);

		$this->assertCount(3, $batches);
		$this->assertSame($items, array_merge(...$batches));
		$this->assertSame([], $this->decisions->chunk(items: [], size: 3));
	}//end testChunkingLosesNothing()

	/**
	 * Placeholders match the bound-parameter count exactly.
	 *
	 * @return void
	 */
	public function testPlaceholdersMatchTheParameterCount(): void {
		$this->assertSame('?,?,?', $this->decisions->placeholders(count: 3));
		$this->assertSame('', $this->decisions->placeholders(count: 0));
		$this->assertSame('', $this->decisions->placeholders(count: -1));
	}//end testPlaceholdersMatchTheParameterCount()

	/**
	 * Only `_register` becomes a bound parameter; everything else copies through.
	 *
	 * @return void
	 */
	public function testOnlyTheRegisterColumnIsRewritten(): void {
		$this->assertSame(
			['name', '?', '_uuid'],
			$this->decisions->selectExpressions(columns: ['name', '_register', '_uuid'])
		);
	}//end testOnlyTheRegisterColumnIsRewritten()

	/**
	 * A null column value is dropped, never a TypeError and never a blank.
	 *
	 * Two things at once. Inside a repair step registered under `<install>`, an
	 * escaping exception aborts the install and the app never enables at all —
	 * so a null must not become a TypeError. And every caller reads an
	 * IDENTIFIER, so the empty string it becomes must not be returned either: a
	 * blank uuid addresses nothing, and a blank table name would be
	 * interpolated into SQL.
	 *
	 * @return void
	 */
	public function testABlankColumnValueIsDroppedRatherThanReturned(): void {
		$this->assertSame(
			['a', 'b'],
			$this->decisions->column(
				rows: [['_uuid' => 'a'], ['_uuid' => null], ['_uuid' => ''], ['_uuid' => 'b']],
				column: '_uuid'
			)
		);
	}//end testABlankColumnValueIsDroppedRatherThanReturned()

	/**
	 * An upper-cased result label is read, not silently turned into blanks.
	 *
	 * Some drivers report `information_schema` labels in upper case. Reading
	 * only the lower-cased key there yields a list of empty strings — a list
	 * that has the right LENGTH and no content, which reads as data.
	 *
	 * @return void
	 */
	public function testAnUpperCasedResultLabelIsStillRead(): void {
		$this->assertSame(
			['oc_openregister_table_66_227'],
			$this->decisions->column(
				rows: [['TABLE_NAME' => 'oc_openregister_table_66_227']],
				column: 'table_name'
			)
		);
	}//end testAnUpperCasedResultLabelIsStillRead()
}//end class
