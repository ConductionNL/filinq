<?php

/**
 * ActingUser: background work done as the user who asked for it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Runs one piece of background work as a named user, then restores whoever was active.
 *
 * A background job has no user, so OpenRegister's RBAC refuses every write it
 * makes on a user's behalf ("User 'Anonymous' does not have permission to
 * 'create' objects", seen live on the mail archive import). The work runs
 * with the requester's own rights, the same rights that were checked when
 * they started it; nothing is widened. The same pattern as FlowOperationJob.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
final class ActingUser {

	/**
	 * Constructor.
	 *
	 * @param IUserManager $users   The users.
	 * @param IUserSession $session The session whose active user is set for the work.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
	 */
	public function __construct(
		private readonly IUserManager $users,
		private readonly IUserSession $session,
	) {
	}//end __construct()

	/**
	 * Run the work as the user; when there is no such user, run it as it is.
	 *
	 * @param string   $userId    The user who asked for the work; '' for none.
	 * @param callable $operation The work.
	 *
	 * @return mixed What the work returns.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
	 */
	public function run(string $userId, callable $operation): mixed {
		$user = null;
		if ($userId !== '') {
			$user = $this->users->get($userId);
		}

		if ($user === null) {
			return $operation();
		}

		$previous = $this->session->getUser();
		$this->session->setVolatileActiveUser($user);
		try {
			return $operation();
		} finally {
			$this->session->setVolatileActiveUser($previous);
		}
	}//end run()
}//end class
