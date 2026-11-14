<?php

/**
 * Hunk Walk
 *
 * The position of one word diff while its ops are coalesced into hunks.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Comparison
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Comparison;

/**
 * The index of the next op and the left and right byte offsets reached so far.
 * Built and used only by WordDiffer::diff().
 *
 * @category Service
 * @package  OCA\Filinq\Service\Comparison
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
final class HunkWalk {

	/**
	 * Index of the next op to coalesce.
	 *
	 * @var integer
	 */
	public int $cursor = 0;

	/**
	 * Current left byte offset.
	 *
	 * @var integer
	 */
	public int $left = 0;

	/**
	 * Current right byte offset.
	 *
	 * @var integer
	 */
	public int $right = 0;

	/**
	 * Move both offsets past a hunk.
	 *
	 * @param int $left  Bytes the hunk consumed on the left
	 * @param int $right Bytes the hunk consumed on the right
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function advance(int $left, int $right): void {
		$this->left += $left;
		$this->right += $right;

	}//end advance()
}//end class
