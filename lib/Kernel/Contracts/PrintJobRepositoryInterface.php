<?php

/**
 * Contract of PrintJobRepository
 *
 * The public surface of the PrintJobRepository service, so callers in other
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
 * Contract of PrintJobRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PrintJobRepositoryInterface {
	/**
	 * The schema slug of a print job.
	 */
	public const SCHEMA = 'printJob';

	/**
	 * Write one job, creating it when it has no uuid yet.
	 *
	 * @param array<string, mixed> $job  The job
	 * @param string|null          $uuid The uuid to write under, or null to let OpenRegister pick one
	 *
	 * @return array<string, mixed> The stored job, with its uuid.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function save(array $job, ?string $uuid = null): array;

	/**
	 * Find one job by its uuid.
	 *
	 * @param string $uuid The uuid
	 *
	 * @return array<string, mixed>|null The job, or null when there is none.
	 *
	 * @throws RuntimeException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function find(string $uuid): ?array;

	/**
	 * Every job one user sent, newest first.
	 *
	 * @param string $userId The user
	 *
	 * @return array<int, array<string, mixed>> The user's jobs.
	 *
	 * @throws RuntimeException When the read failed, as opposed to finding nothing.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.3
	 */
	public function findForUser(string $userId): array;
}//end interface
