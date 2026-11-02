<?php
/**
 * Subject Erasure Refused Exception
 *
 * Thrown when a step of a subject erasure is refused. The reason says which
 * refusal it was, so the controller answers with the right status and the
 * audit trail records the same word.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-erase-a-person-while-the-records-stay/tasks.md#task-4.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * An erasure step that did not happen, and why.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class SubjectErasureRefusedException extends RuntimeException {

	/**
	 * The caller is neither an admin nor in an erasure group.
	 *
	 * @var string
	 */
	public const REASON_NOT_ALLOWED = 'not_allowed';

	/**
	 * The erasure groups setting could not be read, so nobody may erase.
	 *
	 * @var string
	 */
	public const REASON_CONFIG_UNREADABLE = 'config_unreadable';

	/**
	 * No request under this id.
	 *
	 * @var string
	 */
	public const REASON_NOT_FOUND = 'not_found';

	/**
	 * The request lacks a subject, an identifier, a ground, or an exclusion reason.
	 *
	 * @var string
	 */
	public const REASON_INVALID = 'invalid';

	/**
	 * The step does not fit the request's state: a run before a preview, or a
	 * second run of a finished request.
	 *
	 * @var string
	 */
	public const REASON_WRONG_STATE = 'wrong_state';

	/**
	 * The entity catalogue could not be read, so nobody can say where the person is.
	 *
	 * @var string
	 */
	public const REASON_CATALOGUE_UNAVAILABLE = 'catalogue_unavailable';

	/**
	 * The audit trail did not take the entry, so the step was not done.
	 *
	 * @var string
	 */
	public const REASON_AUDIT_UNAVAILABLE = 'audit_unavailable';

	/**
	 * Constructor.
	 *
	 * @param string $reason  One of the REASON_* constants.
	 * @param string $message What happened, for the log.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly string $reason,
		string $message
	) {
		parent::__construct(message: $message);

	}//end __construct()

	/**
	 * The refusal reason.
	 *
	 * @return string One of the REASON_* constants.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()
}//end class
