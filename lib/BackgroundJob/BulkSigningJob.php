<?php

/**
 * Bulk Signing Job
 *
 * Runs phase 2 of a confirmed bulk send.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\BulkSigning\BulkSigningRunner;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

/**
 * One queued job per confirmed batch; arguments `batchId` and `userId` (the initiator).
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
 */
class BulkSigningJob extends QueuedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory      $time   Time factory
	 * @param BulkSigningRunner $runner Creates the requests
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly BulkSigningRunner $runner,
	) {
		parent::__construct(time: $time);

	}//end __construct()

	/**
	 * Run the batch.
	 *
	 * @param mixed $argument ['batchId' => string, 'userId' => string]
	 *
	 * @return void
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-bulk-send-validates-first-then-creates-isolated-ordinary-requests-req-ddbsf-002
	 */
	protected function run($argument): void {
		$this->runner->run(batchId: (string) ($argument['batchId'] ?? ''), userId: (string) ($argument['userId'] ?? ''));

	}//end run()
}//end class
