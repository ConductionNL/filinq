<?php

/**
 * Contract of ContractDates
 *
 * The public surface of the ContractDates service, so callers in other
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
 * Contract of ContractDates.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ContractDatesInterface {
	/**
	 * The contract with its notice deadline filled in, when it was empty and
	 * both the end date and the notice period are known.
	 *
	 * A notice deadline somebody entered is never overwritten.
	 *
	 * @param array<string, mixed> $contract The contract fields.
	 *
	 * @return array<string, mixed> The contract, possibly with `noticeDeadline` set.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function withNoticeDeadline(array $contract): array;

	/**
	 * The end date minus the notice period, as Y-m-d, or null when either is
	 * missing or unreadable.
	 *
	 * @param string $endDate          The end date, Y-m-d.
	 * @param mixed  $noticePeriodDays The notice period in days.
	 *
	 * @return string|null The notice deadline.
	 *
	 * @spec openspec/changes/archive/2026-09-30-contract-lifecycle-management/tasks.md#2-1
	 */
	public function noticeDeadlineFor(string $endDate, mixed $noticePeriodDays): ?string;
}//end interface
