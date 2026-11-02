<?php

/**
 * Contract of MergeJobRepository
 *
 * The public surface of the MergeJobRepository service, so callers in other
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

use OCA\Filinq\Exception\MergeJobStoreUnreadableException;
use RuntimeException;

/**
 * Contract of MergeJobRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface MergeJobRepositoryInterface {
	/**
	 * Create one job.
	 *
	 * @param array<string, mixed> $job The job to store.
	 *
	 * @return array<string, mixed> The stored job, with its uuid.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function create(array $job): array;

	/**
	 * Update one job.
	 *
	 * @param array<string, mixed> $job The job as it stands.
	 * @param string $uuid The job's uuid.
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function save(array $job, string $uuid): array;

	/**
	 * Find one job by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The job, or null when there is none.
	 *
	 * @throws MergeJobStoreUnreadableException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function find(string $uuid): ?array;

	/**
	 * Every job still queued, oldest first.
	 *
	 * @return array<int, array<string, mixed>> The queued jobs.
	 *
	 * @throws MergeJobStoreUnreadableException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function findQueued(): array;
}//end interface
