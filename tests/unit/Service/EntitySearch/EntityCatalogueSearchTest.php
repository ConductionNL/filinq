<?php

/**
 * The SQL EntityCatalogue::search builds, clause by clause.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\EntitySearch
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\EntitySearch;

use OCA\Filinq\Exception\EntitySearchRefusedException;
use OCA\Filinq\Service\EntitySearch\EntityCatalogue;
use OCP\App\IAppManager;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IFunctionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\QueryBuilder\IQueryFunction;
use OCP\IDBConnection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * Every query of a catalogue search, recorded as the builder calls it.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EntityCatalogueSearchTest extends TestCase {

	/**
	 * The builder calls, one list per query.
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $queries = [];

	/**
	 * A catalogue over recording query builders.
	 *
	 * @param int                               $total     What the count query answers.
	 * @param array<int, array<string, mixed>>  $rows      What the row query answers.
	 * @param array<int, array<string, mixed>>  $relations What the relation count query answers.
	 * @param bool                              $fails     Whether executing the first query throws.
	 *
	 * @return EntityCatalogue The catalogue.
	 */
	private function catalogue(int $total = 0, array $rows = [], array $relations = [], bool $fails = false): EntityCatalogue {
		$db = $this->createMock(IDBConnection::class);
		$db->method('escapeLikeParameter')->willReturnCallback(static fn (string $value): string => addcslashes($value, '%_\\'));
		$db->method('getQueryBuilder')->willReturnCallback(
			function () use ($total, $rows, $relations, $fails): IQueryBuilder {
				$index = count($this->queries);
				$this->queries[$index] = [];
				return $this->builder(index: $index, answers: [$total, $rows, $relations], fails: $fails);
			}
		);

		return new EntityCatalogue($db, $this->createMock(ContainerInterface::class), $this->createMock(IAppManager::class));

	}//end catalogue()

	/**
	 * One recording query builder.
	 *
	 * @param int               $index   Which query this is (0 count, 1 rows, 2 relations).
	 * @param array<int, mixed> $answers The answers of the three queries.
	 * @param bool              $fails   Whether executing it throws.
	 *
	 * @return IQueryBuilder The builder.
	 */
	private function builder(int $index, array $answers, bool $fails): IQueryBuilder {
		$qb = $this->createMock(IQueryBuilder::class);
		$record = function (string $call) use ($index): void {
			$this->queries[$index][] = $call;
		};
		$show = static fn (mixed $value): string => match (true) {
			is_string($value) === true, $value instanceof \Stringable => (string) $value,
			default => (string) json_encode($value),
		};

		foreach (['select', 'from', 'andWhere', 'where', 'orderBy', 'setMaxResults', 'setFirstResult', 'groupBy', 'selectAlias'] as $method) {
			$qb->method($method)->willReturnCallback(
				function (...$arguments) use ($qb, $record, $show, $method): IQueryBuilder {
					$record($method . '(' . implode(', ', array_map($show, $arguments)) . ')');
					return $qb;
				}
			);
		}

		$qb->method('createNamedParameter')->willReturnCallback(
			static fn (mixed $value, mixed $type = 2): string => ':' . $show($value) . ($type === 2 ? '' : '/' . $show($type))
		);
		$expr = $this->createMock(IExpressionBuilder::class);
		foreach (['in', 'iLike', 'eq'] as $method) {
			$expr->method($method)->willReturnCallback(static fn (string $column, string $value): string => $column . ' ' . $method . ' ' . $value);
		}

		$qb->method('expr')->willReturn($expr);
		$func = $this->createMock(IFunctionBuilder::class);
		$func->method('count')->willReturnCallback(
			function (...$arguments): IQueryFunction {
				$function = $this->createMock(IQueryFunction::class);
				$function->method('__toString')->willReturn('COUNT(' . implode(', ', array_filter($arguments, static fn (mixed $part): bool => $part !== '' && $part !== null)) . ')');
				return $function;
			}
		);
		$qb->method('func')->willReturn($func);

		$result = $this->createMock(IResult::class);
		$result->method('fetchOne')->willReturn((string) $answers[0]);
		$result->method('fetchAll')->willReturn($answers[1]);
		$relations = $answers[2];
		$result->method('fetch')->willReturnCallback(
			static function () use (&$relations): array|false {
				return array_shift($relations) ?? false;
			}
		);
		$result->method('closeCursor')->willReturnCallback(
			function () use ($record): bool {
				$record('closeCursor');
				return true;
			}
		);
		$qb->method('executeQuery')->willReturnCallback(
			function () use ($record, $result, $fails): IResult {
				$record('executeQuery');
				if ($fails === true) {
					throw new RuntimeException('db gone');
				}

				return $result;
			}
		);

		return $qb;

	}//end builder()

	/**
	 * A scoped search with every filter: the count, the page and the
	 * occurrences, and the answer built from them.
	 *
	 * @return void
	 */
	public function testAFullSearch(): void {
		$page = $this->catalogue(
			total: 12,
			rows: [
				['id' => '3', 'uuid' => 'e-3', 'type' => 'PERSON', 'value' => 'Jan de Vries', 'category' => 'name'],
				['id' => '5', 'uuid' => 'e-5', 'type' => 'PERSON', 'value' => 'Bo de Vries', 'category' => null],
			],
			relations: [['entity_id' => '3', 'occurrences' => '4']]
		)->search(scope: ['org-1', 'org-2'], query: 'de_vries%', type: 'PERSON', category: 'name', limit: 2, offset: 4);

		$this->assertSame(
			[
				'results' => [
					['uuid' => 'e-3', 'type' => 'PERSON', 'value' => 'Jan de Vries', 'category' => 'name', 'occurrences' => 4],
					['uuid' => 'e-5', 'type' => 'PERSON', 'value' => 'Bo de Vries', 'category' => '', 'occurrences' => 0],
				],
				'total' => 12,
			],
			$page
		);
		$filters = [
			'andWhere(e.organisation in :["org-1","org-2"]/' . IQueryBuilder::PARAM_STR_ARRAY . ')',
			'andWhere(e.value iLike :%de\_vries\%%)',
			'andWhere(e.type eq :PERSON)',
			'andWhere(e.category eq :name)',
		];
		$this->assertSame(
			[
				array_merge(['select(COUNT(*, total))', 'from(openregister_entities, e)'], $filters, ['executeQuery', 'closeCursor']),
				array_merge(
					['select(e.id, e.uuid, e.type, e.value, e.category)', 'from(openregister_entities, e)'],
					$filters,
					['orderBy(e.detected_at, DESC)', 'setMaxResults(2)', 'setFirstResult(4)', 'executeQuery', 'closeCursor']
				),
				[
					'select(r.entity_id)',
					'selectAlias(COUNT(*), occurrences)',
					'from(openregister_entity_relations, r)',
					'where(r.entity_id in :[3,5]/' . IQueryBuilder::PARAM_INT_ARRAY . ')',
					'groupBy(r.entity_id)',
					'executeQuery',
					'closeCursor',
				],
			],
			$this->queries
		);

	}//end testAFullSearch()

	/**
	 * Without a scope (an admin) and without filters no clause is added, and
	 * an empty page counts no occurrences.
	 *
	 * @return void
	 */
	public function testAnUnscopedSearchWithoutFilters(): void {
		$page = $this->catalogue(total: 0)->search(scope: null, query: '', type: '', category: '', limit: 25, offset: 0);

		$this->assertSame(['results' => [], 'total' => 0], $page);
		$this->assertSame(['select(COUNT(*, total))', 'from(openregister_entities, e)', 'executeQuery', 'closeCursor'], $this->queries[0]);
		$this->assertSame('orderBy(e.detected_at, DESC)', $this->queries[1][2]);
		$this->assertCount(2, $this->queries);

	}//end testAnUnscopedSearchWithoutFilters()

	/**
	 * An empty scope finds nothing without asking the database; a failing
	 * database is a refusal.
	 *
	 * @return void
	 */
	public function testAnEmptyScopeAndAFailingDatabase(): void {
		$this->assertSame(['results' => [], 'total' => 0], $this->catalogue()->search(scope: [], query: 'x', type: '', category: '', limit: 25, offset: 0));
		$this->assertSame([], $this->queries);

		try {
			$this->catalogue(fails: true)->search(scope: null, query: 'x', type: '', category: '', limit: 25, offset: 0);
			$this->fail('Expected a refusal');
		} catch (EntitySearchRefusedException $e) {
			$this->assertSame(EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE, $e->getReason());
			$this->assertSame('The entity catalogue could not be searched: db gone', $e->getMessage());
		}

	}//end testAnEmptyScopeAndAFailingDatabase()
}//end class

