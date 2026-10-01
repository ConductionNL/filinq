<?php

/**
 * Email ingestion
 *
 * Scans the watched inbox folders and files every .eml into the dossier its
 * inbox is mapped to, with one emailDocument record each. A tick takes at
 * most its budget, so a bulk drop drains over several ticks. Scanning is
 * idempotent: the same file (sha256) or the same Message-ID for the same
 * dossier dropped again is removed from the inbox without a second record or
 * a second copy. Nothing is skipped in silence: an Outlook .msg, an email
 * OpenRegister cannot parse or a dossier without a folder leaves the file in
 * the inbox and writes a failed record with the reason, once.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Watched-folder email ingestion.
 */
class EmailIngestionService {

	public const REASON_UNSUPPORTED_FORMAT = 'unsupported-format';

	public const REASON_UNPARSEABLE = 'unparseable';

	public const REASON_NO_DOSSIER_FOLDER = 'dossier-folder-unavailable';

	public const REASON_FILING_FAILED = 'filing-failed';

	public const SOURCE_WATCHED_FOLDER = 'watched-folder';

	public const SOURCE_MANUAL = 'manual';

	private const EXTENSIONS = ['eml', 'msg'];

	/**
	 * Constructor.
	 *
	 * @param EmailIngestionSettings  $settings   The inbox mapping.
	 * @param EmailDocumentRepository $repository The records.
	 * @param EmailMessageReader      $reader     Envelope and thread fields.
	 * @param EmailFiling             $filing     Move and convert.
	 * @param IRootFolder             $rootFolder Resolves the inboxes without a user session.
	 * @param ITimeFactory            $clock      The clock.
	 * @param LoggerInterface         $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly EmailIngestionSettings $settings,
		private readonly EmailDocumentRepository $repository,
		private readonly EmailMessageReader $reader,
		private readonly EmailFiling $filing,
		private readonly IRootFolder $rootFolder,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * One tick: file up to $limit emails from the watched inboxes.
	 *
	 * @param int    $limit  The budget.
	 * @param string $source watched-folder for the job, manual for a re-scan by hand.
	 *
	 * @return array{processed: int, filed: int, failed: int, duplicates: int} What the tick did.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function scan(int $limit, string $source = self::SOURCE_WATCHED_FOLDER): array {
		$summary = ['processed' => 0, 'filed' => 0, 'failed' => 0, 'duplicates' => 0];
		$recorded = $this->repository->failedSourceRefs();
		foreach ($this->settings->inboxes() as $inbox) {
			foreach ($this->candidates(folderId: $inbox['folderId'], recorded: $recorded) as $email) {
				if ($summary['processed'] >= $limit) {
					return $summary;
				}

				$outcome = $this->ingest(email: $email, dossierRef: $inbox['dossierRef'], source: $source);
				$summary['processed']++;
				$summary[$outcome]++;
			}
		}

		return $summary;

	}//end scan()

	/**
	 * Convert a filed email that was not converted; the record is updated, never duplicated.
	 *
	 * @param string $uuid The record.
	 *
	 * @return array<string, mixed> The record, with pdfFileRef when the cascade converted it.
	 *
	 * @throws InvalidArgumentException 404 for an unknown record or a gone file, 409 for a failed one.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function retryConversion(string $uuid): array {
		$record = $this->repository->findByUuid(uuid: $uuid);
		if ($record === null) {
			throw new InvalidArgumentException('not_found', 404);
		}

		if (($record['status'] ?? '') !== 'filed') {
			throw new InvalidArgumentException('not_filed', 409);
		}

		if ((string) ($record['pdfFileRef'] ?? '') !== '') {
			return $record;
		}

		$email = $this->filing->fileById(fileId: (string) ($record['sourceFileRef'] ?? ''));
		if ($email === null) {
			throw new InvalidArgumentException('file_gone', 404);
		}

		$pdf = $this->filing->convert(email: $email);
		if ($pdf === '') {
			return $record;
		}

		$record['pdfFileRef'] = $pdf;

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end retryConversion()

	/**
	 * The emails waiting in an inbox, oldest name first, without those already recorded as failed.
	 *
	 * @param int          $folderId The inbox.
	 * @param list<string> $recorded File ids with a failed record.
	 *
	 * @return list<File> The files.
	 */
	private function candidates(int $folderId, array $recorded): array {
		$folder = $this->rootFolder->getFirstNodeById($folderId);
		if (($folder instanceof Folder) === false) {
			$this->logger->warning('[EmailIngestionService] a watched inbox folder is gone', ['folderId' => $folderId]);
			return [];
		}

		$files = [];
		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof File
				&& in_array($this->extension(file: $node), self::EXTENSIONS, true) === true
				&& in_array((string) $node->getId(), $recorded, true) === false
			) {
				$files[] = $node;
			}
		}

		usort($files, static fn (File $left, File $right): int => strcmp($left->getName(), $right->getName()));

		return $files;

	}//end candidates()

	/**
	 * File one email.
	 *
	 * @param File   $email      The email in its inbox.
	 * @param string $dossierRef The dossier.
	 * @param string $source     How it arrived.
	 *
	 * @return string filed, failed or duplicates.
	 */
	private function ingest(File $email, string $dossierRef, string $source): string {
		$record = [
			'sourceFileRef' => (string) $email->getId(),
			'dossierRef' => $dossierRef,
			'contentHash' => $this->reader->contentHash(file: $email),
			'ingestSource' => $source,
			'ingestedAt' => $this->now(),
		];

		try {
			if ($this->extension(file: $email) !== 'eml') {
				throw new EmailNotFiled(self::REASON_UNSUPPORTED_FORMAT);
			}

			$record += $this->reader->read(file: $email);
			if ($this->repository->findFiledDuplicate(dossierRef: $dossierRef, contentHash: $record['contentHash'], messageId: (string) ($record['messageId'] ?? '')) !== null) {
				$email->delete();
				return 'duplicates';
			}

			$filed = $this->filing->file(email: $email, dossierRef: $dossierRef);
		} catch (EmailNotFiled $e) {
			return $this->fail(record: $record, reason: $e->getMessage());
		}

		$record['sourceFileRef'] = (string) $filed->getId();
		$pdf = $this->filing->convert(email: $filed);
		if ($pdf !== '') {
			$record['pdfFileRef'] = $pdf;
		}

		$record['status'] = 'filed';
		$this->repository->save(record: $record);

		return 'filed';

	}//end ingest()

	/**
	 * Record a failure; the file stays in the inbox.
	 *
	 * @param array<string, mixed> $record The fields known so far.
	 * @param string               $reason The reason code.
	 *
	 * @return string failed.
	 */
	private function fail(array $record, string $reason): string {
		$record['status'] = 'failed';
		$record['failureReason'] = $reason;
		$record += ['references' => [], 'toAddresses' => [], 'ccAddresses' => [], 'attachmentNames' => []];

		try {
			$this->repository->save(record: $record);
		} catch (Throwable $e) {
			// Without a record the file stays in the inbox and is tried again next tick.
			$this->logger->error('[EmailIngestionService] could not record a failed email', ['fileId' => $record['sourceFileRef'], 'reason' => $reason, 'exception' => $e->getMessage()]);
		}

		return 'failed';

	}//end fail()

	/**
	 * The lower-case extension of a file.
	 *
	 * @param File $file The file.
	 *
	 * @return string The extension.
	 */
	private function extension(File $file): string {
		return strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

	}//end extension()

	/**
	 * Now, as ISO 8601.
	 *
	 * @return string The time.
	 */
	private function now(): string {
		return (new DateTimeImmutable('@' . $this->clock->getTime()))->format(DateTimeInterface::ATOM);

	}//end now()
}//end class
