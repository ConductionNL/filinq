<?php

/**
 * Pseudonym Restore Service
 *
 * Turns a reversibly anonymised copy back into the original, for someone who
 * may. The anonymised file is never touched: a text copy gets a restored sibling,
 * and a format whose text cannot be rewritten safely (PDF, office documents)
 * gets a list of which placeholder stood for which value instead of a file that
 * would come out broken.
 *
 * Every step of an attempt is in the audit trail, and the entry that grants the
 * restore is written before anything is decrypted. If that write fails, nothing
 * is restored.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Pseudonymisation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Pseudonymisation;

use OCA\Filinq\Exception\PseudonymRestoreRefusedException;
use OCA\Filinq\Service\Redaction\AnonymizationLinkReader;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Restore the original of one reversibly anonymised copy.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Pseudonymisation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class PseudonymRestoreService {

	/**
	 * Mime types whose bytes are the text, so a placeholder in them can be
	 * rewritten without breaking the file.
	 *
	 * @var array<int, string>
	 */
	private const REWRITABLE = [
		'text/plain',
		'text/markdown',
		'text/csv',
		'text/html',
		'application/json',
		'application/xml',
		'text/xml',
	];

	/**
	 * Constructor.
	 *
	 * @param PseudonymRestoreGate $gate Who may restore.
	 * @param PseudonymRestoreAudit $audit The audit trail of restores.
	 * @param PseudonymMapService $maps The encrypted key store.
	 * @param PseudonymPairs $pairs Puts the original values back.
	 * @param AnonymizationLinkReader $links Reads the anonymisation link.
	 * @param IRootFolder $rootFolder The caller's files.
	 * @param LoggerInterface $logger Logger.
	 */
	public function __construct(
		private readonly PseudonymRestoreGate $gate,
		private readonly PseudonymRestoreAudit $audit,
		private readonly PseudonymMapService $maps,
		private readonly PseudonymPairs $pairs,
		private readonly AnonymizationLinkReader $links,
		private readonly IRootFolder $rootFolder,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Restore the original of one anonymised copy.
	 *
	 * The order is the control: the gate, then the copy the caller can open,
	 * then the key, then the audit entry that grants it, and only then the
	 * decrypted values touch a file or a response.
	 *
	 * @param string $linkId The anonymisation link uuid.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed> `mode` copy (fileId, fileName, path, restored) or
	 *                              report (entries, reason).
	 *
	 * @throws PseudonymRestoreRefusedException When the restore is refused, for any reason.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.1
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-3.2
	 */
	public function restore(string $linkId, string $userId): array {
		try {
			$this->gate->assertMayRestore(userId: $userId);
			$copy = $this->anonymisedCopy(linkId: $linkId, userId: $userId);
		} catch (PseudonymRestoreRefusedException $refusal) {
			$this->recordRefusal(linkId: $linkId, userId: $userId, refusal: $refusal, action: PseudonymRestoreAudit::ACTION_DENIED);
			throw $refusal;
		}

		try {
			$map = $this->maps->readPairs(linkId: $linkId);
		} catch (PseudonymRestoreRefusedException $refusal) {
			$this->recordRefusal(linkId: $linkId, userId: $userId, refusal: $refusal, action: PseudonymRestoreAudit::ACTION_FAILED);
			throw $refusal;
		}

		try {
			$this->audit->record(
				linkId: $linkId,
				action: PseudonymRestoreAudit::ACTION_GRANTED,
				userId: $userId,
				details: ['pseudonymMap' => $map['uuid'], 'anonymizedFileId' => $copy->getId()]
			);
		} catch (RuntimeException $e) {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_AUDIT_UNAVAILABLE,
				message: 'The restore was not done because the audit trail could not record it: ' . $e->getMessage()
			);
		}

		$result = $this->produce(copy: $copy, pairs: $map['pairs']);
		$this->recordOutcome(linkId: $linkId, userId: $userId, result: $result);

		return $result;

	}//end restore()

	/**
	 * Whether this link kept a key and whether the caller may use it.
	 *
	 * @param int $anonymizedFileId The redacted copy's file id.
	 * @param string $userId The caller.
	 *
	 * @return array<string, mixed>|null linkId, reversible, entryCount, mayRestore; null when
	 *                                   the caller cannot open the copy or it has no link.
	 *
	 * @spec openspec/changes/archive/2026-09-29-reversible-pseudonymization/tasks.md#task-4.2
	 */
	public function status(int $anonymizedFileId, string $userId): ?array {
		if ($this->openableFile(fileId: $anonymizedFileId, userId: $userId) === null) {
			return null;
		}

		$link = $this->links->forAnonymized(anonymizedFileId: $anonymizedFileId);
		if ($link === null || ($link['uuid'] ?? '') === '') {
			return null;
		}

		$map = null;
		try {
			$map = $this->maps->describe(linkId: (string) $link['uuid']);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PseudonymRestoreService] the key metadata could not be read',
				context: ['linkId' => $link['uuid'], 'error' => $e->getMessage()]
			);
		}

		return [
			'linkId' => (string) $link['uuid'],
			'reversible' => $map !== null,
			'entryCount' => (int) ($map['entryCount'] ?? 0),
			'mayRestore' => $map !== null && $this->gate->mayRestore(userId: $userId),
		];

	}//end status()

	/**
	 * The anonymised copy of this link, as a file the caller can open.
	 *
	 * @param string $linkId The link uuid.
	 * @param string $userId The caller.
	 *
	 * @return File The anonymised copy.
	 *
	 * @throws PseudonymRestoreRefusedException When there is no such link, or the caller cannot open its copy.
	 */
	private function anonymisedCopy(string $linkId, string $userId): File {
		$link = $this->links->byId(linkId: $linkId);
		$file = null;
		if ($link !== null) {
			$file = $this->openableFile(fileId: (int) ($link['anonymizedFileId'] ?? 0), userId: $userId);
		}

		if ($file === null) {
			// One answer for "no such link" and "not yours": a guessed id learns nothing.
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_NOT_FOUND,
				message: 'No anonymised copy under this link that the caller can open.'
			);
		}

		return $file;

	}//end anonymisedCopy()

	/**
	 * A file in the caller's own view of the files, or null.
	 *
	 * @param int $fileId The file id.
	 * @param string $userId The caller.
	 *
	 * @return File|null The file.
	 */
	private function openableFile(int $fileId, string $userId): ?File {
		if ($fileId <= 0 || $userId === '') {
			return null;
		}

		try {
			$node = $this->rootFolder->getUserFolder($userId)->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

		if ($node instanceof File) {
			return $node;
		}

		return null;

	}//end openableFile()

	/**
	 * Write the restored sibling of a text copy, or build the report for any other format.
	 *
	 * @param File $copy The anonymised copy. Read, never written.
	 * @param array<int, array<string, string>> $pairs The decrypted pairs.
	 *
	 * @return array<string, mixed> The result.
	 *
	 * @throws PseudonymRestoreRefusedException When the restored copy could not be written.
	 */
	private function produce(File $copy, array $pairs): array {
		if (in_array(strtolower((string) $copy->getMimeType()), self::REWRITABLE, true) === false) {
			return [
				'mode' => 'report',
				'reason' => 'format_not_rewritable',
				'entries' => $this->reportEntries(pairs: $pairs),
			];
		}

		try {
			$reversed = $this->pairs->reverse(text: (string) $copy->getContent(), pairs: $pairs);
			$folder = $copy->getParent();
			$name = $folder->getNonExistingName($this->restoredName(name: (string) $copy->getName()));
			$restored = $folder->newFile($name, $reversed['text']);
		} catch (Throwable $e) {
			throw new PseudonymRestoreRefusedException(
				reason: PseudonymRestoreRefusedException::REASON_WRITE_FAILED,
				message: 'The restored copy could not be written: ' . $e->getMessage()
			);
		}

		return [
			'mode' => 'copy',
			'fileId' => $restored->getId(),
			'fileName' => $restored->getName(),
			'path' => $restored->getPath(),
			'restored' => $reversed['restored'],
		];

	}//end produce()

	/**
	 * `brief_anonymized.txt` becomes `brief_restored.txt`.
	 *
	 * @param string $name The anonymised copy's name.
	 *
	 * @return string The restored copy's name.
	 */
	private function restoredName(string $name): string {
		$extension = pathinfo($name, PATHINFO_EXTENSION);
		$base = preg_replace('/_anonymi[sz]ed$/i', '', pathinfo($name, PATHINFO_FILENAME));
		$restored = $base . '_restored';
		if ($extension !== '') {
			$restored .= '.' . $extension;
		}

		return $restored;

	}//end restoredName()

	/**
	 * The report rows, in the order a reader counts: by type, then by number.
	 *
	 * @param array<int, array<string, string>> $pairs The pairs.
	 *
	 * @return array<int, array{placeholder: string, originalValue: string, entityType: string}> The rows.
	 */
	private function reportEntries(array $pairs): array {
		$rows = [];
		foreach ($pairs as $pair) {
			$rows[] = [
				'placeholder' => (string) ($pair['placeholder'] ?? ''),
				'originalValue' => (string) ($pair['originalValue'] ?? ''),
				'entityType' => (string) ($pair['entityType'] ?? ''),
			];
		}

		usort($rows, static fn (array $left, array $right): int => strnatcmp($left['placeholder'], $right['placeholder']));

		return $rows;

	}//end reportEntries()

	/**
	 * Record a refusal. A failing audit write does not turn a refusal into a grant.
	 *
	 * @param string $linkId The link uuid.
	 * @param string $userId The caller.
	 * @param PseudonymRestoreRefusedException $refusal Why.
	 * @param string $action ACTION_DENIED or ACTION_FAILED.
	 *
	 * @return void
	 */
	private function recordRefusal(string $linkId, string $userId, PseudonymRestoreRefusedException $refusal, string $action): void {
		try {
			$this->audit->record(linkId: $linkId, action: $action, userId: $userId, details: ['reason' => $refusal->getReason()]);
		} catch (RuntimeException $e) {
			$this->logger->error(
				message: '[PseudonymRestoreService] a refused restore could not be written to the audit trail',
				context: ['linkId' => $linkId, 'userId' => $userId, 'reason' => $refusal->getReason(), 'error' => $e->getMessage()]
			);
		}

	}//end recordRefusal()

	/**
	 * Record what the granted restore produced. The grant is already on record.
	 *
	 * @param string $linkId The link uuid.
	 * @param string $userId The caller.
	 * @param array<string, mixed> $result The result.
	 *
	 * @return void
	 */
	private function recordOutcome(string $linkId, string $userId, array $result): void {
		try {
			$this->audit->record(
				linkId: $linkId,
				action: PseudonymRestoreAudit::ACTION_RESTORED,
				userId: $userId,
				details: ['mode' => $result['mode'], 'restoredFileId' => ($result['fileId'] ?? null)]
			);
		} catch (RuntimeException $e) {
			$this->logger->error(
				message: '[PseudonymRestoreService] the outcome of a granted restore could not be written to the audit trail',
				context: ['linkId' => $linkId, 'userId' => $userId, 'error' => $e->getMessage()]
			);
		}

	}//end recordOutcome()
}//end class
