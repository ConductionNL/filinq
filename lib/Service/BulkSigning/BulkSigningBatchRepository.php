<?php

/**
 * Bulk Signing Batch Repository
 *
 * Reads and writes `bulkSigningBatch` rows in the `filinq` register.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\BulkSigning
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\BulkSigning;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRepository;
use RuntimeException;
use Throwable;

/**
 * Stores and finds bulk-send batches.
 *
 * Reads go through OpenRegister's RBAC: the schema grants read to nobody, so
 * only the batch's owner (the initiator who created it) and admins see one.
 *
 * @category Service
 * @package  OCA\Filinq\Service\BulkSigning
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
 */
class BulkSigningBatchRepository {

	/**
	 * Schema slug.
	 */
	public const SCHEMA = 'bulkSigningBatch';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolves OpenRegister's ObjectService
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
	) {

	}//end __construct()

	/**
	 * Save a batch.
	 *
	 * @param array       $batch The batch fields
	 * @param string|null $uuid  The batch to overwrite, or null for a new one
	 *
	 * @return array The stored batch, with `uuid`
	 *
	 * @throws RuntimeException When OpenRegister refuses the write
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function save(array $batch, ?string $uuid = null): array {
		unset($batch['uuid']);
		$arguments = ['object' => $batch, 'register' => IntakeRepository::REGISTER, 'schema' => self::SCHEMA];
		if ($uuid !== null && $uuid !== '') {
			$arguments['uuid'] = $uuid;
		}

		try {
			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'Could not store the bulk send: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$normalised = $this->normalise(row: $stored);
		if (($normalised['uuid'] ?? '') === '' && $uuid !== null) {
			$normalised['uuid'] = $uuid;
		}

		return $normalised;

	}//end save()

	/**
	 * Find one batch.
	 *
	 * @param string $uuid The batch uuid
	 *
	 * @return array|null The batch, or null when absent
	 *
	 * @throws RuntimeException When the read fails
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function find(string $uuid): ?array {
		if ($uuid === '') {
			return null;
		}

		try {
			$object = $this->objectResolver->resolve()->find(id: $uuid, register: IntakeRepository::REGISTER, schema: self::SCHEMA);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The bulk send could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		if ($object === null) {
			return null;
		}

		return $this->normalise(row: $object);

	}//end find()

	/**
	 * List batches, newest first.
	 *
	 * @param string|null $createdBy Only this initiator's batches, or null for all
	 *
	 * @return list<array>
	 *
	 * @throws RuntimeException When the read fails
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-envelope-and-placement-data-live-in-the-signing-register-req-ddbsf-001
	 */
	public function list(?string $createdBy): array {
		$filters = [];
		if ($createdBy !== null) {
			$filters['createdBy'] = $createdBy;
		}

		try {
			// Slugs go through searchObjectsBySlug: searchObjects answers a slug with zero rows and no error.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: $filters
			);
		} catch (Throwable $e) {
			throw new RuntimeException(message: 'The bulk sends could not be read: ' . $e->getMessage(), code: 0, previous: $e);
		}

		$batches = [];
		foreach ((array) $results as $result) {
			$batch = $this->normalise(row: $result);
			// The filter is the search's; the owner check is ours.
			if ($createdBy === null || ($batch['createdBy'] ?? '') === $createdBy) {
				$batches[] = $batch;
			}
		}

		usort(
			$batches,
			static fn (array $a, array $b): int => strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''))
		);

		return $batches;

	}//end list()

	/**
	 * Flatten an OpenRegister object into its fields plus `uuid`.
	 *
	 * @param mixed $row An ObjectEntity or array
	 *
	 * @return array
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

		unset($fields['@self']);
		$fields['uuid'] = (string) ($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));

		return $fields;

	}//end normalise()
}//end class
