<?php

/**
 * veraPDF could not give a verdict: not installed, switched off, too slow,
 * crashed, or answered with something that is not a report.
 *
 * Every caller treats this as "not validated", never as "compliant".
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A validator run that produced no verdict.
 */
class VeraPdfException extends RuntimeException {

	/**
	 * The validator is not installed or is switched off.
	 */
	public const REASON_UNAVAILABLE = 'unavailable';

	/**
	 * The run took longer than the configured budget.
	 */
	public const REASON_TIMEOUT = 'timeout';

	/**
	 * The run failed or its output was not a report.
	 */
	public const REASON_FAILED = 'failed';

	/**
	 * Constructor.
	 *
	 * @param string         $reason   One of the REASON_* constants.
	 * @param string         $message  What happened, in English, for the log.
	 * @param Throwable|null $previous The cause.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function __construct(
		private readonly string $reason,
		string $message,
		?Throwable $previous = null,
	) {
		parent::__construct(message: $message, code: 0, previous: $previous);

	}//end __construct()

	/**
	 * Why no verdict was given.
	 *
	 * @return string One of the REASON_* constants.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()
}//end class
