<?php

/**
 * Merge Run
 *
 * The state of one merge while it runs.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * One running merge: its stored job, its plan and the pages added so far.
 *
 * @spec openspec/specs/document-merge/spec.md
 */
final class MergeRun {

	/**
	 * The pages added so far, cover included.
	 *
	 * @var integer
	 */
	public int $pages = 0;

	/**
	 * @param array<string, mixed> $job The stored job, updated as the merge runs.
	 * @param MergePlan $plan What is merged and where the result goes.
	 */
	public function __construct(
		public array $job,
		public readonly MergePlan $plan,
	) {
	}//end __construct()

	/**
	 * The job's id.
	 *
	 * @return string The uuid, or an empty string when the job has none.
	 *
	 * @spec openspec/specs/document-merge/spec.md
	 */
	public function uuid(): string {
		return (string)($this->job['uuid'] ?? '');
	}//end uuid()
}//end class
