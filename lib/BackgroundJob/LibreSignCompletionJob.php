<?php

/**
 * LibreSign Completion Job
 *
 * Every ten minutes, concludes the LibreSign requests LibreSign has finished.
 *
 * @category  BackgroundJob
 * @package   OCA\Filinq\BackgroundJob
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

namespace OCA\Filinq\BackgroundJob;

use OCA\Filinq\Service\Signing\LibreSignCompletion;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Polls LibreSign for the open requests Filinq handed it.
 *
 * LibreSign can POST a callback when a document is signed, but that needs
 * a public route with its own secret; polling reads the same state through
 * the API LibreSign already authenticates, and a signed document is at most
 * ten minutes late.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/libresign-signing-provider/specs/libresign-signing-provider/spec.md
 */
class LibreSignCompletionJob extends TimedJob {

	/**
	 * Constructor.
	 *
	 * @param ITimeFactory        $clock      The clock.
	 * @param LibreSignCompletion $completion Concludes the finished requests.
	 * @param LoggerInterface     $logger     Logs what the run did.
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $clock,
		private readonly LibreSignCompletion $completion,
		private readonly LoggerInterface $logger,
	) {
		parent::__construct(time: $clock);
		$this->setInterval(seconds: 600);

	}//end __construct()

	/**
	 * Conclude what LibreSign finished.
	 *
	 * @param mixed $argument Unused; the job takes no arguments.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/libresign-signing-provider/tasks.md#task-3.1
	 */
	protected function run(mixed $argument): void {
		$concluded = $this->completion->syncAll();
		if ($concluded > 0) {
			$this->logger->info('[LibreSignCompletionJob] Concluded ' . $concluded . ' LibreSign request(s)', ['argument' => $argument]);
		}

	}//end run()
}//end class
