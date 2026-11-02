<?php

/**
 * Bulk Signing Row Validator
 *
 * Sorts parsed recipient rows into accepted signers and rejected rows with a reason.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\BulkSigning
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\BulkSigning;

use OCP\IUserManager;

/**
 * Decides per row whether it names somebody who can be sent a request.
 *
 * Reasons are stable codes the report shows translated: `no-recipient`,
 * `invalid-email`, `unknown-user` and `duplicate` (detail: the row it repeats).
 *
 * @category Service
 * @package  OCA\Filinq\Service\BulkSigning
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningRowValidator {

	/**
	 * Constructor.
	 *
	 * @param IUserManager $userManager Resolves user ids
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		private readonly IUserManager $userManager,
	) {

	}//end __construct()

	/**
	 * Validate the rows.
	 *
	 * @param list<array{row: int, userId: string, email: string, displayName: string}> $rows The parsed rows
	 *
	 * @return array{accepted: list<array{row: int, userId: string, email: string, displayName: string}>,
	 *     rejected: list<array{row: int, reason: string, detail: string}>}
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function validate(array $rows): array {
		$accepted = [];
		$rejected = [];
		$seen = [];
		foreach ($rows as $row) {
			[$reason, $detail] = $this->problemWith(row: $row);
			$key = $this->identity(row: $row);
			if ($reason === '' && isset($seen[$key]) === true) {
				[$reason, $detail] = ['duplicate', 'row ' . $seen[$key]];
			}

			if ($reason !== '') {
				$rejected[] = ['row' => $row['row'], 'reason' => $reason, 'detail' => $detail];
				continue;
			}

			$seen[$key] = $row['row'];
			$accepted[] = $row;
		}

		return ['accepted' => $accepted, 'rejected' => $rejected];

	}//end validate()

	/**
	 * What is wrong with one row on its own, if anything.
	 *
	 * @param array{row: int, userId: string, email: string, displayName: string} $row The row
	 *
	 * @return array{0: string, 1: string} Reason code ('' when fine) and detail
	 */
	private function problemWith(array $row): array {
		if ($row['userId'] === '' && $row['email'] === '') {
			return ['no-recipient', ''];
		}

		if ($row['email'] !== '' && filter_var($row['email'], FILTER_VALIDATE_EMAIL) === false) {
			return ['invalid-email', ''];
		}

		if ($row['userId'] !== '' && $this->userManager->userExists($row['userId']) === false) {
			return ['unknown-user', $row['userId']];
		}

		return ['', ''];

	}//end problemWith()

	/**
	 * The key two rows for the same person share.
	 *
	 * @param array{row: int, userId: string, email: string, displayName: string} $row The row
	 *
	 * @return string
	 */
	private function identity(array $row): string {
		if ($row['userId'] !== '') {
			return 'user:' . $row['userId'];
		}

		return 'mail:' . strtolower($row['email']);

	}//end identity()
}//end class
