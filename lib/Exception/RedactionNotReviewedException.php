<?php

/**
 * Thrown when a redacted copy is asked for before a person has checked it.
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
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * The review gate refused.
 */
class RedactionNotReviewedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $reason      Why it was refused, one of the gate's reasons.
	 * @param string $message     What the operator should do about it.
	 * @param string $document    The document that was refused.
	 * @param string $checkedRun  The run the existing mark covers, if any.
	 * @param string $currentRun  The run that would have been published.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
		string $message,
		private readonly string $document = '',
		private readonly string $checkedRun = '',
		private readonly string $currentRun = '',
	) {
		parent::__construct(message: $message);

	}//end __construct()

	/**
	 * Why it was refused.
	 *
	 * @return string The reason.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()

	/**
	 * The document that was refused.
	 *
	 * @return string The document.
	 */
	public function getDocument(): string {
		return $this->document;

	}//end getDocument()

	/**
	 * The run an existing mark covers.
	 *
	 * @return string The run.
	 */
	public function getCheckedRun(): string {
		return $this->checkedRun;

	}//end getCheckedRun()

	/**
	 * The run that would have been published.
	 *
	 * @return string The run.
	 */
	public function getCurrentRun(): string {
		return $this->currentRun;

	}//end getCurrentRun()
}//end class
