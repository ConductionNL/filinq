<?php

/**
 * Contract of BatchStateService
 *
 * The public surface of the BatchStateService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use RuntimeException;

/**
 * Contract of BatchStateService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchStateServiceInterface {
	/**
	 * Return the maximum number of files allowed in a single batch.
	 *
	 * @return int Configured or default maximum.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function getMaxFiles(): int;

	/**
	 * Create and persist a new batch record for a user.
	 *
	 * @param string $userId User identifier to associate with the batch.
	 * @param array<int, array<string, mixed>> $files Per-file entries to seed the batch with.
	 *
	 * @return array<string, mixed> The newly created batch record.
	 *
	 * @throws RuntimeException When the batch cannot be persisted.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-creation-via-multi-file-upload
	 */
	public function createBatch(string $userId, array $files): array;

	/**
	 * Load a batch record by ID and refresh its TTL.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return array<string, mixed>|null The decoded batch record, or null when missing or corrupt.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-status-endpoint
	 */
	public function getBatch(string $batchId): ?array;

	/**
	 * Persist an updated batch record.
	 *
	 * @param string $batchId Batch identifier.
	 * @param array<string, mixed> $batch Full batch record to store.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the batch cannot be persisted.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function updateBatch(string $batchId, array $batch): void;

	/**
	 * Remove a batch record from the store.
	 *
	 * @param string $batchId Batch identifier.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 *
	 * @orphaned-write-capability exclude completes the store's CRUD surface
	 * beside createBatch/getBatch/updateBatch. No caller has needed it yet —
	 * but a store that can create and update and not delete is the odd shape,
	 * and now that records outlive the request in OpenRegister rather than
	 * expiring with a cache TTL, the ability to remove one is the only way a
	 * batch ever leaves the store.
	 */
	public function deleteBatch(string $batchId): void;
}//end interface
