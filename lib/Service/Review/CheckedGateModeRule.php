<?php

/**
 * The allowed values of the review gate mode setting.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use RuntimeException;

/**
 * Refuses a settings save whose `filinq.review.checked_gate` is not `enforced` or `advisory`.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-checked-gate-blocks-anonymize-commit-and-export-req-ddarw-008
 */
class CheckedGateModeRule {

	/**
	 * Refuse an unknown gate mode; a save without the key passes.
	 *
	 * Checked before anything is written, so a refused save changes nothing.
	 *
	 * @param array<string, mixed> $data The settings data to update.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With code 400 when the mode is not one of the two.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.4
	 */
	public function assertValid(array $data): void {
		if (array_key_exists(DocumentReviewService::GATE_KEY, $data) === false
			|| in_array($data[DocumentReviewService::GATE_KEY], [DocumentReviewService::MODE_ENFORCED, DocumentReviewService::MODE_ADVISORY], true) === true
		) {
			return;
		}

		throw new RuntimeException('The review gate mode must be "enforced" or "advisory".', 400);

	}//end assertValid()
}//end class
