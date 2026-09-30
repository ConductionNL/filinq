<?php

/**
 * Contract dates
 *
 * The one piece of date arithmetic a contract needs: the notice deadline is
 * the end date minus the notice period, filled in only when nobody entered one.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Contract
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Contract;

use DateTimeImmutable;

/**
 * Pure date rules of a contract.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Contract
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/contract-lifecycle-management/specs/contract-lifecycle-management/spec.md#requirement-contract-is-a-first-class-openregister-object-req-ddclm-001
 */
class ContractDates {

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
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function withNoticeDeadline(array $contract): array {
		if (trim((string) ($contract['noticeDeadline'] ?? '')) !== '') {
			return $contract;
		}

		$deadline = $this->noticeDeadlineFor(
			endDate: (string) ($contract['endDate'] ?? ''),
			noticePeriodDays: $contract['noticePeriodDays'] ?? null
		);
		if ($deadline !== null) {
			$contract['noticeDeadline'] = $deadline;
		}

		return $contract;

	}//end withNoticeDeadline()

	/**
	 * The end date minus the notice period, as Y-m-d, or null when either is
	 * missing or unreadable.
	 *
	 * @param string $endDate          The end date, Y-m-d.
	 * @param mixed  $noticePeriodDays The notice period in days.
	 *
	 * @return string|null The notice deadline.
	 *
	 * @spec openspec/changes/contract-lifecycle-management/tasks.md#2-1
	 */
	public function noticeDeadlineFor(string $endDate, mixed $noticePeriodDays): ?string {
		if (is_int($noticePeriodDays) === false && (is_string($noticePeriodDays) === false || ctype_digit($noticePeriodDays) === false)) {
			return null;
		}

		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $endDate, $parts) !== 1
			|| checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) === false
		) {
			return null;
		}

		return (new DateTimeImmutable($endDate))->modify('-' . (int) $noticePeriodDays . ' days')->format('Y-m-d');

	}//end noticeDeadlineFor()
}//end class
