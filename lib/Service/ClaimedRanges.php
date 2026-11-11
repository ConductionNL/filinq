<?php

/**
 * Claimed Ranges
 *
 * The text ranges a dictionary pass has already matched, so a later,
 * shorter term cannot claim an overlapping position.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The `[start, end)` ranges claimed so far in one pass.
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
final class ClaimedRanges {

	/**
	 * The `[start, end]` ranges claimed so far.
	 *
	 * @var array<int, array{0: int, 1: int}>
	 */
	private array $claimed = [];

	/**
	 * Claim `[start, end)` unless it overlaps a range claimed before.
	 *
	 * @param int $start Match start.
	 * @param int $end   Match end.
	 *
	 * @return bool True when the range was free and is now claimed.
	 *
	 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
	 */
	public function claim(int $start, int $end): bool {
		foreach ($this->claimed as [$claimedStart, $claimedEnd]) {
			if ($start < $claimedEnd && $end > $claimedStart) {
				return false;
			}
		}

		$this->claimed[] = [$start, $end];
		return true;
	}//end claim()
}//end class
