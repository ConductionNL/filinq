<?php

/**
 * Pseudonym Map Link Deleted Listener
 *
 * When an anonymisation link is deleted, its key goes with it. A key without its
 * link is re-identification material nobody can reach through the app, which
 * is the worst of both: it still exists, and nobody would think to remove it.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Service\Pseudonymisation\PseudonymMapService;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Deletes the key of a deleted anonymisation link.
 *
 * @category EventListener
 * @package  OCA\Filinq\EventListener
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @template-implements IEventListener<Event>
 */
class PseudonymMapLinkDeletedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param PseudonymMapService $maps The key store.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly PseudonymMapService $maps,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Delete the key of the deleted object, when it was an anonymisation link.
	 *
	 * Recognised by shape (a source and an anonymised file id), because the
	 * event carries a schema id, not a slug. The key store then matches on the
	 * link uuid, so an object of another schema with the same two fields finds
	 * no key and nothing happens.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-2.2
	 */
	public function handle(Event $event): void {
		if (($event instanceof ObjectDeletedEvent) === false) {
			return;
		}

		$object = $event->getObject();
		if ($object === null) {
			return;
		}

		$data = (array) $object->getObject();
		$linkId = (string) $object->getUuid();
		if ($linkId === '' || isset($data['sourceFileId'], $data['anonymizedFileId']) === false) {
			return;
		}

		try {
			$this->maps->deleteForLink(linkId: $linkId);
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[PseudonymMapLinkDeletedListener] the key of a deleted anonymisation link was NOT deleted',
				context: ['linkId' => $linkId, 'error' => $e->getMessage()]
			);
		}

	}//end handle()
}//end class
