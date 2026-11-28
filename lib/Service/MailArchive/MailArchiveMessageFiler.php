<?php

/**
 * Files one message split from a mail archive
 *
 * A message in a folder mapped to a dossier is filed the way email ingestion
 * files an `.eml`: into the dossier's folder, converted, with one
 * `emailDocument` record. A message in an unmapped folder arrives in the
 * intake inbox with channel `mail` (design D4). Both are idempotent: the
 * same message for the same dossier, or the same Message-ID in the inbox, is
 * counted as a skipped duplicate and written nowhere (design D5).
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

use OCA\Filinq\Kernel\Contracts\EmailDocumentRepositoryInterface;
use OCA\Filinq\Service\EmailIngestion\EmailNotFiled;
use OCA\Filinq\Service\EmailIngestion\EmailThreadHeaders;
use OCA\Filinq\Service\Support\EmailHandling;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * One archive message to its dossier or to the intake inbox.
 *
 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-mapped-folders-go-to-dossiers-the-rest-to-the-intake-inbox-req-mai-003
 */
class MailArchiveMessageFiler {

	public const FILED = 'filed';

	public const TO_INBOX = 'toInbox';

	public const SKIPPED_DUPLICATE = 'skippedDuplicate';

	public const FAILED = 'failed';

	public const INGEST_SOURCE = 'archive-import';

	public const REASON_FILING_FAILED = 'filing-failed';

	/**
	 * Constructor.
	 *
	 * @param EmailThreadHeaders               $headers        Reads the Message-ID before anything is written.
	 * @param EmailHandling                    $handling       Reads and files an email the way email ingestion does.
	 * @param EmailDocumentRepositoryInterface $emailDocuments The filed emails.
	 * @param MailArchiveInboxDelivery         $inbox          The intake inbox.
	 * @param MailArchiveWorkspace             $workspace      Writes the message beside the archive.
	 * @param ITimeFactory                     $clock          The clock.
	 * @param LoggerInterface                  $logger         The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly EmailThreadHeaders $headers,
		private readonly EmailHandling $handling,
		private readonly EmailDocumentRepositoryInterface $emailDocuments,
		private readonly MailArchiveInboxDelivery $inbox,
		private readonly MailArchiveWorkspace $workspace,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * File one message.
	 *
	 * @param File                  $archive The archive the message came from.
	 * @param array<string, string> $message `{folder, name, raw}`, or `{folder, name, reason}` for an entry that is not a message.
	 * @param string                $dossier The dossier its folder is mapped to; '' for the inbox.
	 *
	 * @return array{outcome: string, failure?: array{messageId: string, archiveFolder: string, name: string, reason: string}} What happened.
	 *
	 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#3-2
	 */
	public function file(File $archive, array $message, string $dossier): array {
		$failure = ['messageId' => '', 'archiveFolder' => (string) ($message['folder'] ?? ''), 'name' => (string) ($message['name'] ?? '')];
		if (isset($message['reason']) === true) {
			return ['outcome' => self::FAILED, 'failure' => $failure + ['reason' => (string) $message['reason']]];
		}

		$raw = (string) ($message['raw'] ?? '');
		$failure['messageId'] = mb_substr($this->headers->read(raw: $raw)['messageId'], 0, 998);

		try {
			$outcome = match ($dossier) {
				'' => $this->toInbox(archive: $archive, message: $message, messageId: $failure['messageId']),
				default => $this->toDossier(archive: $archive, message: $message, dossier: $dossier, messageId: $failure['messageId']),
			};
		} catch (EmailNotFiled $e) {
			return ['outcome' => self::FAILED, 'failure' => $failure + ['reason' => $e->getMessage()]];
		} catch (Throwable $e) {
			$this->logger->warning('[MailArchiveMessageFiler] a message could not be filed', ['name' => $failure['name'], 'exception' => $e->getMessage()]);

			return ['outcome' => self::FAILED, 'failure' => $failure + ['reason' => self::REASON_FILING_FAILED]];
		}

		return ['outcome' => $outcome];

	}//end file()

	/**
	 * File a message into a dossier, unless the dossier holds it already.
	 *
	 * @param File                  $archive   The archive.
	 * @param array<string, string> $message   The message.
	 * @param string                $dossier   The dossier.
	 * @param string                $messageId The Message-ID, '' when it has none.
	 *
	 * @return string filed or skippedDuplicate.
	 *
	 * @throws EmailNotFiled When the message cannot be read or the dossier has no folder.
	 * @throws Throwable     When the message cannot be written or stored; a written file is removed first.
	 */
	private function toDossier(File $archive, array $message, string $dossier, string $messageId): string {
		$contentHash = hash('sha256', $message['raw']);
		if ($this->emailDocuments->findFiledDuplicate(dossierRef: $dossier, contentHash: $contentHash, messageId: $messageId) !== null) {
			return self::SKIPPED_DUPLICATE;
		}

		$staged = $this->workspace->stage(archive: $archive, folder: $message['folder'], name: $message['name'], raw: $message['raw']);
		try {
			$fields = $this->handling->reader->read(file: $staged);
			$filed = $this->handling->filing->file(email: $staged, dossierRef: $dossier);
		} catch (Throwable $e) {
			$this->removeQuietly(file: $staged);
			throw $e;
		}

		$record = [
			'sourceFileRef' => (string) $filed->getId(),
			'dossierRef' => $dossier,
			'contentHash' => $contentHash,
			'ingestSource' => self::INGEST_SOURCE,
			'ingestedAt' => gmdate(DATE_ATOM, $this->clock->getTime()),
		] + $fields;

		$pdf = $this->handling->filing->convert(email: $filed);
		if ($pdf !== '') {
			$record['pdfFileRef'] = $pdf;
		}

		$record['status'] = 'filed';
		$this->emailDocuments->save(record: $record);

		return self::FILED;

	}//end toDossier()

	/**
	 * Receive a message into the intake inbox, unless it waits there already.
	 *
	 * @param File                  $archive   The archive.
	 * @param array<string, string> $message   The message.
	 * @param string                $messageId The Message-ID, '' when it has none.
	 *
	 * @return string toInbox or skippedDuplicate.
	 *
	 * @throws EmailNotFiled When the message cannot be read.
	 * @throws Throwable     When the message cannot be written; a written file is removed first.
	 */
	private function toInbox(File $archive, array $message, string $messageId): string {
		$sourceRef = $messageId;
		if ($sourceRef === '') {
			$sourceRef = 'sha256:' . hash('sha256', $message['raw']);
		}

		if ($this->inbox->received(sourceRef: $sourceRef) === true) {
			return self::SKIPPED_DUPLICATE;
		}

		$staged = $this->workspace->stage(archive: $archive, folder: $message['folder'], name: $message['name'], raw: $message['raw']);
		try {
			$fields = $this->handling->reader->read(file: $staged);
			$this->inbox->deliver(message: $staged, fields: $fields, sourceRef: $sourceRef);
		} catch (Throwable $e) {
			$this->removeQuietly(file: $staged);
			throw $e;
		}

		return self::TO_INBOX;

	}//end toInbox()

	/**
	 * Remove a written message that was not filed, so a retry starts clean.
	 *
	 * @param File $file The file.
	 *
	 * @return void
	 */
	private function removeQuietly(File $file): void {
		try {
			$file->delete();
		} catch (Throwable $e) {
			$this->logger->info('[MailArchiveMessageFiler] a written message could not be removed', ['fileId' => $file->getId()]);
		}

	}//end removeQuietly()
}//end class
