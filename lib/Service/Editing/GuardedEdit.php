<?php

/**
 * Filinq GuardedEdit
 *
 * One read-modify-write an agent asks GuardedWriter to run: the request
 * (who acts, and the version the caller read) and where it writes (the
 * source file and the resolved output mode).
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


/**
 * The request and the write target of one guarded edit.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
final class GuardedEdit {

	/**
	 * Constructor.
	 *
	 * @param AgentFileRequest $request The acting user and the version the caller read.
	 * @param WriteTarget      $target  The source file and the resolved output mode.
	 */
	public function __construct(
		public readonly AgentFileRequest $request,
		public readonly WriteTarget $target,
	) {
	}//end __construct()
}//end class
