<?php

/**
 * A document's checked mark was refused.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * Thrown when a document cannot be marked checked, with a machine-readable reason.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-per-document-checked-gate-req-ddarw-007
 */
class DocumentReviewRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $reason  Machine-readable reason, e.g. `not_extracted`.
	 * @param string $message What the reviewer should do about it.
	 * @param array<string, mixed> $details What the refusal is about, e.g. the undecided findings.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
		string $message,
		private readonly array $details = [],
	) {
		parent::__construct(message: $message);

	}//end __construct()

	/**
	 * The machine-readable reason.
	 *
	 * @return string The reason.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-2.3
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()

	/**
	 * What the refusal is about, for the answer's body; empty when there is nothing to add.
	 *
	 * @return array<string, mixed> The details.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function getDetails(): array {
		return $this->details;

	}//end getDetails()
}//end class
