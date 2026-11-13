<?php

/**
 * Huisstijl Write Gate
 *
 * The one door through which a house style managed by thematiq may be
 * written: the profile sync opens it for its own write, the write guard
 * refuses every other write while it is shut.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Tracks whether the current request is inside the profile sync's own write.
 *
 * The state is static on purpose: the sync and the write guard are resolved
 * separately (one as a service, one as an event listener), and the gate must
 * be the same for both within a request whatever the container shares.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class HuisstijlWriteGate {

	/**
	 * How many sync writes are in progress.
	 *
	 * @var int
	 */
	private static int $depth = 0;

	/**
	 * Run a write with the gate open, and shut it again whatever happens.
	 *
	 * @param callable $write The write.
	 *
	 * @return mixed What the write returns.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function during(callable $write): mixed {
		self::$depth++;
		try {
			return $write();
		} finally {
			self::$depth--;
		}

	}//end during()

	/**
	 * Whether a sync write is in progress.
	 *
	 * @return bool Whether the gate is open.
	 *
	 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.2
	 */
	public function isOpen(): bool {
		return self::$depth > 0;

	}//end isOpen()
}//end class
