<?php

/**
 * Scan Batch Repository
 *
 * Reads and writes the `scanBatch` rows in the `filinq` register.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores and finds scanned batches.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
 */
class ScanBatchRepository {

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write one batch, creating it when its uuid is not known yet.
	 *
	 * @param array<string, mixed> $batch The batch.
	 * @param string $uuid The uuid to write under, or an empty string to create one.
	 *
	 * @return array<string, mixed> The stored batch.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function save(array $batch, string $uuid = ''): array {
		unset($batch['uuid']);

		try {
			$arguments = [
				'object' => $batch,
				'register' => IntakeRepository::REGISTER,
				'schema' => ScanBatchService::SCHEMA,
			];
			if ($uuid !== '') {
				$arguments['uuid'] = $uuid;
			}

			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the scan batch: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== '') {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Find one batch by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The batch, or null when there is none.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(
				id: $uuid,
				register: IntakeRepository::REGISTER,
				schema: ScanBatchService::SCHEMA
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[ScanBatchRepository] could not read one scan batch',
				context: ['file' => __FILE__, 'line' => __LINE__, 'uuid' => $uuid, 'error' => $e->getMessage()]
			);

			return null;
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * Find the batch of one file, whatever state it is in.
	 *
	 * @param int $fileId The Nextcloud file id of the delivered PDF.
	 *
	 * @return array<string, mixed>|null The batch, or null when the file is not one.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function findByFile(int $fileId): ?array {
		if ($fileId <= 0) {
			return null;
		}

		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` answers a slug with zero rows and no error, so
			// ScanIntakeController never found the existing batch and
			// re-splitting the same scanned PDF created a second one every
			// time instead of resuming.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: ScanBatchService::SCHEMA,
				filters: ['file' => $fileId]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[ScanBatchRepository] could not read the batch of one file',
				context: ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
			);

			return null;
		}

		if (is_array($results) === false || $results === []) {
			return null;
		}

		return $this->normalise(row: $results[0]);

	}//end findByFile()

	/**
	 * Read one OpenRegister row into the flat shape this app uses.
	 *
	 * @param mixed $row The row.
	 *
	 * @return array<string, mixed> The batch.
	 *
	 * @spec exclude Shape adapter over an OpenRegister response.
	 */
	private function normalise(mixed $row): array {
		$data = $row;
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$data = $row->jsonSerialize();
		}

		if (is_array($data) === false) {
			return [];
		}

		$fields = $data;
		if (isset($data['object']) === true && is_array($data['object']) === true) {
			$fields = $data['object'];
		}

		$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
