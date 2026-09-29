<?php
/**
 * Entity Search Refused Exception
 *
 * Thrown when an entity search or entity detail is refused. The reason says
 * which refusal it was, so the controller answers with the right status.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-entity-search/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * An entity lookup that did not happen, and why.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class EntitySearchRefusedException extends RuntimeException {

	/**
	 * The user is neither an admin nor in an entity search group.
	 *
	 * @var string
	 */
	public const REASON_NOT_ALLOWED = 'not_allowed';

	/**
	 * The entity search groups setting does not parse, so nobody may search.
	 *
	 * @var string
	 */
	public const REASON_CONFIG_UNREADABLE = 'config_unreadable';

	/**
	 * No entity with that uuid is visible to the caller.
	 *
	 * @var string
	 */
	public const REASON_NOT_FOUND = 'not_found';

	/**
	 * The search names neither a value nor a type.
	 *
	 * @var string
	 */
	public const REASON_INVALID = 'invalid';

	/**
	 * OpenRegister's entity catalogue cannot be read.
	 *
	 * @var string
	 */
	public const REASON_CATALOGUE_UNAVAILABLE = 'catalogue_unavailable';

	/**
	 * The processing log did not take the entry, so the lookup was not answered.
	 *
	 * @var string
	 */
	public const REASON_LOG_UNAVAILABLE = 'log_unavailable';

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
