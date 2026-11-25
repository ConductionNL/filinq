<?php

/**
 * Delivers an archive message to the intake inbox
 *
 * A message from a folder mapped to no dossier arrives in the intake inbox
 * with channel `mail` and its Message-ID as source reference (design D4), so
 * the inbox's own source-reference check keeps it from arriving twice
 * (design D5).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use OCA\Filinq\Event\IntakeDocumentReceivedEvent;
use OCA\Filinq\Event\Support\IntakeArrival;
use OCA\Filinq\Kernel\Contracts\IntakeRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\IntakeServiceInterface;
use OCP\Files\File;

/**
 * Whether a message waits in the inbox already, and receiving one.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-mapped-folders-go-to-dossiers-the-rest-to-the-intake-inbox-req-mai-003
 */
class MailArchiveInboxDelivery {

	/**
	 * Constructor.
	 *
	 * @param IntakeRepositoryInterface $intake        The intake inbox, for the duplicate check.
	 * @param IntakeServiceInterface    $intakeService Receives a message into the inbox.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IntakeRepositoryInterface $intake,
		private readonly IntakeServiceInterface $intakeService,
	) {

	}//end __construct()

	/**
	 * Whether a message with this source reference is in the inbox already.
	 *
	 * @param string $sourceRef The message's Message-ID, or its content hash when it has none.
	 *
	 * @return bool True when it was received before.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-3
	 */
	public function received(string $sourceRef): bool {
		return $this->intake->findBySourceRef(sourceRef: $sourceRef) !== null;

	}//end received()

	/**
	 * Receive a written message into the inbox as mail.
	 *
	 * @param File                 $message   The message's `.eml` beside the archive.
	 * @param array<string, mixed> $fields    What the reader found: subject, fromAddress, sentAt.
	 * @param string               $sourceRef The message's source reference.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-2
	 */
	public function deliver(File $message, array $fields, string $sourceRef): void {
		$sentAt = $fields['sentAt'] ?? null;
		if (is_string($sentAt) === false) {
			$sentAt = null;
		}

		$this->intakeService->receiveMessage(
			message: new IntakeDocumentReceivedEvent(
				channel: IntakeDocumentReceivedEvent::CHANNEL_MAIL,
				fileId: (int) $message->getId(),
				fileName: $message->getName(),
				subject: (string) ($fields['subject'] ?? ''),
				sender: (string) ($fields['fromAddress'] ?? ''),
				arrival: new IntakeArrival(sourceRef: $sourceRef, receivedAt: $sentAt)
			)
		);

	}//end deliver()
}//end class
