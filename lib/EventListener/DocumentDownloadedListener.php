<?php

/**
 * Writes a document's download into its audit trail
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

use OCA\Filinq\Service\Audit\DocumentDownloadLog;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeDirectFileDownloadEvent;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Turns Nextcloud's direct download of a file into the `downloaded` action of
 * the document it is.
 *
 * Nextcloud raises {@see BeforeDirectFileDownloadEvent} once per download a
 * person asks for (the Files app's Download, a direct link), with the path in
 * the downloader's own files. Previews, thumbnails and sync raise no such
 * event, so the trail is not buried. A download a listener before this one
 * refused did not happen and is not written. The entry is the same download
 * record filinq's own download routes write, with route `files`.
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
class DocumentDownloadedListener implements IEventListener {

	/**
	 * Constructor.
	 *
	 * @param DocumentDownloadLog $downloads   Writes the download into the document's trail.
	 * @param IRootFolder         $rootFolder  Resolves the downloaded path to its file.
	 * @param IUserSession        $userSession The downloader.
	 * @param LoggerInterface     $logger      Logger.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function __construct(
		private readonly DocumentDownloadLog $downloads,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write `downloaded` on the downloaded file, when it is a document.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	public function handle(Event $event): void {
		if (($event instanceof BeforeDirectFileDownloadEvent) === false || $event->isSuccessful() === false) {
			return;
		}

		try {
			$fileId = $this->fileIdOf(path: $event->getPath());
			if ($fileId > 0) {
				$this->downloads->downloaded(fileId: $fileId, version: '', route: DocumentDownloadLog::ROUTE_FILES);
			}
		} catch (Throwable $e) {
			// A download must never fail because its trail could not be written.
			$this->logger->warning(
				message: '[DocumentDownloadedListener] a download could not be audited',
				context: ['error' => $e->getMessage()]
			);
		}

	}//end handle()

	/**
	 * The id of the file at a path in the downloader's files; 0 for a folder,
	 * a missing path or no signed-in user.
	 *
	 * @param string $path The path, relative to the downloader's files.
	 *
	 * @return int The file id, or 0.
	 *
	 * @spec openspec/changes/specify-capability-rows/specs/document-register/spec.md#requirement-every-action-on-a-document-is-in-its-audit-trail-req-scr-007
	 */
	private function fileIdOf(string $path): int {
		$user = $this->userSession->getUser();
		if ($user === null || trim($path, '/') === '') {
			return 0;
		}

		$node = $this->rootFolder->getUserFolder($user->getUID())->get(ltrim($path, '/'));
		if (($node instanceof File) === false) {
			return 0;
		}

		return (int) $node->getId();

	}//end fileIdOf()
}//end class
