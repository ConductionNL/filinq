<?php

/**
 * Contract of PaperOriginalImport
 *
 * The public surface of the PaperOriginalImport service, so callers in other
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
 * Contract of PaperOriginalImport.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PaperOriginalImportInterface {
	/**
	 * Import a CSV box list into a project.
	 *
	 * The first line names the columns. A row without a box or a shelf mark,
	 * with a different number of cells than the header, or that OpenRegister
	 * refuses, is listed in `failures` with its line number and reason; the
	 * other rows are saved as `registered` originals of the project.
	 *
	 * @param string $projectId The digitisationProject the originals belong to.
	 * @param string $csv       The box list.
	 *
	 * @return array{imported: int, failures: array<int, array{line: int, reason: string}>}
	 *
	 * @throws InvalidArgumentException When the list has no header with the required columns.
	 *
	 * @spec openspec/changes/intake-paper-archive-digitisation/tasks.md#task-3.2
	 */
	public function import(string $projectId, string $csv): array;
}//end interface
