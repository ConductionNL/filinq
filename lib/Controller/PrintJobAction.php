<?php

/**
 * Print Job Action
 *
 * One action a caller asks for on an existing print job: which job, and the
 * label a failure is logged under.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller;

/**
 * Which print job an action targets, and the label its failure is logged under.
 *
 * @category Controller
 * @package  OCA\Filinq\Controller
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-5
 */
final class PrintJobAction {
	/**
	 * Constructor.
	 *
	 * @param string $jobId Job UUID
	 * @param string $label What the action is called in a failure log line.
	 */
	public function __construct(
		public readonly string $jobId,
		public readonly string $label,
	) {

	}//end __construct()
}//end class
