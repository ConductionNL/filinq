<?php

/**
 * Contract of ScanBatchRepository
 *
 * The public surface of the ScanBatchRepository service, so callers in other
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
 * Contract of ScanBatchRepository.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ScanBatchRepositoryInterface {
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
	public function save(array $batch, string $uuid = ''): array;

	/**
	 * Find one batch by its uuid.
	 *
	 * @param string $uuid The uuid.
	 *
	 * @return array<string, mixed>|null The batch, or null when there is none.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function find(string $uuid): ?array;

	/**
	 * Find the batch of one file, whatever state it is in.
	 *
	 * @param int $fileId The Nextcloud file id of the delivered PDF.
	 *
	 * @return array<string, mixed>|null The batch, or null when the file is not one.
	 *
	 * @spec openspec/changes/scan-intake-with-separator-sheets/specs/scan-intake/spec.md
	 */
	public function findByFile(int $fileId): ?array;
}//end interface
