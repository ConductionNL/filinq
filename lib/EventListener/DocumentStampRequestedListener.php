<?php

/**
 * Answers a DocumentStampRequestedEvent.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Event\DocumentStampRequestedEvent;
use OCA\Filinq\Exception\PdfStampRefusal;
use OCA\Filinq\Service\PdfStampService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Runs the stamp and writes the PDF, or a refusal, into the event.
 *
 * It never throws to the dispatching app and never stores the stamped copy.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
 */
class DocumentStampRequestedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param PdfStampService $stamps The stamping service.
	 * @param LoggerInterface $logger The logger.
	 */
	public function __construct(
		private readonly PdfStampService $stamps,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Stamp the event's PDF.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/work-stamp-text-on-every-page/tasks.md#task-2.1
	 */
	public function handle(Event $event): void {
		if ($event instanceof DocumentStampRequestedEvent === false) {
			return;
		}

		$context = ['sourceApp' => $event->getSourceApp(), 'correlationId' => $event->getCorrelationId()];
		try {
			$event->setStampedPdf(
				$this->stamps->stamp(pdf: $event->getPdfContent(), text: $event->getText(), options: ['placement' => $event->getPlacement()])
			);
			$event->setHandled(true);
		} catch (PdfStampRefusal $refusal) {
			$event->setRefusal(code: $refusal->getRefusalCode(), reason: $refusal->getMessage());
			$this->logger->info('Filinq: stamp refused (' . $refusal->getRefusalCode() . ')', $context);
		} catch (Throwable $e) {
			$event->setRefusal(code: PdfStampRefusal::FAILED, reason: 'The PDF could not be stamped.');
			$this->logger->error('Filinq: DocumentStampRequestedListener failed', $context + ['exception' => $e->getMessage()]);
		}
	}//end handle()
}//end class
