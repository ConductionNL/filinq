<?php

/**
 * Contract of PersonalDataReport
 *
 * The public surface of the PersonalDataReport service, so callers in other
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
 * Contract of PersonalDataReport.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PersonalDataReportInterface {
	/**
	 * The report for the signed-in reader.
	 *
	 * `available` is false when the installed OpenRegister cannot list the
	 * files that hold entities; the report is then empty rather than partial.
	 * `truncated` is true when the scan stopped at {@see self::MAX_SCANNED}
	 * candidates or {@see self::MAX_ROWS} rows.
	 *
	 * @return array<string, mixed> `results` (the rows {@see self::row()} builds), `available` and `truncated`.
	 *
	 * @spec openspec/changes/archive/2026-11-24-specify-capability-rows/specs/management-reports/spec.md#requirement-the-personal-data-report-lists-which-documents-hold-personal-data-req-scr-010
	 */
	public function forReader(): array;
}//end interface
