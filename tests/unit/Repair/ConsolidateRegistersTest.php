<?php

/**
 * Tests for the five-into-one register consolidation.
 *
 * WHY THIS EXISTS, AND WHY IT IS NOT A SET OF EXPECTED CALLS. This step
 * physically MOVES object rows between database tables. Asserting "executeQuery
 * was called with this SQL" against a mock proves only that the test and the
 * code agree on a string. What has to be true is a property of the DATA: after
 * the step runs, are the rows in the target, are they gone from the source, and
 * — the question every one of these tests is really asking — when something
 * goes wrong, are they still SOMEWHERE.
 *
 * So the double is a fake in-memory shard database. It parses the small,
 * closed set of statements this step issues, applies them to described tables,
 * and can be told to fail any one of them. The tests then run the step and ask
 * the fake what it HOLDS.
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

use OCA\Filinq\Repair\ConsolidateRegisters;
use OCA\Filinq\Repair\ConsolidateRegistersGateway;
use OCP\DB\IResult;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Verifies the safety properties of the row-moving consolidation.
 */
class ConsolidateRegistersTest extends TestCase {

	/**
	 * The register ids used throughout, mirroring the reference instance.
	 *
	 * @var array<string, int>
	 */
	private const IDS = [
		'consent' => 3,
		'signing' => 4,
		'templates' => 5,
		'document' => 6,
		'dossier' => 66,
		'filinq' => 517,
	];

	/**
	 * A database holding one source pair and its empty target.
	 *
	 * `nc_` on purpose, not `oc_`: the step must find these through the
	 * `openregister_table_` marker and never through a guessed prefix.
	 *
	 * @param array<int, string> $sourceUuids Uuids in the source table.
	 * @param array<int, string> $targetUuids Uuids already in the target table.
	 *
	 * @return ShardDatabase
	 */
	private function oneDossierPair(array $sourceUuids = ['u1', 'u2'], array $targetUuids = []): ShardDatabase {
		$columns = ['_id', '_uuid', '_register', '_schema', 'name'];

		return new ShardDatabase(
			registers: self::IDS,
			tables: [
				'nc_openregister_table_66_227' => [
					'columns' => $columns,
					'rows' => $this->rowsFor(uuids: $sourceUuids, register: '66'),
				],
				'nc_openregister_table_517_227' => [
					'columns' => $columns,
					'rows' => $this->rowsFor(uuids: $targetUuids, register: '517'),
				],
				'nc_filecache' => ['columns' => ['fileid'], 'rows' => []],
			]
		);
	}//end oneDossierPair()

	/**
	 * Build shard rows for a list of uuids.
	 *
	 * @param array<int, string> $uuids The uuids.
	 * @param string $register The `_register` value the rows carry.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function rowsFor(array $uuids, string $register): array {
		$rows = [];
		foreach ($uuids as $i => $uuid) {
			$rows[] = [
				'_id' => ($i + 1),
				'_uuid' => $uuid,
				'_register' => $register,
				'_schema' => '227',
				'name' => 'object ' . $uuid,
			];
		}

		return $rows;
	}//end rowsFor()

	/**
	 * Run the step against a fake database.
	 *
	 * @param ShardDatabase $database The fake.
	 *
	 * @return array{output: array<int, string>, warnings: array<int, string>}
	 */
	private function runStep(ShardDatabase $database): array {
		$db = $this->createMock(IDBConnection::class);
		$db->method('executeQuery')->willReturnCallback(
			function (string $sql, array $params = []) use ($database): IResult {
				$rows = $database->query(sql: $sql, params: $params);
				$result = $this->createMock(IResult::class);
				$result->method('fetchAll')->willReturn($rows);
				return $result;
			}
		);
		$db->method('executeStatement')->willReturnCallback(
			static fn (string $sql, array $params = []): int => $database->statement(sql: $sql, params: $params)
		);

		$messages = [];
		$output = $this->createMock(IOutput::class);
		$output->method('info')->willReturnCallback(
			static function (string $message) use (&$messages): void {
				$messages[] = $message;
			}
		);

		$warnings = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')->willReturnCallback(
			static function (string|\Stringable $message) use (&$warnings): void {
				$warnings[] = (string)$message;
			}
		);
		$logger->method('error')->willReturnCallback(
			static function (string|\Stringable $message) use (&$warnings): void {
				$warnings[] = (string)$message;
			}
		);

		(new ConsolidateRegisters(
			logger: $logger,
			gateway: new ConsolidateRegistersGateway(db: $db, logger: $logger)
		))->run($output);

		return ['output' => $messages, 'warnings' => $warnings];
	}//end runStep()

	/**
	 * The happy path: rows arrive, `_register` is rewritten, source is emptied.
	 *
	 * @return void
	 */
	public function testRowsMoveAcrossAndTheRegisterColumnIsRewritten(): void {
		$database = $this->oneDossierPair();

		$this->runStep(database: $database);

		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_517_227'));

		foreach ($database->rowsIn(table: 'nc_openregister_table_517_227') as $row) {
			$this->assertSame('517', $row['_register'], 'the moved row must belong to the target register');
			$this->assertArrayHasKey('name', $row, 'the schema columns must travel with the row');
		}
	}//end testRowsMoveAcrossAndTheRegisterColumnIsRewritten()

	/**
	 * PROPERTY 1 — a second run after a completed move does nothing, and says so.
	 *
	 * @return void
	 */
	public function testASecondRunAfterACompletedMoveIsANoOp(): void {
		$database = $this->oneDossierPair();

		$this->runStep(database: $database);
		$afterFirst = $database->rowsIn(table: 'nc_openregister_table_517_227');

		$second = $this->runStep(database: $database);

		$this->assertSame(
			$afterFirst,
			$database->rowsIn(table: 'nc_openregister_table_517_227'),
			'a re-run must not duplicate, reorder or re-write anything'
		);
		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertStringContainsString('0 object(s) moved', implode(' ', $second['output']));
	}//end testASecondRunAfterACompletedMoveIsANoOp()

	/**
	 * PROPERTY 2 — a database that fails every call does not abort the install.
	 *
	 * The step is registered under `<install>`. An escaping exception there
	 * aborts the install and the app never enables at all, so "the database is
	 * broken" must degrade to "nothing moved", never to a fatal.
	 *
	 * @return void
	 */
	public function testATotallyBrokenDatabaseNeverThrows(): void {
		$database = new ShardDatabase(registers: self::IDS, tables: [], failOn: '');

		$result = $this->runStep(database: $database);

		$this->assertStringContainsString('nothing moved', implode(' ', $result['output']));
	}//end testATotallyBrokenDatabaseNeverThrows()

	/**
	 * PROPERTY 3 — a uuid already in the target is left alone on BOTH sides.
	 *
	 * Not overwritten, and not deleted from the source either. Two rows sharing
	 * an identity can mean a previous run that inserted and failed to delete, or
	 * two genuinely different objects; those want opposite treatments and
	 * nothing here tells them apart.
	 *
	 * @return void
	 */
	public function testAConflictingUuidIsNeitherOverwrittenNorDeleted(): void {
		$database = $this->oneDossierPair(sourceUuids: ['u1', 'u2'], targetUuids: ['u2']);
		$before = $database->rowFor(table: 'nc_openregister_table_517_227', uuid: 'u2');

		$result = $this->runStep(database: $database);

		$this->assertSame(
			['u2'],
			$database->uuidsIn(table: 'nc_openregister_table_66_227'),
			'the conflicting source row must stay exactly where it is'
		);
		$this->assertSame(
			$before,
			$database->rowFor(table: 'nc_openregister_table_517_227', uuid: 'u2'),
			'the target row must not be overwritten'
		);
		$this->assertSame(['u2', 'u1'], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
		$this->assertStringContainsString('1 conflict(s) left in place', implode(' ', $result['output']));
		$this->assertStringContainsString('leaving BOTH rows in place', implode(' ', $result['warnings']));
	}//end testAConflictingUuidIsNeitherOverwrittenNorDeleted()

	/**
	 * PROPERTY 4 — a source table that cannot be READ is not an empty one.
	 *
	 * This is the failure this whole class is shaped around. If an unreadable
	 * source were treated as empty, the step would find nothing to move, delete
	 * nothing, and report a clean consolidation over an install whose objects
	 * are still in the old tables — success with the data left behind.
	 *
	 * @return void
	 */
	public function testAnUnreadableSourceStopsThePairInsteadOfLookingEmpty(): void {
		$database = $this->oneDossierPair();
		$database->failOn(fragment: 'SELECT _uuid FROM `nc_openregister_table_66_227`');

		$result = $this->runStep(database: $database);

		$this->assertSame(
			['u1', 'u2'],
			$database->uuidsIn(table: 'nc_openregister_table_66_227'),
			'nothing may be deleted when the source could not be read'
		);
		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
		$this->assertStringContainsString('1 pair(s) refused', implode(' ', $result['output']));
		$this->assertStringContainsString('An unreadable table is not an empty one', implode(' ', $result['warnings']));
	}//end testAnUnreadableSourceStopsThePairInsteadOfLookingEmpty()

	/**
	 * PROPERTY 4 — an unreadable REGISTER table stops the whole step.
	 *
	 * @return void
	 */
	public function testAnUnreadableRegisterTableStopsEverything(): void {
		$database = $this->oneDossierPair();
		$database->failOn(fragment: 'openregister_registers');

		$result = $this->runStep(database: $database);

		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertStringContainsString(
			'could not read the register table; nothing moved',
			implode(' ', $result['output'])
		);
	}//end testAnUnreadableRegisterTableStopsEverything()

	/**
	 * PROPERTY 5 — an insert that does not reconcile leaves the source intact.
	 *
	 * The fake accepts the INSERT and quietly drops the rows, which is exactly
	 * the shape of the failure a driver's affected-row count cannot see. The
	 * DELETE is authorised by counting the uuids back OUT of the target, so it
	 * must not fire.
	 *
	 * @return void
	 */
	public function testAnInsertThatDoesNotArriveNeverAuthorisesTheDelete(): void {
		$database = $this->oneDossierPair();
		$database->swallowInserts();

		$result = $this->runStep(database: $database);

		$this->assertSame(
			['u1', 'u2'],
			$database->uuidsIn(table: 'nc_openregister_table_66_227'),
			'the source must survive an insert that silently went nowhere'
		);
		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
		$this->assertStringContainsString('NOT deleting the source rows', implode(' ', $result['warnings']));
	}//end testAnInsertThatDoesNotArriveNeverAuthorisesTheDelete()

	/**
	 * PROPERTY 5 — a failing INSERT leaves both sides untouched.
	 *
	 * @return void
	 */
	public function testAFailingInsertLeavesTheSourceIntact(): void {
		$database = $this->oneDossierPair();
		$database->failOn(fragment: 'INSERT INTO');

		$this->runStep(database: $database);

		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
	}//end testAFailingInsertLeavesTheSourceIntact()

	/**
	 * PROPERTY 5 — a failing DELETE is reported loudly, with the rows duplicated.
	 *
	 * This is the one outcome that is genuinely messy, and pinning it here is
	 * the point: the rows now exist on BOTH sides. No object is lost — the app
	 * reads the target — but a re-run will see them as conflicts and refuse, by
	 * design, so the operator has to clear the source copies by hand. A test
	 * that asserted this was "fine" would be describing a different program.
	 *
	 * @return void
	 */
	public function testAFailingDeleteLeavesDuplicatesAndSaysSo(): void {
		$database = $this->oneDossierPair();
		$database->failOn(fragment: 'DELETE FROM');

		$result = $this->runStep(database: $database);

		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
		$this->assertStringContainsString('exist in BOTH tables', implode(' ', $result['warnings']));
	}//end testAFailingDeleteLeavesDuplicatesAndSaysSo()

	/**
	 * PROPERTY 6 — nothing outside the proven (register, schema) tables is touched.
	 *
	 * The decoys are the shapes a slightly-loose filter would accept: another
	 * app's shard table, a look-alike backup, and an ordinary core table.
	 *
	 * @return void
	 */
	public function testTablesThatAreNotOursAreNeverTouched(): void {
		$database = $this->oneDossierPair();
		$database->addTable(
			name: 'nc_openregister_table_7_227',
			columns: ['_id', '_uuid', '_register', '_schema', 'name'],
			rows: $this->rowsFor(uuids: ['docudesk-1'], register: '7')
		);
		$database->addTable(
			name: 'nc_openregister_table_66_227_backup',
			columns: ['_id', '_uuid', '_register', '_schema', 'name'],
			rows: $this->rowsFor(uuids: ['backup-1'], register: '66')
		);

		$this->runStep(database: $database);

		$this->assertSame(
			['docudesk-1'],
			$database->uuidsIn(table: 'nc_openregister_table_7_227'),
			'the docudesk register is not one of the five and must be untouched'
		);
		$this->assertSame(
			['backup-1'],
			$database->uuidsIn(table: 'nc_openregister_table_66_227_backup'),
			'a look-alike name must not be matched'
		);
		$this->assertSame([], $database->uuidsIn(table: 'nc_filecache'));
	}//end testTablesThatAreNotOursAreNeverTouched()

	/**
	 * PROPERTY 7 — a missing target shard table is a SKIP, and no DDL is issued.
	 *
	 * This is the ordinary state on the very first pass: the register import
	 * runs after every repair step, so the target tables do not exist yet. The
	 * step must say "not yet", not "done", and must not create the table
	 * itself — DDL a repair step does not own makes a rollback impossible.
	 *
	 * @return void
	 */
	public function testAMissingTargetShardTableIsSkippedNotCreated(): void {
		$database = $this->oneDossierPair();
		$database->addTable(
			name: 'nc_openregister_table_66_228',
			columns: ['_id', '_uuid', '_register', '_schema', 'name'],
			rows: $this->rowsFor(uuids: ['orphan-1'], register: '66')
		);

		$result = $this->runStep(database: $database);

		$this->assertSame(
			['orphan-1'],
			$database->uuidsIn(table: 'nc_openregister_table_66_228'),
			'a pair with no target table keeps its rows'
		);
		$this->assertFalse(
			$database->hasTable(table: 'nc_openregister_table_517_228'),
			'the step must NOT create the missing target table'
		);
		$this->assertStringContainsString('1 pair(s) skipped', implode(' ', $result['output']));
	}//end testAMissingTargetShardTableIsSkippedNotCreated()

	/**
	 * PROPERTY 7 — with no target register at all, nothing moves and it says why.
	 *
	 * @return void
	 */
	public function testWithoutTheTargetRegisterNothingMoves(): void {
		$registers = self::IDS;
		unset($registers['filinq']);
		$database = $this->oneDossierPair();
		$database->setRegisters(registers: $registers);

		$result = $this->runStep(database: $database);

		$this->assertSame(['u1', 'u2'], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertStringContainsString('does not exist yet; nothing moved', implode(' ', $result['output']));
	}//end testWithoutTheTargetRegisterNothingMoves()

	/**
	 * A source column the target lacks refuses the pair rather than dropping it.
	 *
	 * @return void
	 */
	public function testAWiderSourceTableRefusesThePair(): void {
		$database = $this->oneDossierPair();
		$database->addTable(
			name: 'nc_openregister_table_66_227',
			columns: ['_id', '_uuid', '_register', '_schema', 'name', 'checked_on'],
			rows: $this->rowsFor(uuids: ['u1'], register: '66')
		);

		$result = $this->runStep(database: $database);

		$this->assertSame(['u1'], $database->uuidsIn(table: 'nc_openregister_table_66_227'));
		$this->assertSame([], $database->uuidsIn(table: 'nc_openregister_table_517_227'));
		$this->assertStringContainsString('column(s) the target does not', implode(' ', $result['warnings']));
	}//end testAWiderSourceTableRefusesThePair()

	/**
	 * All five source registers are visited, not just the first one.
	 *
	 * Consolidating four of five is worse than either end: `templateVersion`
	 * objects reference `template` objects, `signerRecord` rows reference
	 * `signingRequest` rows, and a half-migrated install has those references
	 * crossing a register boundary the app no longer addresses.
	 *
	 * @return void
	 */
	public function testEveryOneOfTheFiveRegistersIsVisited(): void {
		$columns = ['_id', '_uuid', '_register', '_schema', 'name'];
		$tables = [];
		$pairs = [3 => 11, 4 => 15, 5 => 12, 6 => 5023, 66 => 227];

		foreach ($pairs as $registerId => $schemaId) {
			$tables['oc_openregister_table_' . $registerId . '_' . $schemaId] = [
				'columns' => $columns,
				'rows' => $this->rowsFor(uuids: ['u-' . $registerId], register: (string)$registerId),
			];
			$tables['oc_openregister_table_517_' . $schemaId] = ['columns' => $columns, 'rows' => []];
		}

		$database = new ShardDatabase(registers: self::IDS, tables: $tables);

		$result = $this->runStep(database: $database);

		foreach ($pairs as $registerId => $schemaId) {
			$this->assertSame(
				[],
				$database->uuidsIn(table: 'oc_openregister_table_' . $registerId . '_' . $schemaId),
				'register ' . $registerId . ' must be emptied too'
			);
			$this->assertSame(
				['u-' . $registerId],
				$database->uuidsIn(table: 'oc_openregister_table_517_' . $schemaId)
			);
		}

		$this->assertStringContainsString('5 object(s) moved into filinq', implode(' ', $result['output']));
	}//end testEveryOneOfTheFiveRegistersIsVisited()
}//end class

/**
 * An in-memory stand-in for the OpenRegister shard tables.
 *
 * WHY A FAKE AND NOT A MOCK. ConsolidateRegisters is a data migration: what
 * matters is not which statements it issues but where the rows END UP, and
 * above all where they end up when something fails halfway. A mock can only
 * assert that a call happened; this fake actually applies the statements to
 * described tables, so a test can run the step and then ask what the database
 * HOLDS -- including in the cases where the answer must be "exactly what it
 * held before".
 *
 * It understands only the small, closed set of statements the step issues, and
 * it is deliberately strict about that: an unrecognised statement returns
 * nothing rather than quietly succeeding, so a future change to the step's SQL
 * surfaces as a failing test instead of a fake that agrees with everything.
 *
 * It lives in this file rather than its own because the test namespace's psr-4
 * mapping does not resolve `tests/unit/` (it maps `OCA\Filinq\Tests\` to
 * `tests/`, and PHPUnit loads the test files by PATH). A helper in a separate
 * file is simply never autoloaded -- the same reason MigrateAppConfigKeysTest
 * carries its own AppConfigStore inline.
 */
/**
 * A described set of shard tables that answers the step's statements.
 */
final class ShardDatabase {

	/**
	 * When true, an INSERT is accepted and its rows are dropped on the floor.
	 *
	 * @var bool
	 */
	private bool $swallowInserts = false;

	/**
	 * Constructor.
	 *
	 * @param array<string, int> $registers Register slug => id.
	 * @param array<string, array{columns: array<int, string>, rows: array<int, array<string, mixed>>}> $tables
	 *                                                                                                          The described tables.
	 * @param string|null $failOn A SQL fragment whose statements throw, or null.
	 *                            An EMPTY STRING matches every statement, which is how the "totally broken
	 *                            database" case is described.
	 *
	 * @return void
	 */
	public function __construct(
		private array $registers = [],
		private array $tables = [],
		private ?string $failOn = null,
	) {
	}//end __construct()

	/**
	 * Replace the register table's contents.
	 *
	 * @param array<string, int> $registers Register slug => id.
	 *
	 * @return void
	 */
	public function setRegisters(array $registers): void {
		$this->registers = $registers;
	}//end setRegisters()

	/**
	 * Add or replace a described table.
	 *
	 * @param string $name Table name.
	 * @param array<int, string> $columns Column names.
	 * @param array<int, array<string, mixed>> $rows Initial rows.
	 *
	 * @return void
	 */
	public function addTable(string $name, array $columns, array $rows = []): void {
		$this->tables[$name] = ['columns' => $columns, 'rows' => $rows];
	}//end addTable()

	/**
	 * Make every statement containing this fragment throw.
	 *
	 * @param string $fragment The SQL fragment.
	 *
	 * @return void
	 */
	public function failOn(string $fragment): void {
		$this->failOn = $fragment;
	}//end failOn()

	/**
	 * Accept INSERTs and discard their rows.
	 *
	 * Models the failure a driver's affected-row count cannot see: the statement
	 * reports success and the rows are not there afterwards.
	 *
	 * @return void
	 */
	public function swallowInserts(): void {
		$this->swallowInserts = true;
	}//end swallowInserts()

	/**
	 * Whether a table exists.
	 *
	 * @param string $name Table name.
	 *
	 * @return bool
	 */
	public function hasTable(string $table): bool {
		return isset($this->tables[$table]);
	}//end hasTable()

	/**
	 * The uuids a table currently holds, in insertion order.
	 *
	 * @param string $name Table name.
	 *
	 * @return array<int, string>
	 */
	public function uuidsIn(string $table): array {
		return array_values(array_map(
			static fn (array $row): string => (string)$row['_uuid'],
			$this->tables[$table]['rows'] ?? []
		));
	}//end uuidsIn()

	/**
	 * The rows a table currently holds.
	 *
	 * @param string $name Table name.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function rowsIn(string $table): array {
		return array_values($this->tables[$table]['rows'] ?? []);
	}//end rowsIn()

	/**
	 * One row by uuid, or null.
	 *
	 * @param string $table Table name.
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null
	 */
	public function rowFor(string $table, string $uuid): ?array {
		foreach ($this->tables[$table]['rows'] ?? [] as $row) {
			if ((string)$row['_uuid'] === $uuid) {
				return $row;
			}
		}

		return null;
	}//end rowFor()

	/**
	 * Answer a SELECT.
	 *
	 * @param string $sql The statement.
	 * @param array<int, mixed> $params Bound parameters.
	 *
	 * @return array<int, array<string, mixed>> Result rows.
	 *
	 * @throws \RuntimeException When the statement was configured to fail.
	 */
	public function query(string $sql, array $params = []): array {
		$this->guard(sql: $sql);

		if (str_contains($sql, 'openregister_registers') === true) {
			return $this->registerRows(params: $params);
		}

		if (str_contains($sql, 'information_schema.tables') === true) {
			return array_map(
				static fn (string $name): array => ['table_name' => $name],
				array_keys($this->tables)
			);
		}

		if (str_contains($sql, 'information_schema.columns') === true) {
			$table = (string)($params[0] ?? '');
			return array_map(
				static fn (string $column): array => ['column_name' => $column],
				$this->tables[$table]['columns'] ?? []
			);
		}

		if (str_starts_with($sql, 'SELECT _uuid FROM ') === true) {
			return $this->selectUuids(sql: $sql, params: $params);
		}

		return [];
	}//end query()

	/**
	 * Apply an INSERT or a DELETE.
	 *
	 * @param string $sql The statement.
	 * @param array<int, mixed> $params Bound parameters.
	 *
	 * @return int Affected rows.
	 *
	 * @throws \RuntimeException When the statement was configured to fail.
	 */
	public function statement(string $sql, array $params = []): int {
		$this->guard(sql: $sql);

		if (str_starts_with($sql, 'INSERT INTO ') === true) {
			return $this->applyInsert(sql: $sql, params: $params);
		}

		if (str_starts_with($sql, 'DELETE FROM ') === true) {
			return $this->applyDelete(sql: $sql, params: $params);
		}

		return 0;
	}//end statement()

	/**
	 * Throw when this statement was configured to fail.
	 *
	 * @param string $sql The statement.
	 *
	 * @return void
	 *
	 * @throws \RuntimeException Always, when configured.
	 */
	private function guard(string $sql): void {
		if ($this->failOn === null) {
			return;
		}

		if ($this->failOn === '' || str_contains($sql, $this->failOn) === true) {
			throw new class('database unavailable') extends \RuntimeException implements \Throwable {
			};
		}
	}//end guard()

	/**
	 * The register rows matching the bound slugs.
	 *
	 * @param array<int, mixed> $params The slugs.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function registerRows(array $params): array {
		$rows = [];
		foreach ($this->registers as $slug => $id) {
			if (in_array($slug, $params, true) === true) {
				$rows[] = ['id' => $id, 'slug' => $slug];
			}
		}

		return $rows;
	}//end registerRows()

	/**
	 * Answer `SELECT _uuid FROM <table> [WHERE _uuid IN (...)]`.
	 *
	 * @param string $sql The statement.
	 * @param array<int, mixed> $params Bound uuids, when the IN clause is present.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function selectUuids(string $sql, array $params): array {
		$table = $this->tableIn(sql: $sql, after: 'SELECT _uuid FROM ');
		$scoped = (str_contains($sql, 'WHERE _uuid IN') === true);

		$rows = [];
		foreach ($this->tables[$table]['rows'] ?? [] as $row) {
			if ($scoped === true && in_array((string)$row['_uuid'], $params, true) === false) {
				continue;
			}

			$rows[] = ['_uuid' => $row['_uuid']];
		}

		return $rows;
	}//end selectUuids()

	/**
	 * Apply `INSERT INTO <target> (cols) SELECT ... FROM <source> WHERE _uuid IN (...)`.
	 *
	 * @param string $sql The statement.
	 * @param array<int, mixed> $params The `_register` value followed by the uuids.
	 *
	 * @return int Rows inserted.
	 */
	private function applyInsert(string $sql, array $params): int {
		$matches = [];
		if (preg_match('/^INSERT INTO `([^`]+)` \((.*?)\) SELECT .*? FROM `([^`]+)` WHERE/', $sql, $matches) !== 1) {
			return 0;
		}

		[, $target, $columnList, $source] = $matches;
		$columns = array_map('trim', explode(',', $columnList));

		$register = (string)array_shift($params);
		$uuids = array_map(static fn (mixed $value): string => (string)$value, $params);

		$inserted = 0;
		foreach ($this->tables[$source]['rows'] ?? [] as $row) {
			if (in_array((string)$row['_uuid'], $uuids, true) === false) {
				continue;
			}

			$inserted++;
			if ($this->swallowInserts === true) {
				continue;
			}

			$new = [];
			foreach ($columns as $column) {
				$new[$column] = ($column === '_register' ? $register : ($row[$column] ?? null));
			}

			$this->tables[$target]['rows'][] = $new;
		}

		return $inserted;
	}//end applyInsert()

	/**
	 * Apply `DELETE FROM <table> WHERE _uuid IN (...)`.
	 *
	 * @param string $sql The statement.
	 * @param array<int, mixed> $params The uuids.
	 *
	 * @return int Rows deleted.
	 */
	private function applyDelete(string $sql, array $params): int {
		$table = $this->tableIn(sql: $sql, after: 'DELETE FROM ');
		$uuids = array_map(static fn (mixed $value): string => (string)$value, $params);

		$kept = [];
		$deleted = 0;
		foreach ($this->tables[$table]['rows'] ?? [] as $row) {
			if (in_array((string)$row['_uuid'], $uuids, true) === true) {
				$deleted++;
				continue;
			}

			$kept[] = $row;
		}

		$this->tables[$table]['rows'] = $kept;

		return $deleted;
	}//end applyDelete()

	/**
	 * Pull the back-quoted table name that follows a clause.
	 *
	 * @param string $sql The statement.
	 * @param string $after The clause the name follows.
	 *
	 * @return string The table name, or an empty string.
	 */
	private function tableIn(string $sql, string $after): string {
		$matches = [];
		if (preg_match('/' . preg_quote($after, '/') . '`([^`]+)`/', $sql, $matches) !== 1) {
			return '';
		}

		return $matches[1];
	}//end tableIn()
}//end class
