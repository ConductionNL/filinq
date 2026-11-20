<?php

/**
 * Filinq Flow Run Status
 *
 * The states and reasons of a `flowOperationRun`, named once so the
 * operations, the job and the register agree on the spelling.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow;

/**
 * Run states and reasons.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.3
 */
final class FlowRunStatus {

	public const QUEUED = 'queued';

	public const RUNNING = 'running';

	public const SUCCEEDED = 'succeeded';

	public const FAILED = 'failed';

	public const SKIPPED = 'skipped';

	/**
	 * The candidate is a file a Filinq Flow operation wrote.
	 */
	public const REASON_OWN_OUTPUT = 'own_output';

	/**
	 * The file the run was queued for is gone or no longer a file.
	 */
	public const REASON_FILE_GONE = 'file_not_found';

	/**
	 * The file owner no longer exists.
	 */
	public const REASON_OWNER_GONE = 'owner_not_found';

	/**
	 * The operation's engine threw.
	 */
	public const REASON_ERROR = 'operation_error';

	/**
	 * Not instantiable: constants only.
	 *
	 * @return void
	 */
	private function __construct() {

	}//end __construct()
}//end class
