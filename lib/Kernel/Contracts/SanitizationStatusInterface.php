<?php

/**
 * Contract of SanitizationStatus
 *
 * The public surface of the SanitizationStatus service, so callers in other
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

use InvalidArgumentException;

/**
 * Contract of SanitizationStatus.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface SanitizationStatusInterface {
	/**
	 * The status of a file the user can read.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The user.
	 *
	 * @return array{fileId: int, sanitized: bool, runs: list<array<string, mixed>>} Whether it is a sanitized derivative, and the runs on it.
	 *
	 * @throws InvalidArgumentException 404 when the user cannot read the file.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	public function forFile(int $fileId, string $userId): array;

	/**
	 * Whether the file is the derivative of a sanitization run.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when a record names it as the sanitized file.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-5
	 */
	public function isSanitized(int $fileId): bool;
}//end interface
