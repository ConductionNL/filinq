<?php
/**
 * Legal Hold Refused Exception
 *
 * Thrown when a hold case action is refused. The reason says which refusal it
 * was, so the controller can answer with the right status.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A hold case action that did not happen, and why.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class LegalHoldRefusedException extends RuntimeException {

	/**
	 * The caller is neither an admin nor in a hold authority group.
	 *
	 * @var string
	 */
	public const REASON_NOT_ALLOWED = 'not_allowed';

	/**
	 * The authority groups could not be read, so nobody may place or release.
	 *
	 * @var string
	 */
	public const REASON_CONFIG_UNREADABLE = 'config_unreadable';

	/**
	 * No hold case under this id.
	 *
	 * @var string
	 */
	public const REASON_NOT_FOUND = 'not_found';

	/**
	 * The request is missing something the case needs, such as a reason.
	 *
	 * @var string
	 */
	public const REASON_INVALID = 'invalid';

	/**
	 * The case is released, and released is final.
	 *
	 * @var string
	 */
	public const REASON_RELEASED = 'released';

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
