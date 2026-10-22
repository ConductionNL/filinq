<?php

/**
 * OpenRegister's detected-entity catalogue, read with OpenRegister's own tenant rule.
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
use OCP\App\IAppManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Reads `openregister_entities` and `openregister_entity_relations`, the
 * catalogue OpenRegister's detection pipeline fills. Filinq keeps no copy.
 *
 * Tenant scoping mirrors OpenRegister's GdprEntitiesController (#1825): an
 * admin sees every entity; anybody else only entities of an organisation
 * they belong to, and nothing when they belong to none. When the
 * organisations cannot be resolved the lookup is refused, never widened.
 */
class EntityCatalogue {

	/**
	 * The catalogue table.
	 *
	 * @var string
	 */
	private const ENTITIES = 'openregister_entities';

	/**
	 * The occurrence table.
	 *
	 * @var string
	 */
	private const RELATIONS = 'openregister_entity_relations';

	/**
	 * OpenRegister's organisation service.
	 *
	 * @var string
	 */
	private const ORGANISATIONS = 'OCA\OpenRegister\Service\OrganisationService';

	/**
	 * OpenRegister's relation mapper.
	 *
	 * @var string
	 */
	private const RELATION_MAPPER = 'OCA\OpenRegister\Db\EntityRelationMapper';

	/**
	 * Constructor.
	 *
	 * @param IDBConnection      $db         The database OpenRegister's tables live in.
	 * @param ContainerInterface $container  Resolves OpenRegister's services.
	 * @param IAppManager        $appManager Whether OpenRegister is installed.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IDBConnection $db,
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
	) {

	}//end __construct()

	/**
	 * The organisations the caller may see: null for everything (an admin),
	 * an empty list for nothing.
	 *
	 * @param bool $isAdmin Whether the caller is an admin.
	 *
	 * @return array<int, string>|null The organisation uuids.
	 *
	 * @throws EntitySearchRefusedException When OpenRegister is absent or the organisations cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	public function scope(bool $isAdmin): ?array {
		$this->assertAvailable();
		if ($isAdmin === true) {
			return null;
		}

		try {
			$organisations = $this->container->get(self::ORGANISATIONS)->getUserOrganisations();
		} catch (Throwable $e) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'The caller\'s organisations could not be read: ' . $e->getMessage()
			);
		}

		$uuids = [];
		foreach ((array) $organisations as $organisation) {
			if (is_object($organisation) === false || method_exists($organisation, 'getUuid') === false) {
				continue;
			}

			$uuid = (string) $organisation->getUuid();
			if ($uuid !== '') {
				$uuids[] = $uuid;
			}
		}

		return $uuids;

	}//end scope()

	/**
	 * Entities whose value contains the query, newest first.
	 *
	 * @param array<int, string>|null $scope    The organisations, null for all.
	 * @param string                  $query    Case-insensitive substring of the value, '' for any.
	 * @param string                  $type     Exact type, '' for any.
	 * @param string                  $category Exact category, '' for any.
	 * @param int                     $limit    Page size.
	 * @param int                     $offset   Page start.
	 *
	 * @return array{results: array<int, array<string, mixed>>, total: int} The page and the full count.
	 *
	 * @throws EntitySearchRefusedException When the catalogue cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.1
	 */
	public function search(?array $scope, string $query, string $type, string $category, int $limit, int $offset): array {
		if ($scope === []) {
			return ['results' => [], 'total' => 0];
		}

		try {
			$count = $this->db->getQueryBuilder();
			$count->select($count->func()->count('*', 'total'))->from(self::ENTITIES, 'e');
			$this->filter(qb: $count, scope: $scope, query: $query, type: $type, category: $category);
			$result = $count->executeQuery();
			$total = (int) $result->fetchOne();
			$result->closeCursor();

			$rows = $this->db->getQueryBuilder();
			$rows->select('e.id', 'e.uuid', 'e.type', 'e.value', 'e.category')->from(self::ENTITIES, 'e');
			$this->filter(qb: $rows, scope: $scope, query: $query, type: $type, category: $category);
			$rows->orderBy('e.detected_at', 'DESC')->setMaxResults($limit)->setFirstResult($offset);
			$result = $rows->executeQuery();
			$entities = $result->fetchAll();
			$result->closeCursor();
		} catch (Throwable $e) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'The entity catalogue could not be searched: ' . $e->getMessage()
			);
		}//end try

		$counts = $this->relationCounts(entityIds: array_map(static fn (array $row): int => (int) $row['id'], $entities));
		$results = [];
		foreach ($entities as $row) {
			$results[] = [
				'uuid' => (string) $row['uuid'],
				'type' => (string) $row['type'],
				'value' => (string) $row['value'],
				'category' => (string) ($row['category'] ?? ''),
				'occurrences' => ($counts[(int) $row['id']] ?? 0),
			];
		}

		return ['results' => $results, 'total' => $total];

	}//end search()

	/**
	 * One entity and its occurrences, or null when the caller may not see it.
	 *
	 * @param array<int, string>|null $scope The organisations, null for all.
	 * @param string                  $uuid  The entity uuid.
	 *
	 * @return array{entity: array<string, mixed>, relations: array<int, array<string, mixed>>}|null The entity.
	 *
	 * @throws EntitySearchRefusedException When the catalogue cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.2
	 */
	public function find(?array $scope, string $uuid): ?array {
		if ($scope === [] || $uuid === '') {
			return null;
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('e.id', 'e.uuid', 'e.type', 'e.value', 'e.category', 'e.organisation')->from(self::ENTITIES, 'e')
				->where($qb->expr()->eq('e.uuid', $qb->createNamedParameter($uuid)));
			$result = $qb->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			if (is_array($row) === false || $this->inScope(scope: $scope, organisation: (string) ($row['organisation'] ?? '')) === false) {
				return null;
			}

			$relations = [];
			foreach ($this->container->get(self::RELATION_MAPPER)->findByEntityId((int) $row['id']) as $relation) {
				$relations[] = [
					'fileId' => (int) $relation->getFileId(),
					'objectId' => (int) $relation->getObjectId(),
					'emailId' => (int) $relation->getEmailId(),
					'confidence' => (float) $relation->getConfidence(),
					'anonymized' => (bool) $relation->getAnonymized(),
					'detectionMethod' => (string) $relation->getDetectionMethod(),
				];
			}
		} catch (Throwable $e) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'The entity could not be read: ' . $e->getMessage()
			);
		}//end try

		return [
			'entity' => [
				'uuid' => (string) $row['uuid'],
				'type' => (string) $row['type'],
				'value' => (string) $row['value'],
				'category' => (string) ($row['category'] ?? ''),
			],
			'relations' => $relations,
		];

	}//end find()

	/**
	 * Refuse when OpenRegister is not installed.
	 *
	 * @return void
	 *
	 * @throws EntitySearchRefusedException When it is not.
	 */
	private function assertAvailable(): void {
		if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'OpenRegister is not installed, so there is no entity catalogue.'
			);
		}

	}//end assertAvailable()

	/**
	 * Whether an entity's organisation is in the caller's scope.
	 *
	 * @param array<int, string>|null $scope        The organisations, null for all.
	 * @param string                  $organisation The entity's organisation.
	 *
	 * @return bool True when visible.
	 */
	private function inScope(?array $scope, string $organisation): bool {
		if ($scope === null) {
			return true;
		}

		return ($organisation !== '' && in_array($organisation, $scope, true) === true);

	}//end inScope()

	/**
	 * Apply the tenant scope and the search filters.
	 *
	 * @param IQueryBuilder           $qb       The query.
	 * @param array<int, string>|null $scope    The organisations, null for all.
	 * @param string                  $query    Substring of the value.
	 * @param string                  $type     Exact type.
	 * @param string                  $category Exact category.
	 *
	 * @return void
	 */
	private function filter(IQueryBuilder $qb, ?array $scope, string $query, string $type, string $category): void {
		if ($scope !== null) {
			$qb->andWhere($qb->expr()->in('e.organisation', $qb->createNamedParameter($scope, IQueryBuilder::PARAM_STR_ARRAY)));
		}

		if ($query !== '') {
			$qb->andWhere($qb->expr()->iLike('e.value', $qb->createNamedParameter('%' . $this->db->escapeLikeParameter($query) . '%')));
		}

		if ($type !== '') {
			$qb->andWhere($qb->expr()->eq('e.type', $qb->createNamedParameter($type)));
		}

		if ($category !== '') {
			$qb->andWhere($qb->expr()->eq('e.category', $qb->createNamedParameter($category)));
		}

	}//end filter()

	/**
	 * Occurrences per entity, in one query.
	 *
	 * @param array<int, int> $entityIds The entity ids.
	 *
	 * @return array<int, int> Entity id => occurrences.
	 *
	 * @throws EntitySearchRefusedException When the relations cannot be counted.
	 */
	private function relationCounts(array $entityIds): array {
		if ($entityIds === []) {
			return [];
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('r.entity_id')->selectAlias($qb->func()->count('*'), 'occurrences')->from(self::RELATIONS, 'r')
				->where($qb->expr()->in('r.entity_id', $qb->createNamedParameter($entityIds, IQueryBuilder::PARAM_INT_ARRAY)))
				->groupBy('r.entity_id');
			$result = $qb->executeQuery();
			$counts = [];
			while (($row = $result->fetch()) !== false) {
				$counts[(int) $row['entity_id']] = (int) $row['occurrences'];
			}

			$result->closeCursor();
		} catch (Throwable $e) {
			throw new EntitySearchRefusedException(
				reason: EntitySearchRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'The occurrences could not be counted: ' . $e->getMessage()
			);
		}//end try

		return $counts;

	}//end relationCounts()
}//end class
