<?php

/**
 * Thrown when an anonymisation run has no live entity detector behind it.
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
 * @spec openspec/changes/archive/2026-09-29-anonymisation-fails-closed-without-a-detector/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * The detector gate refused: a copy of the input filed as an anonymised
 * document is the failure it exists to prevent.
 */
class DetectionUnavailableException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $reason  One of AnonymiserBackendStateClient::REFUSE_*.
	 * @param string $message What went wrong, in English, for the log.
	 * @param string $backend The effective backend OpenRegister named, '' when unknown.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
		string $message,
		private readonly string $backend = '',
	) {
		parent::__construct(message: $message);

	}//end __construct()

	/**
	 * Why the run was refused.
	 *
	 * @return string The reason.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()

	/**
	 * The backend OpenRegister named, '' when the state was unknown.
	 *
	 * @return string The backend.
	 */
	public function getBackend(): string {
		return $this->backend;

	}//end getBackend()
}//end class
