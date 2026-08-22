<?php

/**
 * Custom Dictionary Repository
 *
 * The OpenRegister persistence primitives behind {@see CustomDictionaryService}:
 * slug-aware list, find, save and delete for the `customDictionary` /
 * `customDictionaryTerm` schemas, plus the serialisation of OpenRegister's
 * object shapes down to plain arrays. Extracted from CustomDictionaryService
 * so that service holds only the organisation gate, import parsing and CRUD
 * orchestration.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * OpenRegister persistence primitives for custom dictionaries and terms.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryRepository {

	/**
	 * Register slug both schemas live in.
	 *
	 * @var string
	 */
	public const REGISTER = 'document';

	/**
	 * Constructor.
	 *
	 * @param SettingsService $settingsService Provides OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Structured logger.
	 */
	public function __construct(
		private readonly SettingsService $settingsService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * List records by schema slug (register is always {@see REGISTER}) and
	 * serialise them to plain arrays.
	 *
	 * @param string $schema Schema slug.
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @throws Exception On query failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function listBySchema(string $schema): array {
		$objectService = $this->settingsService->getObjectService();
		// Slug-aware variant — OR's standard searchObjects requires numeric
		// register/schema ids and silently returns nothing otherwise.
		$results = $objectService->searchObjectsBySlug(
			registerSlug: self::REGISTER,
			schemaSlug: $schema,
			_rbac: false,
			_multitenancy: false
		);

		if (is_int($results) === true) {
			return [];
		}

		$rows = [];
		foreach ($results as $result) {
			$row = $this->toArrayOrNull(value: $result);
			if ($row !== null) {
				$rows[] = $row;
			}
		}

		return $rows;
	}//end listBySchema()

	/**
	 * Look up one record by UUID.
	 *
	 * @param string $schema Schema slug.
	 * @param string $uuid Record UUID.
	 *
	 * @return array<string, mixed>|null
	 *
	 * @throws Exception On lookup failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function findOne(string $schema, string $uuid): ?array {
		$objectService = $this->settingsService->getObjectService();
		$object = $objectService->find(
			id: $uuid,
			register: self::REGISTER,
			schema: $schema,
			_rbac: false,
			_multitenancy: false
		);

		if ($object === null) {
			return null;
		}

		return $this->toArrayOrCast(value: $object);
	}//end findOne()

	/**
	 * Persist a record via `ObjectService::saveObject`.
	 *
	 * @param string $schema Schema slug.
	 * @param array<string, mixed> $data Record payload.
	 * @param string|null $uuid Optional UUID for updates.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws Exception On write failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function save(string $schema, array $data, ?string $uuid = null): array {
		try {
			$objectService = $this->settingsService->getObjectService();
			$saved = $objectService->saveObject(
				object: $data,
				register: self::REGISTER,
				schema: $schema,
				uuid: $uuid,
				_rbac: false,
				_multitenancy: false
			);

			return $this->toArrayOrCast(value: $saved);
		} catch (Exception $e) {
			$this->logger->error(
				'CustomDictionaryRepository: save failed',
				['schema' => $schema, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);
			throw $e;
		}//end try

	}//end save()

	/**
	 * Delete a record via `ObjectService::deleteObject`.
	 *
	 * @param string $schema Schema slug.
	 * @param string $uuid Record UUID.
	 *
	 * @return void
	 *
	 * @throws Exception On deletion failure.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function delete(string $schema, string $uuid): void {
		$objectService = $this->settingsService->getObjectService();
		// NOTE: unlike find()/saveObject() (whose first param is $id / a
		// $uuid keyword respectively), ObjectService::deleteObject()'s first
		// parameter is named $uuid — verified against
		// OCA\OpenRegister\Service\ObjectService::deleteObject() at HEAD.
		$objectService->deleteObject(
			uuid: $uuid,
			register: self::REGISTER,
			schema: $schema,
			_rbac: false,
			_multitenancy: false
		);

	}//end delete()

	/**
	 * Normalise one OpenRegister result to a plain array, discarding values
	 * that are neither serialisable nor already an array.
	 *
	 * Used on list results, where an unrecognised row is skipped rather than
	 * coerced into a meaningless array.
	 *
	 * @param mixed $value An ObjectEntity, a plain array, or anything else.
	 *
	 * @return array<string, mixed>|null The array form, or null when unrecognised.
	 */
	private function toArrayOrNull(mixed $value): ?array {
		if (is_object($value) === true && method_exists($value, 'jsonSerialize') === true) {
			return $value->jsonSerialize();
		}

		if (is_array($value) === true) {
			return $value;
		}

		return null;
	}//end toArrayOrNull()

	/**
	 * Normalise one OpenRegister result to a plain array, casting anything
	 * unrecognised rather than discarding it.
	 *
	 * Used on single-record results, where the caller has already established
	 * that a record exists and expects an array back.
	 *
	 * @param mixed $value An ObjectEntity, a plain array, or anything else.
	 *
	 * @return array<string, mixed> The array form.
	 */
	private function toArrayOrCast(mixed $value): array {
		return ($this->toArrayOrNull(value: $value) ?? (array)$value);
	}//end toArrayOrCast()
}//end class
