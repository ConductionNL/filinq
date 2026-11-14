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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
		string $message,
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
}//end class
