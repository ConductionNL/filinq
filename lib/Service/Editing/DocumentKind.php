<?php

/**
 * Document Kind
 *
 * Which file extensions one codec edits, and how a refusal names them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Editing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use Closure;

/**
 * The extension predicate of one codec and the format list a refusal names.
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-1.2
 */
class DocumentKind {

	/**
	 * @param Closure $supports Predicate `fn (string $extension): bool` deciding whether the codec edits a file
	 * @param string  $formats  The human-readable format list named in the refusal when `$supports` says no
	 */
	public function __construct(
		public readonly Closure $supports,
		public readonly string $formats,
	) {
	}//end __construct()
}//end class
