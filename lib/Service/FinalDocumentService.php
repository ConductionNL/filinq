<?php

/**
 * Final Document Service
 *
 * The one place a document is made final, and the one place a write is refused
 * because it is. Every write path in Filinq asks this service before it touches
 * bytes, so the API, both editors, the batch correspondence path, the merge and
 * the anonymisation output all refuse the same document for the same reason. A
 * guard repeated in six callers is a guard missing from the seventh.
 *
 * The lifecycle itself is OpenRegister's: `documentVersion` declares
 * `x-openregister-lifecycle` with a terminal `final` state, so the transition,
 * its audit entry and the refusal of a transition back are the register's work
 * rather than a state machine written here (ADR-031, ADR-022).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use OCA\Filinq\Exception\DocumentFinalException;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Makes a document version final, and refuses every write to one that is.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentService {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository Store of the finalisation records.
	 * @param IRootFolder $rootFolder Root folder for user-scoped file access.
	 * @param IUserSession $userSession The current user session.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Make a document's current version final.
	 *
	 * Records who made it final, when, the reason or the act that did so, and
	 * the checksum of the file at that moment. A version that is already final
	 * is refused rather than re-finalised: the first moment is the one the
	 * archive needs, and overwriting it would lose it.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $reason The reason or the act that makes it final.
	 * @param string|null $actorId The acting user id, or null for the session user.
	 * @param string|null $actorName The acting user's display name, or null to resolve it.
	 *
	 * @return array<string, mixed> The stored final record.
	 *
	 * @throws DocumentFinalException When the version is already final.
	 * @throws RuntimeException When the document cannot be read or the record cannot be stored.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function finalise(int $fileId, string $reason, ?string $actorId = null, ?string $actorName = null): array {
		$file = $this->resolveFile(fileId: $fileId);
		$existing = $this->repository->findCurrent(fileId: $fileId);

		if ($existing !== null && $this->isFinal(record: $existing) === true) {
			throw new DocumentFinalException(
				message: $this->refusalSentence(record: $existing, action: 'make this document final again'),
				version: $existing
			);
		}

		$actor = $this->resolveActor(actorId: $actorId, actorName: $actorName);

		$record = [
			'fileId' => $fileId,
			'versionLabel' => '',
			'documentName' => $file->getName(),
			'status' => FinalDocumentRepository::STATUS_FINAL,
			'finalisedBy' => $actor['id'],
			'finalisedByName' => $actor['name'],
			'finalisedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
			'finalReason' => $reason,
			'fileChecksum' => $this->checksumOf(file: $file),
			'unfrozen' => (bool)($existing['unfrozen'] ?? false),
		];

		foreach (['supersedes', 'supersededBy', 'unfrozenBy', 'unfrozenAt', 'unfrozenReason'] as $carried) {
			if (isset($existing[$carried]) === true && (string)$existing[$carried] !== '') {
				$record[$carried] = $existing[$carried];
			}
		}

		$uuid = null;
		if ($existing !== null && (string)($existing['uuid'] ?? '') !== '') {
			$uuid = (string)$existing['uuid'];
		}

		return $this->repository->save(record: $record, uuid: $uuid);

	}//end finalise()

	/**
	 * The refusal for a write to this document, or null when it is free to change.
	 *
	 * 🔴 Fails closed. An unreadable register answers "refuse", never "allow":
	 * the whole point of the state is that a besluit cannot change quietly, and
	 * a guard that opens when its store is down is a guard that is off exactly
	 * when it matters.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $action What the caller is about to do, named in the sentence.
	 *
	 * @return string|null The refusal sentence, or null when the write may proceed.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refusalFor(int $fileId, string $action = 'change this document'): ?string {
		if ($fileId <= 0) {
			return null;
		}

		$records = $this->repository->findForFile(fileId: $fileId);
		if ($records === null) {
			return 'Filinq could not check whether this document is final, because the document '
				. 'register is unreachable. The document is left as it is.';
		}

		foreach ($records as $record) {
			if ((string)($record['versionLabel'] ?? '') !== '') {
				continue;
			}

			if ($this->isFinal(record: $record) === false) {
				continue;
			}

			return $this->refusalSentence(record: $record, action: $action);
		}

		return null;

	}//end refusalFor()

	/**
	 * Refuse the write, loudly, when the document is final.
	 *
	 * @param int $fileId The Nextcloud file id.
	 * @param string $action What the caller is about to do, named in the sentence.
	 *
	 * @return void
	 *
	 * @throws DocumentFinalException When the document's current version is final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function assertWritable(int $fileId, string $action = 'change this document'): void {
		$refusal = $this->refusalFor(fileId: $fileId, action: $action);
		if ($refusal === null) {
			return;
		}

		$record = [];
		$records = $this->repository->findForFile(fileId: $fileId);
		foreach (($records ?? []) as $candidate) {
			if ((string)($candidate['versionLabel'] ?? '') === '') {
				$record = $candidate;
				break;
			}
		}

		throw new DocumentFinalException(message: $refusal, version: $record);

	}//end assertWritable()

	/**
	 * The finalisation record of a document's current version, if it has one.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed>|null The record, or null when the version was never finalised.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function statusOf(int $fileId): ?array {
		return $this->repository->findCurrent(fileId: $fileId);

	}//end statusOf()

	/**
	 * Report whether a final version's file still holds the bytes it was frozen on.
	 *
	 * Freezing the record does not freeze the filesystem. Somebody with shell
	 * access can still change the file, and this is how that becomes visible.
	 * It is detection, not prevention, and it says so rather than implying more.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> `{final, matches, recordedChecksum, currentChecksum, message}`.
	 *
	 * @throws RuntimeException When the document cannot be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function verifyChecksum(int $fileId): array {
		$record = $this->repository->findCurrent(fileId: $fileId);
		if ($record === null || $this->isFinal(record: $record) === false) {
			return [
				'final' => false,
				'matches' => true,
				'recordedChecksum' => '',
				'currentChecksum' => '',
				'message' => '',
			];
		}

		$file = $this->resolveFile(fileId: $fileId);
		$recorded = (string)($record['fileChecksum'] ?? '');
		$current = $this->checksumOf(file: $file);
		$matches = ($recorded !== '' && hash_equals($recorded, $current) === true);

		$message = '';
		if ($matches === false) {
			$message = sprintf(
				'The file behind this final version has changed on the storage. It was frozen on %s '
				. 'with checksum %s and now reads %s. Filinq refuses writes to a final version, so this '
				. 'change was made outside Filinq.',
				$this->readableMoment(value: (string)($record['finalisedAt'] ?? '')),
				$this->shortChecksum(value: $recorded),
				$this->shortChecksum(value: $current)
			);
		}

		return [
			'final' => true,
			'matches' => $matches,
			'recordedChecksum' => $recorded,
			'currentChecksum' => $current,
			'message' => $message,
		];

	}//end verifyChecksum()

	/**
	 * Whether a record sits in the final state.
	 *
	 * @param array<string, mixed> $record The finalisation record.
	 *
	 * @return bool True when the record is final.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function isFinal(array $record): bool {
		return (strtolower((string)($record['status'] ?? '')) === FinalDocumentRepository::STATUS_FINAL);

	}//end isFinal()

	/**
	 * The sentence a refused caller shows, naming the version, its state, who made it final and when.
	 *
	 * "Cannot edit" with no reason is a support ticket, so the four facts the
	 * reader needs to act are all in it: which document, that it is final, who
	 * made it final, and when.
	 *
	 * @param array<string, mixed> $record The finalisation record.
	 * @param string $action What the caller was about to do.
	 *
	 * @return string The refusal sentence.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function refusalSentence(array $record, string $action = 'change this document'): string {
		$name = (string)($record['documentName'] ?? '');
		if ($name === '') {
			$name = 'this document';
		}

		$who = (string)($record['finalisedByName'] ?? '');
		if ($who === '') {
			$who = (string)($record['finalisedBy'] ?? '');
		}

		if ($who === '') {
			$who = 'somebody whose name was not recorded';
		}

		$sentence = sprintf(
			'You cannot %s. The current version of %s is final. %s made it final on %s.',
			$action,
			$name,
			$who,
			$this->readableMoment(value: (string)($record['finalisedAt'] ?? ''))
		);

		$reason = (string)($record['finalReason'] ?? '');
		if ($reason !== '') {
			$sentence .= ' Reason: ' . $reason . '.';
		}

		return $sentence . ' Issue a correction instead, which keeps this version readable.';

	}//end refusalSentence()

	/**
	 * Read a stored moment back as something a person can read.
	 *
	 * @param string $value The stored ISO 8601 moment.
	 *
	 * @return string The readable moment, or the raw value when it cannot be parsed.
	 *
	 * @spec exclude Formatting helper for the refusal sentence; no behaviour of its own.
	 */
	private function readableMoment(string $value): string {
		if ($value === '') {
			return 'a moment that was not recorded';
		}

		try {
			return (new DateTimeImmutable($value))->format('j F Y \a\t H:i');
		} catch (Throwable $e) {
			$this->logger->debug(
				message: '[FinalDocumentService] could not read a stored moment',
				context: ['file' => __FILE__, 'line' => __LINE__, 'value' => $value, 'error' => $e->getMessage()]
			);

			return $value;
		}

	}//end readableMoment()

	/**
	 * Shorten a checksum for a sentence a person reads.
	 *
	 * @param string $value The full checksum.
	 *
	 * @return string The first twelve characters, or a placeholder.
	 *
	 * @spec exclude Formatting helper; no behaviour of its own.
	 */
	private function shortChecksum(string $value): string {
		if ($value === '') {
			return 'nothing';
		}

		return substr($value, 0, 12);

	}//end shortChecksum()

	/**
	 * The sha256 of a file's current content.
	 *
	 * @param File $file The file.
	 *
	 * @return string The checksum.
	 *
	 * @throws RuntimeException When the file cannot be read.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function checksumOf(File $file): string {
		try {
			return hash('sha256', $file->getContent());
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not read the document to record its checksum: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

	}//end checksumOf()

	/**
	 * Resolve a file through the acting user's folder.
	 *
	 * A file the user cannot read is indistinguishable from one that is not
	 * there, which is the same posture the version endpoints already take
	 * (ADR-005).
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File The resolved file.
	 *
	 * @throws RuntimeException When the file cannot be resolved.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function resolveFile(int $fileId): File {
		$user = $this->userSession->getUser();
		if ($user === null || $fileId <= 0) {
			throw new RuntimeException(message: 'Document not found.');
		}

		$nodes = $this->rootFolder->getUserFolder($user->getUID())->getById($fileId);
		if ($nodes === []) {
			throw new RuntimeException(message: 'Document not found.');
		}

		$node = $nodes[0];
		if (($node instanceof File) === false) {
			throw new RuntimeException(message: 'Document not found.');
		}

		return $node;

	}//end resolveFile()

	/**
	 * Resolve the acting person, preferring what the caller passed.
	 *
	 * @param string|null $actorId The acting user id, or null for the session user.
	 * @param string|null $actorName The acting user's display name, or null to resolve it.
	 *
	 * @return array{id: string, name: string} The acting person.
	 *
	 * @throws RuntimeException When there is no acting person at all.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function resolveActor(?string $actorId, ?string $actorName): array {
		$user = $this->userSession->getUser();

		$id = (string)$actorId;
		if ($id === '' && $user !== null) {
			$id = $user->getUID();
		}

		if ($id === '') {
			throw new RuntimeException(
				message: 'Making a document final needs a named person, and there is no acting user.'
			);
		}

		$name = (string)$actorName;
		if ($name === '' && $user !== null && $user->getUID() === $id) {
			$name = $user->getDisplayName();
		}

		if ($name === '') {
			$name = $id;
		}

		return ['id' => $id, 'name' => $name];

	}//end resolveActor()
}//end class
