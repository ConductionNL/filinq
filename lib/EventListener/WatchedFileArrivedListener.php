<?php

/**
 * Watched File Arrived Listener
 *
 * Takes a scanned batch in when integriq's watched folder hands one over to
 * filinq. The event is integriq's class, registered by name so filinq boots
 * without integriq, and checked with class_exists() before it is read.
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
 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\AppIdentity;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Integriq\Event\WatchedFileArrivedEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns a file integriq hands to filinq into a scan batch, and accepts it only when one is stored.
 *
 * @template-implements IEventListener<Event>
 *
 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md
 */
class WatchedFileArrivedListener implements IEventListener {

	/**
	 * integriq's hand-over event, as a string literal so filinq boots without integriq.
	 *
	 * @var string
	 */
	public const ARRIVED_EVENT = 'OCA\\Integriq\\Event\\WatchedFileArrivedEvent';

	/**
	 * The mime type a scanned batch has.
	 *
	 * @var string
	 */
	private const PDF = 'application/pdf';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder                 $rootFolder Resolves the file in its owner's folder.
	 * @param ScanProfileServiceInterface $profiles   The profile that watches the file's folder.
	 * @param ScanBatchServiceInterface   $batches    The stored batch of a file; receives and cuts a new one.
	 * @param LoggerInterface             $logger     Logs what was not accepted.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly ScanProfileServiceInterface $profiles,
		private readonly ScanBatchServiceInterface $batches,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Take the handed-over file in as a scan batch and accept it with the batch's uuid.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-takes-a-scanned-batch-that-integriqs-watched-folder-hands-over-req-scw-001
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-without-integriq-the-scan-intake-works-as-today-req-scw-003
	 */
	public function handle(Event $event): void {
		if (class_exists(self::ARRIVED_EVENT) === false || ($event instanceof WatchedFileArrivedEvent) === false) {
			return;
		}

		if ($event->getIntakeApp() !== AppIdentity::APP_ID) {
			return;
		}

		$reference = $this->batchOf(event: $event);
		if ($reference === null) {
			return;
		}

		if ($event->accept(AppIdentity::APP_ID, $reference) === false) {
			// Logged once and left: retrying, or accepting under another id,
			// would claim a file integriq already settled.
			$this->logger->warning(
				message: '[WatchedFileArrivedListener] the hand-over did not take the acceptance',
				context: ['fileId' => $event->getFileId(), 'reference' => $reference, 'result' => $event->getResult()]
			);
		}

	}//end handle()

	/**
	 * The uuid of the batch the file is, stored now or before, or null when filinq may not accept it.
	 *
	 * @param WatchedFileArrivedEvent $event The hand-over.
	 *
	 * @return string|null The batch uuid, or null when nothing was stored.
	 *
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-accepts-only-what-it-stored-and-each-file-once-req-scw-002
	 */
	private function batchOf(WatchedFileArrivedEvent $event): ?string {
		try {
			$file = $this->pdfOf(event: $event);
			if ($file === null) {
				return null;
			}

			$existing = $this->batches->storedFor(fileId: (int)$file->getId());
			if ($existing !== null) {
				// Once per file: the batch that already owns it is the answer,
				// and it is not received or cut again.
				return $this->uuidOf(batch: $existing);
			}

			$profile = $this->profiles->forPath(path: $event->getPath());
			if ($profile === null) {
				$this->logger->info(
					message: '[WatchedFileArrivedListener] no scan profile watches the folder of the handed-over file',
					context: ['fileId' => $event->getFileId(), 'path' => $event->getPath()]
				);
				return null;
			}

			// A batch that is stored but fails to cut comes back `failed`
			// with its uuid; it owns the file, so it is accepted as well.
			return $this->uuidOf(batch: $this->batches->take(file: $file, profile: $profile));
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[WatchedFileArrivedListener] the handed-over file could not be taken in',
				context: ['fileId' => $event->getFileId(), 'error' => $e->getMessage()]
			);
			return null;
		}//end try

	}//end batchOf()

	/**
	 * The handed-over node, when it is a PDF file in its owner's folder.
	 *
	 * @param WatchedFileArrivedEvent $event The hand-over.
	 *
	 * @return File|null The file, or null for a missing node, a folder or another type.
	 *
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-accepts-only-what-it-stored-and-each-file-once-req-scw-002
	 */
	private function pdfOf(WatchedFileArrivedEvent $event): ?File {
		$nodes = $this->rootFolder->getUserFolder($event->getOwnerUid())->getById($event->getFileId());
		$node = $nodes[0] ?? null;
		if (($node instanceof File) === false || $node->getMimetype() !== self::PDF) {
			$this->logger->info(
				message: '[WatchedFileArrivedListener] the handed-over id is no PDF file of its owner',
				context: ['fileId' => $event->getFileId(), 'ownerUid' => $event->getOwnerUid()]
			);
			return null;
		}

		return $node;

	}//end pdfOf()

	/**
	 * The uuid of a stored batch, or null when it carries none.
	 *
	 * @param array<string, mixed> $batch The batch.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md#requirement-filinq-accepts-only-what-it-stored-and-each-file-once-req-scw-002
	 */
	private function uuidOf(array $batch): ?string {
		$uuid = trim((string)($batch['uuid'] ?? ''));
		if ($uuid === '') {
			return null;
		}

		return $uuid;

	}//end uuidOf()
}//end class
