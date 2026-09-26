<?php

/**
 * Intake Document Received Listener
 *
 * The one subscriber to `IntakeDocumentReceivedEvent`. Every channel dispatches
 * that event and nothing else: the scan folder watch, the mail intake and the
 * digital post adapter all stop at "this arrived". Turning it into a document
 * waiting for a clerk happens here, once, so a new channel needs no new intake
 * code at all.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Service\IntakeService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Creates one waiting intake document per delivered document.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
 */
class IntakeDocumentReceivedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param IntakeService $intake The intake inbox.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeService $intake,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Handle one delivery.
	 *
	 * A feeder is usually a background job, so a refusal here is logged rather
	 * than thrown: a malformed delivery must not stop the rest of the batch.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-intake-inbox/specs/document-intake-inbox/spec.md
	 */
	public function handle(Event $event): void {
		if (($event instanceof IntakeDocumentReceivedEvent) === false) {
			return;
		}

		try {
			$document = $this->intake->receive(event: $event);
			$this->logger->info(
				message: '[IntakeDocumentReceivedListener] a document is waiting in the intake inbox',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'channel' => $event->getChannel(),
					'uuid' => ($document['uuid'] ?? ''),
				]
			);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[IntakeDocumentReceivedListener] could not take in a delivered document',
				context: [
					'file' => __FILE__,
					'line' => __LINE__,
					'channel' => $event->getChannel(),
					'sourceRef' => $event->getSourceRef(),
					'error' => $e->getMessage(),
				]
			);
		}//end try

	}//end handle()
}//end class
