<?php

/**
 * Filinq DocumentStampRequestedListener
 *
 * Handles DocumentStampRequestedEvent: stamps the PDF through PdfStampService
 * and writes the result, or the refusal, onto the event. It never throws to
 * the dispatching app and never stores the stamped copy.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\Exception\PdfStampRefusedException;
use OCA\Filinq\Service\PdfStampService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Stamps the PDF a sibling app asked for.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
 */
class DocumentStampRequestedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param PdfStampService $stampService Stamps the PDF.
	 * @param LoggerInterface $logger       Logs refusals and failures.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfStampService $stampService,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Stamp the PDF, or record why not.
	 *
	 * @param Event $event The dispatched event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2-1
	 */
	public function handle(Event $event): void {
		if (($event instanceof DocumentStampRequestedEvent) === false) {
			return;
		}

		$context = [
			'sourceApp' => $event->getSourceApp(),
			'correlationId' => $event->getCorrelationId(),
		];

		try {
			$stamped = $this->stampService->stamp(
				$event->getPdfContent(),
				$event->getText(),
				['placement' => $event->getPlacement()]
			);
			$event->setStampedPdf($stamped);
		} catch (PdfStampRefusedException $e) {
			$event->setRefusal(code: $e->getRefusalCode(), reason: $e->getMessage());
			$this->logger->warning(
				'Filinq: DocumentStampRequestedEvent refused',
				array_merge($context, ['code' => $e->getRefusalCode()])
			);
		} catch (Throwable $e) {
			// Covers a bad text or placement too: the dispatching app never
			// sees an exception from filinq, only an unhandled event and a code.
			$event->setRefusal(code: PdfStampRefusedException::FAILED, reason: $e->getMessage());
			$this->logger->error(
				'Filinq: DocumentStampRequestedListener failed',
				array_merge($context, ['exception' => $e->getMessage()])
			);
		}//end try

	}//end handle()
}//end class
