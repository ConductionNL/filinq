<?php

/**
 * Pseudonym Restore Refused Exception
 *
 * Thrown when a restore of a reversibly anonymised document is refused. The
 * reason says which refusal it was, so the controller can answer with the right
 * status and the audit entry can name it.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A restore that did not happen, and why.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymRestoreRefusedException extends RuntimeException {

	/**
	 * The caller is neither an admin nor in an allowed group.
	 *
	 * @var string
	 */
	public const REASON_NOT_ALLOWED = 'not_allowed';

	/**
	 * The allowed groups could not be read, so nobody is allowed.
	 *
	 * @var string
	 */
	public const REASON_CONFIG_UNREADABLE = 'config_unreadable';

	/**
	 * No anonymisation link, or the caller cannot open its anonymised copy.
	 *
	 * @var string
	 */
	public const REASON_NOT_FOUND = 'not_found';

	/**
	 * The run was irreversible: no key was kept.
	 *
	 * @var string
	 */
	public const REASON_NO_MAP = 'no_map';

	/**
	 * A key exists but could not be decrypted.
	 *
	 * @var string
	 */
	public const REASON_MAP_UNREADABLE = 'map_unreadable';

	/**
	 * The restored copy could not be written next to the anonymised one.
	 *
	 * @var string
	 */
	public const REASON_WRITE_FAILED = 'write_failed';

	/**
	 * The audit trail could not record the attempt, so it does not happen.
	 *
	 * @var string
	 */
	public const REASON_AUDIT_UNAVAILABLE = 'audit_unavailable';

	/**
	 * Constructor.
	 *
	 * @param string $reason  One of the REASON_* constants.
	 * @param string $message What went wrong, in English, for the log.
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
	 * Why the restore was refused.
	 *
	 * @return string One of the REASON_* constants.
	 */
	public function getReason(): string {
		return $this->reason;

	}//end getReason()
}//end class
