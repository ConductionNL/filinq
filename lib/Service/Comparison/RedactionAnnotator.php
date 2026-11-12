<?php

/**
 * Redaction Annotator
 *
 * Annotates document-comparison diff hunks with redaction metadata from the
 * OpenRegister NER pipeline (EntityRelation rows for the source file) and
 * derives the redaction-completeness signal.
 *
 * Resolves the OpenRegister mapper lazily so the comparison degrades to a
 * plain diff (status 'unavailable') when OpenRegister is absent, rather than
 * failing.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Comparison;

use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Annotates diff hunks with redaction metadata and completeness.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
class RedactionAnnotator {
	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param IAppManager $appManager App manager (OpenRegister availability check).
	 * @param ContainerInterface $container DI container for lazy OR resolution.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {

	}//end __construct()

	/**
	 * Annotate change hunks with redaction metadata + completeness signal.
	 *
	 * Falls back to a plain diff (status 'unavailable') when OpenRegister is
	 * absent, and 'none' when no relations exist for the source file
	 * (unrelated pair).
	 *
	 * @param array<int, array<string, mixed>> $hunks The diff hunks.
	 * @param int $sourceFileId The source (left) file id.
	 *
	 * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function annotate(array $hunks, int $sourceFileId): array {
		$lookup = $this->lookupForFile(sourceFileId: $sourceFileId);
		if ($lookup === null) {
			return $this->plainDiff(hunks: $hunks, status: 'unavailable');
		}

		if (empty($lookup['relations']) === true) {
			// No anonymisation link for this file: plain diff, no annotation.
			return $this->plainDiff(hunks: $hunks, status: 'none');
		}

		return $this->annotateLinked(hunks: $hunks, lookup: $lookup);
	}//end annotate()

	/**
	 * The hunks as they are, with no annotation.
	 *
	 * @param array<int, array<string, mixed>> $hunks  The diff hunks.
	 * @param string                           $status Why there is no annotation: unavailable or none.
	 *
	 * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
	 */
	private function plainDiff(array $hunks, string $status): array {
		return [
			'hunks' => $hunks,
			'status' => $status,
			'unredactedEntities' => [],
		];
	}//end plainDiff()

	/**
	 * Annotate the hunks of a file that has entity relations.
	 *
	 * Inserted spans match a replacement key, removed spans an entity value;
	 * anonymise-set entities that matched zero hunks are the completeness signal.
	 *
	 * @param array<int, array<string, mixed>> $hunks  The diff hunks.
	 * @param array{relations: mixed, joined: mixed} $lookup The relations and joined entity rows.
	 *
	 * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
	 */
	private function annotateLinked(array $hunks, array $lookup): array {
		$entityMeta = $this->indexEntityMeta(joined: $lookup['joined']);
		$matchSet = new RedactionMatchSet(
			anonymiseSet: $this->buildAnonymiseSet(relations: $lookup['relations']),
			entityMeta: $entityMeta
		);

		return [
			'hunks' => $matchSet->annotateHunks(hunks: $hunks),
			'status' => 'annotated',
			'unredactedEntities' => $matchSet->unmatchedEntities(),
		];
	}//end annotateLinked()

	/**
	 * Read the relations for a source file, or null when OpenRegister or the lookup is unavailable.
	 *
	 * @param int $sourceFileId The source (left) file id.
	 *
	 * @return array{relations: mixed, joined: mixed}|null The rows, or null.
	 */
	private function lookupForFile(int $sourceFileId): ?array {
		$mapper = $this->resolveMapper();
		if ($mapper === null) {
			return null;
		}

		return $this->lookupRelations(mapper: $mapper, sourceFileId: $sourceFileId);
	}//end lookupForFile()

	/**
	 * Resolve the OpenRegister EntityRelationMapper, or null when unavailable.
	 *
	 * @return mixed The mapper or null.
	 */
	private function resolveMapper(): mixed {
		if ($this->isOpenRegisterAvailable() === false) {
			return null;
		}

		return $this->tryGetEntityRelationMapper();
	}//end resolveMapper()

	/**
	 * Read the entity relations and joined entity rows for a source file.
	 *
	 * @param mixed $mapper The OpenRegister EntityRelationMapper.
	 * @param int $sourceFileId The source (left) file id.
	 *
	 * @return array{relations: mixed, joined: mixed}|null The rows, or null when
	 *                                                     the lookup failed.
	 */
	private function lookupRelations(mixed $mapper, int $sourceFileId): ?array {
		try {
			return [
				'relations' => $mapper->findByFileId(fileId: $sourceFileId),
				'joined' => $mapper->findEntitiesForFile(fileId: $sourceFileId),
			];
		} catch (Throwable $e) {
			$this->logger->debug('Entity relation lookup failed', ['fileId' => $sourceFileId]);
			return null;
		}

	}//end lookupRelations()

	/**
	 * Index entity metadata (type + canonical value/name) by entity id.
	 *
	 * @param mixed $joined The joined entity rows.
	 *
	 * @return array<int, array{entityType:string, entityName:string, value:string}>
	 */
	private function indexEntityMeta(mixed $joined): array {
		$entityMeta = [];
		foreach ($joined as $row) {
			$eid = (int)($row['entity_id'] ?? 0);
			if ($eid === 0) {
				continue;
			}

			$entityMeta[$eid] = [
				'entityType' => (string)($row['entity_type'] ?? ''),
				'entityName' => (string)($row['entity_name'] ?? ($row['entity_value'] ?? '')),
				'value' => (string)($row['entity_value'] ?? ''),
			];
		}

		return $entityMeta;
	}//end indexEntityMeta()

	/**
	 * Build the anonymise set: non-skip relations with their replacement keys.
	 *
	 * @param mixed $relations The EntityRelation rows.
	 *
	 * @return array<int, array{replacement:string, matched:bool}>
	 */
	private function buildAnonymiseSet(mixed $relations): array {
		$anonymiseSet = [];
		foreach ($relations as $relation) {
			if ($this->isSkipFlagged(relation: $relation) === true) {
				continue;
			}

			$eid = (int)$relation->getEntityId();
			$anonymiseSet[$eid] = [
				'replacement' => (string)($relation->getAnonymizedValue() ?? ''),
				'matched' => false,
			];
		}

		return $anonymiseSet;
	}//end buildAnonymiseSet()

	/**
	 * Determine whether a relation is skip-flagged (operator-released override).
	 *
	 * @param mixed $relation The EntityRelation object.
	 *
	 * @return bool True when skip-flagged.
	 */
	private function isSkipFlagged(mixed $relation): bool {
		if (method_exists($relation, 'getSkipAnonymization') === true) {
			return ($relation->getSkipAnonymization() === true);
		}

		return false;
	}//end isSkipFlagged()

	/**
	 * Whether OpenRegister is installed/available.
	 *
	 * @return bool True when available.
	 */
	private function isOpenRegisterAvailable(): bool {
		return in_array('openregister', $this->appManager->getInstalledApps(), true);
	}//end isOpenRegisterAvailable()

	/**
	 * Try to resolve the OR EntityRelationMapper, returning null on failure.
	 *
	 * @return mixed The mapper or null.
	 */
	private function tryGetEntityRelationMapper(): mixed {
		try {
			return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
		} catch (Throwable $e) {
			$this->logger->debug('EntityRelationMapper unavailable: ' . $e->getMessage());
			return null;
		}

	}//end tryGetEntityRelationMapper()
}//end class
