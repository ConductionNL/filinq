<?php

/**
 * Contract of PseudonymRestoreService
 *
 * The public surface of the PseudonymRestoreService service, so callers in other
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

use OCA\Filinq\Exception\PseudonymRestoreRefusedException;

/**
 * Contract of PseudonymRestoreService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PseudonymRestoreServiceInterface {
	/**
	 * Restore the original of one anonymised copy.
	 *
	 * The order is the control: the gate, then the copy the caller can open,
	 * then the key, then the audit entry that grants it, and only then the
	 * decrypted values touch a file or a response.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> `mode` copy (fileId, fileName, path, restored) or
	 *                              report (entries, reason).
	 *
	 * @throws PseudonymRestoreRefusedException When the restore is refused, for any reason.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function restore(string $linkId, string $userId): array;

	/**
	 * Whether this link kept a key and whether the caller may use it.
	 *
	 * @param int $anonymizedFileId The redacted copy's file id.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed>|null linkId, reversible, entryCount, mayRestore; null when
	 *                                   the caller cannot open the copy or it has no link.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
	 */
	public function status(int $anonymizedFileId, string $userId): ?array;
}//end interface
