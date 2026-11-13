<?php

/**
 * Detection Catalogue
 *
 * Reads a file's text chunks from OpenRegister and writes LLM-detected entities
 * into OpenRegister's entity catalogue.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

use DateTime;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * OpenRegister reads and writes for the LLM provider.
 *
 * Writes go through OpenRegister's `GdprEntityMapper` (one catalogue entity per
 * value and type, reused when it exists) and `EntityRelationMapper` (one
 * relation per occurrence, `detectionMethod = llm`), the same mappers and row
 * shape the custom-dictionary pass uses. A rerun first removes the file's
 * earlier `llm` relations, so detections never pile up.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class DetectionCatalogue {

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container  Container for lazy OpenRegister resolution.
	 * @param IAppManager        $appManager App manager (OpenRegister availability).
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.2
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
	) {
	}//end __construct()

	/**
	 * The file's text chunks as OpenRegister extracted them.
	 *
	 * @param int $fileId The file.
	 *
	 * @return list<array{id: int, text: string, startOffset: int}> The chunks with text.
	 *
	 * @throws RuntimeException When OpenRegister is not available.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.1
	 */
	public function chunks(int $fileId): array {
		$chunks = [];
		foreach ($this->service(className: 'OCA\OpenRegister\Files\Db\ChunkMapper')->findBySource('file', $fileId) as $chunk) {
			$text = (string) $chunk->getTextContent();
			if (trim($text) === '') {
				continue;
			}

			$chunks[] = ['id' => (int) $chunk->getId(), 'text' => $text, 'startOffset' => (int) $chunk->getStartOffset()];
		}

		return $chunks;
	}//end chunks()

	/**
	 * The current user's active OpenRegister organisation, or null when none resolves.
	 *
	 * Never throws: an organisation that cannot be resolved means the instance
	 * default, which is the default provider.
	 *
	 * @return string|null The organisation UUID.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
	 */
	public function activeOrganisationUuid(): ?string {
		try {
			$organisation = $this->service(className: 'OCA\OpenRegister\Service\OrganisationService')->getActiveOrganisation();
		} catch (Throwable) {
			return null;
		}

		if (is_object($organisation) === false || method_exists($organisation, 'getUuid') === false) {
			return null;
		}

		$uuid = (string) $organisation->getUuid();
		if ($uuid === '') {
			return null;
		}

		return $uuid;
	}//end activeOrganisationUuid()

	/**
	 * Whether the file already has relations the LLM provider wrote.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when at least one `llm` relation exists.
	 *
	 * @throws RuntimeException When OpenRegister is not available.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.1
	 */
	public function hasLlmRelations(int $fileId): bool {
		foreach ($this->service(className: 'OCA\OpenRegister\Db\EntityRelationMapper')->findByFileId($fileId) as $relation) {
			if ($relation->getDetectionMethod() === LlmEntityMapper::DETECTION_METHOD) {
				return true;
			}
		}

		return false;
	}//end hasLlmRelations()

	/**
	 * Replace the file's `llm` relations with these entities.
	 *
	 * @param int                              $fileId   The file.
	 * @param array<int, array<string, mixed>> $entities Mapped entities with absolute positions and a `chunkId`.
	 *
	 * @return int The number of relations written.
	 *
	 * @throws RuntimeException When OpenRegister is not available.
	 *
	 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-2.2
	 */
	public function replace(int $fileId, array $entities): int {
		$relationMapper = $this->service(className: 'OCA\OpenRegister\Db\EntityRelationMapper');
		$entityMapper   = $this->service(className: 'OCA\OpenRegister\Db\GdprEntityMapper');

		foreach ($relationMapper->findByFileId($fileId) as $relation) {
			if ($relation->getDetectionMethod() === LlmEntityMapper::DETECTION_METHOD) {
				$relationMapper->delete($relation);
			}
		}

		$rows = [];
		$ids  = [];
		foreach ($entities as $entity) {
			$key = $entity['type']."\0".$entity['value'];
			if (isset($ids[$key]) === false) {
				$ids[$key] = $this->entityId(entity: $entity, entityMapper: $entityMapper);
			}

			$rows[] = [
				'entityId'          => $ids[$key],
				'fileId'            => $fileId,
				'chunkId'           => $entity['chunkId'],
				'positionStart'     => $entity['positionStart'],
				'positionEnd'       => $entity['positionEnd'],
				'confidence'        => $entity['confidence'],
				'detectionMethod'   => LlmEntityMapper::DETECTION_METHOD,
				'anonymized'        => false,
				'skipAnonymization' => false,
				'createdAt'         => new DateTime(),
			];
		}

		if ($rows !== []) {
			$relationMapper->insertBatch(rows: $rows);
		}

		return count($rows);
	}//end replace()

	/**
	 * The catalogue id for an entity's value and type, created when absent.
	 *
	 * @param array<string, mixed> $entity       The mapped entity.
	 * @param mixed                $entityMapper OpenRegister's `GdprEntityMapper`.
	 *
	 * @return int The id.
	 */
	private function entityId(array $entity, mixed $entityMapper): int {
		$existing = $entityMapper->findOneByValueAndType(value: $entity['value'], type: $entity['type']);
		if ($existing !== null) {
			return (int) $existing->getId();
		}

		// By FQCN string, so this class loads without OpenRegister installed.
		$entityClass = 'OCA\OpenRegister\Db\GdprEntity';
		$now         = new DateTime();
		$record      = new $entityClass();
		$record->setUuid(Uuid::v4()->toRfc4122());
		$record->setValue($entity['value']);
		$record->setType($entity['type']);
		$record->setCategory($entity['category']);
		$record->setDetectedAt($now);
		$record->setUpdatedAt($now);

		return (int) $entityMapper->insert($record)->getId();
	}//end entityId()

	/**
	 * An OpenRegister service or mapper.
	 *
	 * @param string $className The class name.
	 *
	 * @return mixed The instance.
	 *
	 * @throws RuntimeException When OpenRegister is not installed.
	 */
	private function service(string $className): mixed {
		if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
			throw new RuntimeException('OpenRegister is not available.');
		}

		return $this->container->get($className);
	}//end service()
}//end class
