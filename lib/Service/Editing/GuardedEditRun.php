<?php

/**
 * Filinq GuardedEditRun
 *
 * The state of one guarded edit while GuardedWriter runs it: the edit and
 * the lock taken for it. Built and used inside GuardedWriter only.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use OCP\Files\Lock\LockContext;

/**
 * One guarded edit and the lock it runs under.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
final class GuardedEditRun {

	/**
	 * Constructor.
	 *
	 * @param GuardedEdit $edit The edit being run.
	 * @param LockContext $lock The lock taken for it.
	 */
	public function __construct(
		public readonly GuardedEdit $edit,
		public readonly LockContext $lock,
	) {
	}//end __construct()
}//end class
