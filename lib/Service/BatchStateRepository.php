<?php

/**
 * Batch State Repository
 *
 * OpenRegister persistence for anonymization batch state. The batch record is
 * stored as one object in the `document` register under the
 * `anonymizationBatch` schema, keyed by the batch UUID, so the multi-request
 * batch flow (create -> extract -> review -> anonymize -> report) survives on
 * an instance with no distributed cache configured.
 *
 * ⚠️ WHY THIS EXISTS. `ICacheFactory::createDistributed()` falls back to the
 * LOCAL cache class, and the local cache class defaults to
 * `OC\Memcache\NullCache` when `memcache.local` is unset — which is every
 * Nextcloud that has not been explicitly configured with APCu/Redis/Memcached.
 * `NullCache::set()` discards and `NullCache::get()` returns null, so a
 * cache-only batch store loses the record the instant the request that created
 * it ends: the folder-batch POST answers 200 with a batchId and the very next
 * call on that batchId answers 404. Per ADR-022/ADR-083 the fix is to consume
 * OpenRegister's object abstraction rather than to require every small install
 * to deploy a distributed cache.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * OpenRegister-backed store for anonymization batch records.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
 */
class BatchStateRepository {

	/**
	 * Register slug the batch schema lives in.
	 *
	 * `filinq`, not `document`: this app declares ONE register holding all 23
	 * schemas. The five it used to declare are retired.
	 *
	 * @var string
	 */
	public const REGISTER = 'filinq';

	/**
	 * Schema slug for a batch record.
	 *
	 * @var string
	 */
	public const SCHEMA = 'anonymizationBatch';

	/**
	 * Property name the per-file entries are stored under in OpenRegister.
	 *
	 * The in-process batch record calls this list `files`. It is stored as
	 * `documents` because `files` is also the name of an ObjectEntity column
	 * (surfaced under `@self.files` for attachments), and a data property that
	 * shadows a `@self` key is a collision waiting to happen. The mapping is
	 * confined to this class; nothing outside it sees `documents`.
	 *
	 * @var string
	 */
	private const DOCUMENTS_PROPERTY = 'documents';

	/**
	 * Constructor for BatchStateRepository
	 *
	 * The OpenRegister handle is a CONSTRUCTOR dependency (ADR-083): a bare
	 * `$container->get()` inside a method declares the dependency nowhere a
	 * reader — or a gate — can see it.
	 *
	 * @param OpenRegisterAvailabilityService $openRegister Owns the OpenRegister
	 *                                                      installed/version probe
	 *                                                      and the ObjectService handle.
	 * @param LoggerInterface $logger Structured logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OpenRegisterAvailabilityService $openRegister,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether OpenRegister is installed and new enough to hold batch state.
	 *
	 * @return bool True when batch state can be persisted.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function isAvailable(): bool {
		return $this->openRegister->isInstalled();
	}//end isAvailable()

	/**
	 * Load one batch record by its identifier.
	 *
	 * A miss is a miss: `ObjectService::find()` raises DoesNotExistException
	 * for an unknown identifier, which is translated to null here. Anything
	 * else — a missing schema, a broken register — is NOT swallowed, because a
	 * store that reports "no such batch" when it actually cannot reach its
	 * backing store is indistinguishable from a store that is working.
	 *
	 * @param string $batchId Batch identifier (also the OpenRegister object UUID).
	 *
	 * @return array<string, mixed>|null The batch record, or null when absent.
	 *
	 * @throws RuntimeException When OpenRegister is unavailable.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function find(string $batchId): ?array {
		if ($this->isAvailable() === false) {
			throw new RuntimeException(
				'Cannot read anonymization batch state: OpenRegister is not available.'
			);
		}

		$objectService = $this->openRegister->getObjectService();

		try {
			$object = $objectService->find(
				id: $batchId,
				register: self::REGISTER,
				schema: self::SCHEMA,
				_rbac: false,
				_multitenancy: false
			);
		} catch (DoesNotExistException $e) {
			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->fromStoredShape(stored: $this->toArray(value: $object));
	}//end find()

	/**
	 * Persist a batch record, creating it when it does not exist yet.
	 *
	 * The batch identifier is passed as the OpenRegister object UUID, so the
	 * write is an upsert keyed by the identifier the rest of the app already
	 * hands around; no secondary lookup is needed to update.
	 *
	 * @param string $batchId Batch identifier (used as the object UUID).
	 * @param array<string, mixed> $batch The full batch record.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister is unavailable or the write fails.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function save(string $batchId, array $batch): void {
		if ($this->isAvailable() === false) {
			// A write MUST NOT report success it did not achieve. Returning the
			// unsaved payload here is exactly the failure this class was written
			// to remove: every caller reads it as a stored batch.
			throw new RuntimeException(
				'Cannot persist anonymization batch state: OpenRegister is not available.'
			);
		}

		$objectService = $this->openRegister->getObjectService();

		try {
			$objectService->saveObject(
				object: $this->toStoredShape(batch: $batch),
				register: self::REGISTER,
				schema: self::SCHEMA,
				uuid: $batchId,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->error(
				'BatchStateRepository: failed to persist batch state',
				['batchId' => $batchId, 'error' => $e->getMessage()]
			);
			throw new RuntimeException(
				'Failed to persist anonymization batch state: ' . $e->getMessage(),
				0,
				$e
			);
		}//end try

	}//end save()

	/**
	 * Remove a batch record.
	 *
	 * Deleting a batch that is already gone is not an error — the caller's
	 * intent (no batch under this id) is satisfied either way.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function delete(string $batchId): void {
		if ($this->isAvailable() === false) {
			return;
		}

		try {
			$this->openRegister->getObjectService()->deleteObject(
				uuid: $batchId,
				register: self::REGISTER,
				schema: self::SCHEMA,
				_rbac: false,
				_multitenancy: false
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'BatchStateRepository: failed to delete batch state',
				['batchId' => $batchId, 'error' => $e->getMessage()]
			);
		}

	}//end delete()

	/**
	 * Translate the in-process batch record into its stored representation.
	 *
	 * @param array<string, mixed> $batch The in-process batch record.
	 *
	 * @return array<string, mixed> The payload handed to OpenRegister.
	 */
	private function toStoredShape(array $batch): array {
		$stored = $batch;
		unset($stored['files']);
		$stored[self::DOCUMENTS_PROPERTY] = array_values((array)($batch['files'] ?? []));

		return $stored;
	}//end toStoredShape()

	/**
	 * Translate a stored record back into the in-process batch record.
	 *
	 * OpenRegister decorates every serialised object with `@self` metadata and
	 * a top-level `id` mirroring the UUID. Neither belongs to the batch record,
	 * and leaving `id` in place would put a key in the batch that the batch
	 * never had.
	 *
	 * @param array<string, mixed> $stored The serialised OpenRegister object.
	 *
	 * @return array<string, mixed> The batch record.
	 */
	private function fromStoredShape(array $stored): array {
		$batch = $stored;
		unset($batch['@self'], $batch['id'], $batch[self::DOCUMENTS_PROPERTY]);
		$batch['files'] = array_values((array)($stored[self::DOCUMENTS_PROPERTY] ?? []));

		return $batch;
	}//end fromStoredShape()

	/**
	 * Normalise an OpenRegister result to a plain array.
	 *
	 * @param mixed $value An ObjectEntity, a plain array, or anything else.
	 *
	 * @return array<string, mixed> The array form.
	 */
	private function toArray(mixed $value): array {
		if (is_object($value) === true && method_exists($value, 'jsonSerialize') === true) {
			return (array)$value->jsonSerialize();
		}

		if (is_array($value) === true) {
			return $value;
		}

		return (array)$value;
	}//end toArray()
}//end class
