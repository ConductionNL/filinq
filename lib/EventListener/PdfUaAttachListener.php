<?php

/**
 * Filinq PdfUaAttachListener
 *
 * Queues a PDF/UA check for every PDF that is created or written under
 * OpenRegister's `Open Registers` folder, the files attached to register
 * objects. It only queues: it does not read the file and does not run
 * veraPDF on the request that stored it. Files anywhere else are left to the
 * existing validation paths.
 *
 * @category  EventListener
 * @package   OCA\Filinq\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\EventListener;

use OCA\Filinq\Kernel\JobQueueInterface;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @template-implements IEventListener<Event>
 */
class PdfUaAttachListener implements IEventListener {

	/**
	 * The folder in a user's files where OpenRegister keeps object files.
	 */
	public const REGISTER_ROOT = 'Open Registers';

	/**
	 * Constructor.
	 *
	 * @param JobQueueInterface $jobs   The background job queue.
	 * @param LoggerInterface   $logger The log.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly JobQueueInterface $jobs,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Queue one check for an attached PDF.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-every-pdf-attached-to-an-openregister-object-is-checked-for-pdfua-req-ddpum-006
	 */
	public function handle(Event $event): void {
		if (($event instanceof NodeCreatedEvent) === false && ($event instanceof NodeWrittenEvent) === false) {
			return;
		}

		try {
			$node = $event->getNode();
			if (($node instanceof File) === false
				|| $node->getMimetype() !== 'application/pdf'
				|| self::isUnderRegisterRoot(path: $node->getPath()) === false
			) {
				return;
			}

			$this->jobs->add(job: JobQueueInterface::PDFUA_ATTACH_CHECK, argument: ['fileId' => (int) $node->getId()]);
		} catch (Throwable $e) {
			// A failed queue must never fail the upload that triggered it.
			$this->logger->warning('PdfUaAttachListener: could not queue the PDF/UA check', ['error' => $e->getMessage()]);
		}

	}//end handle()

	/**
	 * Whether a node path lies under a user's `Open Registers` folder.
	 *
	 * @param string $path The node path, `/<user>/files/...`.
	 *
	 * @return bool True for a file inside it.
	 */
	public static function isUnderRegisterRoot(string $path): bool {
		return preg_match('#^/[^/]+/files/' . preg_quote(self::REGISTER_ROOT, '#') . '/.#', $path) === 1;

	}//end isUnderRegisterRoot()
}//end class
