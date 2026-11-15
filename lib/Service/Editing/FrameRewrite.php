<?php

/**
 * Filinq FrameRewrite
 *
 * One frame edit inside a presentation page: which frame, in which region,
 * with which escaped text, and whether a frame was found and rewritten.
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
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

/**
 * A frame edit and its outcome, shared by the page and frame rewriters.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-edits-address-stable-anchors-never-positional-indexes
 */
final class FrameRewrite {

	/**
	 * Set true once a frame was rewritten.
	 *
	 * @var boolean
	 */
	public bool $rewritten = false;

	/**
	 * Constructor.
	 *
	 * @param string $shape   The frame name.
	 * @param string $region  Either `slide` or `notes`.
	 * @param string $escaped The escaped replacement text.
	 */
	public function __construct(
		public readonly string $shape,
		public readonly string $region,
		public readonly string $escaped,
	) {
	}//end __construct()
}//end class
