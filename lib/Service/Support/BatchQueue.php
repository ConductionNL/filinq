<?php

/**
 * Batch queue
 *
 * Where a batch keeps its state and how its jobs are queued.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\BatchStateService;

/**
 * Where a batch keeps its state and how its jobs are queued.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md
 */
final class BatchQueue {

	/**
	 * Constructor.
	 *
	 * @param BatchStateService $state Keeps the batch state
	 * @param JobQueueInterface $jobs  Queues the batch jobs
	 *
	 * @return void
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md
	 */
	public function __construct(
		public readonly BatchStateService $state,
		public readonly JobQueueInterface $jobs,
	) {

	}//end __construct()
}//end class
