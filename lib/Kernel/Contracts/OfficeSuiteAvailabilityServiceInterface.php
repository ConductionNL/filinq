<?php

/**
 * Contract of OfficeSuiteAvailabilityService
 *
 * The public surface of the OfficeSuiteAvailabilityService service, so callers in
 * other components depend on this contract instead of the implementation.
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
 * Contract of OfficeSuiteAvailabilityService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface OfficeSuiteAvailabilityServiceInterface {
	/**
	 * Resolve WOPI availability for every known suite, cached.
	 *
	 * @return array{available: bool, suites: array<string, array{available: bool, configured: bool, reason: string, suite: string|null}>} The verdict.
	 *
	 * @spec openspec/changes/office-suite-portability
	 */
	public function resolve(): array;
}//end interface
