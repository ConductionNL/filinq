<?php
/**
 * Filinq SigningCancellationRefusedException
 *
 * Raised when a signing request may not be withdrawn: the actor is not its
 * creator, or the request is already in a final state.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A refused withdrawal, carrying the HTTP status it answers with.
 *
 * The code is 403 for an actor who is not the creator (or a request that does
 * not exist: the two are one refusal so ids cannot be enumerated), and 409 for
 * a request whose state no longer allows a withdrawal.
 *
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */
final class SigningCancellationRefusedException extends RuntimeException {

	/**
	 * The status for an actor who is not the creator.
	 *
	 * @var int
	 */
	public const NOT_CREATOR = 403;

	/**
	 * The status for a request in a final state.
	 *
	 * @var int
	 */
	public const FINAL_STATE = 409;
}//end class
