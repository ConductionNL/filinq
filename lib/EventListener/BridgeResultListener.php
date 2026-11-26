<?php

/**
 * Bridge Result Listener
 *
 * Records the redacted output of a bridge document's dossier copy on its
 * external document (zgw-document-bridge REQ-DDZGW-003).
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent;
use OCA\Filinq\Service\BridgeService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Listens to `document.anonymised` (raised when an anonymisation run stored its
 * output and its link). Moves the external document
 * whose dossier copy was anonymised from `in_processing` to `processed` with
 * `resultFileRef` naming the output. The staged original is never read or
 * written here.
 *
 * Best-effort, like the anonymisation link: the output already exists, so a
 * failure is logged and never changes the run's answer.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
 */
class BridgeResultListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param BridgeService   $bridge Moves the external documents.
	 * @param LoggerInterface $logger Logs a failed record.
	 *
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
	 */
	public function __construct(
		private readonly BridgeService $bridge,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Record the output of an anonymisation that raised `document.anonymised`; any other event is ignored.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
	 */
	public function handle(Event $event): void {
		if ($event instanceof DocumentAnonymisedEvent === false) {
			return;
		}

		$payload = $event->getPayload();
		$this->record(
			sourceFileId: (int)($payload['sourceFileId'] ?? 0),
			resultFileId: (int)($payload['anonymisedFileId'] ?? 0)
		);
	}//end handle()

	/**
	 * Record one run's output on the bridge documents of its source file.
	 *
	 * @param int $sourceFileId The anonymised file.
	 * @param int $resultFileId The redacted output.
	 *
	 * @return int How many external documents were moved to processed.
	 *
	 * @spec openspec/changes/zgw-document-bridge/tasks.md#task-2.6
	 */
	public function record(int $sourceFileId, int $resultFileId): int {
		try {
			return count($this->bridge->processed(sourceFileId: $sourceFileId, resultFileId: $resultFileId));
		} catch (Throwable $e) {
			$this->logger->warning(
				'[BridgeResultListener] the anonymisation output was not recorded on the external document',
				['sourceFileId' => $sourceFileId, 'resultFileId' => $resultFileId, 'exception' => $e->getMessage()]
			);
			return 0;
		}
	}//end record()
}//end class
