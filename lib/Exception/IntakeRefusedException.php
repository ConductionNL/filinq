<?php

/**
 * Intake Refused Exception
 *
 * Raised when the intake inbox refuses an assign or a reject: the person may
 * not write the intake register, may not write the record they aimed at, the
 * document has already left the inbox, or the rejection carries no reason.
 *
 * It carries the HTTP status the refusal deserves, so the controller can answer
 * 403 for a right the person does not have and 400 for a request that could
 * never be right, without re-deciding what the service already decided.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;
use Throwable;

/**
 * A refusal from the intake inbox, with the status it deserves.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeRefusedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $message What was refused, in a sentence a clerk can read.
	 * @param int $status The HTTP status this refusal deserves.
	 * @param Throwable|null $previous The underlying failure, when there was one.
	 *
	 * @return void
	 */
	public function __construct(
		string $message,
		private readonly int $status = 403,
		?Throwable $previous = null,
	) {
		parent::__construct($message, 0, $previous);

	}//end __construct()

	/**
	 * The HTTP status this refusal deserves.
	 *
	 * @return int The status code.
	 */
	public function getStatus(): int {
		return $this->status;

	}//end getStatus()
}//end class
