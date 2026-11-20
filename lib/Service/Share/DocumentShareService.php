<?php

/**
 * Share a document outside the organisation through a link that runs out.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Share
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/share-document-links/specs/document-share-links/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Share;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use OCA\Filinq\BackgroundJob\UploadFragmentReaperJob;
use OCA\Filinq\Service\CaseArchiveService;
use OCA\Filinq\Service\CaseArchiveWriter;
use OCA\Filinq\Service\ConfidentialityLabelService;
use OCA\Filinq\Service\ReadableFileResolver;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use OCP\IGroupManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Makes a link for one document, records what left, and takes a link back.
 *
 * The link is a Nextcloud public share (D1); the record holds the share id,
 * never its token or URL (D6). Every link runs out (D5), and a labelled
 * document is shared only after the sharer confirms (D8).
 *
 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
 */
class DocumentShareService {

	/**
	 * Days a link stays open when the sharer picks nothing else.
	 *
	 * @var int
	 */
	public const DEFAULT_DAYS = 14;

	/**
	 * Where a bundle's archive is written, under the sharer's Files (D4).
	 *
	 * @var string
	 */
	public const BUNDLE_FOLDER = UploadFragmentReaperJob::DOCUMENTS_FOLDER . '/Shared bundles';

	/**
	 * What a record closed because its share vanished in Files says took it back.
	 *
	 * @var string
	 */
	public const REMOVED_IN_FILES = 'removed in Files';

	/**
	 * Constructor.
	 *
	 * @param LinkShareGateway            $links  Nextcloud's link shares.
	 * @param DocumentShareStore          $store  The share records.
	 * @param ReadableFileResolver        $files  The file behind an id, as the user may read it.
	 * @param ConfidentialityLabelService $labels A file's confidentiality label.
	 * @param IUserSession                $session The user who shares.
	 * @param IGroupManager               $groups Whether a user is an admin.
	 * @param ITimeFactory                $clock  Today.
	 * @param LoggerInterface             $logger Logs a label that could not be read.
	 * @param CaseArchiveService          $archives     Builds a bundle's archive and records its job.
	 * @param CaseArchiveWriter           $archiveFiles Deletes a bundle's archive when its link ends.
	 */
	public function __construct(
		private readonly LinkShareGateway $links,
		private readonly DocumentShareStore $store,
		private readonly ReadableFileResolver $files,
		private readonly ConfidentialityLabelService $labels,
		private readonly IUserSession $session,
		private readonly IGroupManager $groups,
		private readonly ITimeFactory $clock,
		private readonly LoggerInterface $logger,
		private readonly CaseArchiveService $archives,
		private readonly CaseArchiveWriter $archiveFiles,
	) {

	}//end __construct()

	/**
	 * Share one document as itself through a read-only link.
	 *
	 * @param int         $fileId         The document's file id.
	 * @param string      $expiresOn      The last day of the link (YYYY-MM-DD); empty means 14 days ahead.
	 * @param string|null $password       The link password, or null for none.
	 * @param string      $note           Who the link is for.
	 * @param bool        $labelConfirmed Whether the sharer confirmed sharing a labelled document.
	 *
	 * @return array{share: array<string, mixed>, url: string, expiresAt: string, expiryShortened: bool} The record, the link
	 *                                                                                                    and the expiry used.
	 *
	 * @throws InvalidArgumentException When the request breaks a rule (400), or a label needs a confirmation (409).
	 * @throws RuntimeException         When there is no user (401), no readable file (404), Nextcloud refuses (403),
	 *                                  or the record cannot be written (500).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	public function shareDocument(int $fileId, string $expiresOn, ?string $password, string $note, bool $labelConfirmed): array {
		$uid = $this->uid();
		[$expires, $shortened] = $this->linkTerms(expiresOn: $expiresOn, password: $password);

		$file = $this->files->node(fileId: $fileId);
		if (($file instanceof File) === false) {
			throw new RuntimeException('The document cannot be found', 404);
		}

		$label = $this->labelOf(fileId: $fileId);
		$this->requireConfirmation(labels: [$label], confirmed: $labelConfirmed, what: 'This document is');

		$link = $this->links->createLink(fileId: $fileId, uid: $uid, expires: $expires, password: $password, note: trim($note));

		$record = [
			'kind' => 'document',
			'files' => [['fileId' => $fileId, 'name' => $file->getName(), 'version' => (string) $file->getEtag(), 'label' => $label]],
			'shareId' => $link['id'],
			'sharedBy' => $uid,
			'sharedAt' => $this->now()->format(DateTimeInterface::ATOM),
			'expiresAt' => $expires->format('Y-m-d'),
			'note' => trim($note),
			'labelConfirmed' => $label !== '',
			'status' => 'active',
		];

		try {
			$record = $this->store->save(record: $record);
		} catch (Throwable $e) {
			// A link nobody can see in the history must not stay open.
			$this->links->delete(shareId: $link['id']);
			throw new RuntimeException('The share could not be recorded, so the link was taken back again', 500, $e);
		}

		return ['share' => $record, 'url' => $link['url'], 'expiresAt' => $expires->format('Y-m-d'), 'expiryShortened' => $shortened];

	}//end shareDocument()

	/**
	 * Take a link back: delete the share in Nextcloud and mark the record revoked.
	 *
	 * Only the person who made the link, or an admin, may take it back.
	 *
	 * @param string $recordUuid The share record.
	 *
	 * @return array<string, mixed> The revoked record.
	 *
	 * @throws RuntimeException When there is no user (401), the record is unknown (404), the user may not (403), or
	 *                          the link is no longer active (409).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	public function revoke(string $recordUuid): array {
		$uid = $this->uid();
		$record = $this->store->find(uuid: $recordUuid);
		if ($record === null) {
			throw new RuntimeException('The share cannot be found', 404);
		}

		if ((string) ($record['sharedBy'] ?? '') !== $uid && $this->groups->isAdmin($uid) === false) {
			throw new RuntimeException('Only the person who made the link, or an admin, can take it back', 403);
		}

		if (($record['status'] ?? '') !== 'active') {
			throw new RuntimeException('This link is no longer active', 409);
		}

		$this->links->delete(shareId: (string) ($record['shareId'] ?? ''));
		$this->discardArchive(record: $record);

		$record['status'] = 'revoked';
		$record['revokedBy'] = $uid;
		$record['revokedAt'] = $this->now()->format(DateTimeInterface::ATOM);

		return $this->store->save(record: $record, uuid: $recordUuid);

	}//end revoke()

	/**
	 * Share one document as itself, or several as one bundle.
	 *
	 * @param array<int, int|string> $fileIds        The chosen documents.
	 * @param string                 $name           The bundle's name; only used for two or more documents.
	 * @param string                 $expiresOn      The last day of the link (YYYY-MM-DD); empty means 14 days ahead.
	 * @param string|null            $password       The link password, or null for none.
	 * @param string                 $note           Who the link is for.
	 * @param bool                   $labelConfirmed Whether the sharer confirmed sharing labelled documents.
	 *
	 * @return array{share: array<string, mixed>, url: string, expiresAt: string, expiryShortened: bool} The record, the link
	 *                                                                                                    and the expiry used.
	 *
	 * @throws InvalidArgumentException When no document is chosen (400), or as shareDocument() and shareBundle().
	 * @throws RuntimeException         As shareDocument() and shareBundle().
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	public function share(array $fileIds, string $name, string $expiresOn, ?string $password, string $note, bool $labelConfirmed): array {
		$ids = $this->fileIds(fileIds: $fileIds);
		if ($ids === []) {
			throw new InvalidArgumentException('Choose at least one document to share', 400);
		}

		if (count($ids) === 1) {
			return $this->shareDocument(
				fileId: $ids[0],
				expiresOn: $expiresOn,
				password: $password,
				note: $note,
				labelConfirmed: $labelConfirmed
			);
		}

		return $this->shareBundle(
			fileIds: $ids,
			name: $name,
			expiresOn: $expiresOn,
			password: $password,
			note: $note,
			labelConfirmed: $labelConfirmed
		);

	}//end share()

	/**
	 * What the dialog needs to know before anything is shared.
	 *
	 * Nextcloud's link rules, the default expiry, the confidentiality label of
	 * each chosen document, and, for two or more, what the bundle holds and
	 * whether it goes over the ceiling (REQ-SDL-002, REQ-SDL-003, REQ-SDL-006).
	 *
	 * @param array<int, int|string> $fileIds The chosen documents.
	 *
	 * @return array<string, mixed> The preflight: maxDays, passwordRequired, defaultExpiresAt, labels (fileId, label)
	 *                              and bundle (CaseArchiveService::preflightFiles(), or null for one document).
	 *
	 * @throws InvalidArgumentException When no document is chosen (400).
	 * @throws RuntimeException         Without a user (401), or when a label cannot be read (503).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	public function preflight(array $fileIds): array {
		$this->uid();
		$ids = $this->fileIds(fileIds: $fileIds);
		if ($ids === []) {
			throw new InvalidArgumentException('Choose at least one document to share', 400);
		}

		$policy = $this->links->linkPolicy();
		[$expires] = $this->expiry(expiresOn: '', maxDays: $policy['maxDays']);

		$labels = [];
		foreach ($this->readableIds(fileIds: $ids) as $fileId) {
			$label = $this->labelOf(fileId: $fileId);
			if ($label !== '') {
				$labels[] = ['fileId' => $fileId, 'label' => $label];
			}
		}

		$bundle = null;
		if (count($ids) > 1) {
			$bundle = $this->archives->preflightFiles(fileIds: $ids, ceiling: $this->archives->ceiling());
		}

		return [
			'maxDays' => $policy['maxDays'],
			'passwordRequired' => $policy['passwordRequired'],
			'defaultExpiresAt' => $expires->format('Y-m-d'),
			'labels' => $labels,
			'bundle' => $bundle,
		];

	}//end preflight()

	/**
	 * Share several documents as one archive with a manifest, through one link (D4).
	 *
	 * The archive is built and its job recorded by CaseArchiveService; a
	 * refused job write stops the share. A link Nextcloud refuses, or a
	 * record that cannot be written, takes the archive (and the link) back.
	 *
	 * @param array<int, int>   $fileIds        The chosen documents.
	 * @param string            $name           The bundle's name.
	 * @param string            $expiresOn      The last day of the link (YYYY-MM-DD); empty means 14 days ahead.
	 * @param string|null       $password       The link password, or null for none.
	 * @param string            $note           Who the link is for.
	 * @param bool              $labelConfirmed Whether the sharer confirmed sharing labelled documents.
	 *
	 * @return array{share: array<string, mixed>, url: string, expiresAt: string, expiryShortened: bool, manifest: array<string, mixed>} The record,
	 *                                                                                                    the link, the expiry used and what went in.
	 *
	 * @throws InvalidArgumentException When the request breaks a rule (400), or a label needs a confirmation (409).
	 * @throws RuntimeException         When there is no user (401), nothing can go in (400), Nextcloud refuses (403),
	 *                                  or the archive, its job or the record cannot be written (500).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	public function shareBundle(array $fileIds, string $name, string $expiresOn, ?string $password, string $note, bool $labelConfirmed): array {
		$uid = $this->uid();
		$name = trim($name);
		if ($name === '') {
			throw new InvalidArgumentException('A bundle needs a name', 400);
		}

		[$expires, $shortened] = $this->linkTerms(expiresOn: $expiresOn, password: $password);

		$labels = $this->bundleLabels(fileIds: $fileIds, uid: $uid);
		$this->requireConfirmation(labels: $labels, confirmed: $labelConfirmed, what: 'This bundle holds documents');

		$built = $this->archives->buildFromFiles(fileIds: $fileIds, folderPath: self::BUNDLE_FOLDER, name: $name);
		$archiveFileId = (int) $built['archive']['fileId'];

		try {
			$link = $this->links->createLink(fileId: $archiveFileId, uid: $uid, expires: $expires, password: $password, note: trim($note));
		} catch (Throwable $e) {
			// No link, so no archive left lying in the sharer's Files.
			$this->archiveFiles->discardById(userId: $uid, fileId: $archiveFileId);
			throw $e;
		}

		$record = [
			'kind' => 'bundle',
			'files' => $this->bundleFiles(included: $built['included'], labels: $labels),
			'shareId' => $link['id'],
			'archiveJob' => (string) ($built['job']['uuid'] ?? ''),
			'archiveFileId' => $archiveFileId,
			'sharedBy' => $uid,
			'sharedAt' => $this->now()->format(DateTimeInterface::ATOM),
			'expiresAt' => $expires->format('Y-m-d'),
			'note' => trim($note),
			'labelConfirmed' => $this->labelled(labels: $labels) !== [],
			'status' => 'active',
		];

		try {
			$record = $this->store->save(record: $record);
		} catch (Throwable $e) {
			// A link nobody can see in the history must not stay open.
			$this->links->delete(shareId: $link['id']);
			$this->archiveFiles->discardById(userId: $uid, fileId: $archiveFileId);
			throw new RuntimeException('The share could not be recorded, so the link was taken back again', 500, $e);
		}

		return [
			'share' => $record,
			'url' => $link['url'],
			'expiresAt' => $expires->format('Y-m-d'),
			'expiryShortened' => $shortened,
			'manifest' => ['included' => $built['included'], 'excluded' => ($built['excluded'] ?? [])],
		];

	}//end shareBundle()

	/**
	 * The expiry a new link gets under Nextcloud's rules, after checking its password rule.
	 *
	 * @param string      $expiresOn The last day asked for (YYYY-MM-DD); empty means 14 days ahead.
	 * @param string|null $password  The link password, or null for none.
	 *
	 * @return array{0: DateTimeImmutable, 1: bool} The expiry, and whether Nextcloud's maximum shortened it.
	 *
	 * @throws InvalidArgumentException When the date is no future date, or Nextcloud requires a password (400).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function linkTerms(string $expiresOn, ?string $password): array {
		$policy = $this->links->linkPolicy();
		$terms  = $this->expiry(expiresOn: $expiresOn, maxDays: $policy['maxDays']);
		if ($policy['passwordRequired'] === true && trim((string) $password) === '') {
			throw new InvalidArgumentException('Nextcloud requires a password on every link', 400);
		}

		return $terms;

	}//end linkTerms()

	/**
	 * The label of every chosen file the user can open, after checking that each of them may be shared by link.
	 *
	 * A bundle is an archive the sharer owns, so Nextcloud's own reshare check never sees the files inside it:
	 * a file the user may not share by link must not leave inside an archive either.
	 *
	 * @param array<int, int> $fileIds The chosen documents.
	 * @param string          $uid     The sharer.
	 *
	 * @return array<int, string> The label per readable file id, empty when it has none.
	 *
	 * @throws RuntimeException When a file may not be shared (403), or a label cannot be read (503).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	private function bundleLabels(array $fileIds, string $uid): array {
		$readable = $this->readableIds(fileIds: $fileIds);
		$refused  = array_filter(
			$readable,
			fn (int $fileId): bool => $this->links->mayShare(fileId: $fileId, uid: $uid) === false
		);
		if ($refused !== []) {
			throw new RuntimeException(
				'Nextcloud does not let you share '.count($refused).' of the chosen documents by link',
				403
			);
		}

		$labels = [];
		foreach ($readable as $fileId) {
			$labels[$fileId] = $this->labelOf(fileId: $fileId);
		}

		return $labels;

	}//end bundleLabels()

	/**
	 * The record's file list: each file that went into the archive, with its label.
	 *
	 * @param array<int, array<string, mixed>> $included The manifest's included files.
	 * @param array<int, string>               $labels   The label per file id.
	 *
	 * @return array<int, array{fileId: int, name: string, version: string, label: string}> The files.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	private function bundleFiles(array $included, array $labels): array {
		$files = [];
		foreach ($included as $entry) {
			$fileId  = (int) ($entry['fileId'] ?? 0);
			$files[] = [
				'fileId' => $fileId,
				'name' => (string) ($entry['name'] ?? ''),
				'version' => (string) ($entry['version'] ?? ''),
				'label' => ($labels[$fileId] ?? ''),
			];
		}

		return $files;

	}//end bundleFiles()

	/**
	 * Close the links that ran out or were removed in Files (D7, REQ-SDL-004).
	 *
	 * A record past its expiry moves to `expired`; a record whose share is
	 * gone from Nextcloud moves to `revoked`, with "removed in Files" as who
	 * took it back. Either way a bundle's archive is deleted. One record that
	 * cannot be closed does not stop the others.
	 *
	 * @return array{expired: int, revoked: int, failed: int} How many records were closed, and how many could not be.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
	 */
	public function closeLapsed(): array {
		$today  = $this->now()->format('Y-m-d');
		$counts = ['expired' => 0, 'revoked' => 0, 'failed' => 0];

		foreach ($this->store->active() as $record) {
			$uuid = (string) ($record['uuid'] ?? '');
			try {
				$closed = $this->closeIfLapsed(record: $record, uuid: $uuid, today: $today);
				if ($closed !== null) {
					$counts[$closed]++;
				}
			} catch (Throwable $e) {
				$this->logger->warning('A share record could not be closed', ['uuid' => $uuid, 'exception' => $e->getMessage()]);
				$counts['failed']++;
			}//end try
		}//end foreach

		return $counts;

	}//end closeLapsed()

	/**
	 * Close one active record when its link ran out or was removed in Files.
	 *
	 * @param array<string, mixed> $record The active record.
	 * @param string               $uuid   Its uuid.
	 * @param string               $today  Today (YYYY-MM-DD).
	 *
	 * @return string|null 'expired' or 'revoked' when it was closed, null when the link still works.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
	 */
	private function closeIfLapsed(array $record, string $uuid, string $today): ?string {
		$expiresAt = (string) ($record['expiresAt'] ?? '');
		// Nextcloud lets a link work through its expiry day, so the
		// record turns expired the day after.
		if ($expiresAt !== '' && $expiresAt < $today) {
			$record['status'] = 'expired';
		} else if ($this->links->exists(shareId: (string) ($record['shareId'] ?? '')) === false) {
			$record['status']    = 'revoked';
			$record['revokedBy'] = self::REMOVED_IN_FILES;
			$record['revokedAt'] = $this->now()->format(DateTimeInterface::ATOM);
		} else {
			return null;
		}

		$this->discardArchive(record: $record);
		$this->store->save(record: $record, uuid: $uuid);

		return $record['status'];

	}//end closeIfLapsed()

	/**
	 * Delete a bundle's archive from its sharer's Files.
	 *
	 * @param array<string, mixed> $record The share record.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.5
	 */
	private function discardArchive(array $record): void {
		if (($record['kind'] ?? '') !== 'bundle') {
			return;
		}

		$this->archiveFiles->discardById(
			userId: (string) ($record['sharedBy'] ?? ''),
			fileId: (int) ($record['archiveFileId'] ?? 0)
		);

	}//end discardArchive()

	/**
	 * The chosen file ids: positive, each once, in the order given.
	 *
	 * @param array<int, mixed> $fileIds The ids as sent.
	 *
	 * @return array<int, int> The ids.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	private function fileIds(array $fileIds): array {
		$ids = [];
		foreach ($fileIds as $raw) {
			$id = $this->idOf(raw: $raw);
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}

		return array_values($ids);

	}//end fileIds()

	/**
	 * One sent file id as a number: an int, or a string of digits; 0 for anything else.
	 *
	 * @param mixed $raw The id as sent.
	 *
	 * @return int The id, or 0.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.4
	 */
	private function idOf(mixed $raw): int {
		if (is_int($raw) === true || (is_string($raw) === true && ctype_digit($raw) === true)) {
			return (int) $raw;
		}

		return 0;

	}//end idOf()

	/**
	 * Refuse until the sharer confirmed, when any of the files carries a label.
	 *
	 * @param array<int, string> $labels    The files' labels, empty when none.
	 * @param bool               $confirmed Whether the sharer confirmed.
	 * @param string             $what      How the refusal starts: what holds the labels.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a label needs a confirmation (409).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.6
	 */
	private function requireConfirmation(array $labels, bool $confirmed, string $what): void {
		$labelled = $this->labelled(labels: $labels);
		if ($labelled !== [] && $confirmed === false) {
			throw new InvalidArgumentException($what . ' labelled "' . implode('", "', $labelled) . '". Confirm before sharing it by link.', 409);
		}

	}//end requireConfirmation()

	/**
	 * The distinct labels the files carry.
	 *
	 * @param array<int, string> $labels The files' labels, empty when none.
	 *
	 * @return array<int, string> The distinct non-empty labels.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.6
	 */
	private function labelled(array $labels): array {
		return array_values(array_unique(array_filter($labels, static fn (string $label): bool => $label !== '')));

	}//end labelled()

	/**
	 * The expiry to use and whether Nextcloud's maximum shortened it.
	 *
	 * @param string   $expiresOn The requested last day, or empty for the default.
	 * @param int|null $maxDays   Nextcloud's enforced maximum, or null.
	 *
	 * @return array{0: DateTimeImmutable, 1: bool} The expiry and whether it was shortened.
	 *
	 * @throws InvalidArgumentException When the date is not a date, or not after today.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function expiry(string $expiresOn, ?int $maxDays): array {
		$today   = $this->now()->setTime(0, 0);
		$expires = $this->askedExpiry(expiresOn: trim($expiresOn), today: $today);
		if ($expires <= $today) {
			throw new InvalidArgumentException('The expiry date must lie after today', 400);
		}

		$latest = $this->latestExpiry(today: $today, maxDays: $maxDays);
		if ($latest !== null && $expires > $latest) {
			return [$latest, true];
		}

		return [$expires, false];

	}//end expiry()

	/**
	 * The expiry asked for, or 14 days ahead when none was asked.
	 *
	 * @param string            $expiresOn The last day asked for (YYYY-MM-DD), or empty.
	 * @param DateTimeImmutable $today     Today, at midnight.
	 *
	 * @return DateTimeImmutable The day the link stops working.
	 *
	 * @throws InvalidArgumentException When the date is not a YYYY-MM-DD date (400).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function askedExpiry(string $expiresOn, DateTimeImmutable $today): DateTimeImmutable {
		if ($expiresOn === '') {
			return $today->modify('+' . self::DEFAULT_DAYS . ' days');
		}

		$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $expiresOn, $today->getTimezone());
		if ($parsed === false || $parsed->format('Y-m-d') !== $expiresOn) {
			throw new InvalidArgumentException('A link needs an expiry date (YYYY-MM-DD)', 400);
		}

		return $parsed;

	}//end askedExpiry()

	/**
	 * The latest expiry Nextcloud allows, or null when it enforces no maximum.
	 *
	 * @param DateTimeImmutable $today   Today, at midnight.
	 * @param int|null          $maxDays Nextcloud's enforced maximum in days.
	 *
	 * @return DateTimeImmutable|null The latest day, or null.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function latestExpiry(DateTimeImmutable $today, ?int $maxDays): ?DateTimeImmutable {
		if ($maxDays === null || $maxDays <= 0) {
			return null;
		}

		return $today->modify('+' . $maxDays . ' days');

	}//end latestExpiry()

	/**
	 * The ids of the chosen files the signed-in user may read, in order.
	 *
	 * Labels are read only for these, so nothing about a file the user
	 * cannot open is looked up.
	 *
	 * @param array<int, int|string> $fileIds The chosen files.
	 *
	 * @return array<int, int> The readable ones.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.3
	 */
	private function readableIds(array $fileIds): array {
		$readable = [];
		foreach ($fileIds as $fileId) {
			$fileId = (int) $fileId;
			if ($this->files->node(fileId: $fileId) instanceof File) {
				$readable[] = $fileId;
			}
		}

		return $readable;

	}//end readableIds()

	/**
	 * The file's confidentiality label, or an empty string when it has none.
	 *
	 * @param int $fileId The file.
	 *
	 * @return string The label.
	 *
	 * @throws RuntimeException When the label cannot be read (503).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function labelOf(int $fileId): string {
		try {
			return trim((string) $this->labels->getLabelForFile($fileId)?->getLabel());
		} catch (Throwable $e) {
			// A label that cannot be read might be one that needs a confirmation: refuse rather than guess.
			$this->logger->warning('The confidentiality label could not be read', ['fileId' => $fileId, 'exception' => $e->getMessage()]);
			throw new RuntimeException('The confidentiality label of this document could not be read; try again later', 503, $e);
		}

	}//end labelOf()

	/**
	 * The session user.
	 *
	 * @return string The user id.
	 *
	 * @throws RuntimeException Without a user (401).
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function uid(): string {
		$user = $this->session->getUser();
		if ($user === null) {
			throw new RuntimeException('Sharing needs a signed-in user', 401);
		}

		return $user->getUID();

	}//end uid()

	/**
	 * Now, in UTC.
	 *
	 * @return DateTimeImmutable Now.
	 *
	 * @spec openspec/changes/share-document-links/tasks.md#task-2.2
	 */
	private function now(): DateTimeImmutable {
		return DateTimeImmutable::createFromInterface($this->clock->getDateTime())->setTimezone(new DateTimeZone('UTC'));

	}//end now()
}//end class
