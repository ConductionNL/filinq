<?php

/**
 * Where a person appears: the entity catalogue, read by declared identifier.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Exception\SubjectErasureRefusedException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

/**
 * A name is not an identity (design D2): the match is the request's declared
 * identifiers, each looked up EXACTLY in OpenRegister's entity catalogue, under
 * every entity type a person's identifier is stored as. Nothing is matched by
 * similarity, so a common surname is found only when the operator declared it,
 * and the preview is where they see what it caught.
 */
class SubjectErasureLocator {

	/**
	 * The catalogue types a person's identifier can be stored under
	 * (OpenRegister EntityRecognitionHandler, plus filinq's custom dictionary).
	 *
	 * @var string[]
	 */
	public const TYPES = ['PERSON', 'EMAIL', 'PHONE', 'ADDRESS', 'IBAN', 'SSN', 'CUSTOM_DICTIONARY'];

	private const ENTITY_MAPPER = 'OCA\OpenRegister\Db\GdprEntityMapper';

	private const RELATION_MAPPER = 'OCA\OpenRegister\Db\EntityRelationMapper';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container  Resolves OpenRegister's mappers.
	 * @param IAppManager        $appManager Whether OpenRegister is installed.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
	) {

	}//end __construct()

	/**
	 * The files the identifiers occur in, ordered by file id.
	 *
	 * @param array<int, string> $identifiers The request's declared identifiers.
	 *
	 * @return array<int, array{fileId: int, occurrences: int, values: array<int, string>}> Per file.
	 *
	 * @throws SubjectErasureRefusedException When the catalogue cannot be read: an erasure
	 *                                        that cannot see where the person is must not run.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
	 */
	public function locate(array $identifiers): array {
		try {
			if (in_array('openregister', $this->appManager->getInstalledApps(), true) === false) {
				throw new RuntimeException('OpenRegister is not installed.');
			}

			$entities = $this->container->get(self::ENTITY_MAPPER);
			$relations = $this->container->get(self::RELATION_MAPPER);
			$files = [];
			foreach ($this->clean(identifiers: $identifiers) as $identifier) {
				foreach (self::TYPES as $type) {
					$entity = $entities->findOneByValueAndType($identifier, $type);
					if ($entity === null) {
						continue;
					}

					foreach ($relations->findByEntityId((int) $entity->getId()) as $relation) {
						$files = $this->count(files: $files, fileId: (int) $relation->getFileId(), value: $identifier);
					}
				}
			}
		} catch (Throwable $e) {
			throw new SubjectErasureRefusedException(
				reason: SubjectErasureRefusedException::REASON_CATALOGUE_UNAVAILABLE,
				message: 'The entity catalogue could not be read: ' . $e->getMessage()
			);
		}//end try

		ksort($files);

		return array_values($files);

	}//end locate()

	/**
	 * The identifiers, trimmed, non-empty and unique.
	 *
	 * @param array<int, mixed> $identifiers The raw identifiers.
	 *
	 * @return array<int, string> The identifiers.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
	 */
	public function clean(array $identifiers): array {
		$clean = [];
		foreach ($identifiers as $identifier) {
			if (is_string($identifier) === true && trim($identifier) !== '') {
				$clean[trim($identifier)] = true;
			}
		}

		return array_keys($clean);

	}//end clean()

	/**
	 * Add one occurrence to the per-file tally. Relations on an object rather
	 * than a file are the record side, which is OpenRegister's (D10).
	 *
	 * @param array<int, array<string, mixed>> $files  The tally.
	 * @param int                              $fileId The file.
	 * @param string                           $value  The identifier found.
	 *
	 * @return array<int, array<string, mixed>> The tally.
	 */
	private function count(array $files, int $fileId, string $value): array {
		if ($fileId <= 0) {
			return $files;
		}

		$files[$fileId] ??= ['fileId' => $fileId, 'occurrences' => 0, 'values' => []];
		$files[$fileId]['occurrences']++;
		if (in_array($value, $files[$fileId]['values'], true) === false) {
			$files[$fileId]['values'][] = $value;
		}

		return $files;

	}//end count()
}//end class
