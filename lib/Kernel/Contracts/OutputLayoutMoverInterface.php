<?php

/**
 * Contract of OutputLayoutMover
 *
 * The public surface of the OutputLayoutMover service, so callers in other
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

/**
 * Contract of OutputLayoutMover.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OutputLayoutMoverInterface {
	/**
	 * Warning code recorded when the output stayed at its legacy path.
	 */
	public const MOVE_FAILED = 'MOVE_FAILED';

	/**
	 * Move one redacted output into the output subfolder.
	 *
	 * @param string $userId Owning user id.
	 * @param int $anonymizedFileId File id of the redacted output.
	 * @param string $legacyPath Path OpenRegister wrote the output to.
	 *
	 * @return array{path: string, warning: array{code: string, message: string}|null} Where the
	 *         output is now, plus a warning when it stayed at the legacy path.
	 *
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
	 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-4
	 */
	public function relocate(string $userId, int $anonymizedFileId, string $legacyPath): array;
}//end interface
