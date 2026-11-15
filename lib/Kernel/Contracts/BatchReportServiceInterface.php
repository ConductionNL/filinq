<?php

/**
 * Contract of BatchReportService
 *
 * The public surface of the BatchReportService service, so callers in other
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
 * Contract of BatchReportService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface BatchReportServiceInterface {
	/**
	 * Generate a CSV report for a completed batch.
	 *
	 * @param string $batchId Identifier of the batch to report on.
	 *
	 * @return string CSV document (header row + one row per file).
	 *
	 * @throws Exception When the batch cannot be found or is not yet completed.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
	 */
	public function generateReport(string $batchId): string;
}//end interface
