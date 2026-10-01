<?php

/**
 * What classification reads from OpenRegister
 *
 * The entities OpenRegister already detected in a file, and the dossiers a
 * suggestion may point at. Reading only: classification writes nothing but
 * its own classificationResult record. When OpenRegister or its entity
 * mapper is not there, the entities are null ("not known yet"), which the
 * service turns into correspondentPending instead of a guessed name.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Classification;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\FileEntityStatsService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Detected entities and dossiers, read for a classification.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
 */
class ClassificationSources {

	/**
	 * Constructor.
	 *
	 * @param FileEntityStatsService        $entityStats    Gives OpenRegister's EntityRelationMapper when installed.
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService.
	 * @param LoggerInterface               $logger         Logs a source that cannot be read.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FileEntityStatsService $entityStats,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The entity rows detected in a file, or null when detection has not run or cannot be read.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<int, array<string, mixed>>|null The rows (entity_type, entity_value, position_start).
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function entitiesFor(int $fileId): ?array {
		$mapper = $this->entityStats->tryGetEntityRelationMapper();
		if ($mapper === null) {
			return null;
		}

		try {
			$rows = $mapper->findEntitiesForFile($fileId);
		} catch (Throwable $e) {
			$this->logger->warning('[Classification] detected entities cannot be read', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			return null;
		}

		// No rows is "detection has not run yet" as far as a correspondent goes.
		if ($rows === []) {
			return null;
		}

		return $rows;

	}//end entitiesFor()

	/**
	 * The dossiers a suggestion may point at (uuid and name).
	 *
	 * @return array<int, array{uuid: string, name: string}> The dossiers.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-2
	 */
	public function dossiers(): array {
		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers slugs with zero rows.
			$rows = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: 'filinq',
				schemaSlug: 'dossier',
				filters: [],
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->warning('[Classification] dossiers cannot be read', ['exception' => $e->getMessage()]);
			return [];
		}

		$dossiers = [];
		foreach ((array) $rows as $row) {
			if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
				$row = $row->jsonSerialize();
			}

			$fields = (array) ($row['object'] ?? $row);
			$uuid = (string) ($fields['uuid'] ?? ($row['uuid'] ?? ($row['@self']['id'] ?? ($row['id'] ?? ''))));
			$dossiers[] = ['uuid' => $uuid, 'name' => (string) ($fields['name'] ?? '')];
		}

		return $dossiers;

	}//end dossiers()
}//end class
