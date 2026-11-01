<?php

/**
 * Which entity a detail lookup returns, traced per organisation scope.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntityCatalogue;
use OCP\App\IAppManager;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Each lookup's queries and answer, compared with JSON recorded from the
 * implementation before find() was split.
 *
 * The acting user's scope is the list of their organisations (null for an
 * administrator); a row belonging to any other organisation must read as absent.
 *
 * @covers \OCA\Filinq\Service\EntitySearch\EntityCatalogue
 * @uses   \OCA\Filinq\Exception\EntitySearchRefusedException
 */
class EntityCatalogueFindTraceTest extends TestCase {

	/** @var array<int, string> Every query builder and mapper call, in order. */
	private array $calls = [];

	/**
	 * Lookups: the scope, the uuid, the stored row, its relations and what fails.
	 *
	 * @return array<string, array{0: array<int, mixed>|null, 1: string, 2: array<string, mixed>|false, 3: array<int, array<string, mixed>>, 4: string}>
	 */
	public static function lookups(): array {
		$row = ['id' => '41', 'uuid' => 'ent-1', 'type' => 'PERSON', 'value' => 'Jan', 'category' => 'name', 'organisation' => 'org-a'];
		$relation = ['fileId' => '7', 'objectId' => null, 'emailId' => '3', 'confidence' => '0.9', 'anonymized' => 1, 'detectionMethod' => 'presidio'];
		return [
			'an entity of the caller\'s organisation' => [['org-a', 'org-c'], 'ent-1', $row, [$relation, ['fileId' => 8] + $relation], ''],
			'an entity of another organisation' => [['org-b'], 'ent-1', $row, [$relation], ''],
			'an administrator sees any organisation' => [null, 'ent-1', $row, [$relation], ''],
			'an entity without an organisation, for a member' => [['org-a', ''], 'ent-1', ['organisation' => ''] + $row, [], ''],
			'an entity without an organisation, for an administrator' => [null, 'ent-1', ['organisation' => null] + $row, [], ''],
			'an entity with no organisation column' => [['org-a'], 'ent-1', array_diff_key($row, ['organisation' => true]), [], ''],
			'a numeric organisation is compared strictly' => [[1], 'ent-1', ['organisation' => '1'] + $row, [], ''],
			'a caller without organisations' => [[], 'ent-1', $row, [$relation], ''],
			'an empty uuid' => [['org-a'], '', $row, [$relation], ''],
			'an empty uuid for an administrator' => [null, '', $row, [$relation], ''],
			'no such entity' => [['org-a'], 'ent-9', false, [], ''],
			'no such entity for an administrator' => [null, 'ent-9', false, [], ''],
			'another entity reads its own relations' => [['org-a'], 'ent-2', ['id' => '42', 'uuid' => 'ent-2'] + $row, [$relation], ''],
			'no category column' => [['org-a'], 'ent-1', array_diff_key($row, ['category' => true]), [], ''],
			'no category' => [['org-a'], 'ent-1', ['category' => null] + $row, [], ''],
			'the database fails' => [['org-a'], 'ent-1', $row, [], 'db'],
			'the relations cannot be read' => [['org-a'], 'ent-1', $row, [], 'mapper'],
			'the relations cannot be read for another organisation' => [['org-b'], 'ent-1', $row, [], 'mapper'],
		];
	}//end lookups()

	/**
	 * The calls and the answer match the recorded ones.
	 *
	 * @param array<int, mixed>|null $scope The organisations, null for an administrator.
	 * @param string $uuid The uuid asked for.
	 * @param array<string, mixed>|false $row The stored row.
	 * @param array<int, array<string, mixed>> $relations The stored relations.
	 * @param string $fails What fails: '', 'db' or 'mapper'.
	 *
	 * @dataProvider lookups
	 *
	 * @return void
	 */
	public function testTraceMatchesTheRecordedOne(?array $scope, string $uuid, array|false $row, array $relations, string $fails): void {
		$catalogue = new EntityCatalogue(
			db: $this->db(row: $row, fails: ($fails === 'db')),
			container: $this->container(relations: $relations, fails: ($fails === 'mapper')),
			appManager: $this->createMock(IAppManager::class),
		);

		try {
			$answer = ['found' => $catalogue->find(scope: $scope, uuid: $uuid)];
		} catch (EntitySearchRefusedException $e) {
			$answer = ['refused' => [$e->getReason(), $e->getMessage()]];
		}

		$result = ['calls' => $this->calls, 'answer' => $answer];

		$path = __DIR__ . '/golden/entity-catalogue-find.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			if (is_dir(dirname($path)) === false) {
				mkdir(dirname($path));
			}

			$recorded[$name] = $result;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $result);
	}//end testTraceMatchesTheRecordedOne()

	/**
	 * A connection whose one query answers the given row.
	 *
	 * @param array<string, mixed>|false $row The row.
	 * @param bool $fails Whether executing throws.
	 *
	 * @return IDBConnection The connection.
	 */
	private function db(array|false $row, bool $fails): IDBConnection {
		$expr = $this->createMock(IExpressionBuilder::class);
		$expr->method('eq')->willReturnCallback(
			function (string $column, string $value): string {
				$this->calls[] = 'eq ' . $column . ' ' . $value;
				return $column . ' = ' . $value;
			}
		);

		$qb = $this->createMock(IQueryBuilder::class);
		$qb->method('expr')->willReturn($expr);
		$qb->method('createNamedParameter')->willReturnCallback(static fn (mixed $value): string => ':' . json_encode($value));
		foreach (['select', 'from', 'where', 'andWhere'] as $method) {
			$qb->method($method)->willReturnCallback(
				function (...$arguments) use ($qb, $method): IQueryBuilder {
					$this->calls[] = $method . ' ' . json_encode($arguments);
					return $qb;
				}
			);
		}

		$result = $this->createMock(IResult::class);
		$result->method('fetch')->willReturn($row);
		$result->method('closeCursor')->willReturnCallback(
			function (): bool {
				$this->calls[] = 'closeCursor';
				return true;
			}
		);
		$qb->method('executeQuery')->willReturnCallback(
			function () use ($result, $fails): IResult {
				$this->calls[] = 'executeQuery';
				if ($fails === true) {
					throw new RuntimeException('connection lost');
				}

				return $result;
			}
		);

		$db = $this->createMock(IDBConnection::class);
		$db->method('getQueryBuilder')->willReturn($qb);
		return $db;
	}//end db()

	/**
	 * A container whose relation mapper answers only for entity 41.
	 *
	 * @param array<int, array<string, mixed>> $relations The relations of entity 41.
	 * @param bool $fails Whether the mapper throws.
	 *
	 * @return ContainerInterface The container.
	 */
	private function container(array $relations, bool $fails): ContainerInterface {
		$mapper = new class($relations, $fails, $this) {
			/**
			 * @param array<int, array<string, mixed>> $relations The relations of entity 41.
			 * @param bool $fails Whether finding throws.
			 * @param EntityCatalogueFindTraceTest $test The test that records the calls.
			 */
			public function __construct(
				private array $relations,
				private bool $fails,
				private EntityCatalogueFindTraceTest $test,
			) {
			}

			/**
			 * The relations of one entity, as objects with getters.
			 *
			 * @param int $entityId The entity id.
			 *
			 * @return array<int, object>
			 */
			public function findByEntityId(int $entityId): array {
				$this->test->record(call: 'findByEntityId ' . $entityId);
				if ($this->fails === true) {
					throw new RuntimeException('mapper down');
				}

				if ($entityId !== 41) {
					return [];
				}

				return array_map(
					static fn (array $fields): object => new class($fields) {
						/**
						 * @param array<string, mixed> $fields The relation's columns.
						 */
						public function __construct(
							private array $fields,
						) {
						}

						/**
						 * Read a column through its getter name.
						 *
						 * @param string $name The getter.
						 * @param array<int, mixed> $arguments Unused.
						 *
						 * @return mixed
						 */
						public function __call(string $name, array $arguments): mixed {
							return ($this->fields[lcfirst(substr($name, 3))] ?? null);
						}
					},
					$this->relations
				);
			}
		};

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(
			function (string $id) use ($mapper): object {
				$this->calls[] = 'get ' . $id;
				return $mapper;
			}
		);
		return $container;
	}//end container()

	/**
	 * Record a call made by a double.
	 *
	 * @param string $call The call.
	 *
	 * @return void
	 */
	public function record(string $call): void {
		$this->calls[] = $call;
	}//end record()
}//end class
