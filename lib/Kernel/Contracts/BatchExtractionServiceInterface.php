<?php

/**
 * Contract of BatchExtractionService
 *
 * The public surface of the BatchExtractionService service, so callers in other
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

use Exception;

/**
 * Contract of BatchExtractionService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchExtractionServiceInterface {
	/**
	 * Extract and detect entities for the next pending file in a batch.
	 *
	 * Locates the first file in the batch that has not yet been extracted,
	 * runs extraction against it, and persists the updated batch state.
	 * When no pending files remain, the batch transitions to "review".
	 *
	 * @param string $batchId Identifier of the batch to advance.
	 *
	 * @return array Progress report for the caller, with shape:
	 *               {
	 *               batchStatus: string,
	 *               fileId?: int|string,
	 *               fileName?: string,
	 *               entityCount?: int,
	 *               error?: ?string,
	 *               filesExtracted: int,
	 *               totalFiles: int,
	 *               message?: string,
	 *               }
	 *
	 * @throws Exception When the batch cannot be found.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-sequential-batch-extraction
	 */
	public function extractNext(string $batchId): array;
}//end interface
