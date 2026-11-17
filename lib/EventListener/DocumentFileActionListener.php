<?php

/**
 * Writes a document's file actions into its audit trail
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\Audit\DocumentActionAudit;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\AbstractNodeEvent;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use OCP\Share\Events\ShareCreatedEvent;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns Nextcloud's file events into document actions: a written file is
 * edited, a file about to be deleted is deleted, a new share is shared.
 *
 * Reads are not listened to: Nextcloud raises a read for every preview,
 * thumbnail and sync, which would bury the trail. Opening and downloading are
 * written by the endpoints that serve them.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
 */
class DocumentFileActionListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param DocumentActionAudit $audit  The document trail.
	 * @param LoggerInterface     $logger Logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentActionAudit $audit,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write the action of this event on the file it is about, when that file is a document.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function handle(Event $event): void {
		try {
			[$fileId, $action] = $this->actionOf(event: $event);
			if ($fileId > 0) {
				$this->audit->record(fileId: $fileId, action: $action);
			}
		} catch (Throwable $e) {
			// A file action must never fail because its trail could not be written.
			$this->logger->warning(
				message: '[DocumentFileActionListener] a file action could not be audited',
				context: ['event' => $event::class, 'error' => $e->getMessage()]
			);
		}

	}//end handle()

	/**
	 * The file id and action of an event; file id 0 for an event about no file.
	 *
	 * @param Event $event The event.
	 *
	 * @return array{0: int, 1: string}
	 */
	private function actionOf(Event $event): array {
		if ($event instanceof ShareCreatedEvent) {
			$share = $event->getShare();
			if ($share->getNodeType() !== 'file') {
				return [0, ''];
			}

			return [$share->getNodeId(), 'shared'];
		}

		if (($event instanceof AbstractNodeEvent) === false) {
			return [0, ''];
		}

		$action = '';
		if ($event instanceof NodeWrittenEvent) {
			$action = 'edited';
		}

		if ($event instanceof BeforeNodeDeletedEvent) {
			$action = 'deleted';
		}

		if ($action === '') {
			return [0, ''];
		}

		$node = $event->getNode();
		if (($node instanceof File) === false) {
			return [0, ''];
		}

		return [(int) $node->getId(), $action];

	}//end actionOf()
}//end class
