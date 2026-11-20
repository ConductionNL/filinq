<?php

/**
 * Batch QA Draw: the rate and seed one QA sample is drawn with.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-at-scale
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * The percentage of succeeded files to sample, held between 0 and 100, and
 * the seed that ranks them, so the same draw always picks the same files.
 *
 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
 */
final class BatchQaDraw {

	/**
	 * The rate, clamped to 0..100.
	 *
	 * @var integer
	 */
	public readonly int $rate;

	/**
	 * Constructor.
	 *
	 * @param int    $rate Percent of the succeeded files; below 0 reads as 0, above 100 as 100.
	 * @param string $seed The seed stored on the batch.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-sampling-qa-routed-to-human-review-req-ddras-007
	 */
	public function __construct(
		int $rate,
		public readonly string $seed,
	) {
		$this->rate = max(0, min(100, $rate));
	}//end __construct()
}//end class
