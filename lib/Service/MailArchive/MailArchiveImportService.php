<?php

/**
 * Mail archive import
 *
 * Starts an import from a mailbox archive the user can read (it is split
 * enough to list its folders and moves to `mapping`), takes the archivist's
 * folder-to-dossier mapping (refusing a dossier they may not write to,
 * design D6), and drains an `importing` import one bounded unit per cron
 * run from its saved cursor (design D3). A crash between runs resumes at the
 * cursor; idempotency absorbs a half-processed unit (design D5).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use InvalidArgumentException;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use Psr\Log\LoggerInterface;

/**
 * Start, map, read, cancel and drain mail archive imports.
 *
 * A refusal is an InvalidArgumentException whose code is the HTTP status:
 * 404 for an import or file the user cannot see, 400 for a bad mapping,
 * 403 for a dossier they may not write to, 409 for a wrong state.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
class MailArchiveImportService {

	public const STATUS_RECEIVED = 'received';

	public const STATUS_MAPPING = 'mapping';

	public const STATUS_IMPORTING = 'importing';

	public const STATUS_DONE = 'done';

	public const STATUS_FAILED = 'failed';

	public const STATUS_CANCELLED = 'cancelled';

	public const TERMINAL = [self::STATUS_DONE, self::STATUS_FAILED, self::STATUS_CANCELLED];

	public const ARCHIVE_GONE = 'the archive file is no longer available';

	private const DOSSIER_REGISTER = 'filinq';

	private const DOSSIER_SCHEMA = 'dossier';

	/**
	 * Constructor.
	 *
	 * @param MailArchiveImportRepository $repository The imports.
	 * @param MailArchiveSplitter         $splitter   Detects, lists and splits archives.
	 * @param MailArchiveWorkspace        $workspace  The archive file and the messages folder.
	 * @param MailArchiveMessageFiler     $filer      Files one message.
	 * @param MailArchiveSettings         $settings   The per-run limit.
	 * @param IntakeAuthorizationGate     $gate       Whether the user may write a dossier.
	 * @param ITimeFactory                $clock      The clock.
	 * @param LoggerInterface             $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly MailArchiveImportRepository $repository,
		private readonly MailArchiveSplitter $splitter,
		private readonly MailArchiveWorkspace $workspace,
		private readonly MailArchiveMessageFiler $filer,
		private readonly MailArchiveSettings $settings,
		private readonly IntakeAuthorizationGate $gate,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Start an import of a file the user can read, and list its folders.
	 *
	 * @param string $userId The session user.
	 * @param int    $fileId The archive's file id.
	 *
	 * @return array<string, mixed> The import, in `mapping`, or `failed` with the reason.
	 *
	 * @throws InvalidArgumentException 404 when the user cannot read the file.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function start(string $userId, int $fileId): array {
		$archive = $this->workspace->userArchive(userId: $userId, fileId: $fileId);
		if ($archive === null) {
			throw new InvalidArgumentException('file_not_found', 404);
		}

		$record = $this->repository->save(
			record: [
				'file' => $fileId,
				'fileName' => mb_substr($archive->getName(), 0, 255),
				'status' => self::STATUS_RECEIVED,
				'requestedBy' => $userId,
				'folderMap' => [],
				'counts' => $this->zeroCounts(),
				'failures' => [],
				'startedAt' => $this->now(),
			]
		);
		$uuid = (string) $record['uuid'];

		try {
			$record['format'] = $this->splitter->detectFormat(path: $this->workspace->localPath(archive: $archive), fileName: $archive->getName());
			$source = $this->workspace->source(archive: $archive, format: $record['format'], importKey: $uuid);
			$folders = $this->splitter->folders(source: $source, rootName: $this->rootName(record: $record));
		} catch (MailArchiveRefused $e) {
			$this->workspace->discard(importKey: $uuid);

			return $this->repository->save(record: $this->failed(record: $record, reason: $e->getMessage()), uuid: $uuid);
		}

		$this->workspace->releaseCopies();

		$record['folderMap'] = [];
		foreach ($folders as $folder => $count) {
			$record['folderMap'][] = ['archiveFolder' => (string) $folder, 'messages' => $count, 'dossier' => ''];
		}

		$record['counts']['found'] = array_sum($folders);
		$record['status'] = self::STATUS_MAPPING;

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end start()

	/**
	 * Map the archive's folders to dossiers and start importing.
	 *
	 * @param string            $userId    The session user.
	 * @param string            $uuid      The import.
	 * @param array<int, mixed> $folderMap Rows of `{archiveFolder, dossier}`; a folder left out or without a dossier goes to the inbox.
	 *
	 * @return array<string, mixed> The import, in `importing`.
	 *
	 * @throws InvalidArgumentException 404, 409 when not in `mapping`, 400 for an unknown folder, 403 for a dossier the user may not write.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function map(string $userId, string $uuid, array $folderMap): array {
		$record = $this->own(userId: $userId, uuid: $uuid);
		if (($record['status'] ?? '') !== self::STATUS_MAPPING) {
			throw new InvalidArgumentException('not_mapping', 409);
		}

		$dossiers = [];
		foreach ($folderMap as $row) {
			if (is_array($row) === false || is_string($row['archiveFolder'] ?? null) === false) {
				throw new InvalidArgumentException('invalid_mapping', 400);
			}

			$dossiers[$row['archiveFolder']] = trim((string) ($row['dossier'] ?? ''));
		}

		$known = array_column((array) ($record['folderMap'] ?? []), 'archiveFolder');
		if (array_diff(array_keys($dossiers), $known) !== []) {
			throw new InvalidArgumentException('unknown_folder', 400);
		}

		foreach (array_unique(array_filter($dossiers)) as $dossier) {
			if ($this->gate->mayFileOnto(register: self::DOSSIER_REGISTER, schema: self::DOSSIER_SCHEMA, id: $dossier) === false) {
				throw new InvalidArgumentException('dossier_refused', 403);
			}
		}

		foreach ($record['folderMap'] as $index => $row) {
			$record['folderMap'][$index]['dossier'] = ($dossiers[$row['archiveFolder']] ?? '');
		}

		// No cursor yet: an empty one would be stored as a list, not an object.
		$record['status'] = self::STATUS_IMPORTING;
		unset($record['cursor']);
		$record['startedAt'] = $this->now();

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end map()

	/**
	 * One import of the user.
	 *
	 * @param string $userId The session user.
	 * @param string $uuid   The import.
	 *
	 * @return array<string, mixed> The import.
	 *
	 * @throws InvalidArgumentException 404 when it is not theirs.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function get(string $userId, string $uuid): array {
		return $this->own(userId: $userId, uuid: $uuid);

	}//end get()

	/**
	 * The user's imports, newest first.
	 *
	 * @param string $userId The session user.
	 *
	 * @return list<array<string, mixed>> The imports.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function mine(string $userId): array {
		$rows = array_values(
			array_filter(
				$this->repository->search(filters: ['requestedBy' => $userId]),
				static fn (array $row): bool => ($row['requestedBy'] ?? null) === $userId
			)
		);
		usort($rows, static fn (array $left, array $right): int => strcmp((string) ($right['startedAt'] ?? ''), (string) ($left['startedAt'] ?? '')));

		return $rows;

	}//end mine()

	/**
	 * Cancel an import that has not ended; what was filed stays filed.
	 *
	 * @param string $userId The session user.
	 * @param string $uuid   The import.
	 *
	 * @return array<string, mixed> The import, in `cancelled`.
	 *
	 * @throws InvalidArgumentException 404, or 409 when it has ended.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function cancel(string $userId, string $uuid): array {
		$record = $this->own(userId: $userId, uuid: $uuid);
		if (in_array(($record['status'] ?? ''), self::TERMINAL, true) === true) {
			throw new InvalidArgumentException('already_ended', 409);
		}

		$record['status'] = self::STATUS_CANCELLED;
		$record['finishedAt'] = $this->now();
		$this->workspace->discard(importKey: $uuid);

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end cancel()

	/**
	 * One work unit: the oldest importing import takes at most the per-run limit from its cursor.
	 *
	 * @return array<string, mixed>|null The import after the unit, or null when none is importing.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function runUnit(): ?array {
		$record = $this->repository->oldestImporting();
		if ($record === null) {
			return null;
		}

		$uuid = (string) $record['uuid'];
		$archive = $this->workspace->archive(fileId: (int) ($record['file'] ?? 0));
		if ($archive === null) {
			return $this->repository->save(record: $this->failed(record: $record, reason: self::ARCHIVE_GONE), uuid: $uuid);
		}

		try {
			$source = $this->workspace->source(archive: $archive, format: (string) ($record['format'] ?? ''), importKey: $uuid);
			$read = (new MailArchiveRead(rootName: $this->rootName(record: $record), limit: $this->settings->messagesPerRun()))
				->from(cursor: (array) ($record['cursor'] ?? []));
			$unit = $this->splitter->next(source: $source, read: $read);
		} catch (MailArchiveRefused $e) {
			$this->workspace->discard(importKey: $uuid);

			return $this->repository->save(record: $this->failed(record: $record, reason: $e->getMessage()), uuid: $uuid);
		}

		$record = $this->apply(record: $record, archive: $archive, messages: $unit['messages']);
		$record['cursor'] = $unit['cursor'];
		if ($unit['done'] === true) {
			$record['status'] = self::STATUS_DONE;
			$record['finishedAt'] = $this->now();
			$this->workspace->discard(importKey: $uuid);
		} else {
			$this->workspace->releaseCopies();
		}

		return $this->repository->save(record: $this->keepingACancel(record: $record, uuid: $uuid), uuid: $uuid);

	}//end runUnit()

	/**
	 * File a unit's messages and add them to the counts and failures.
	 *
	 * @param array<string, mixed>              $record   The import.
	 * @param File                              $archive  The archive.
	 * @param list<array<string, string>>       $messages The unit's messages.
	 *
	 * @return array<string, mixed> The import with the new counts.
	 */
	private function apply(array $record, File $archive, array $messages): array {
		$dossiers = array_column((array) ($record['folderMap'] ?? []), 'dossier', 'archiveFolder');
		$record['counts'] = array_merge($this->zeroCounts(), (array) ($record['counts'] ?? []));
		$record['failures'] = (array) ($record['failures'] ?? []);

		foreach ($messages as $message) {
			$result = $this->filer->file(archive: $archive, message: $message, dossier: (string) ($dossiers[$message['folder']] ?? ''));
			$record['counts'][$result['outcome']] = ((int) ($record['counts'][$result['outcome']] ?? 0) + 1);
			if (isset($result['failure']) === true) {
				$record['failures'][] = $result['failure'];
			}
		}

		return $record;

	}//end apply()

	/**
	 * Keep a cancel that arrived while the unit ran: the counts are saved, the status stays cancelled.
	 *
	 * @param array<string, mixed> $record The import after the unit.
	 * @param string               $uuid   The import.
	 *
	 * @return array<string, mixed> The import to save.
	 */
	private function keepingACancel(array $record, string $uuid): array {
		$current = $this->repository->find(uuid: $uuid);
		if ($current !== null && ($current['status'] ?? '') === self::STATUS_CANCELLED) {
			$record['status'] = self::STATUS_CANCELLED;
			$record['finishedAt'] = ($current['finishedAt'] ?? $this->now());
			$this->logger->info('[MailArchiveImportService] an import was cancelled while a unit ran', ['uuid' => $uuid]);
		}

		return $record;

	}//end keepingACancel()

	/**
	 * The user's own import.
	 *
	 * @param string $userId The session user.
	 * @param string $uuid   The import.
	 *
	 * @return array<string, mixed> The import.
	 *
	 * @throws InvalidArgumentException 404 when it is missing or someone else's.
	 */
	private function own(string $userId, string $uuid): array {
		$record = $this->repository->find(uuid: $uuid);
		if ($record === null || $userId === '' || ($record['requestedBy'] ?? null) !== $userId) {
			throw new InvalidArgumentException('not_found', 404);
		}

		return $record;

	}//end own()

	/**
	 * An import marked failed with its reason.
	 *
	 * @param array<string, mixed> $record The import.
	 * @param string               $reason The reason, as the archivist reads it.
	 *
	 * @return array<string, mixed> The import.
	 */
	private function failed(array $record, string $reason): array {
		$record['status'] = self::STATUS_FAILED;
		$record['statusReason'] = mb_substr($reason, 0, 255);
		$record['finishedAt'] = $this->now();

		return $record;

	}//end failed()

	/**
	 * The folder name for messages outside any folder: the archive's name without its extension.
	 *
	 * @param array<string, mixed> $record The import.
	 *
	 * @return string The name.
	 */
	private function rootName(array $record): string {
		$name = pathinfo((string) ($record['fileName'] ?? ''), PATHINFO_FILENAME);
		if ($name === '') {
			return 'archive';
		}

		return $name;

	}//end rootName()

	/**
	 * Every count at zero.
	 *
	 * @return array{found: int, filed: int, toInbox: int, skippedDuplicate: int, failed: int} The counts.
	 */
	private function zeroCounts(): array {
		return ['found' => 0, 'filed' => 0, 'toInbox' => 0, 'skippedDuplicate' => 0, 'failed' => 0];

	}//end zeroCounts()

	/**
	 * Now, as ISO 8601.
	 *
	 * @return string The time.
	 */
	private function now(): string {
		return gmdate(DATE_ATOM, $this->clock->getTime());

	}//end now()
}//end class
