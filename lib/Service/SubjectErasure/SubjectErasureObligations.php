<?php

/**
 * What stands over each document an erasure would touch.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use OCA\Filinq\Service\FinalDocumentRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldCaseRepository;
use OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze;
use OCA\Filinq\Service\LegalHold\LegalHoldRecordFreeze;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Builds, per file, the document shape SubjectErasureRules and
 * SubjectErasurePreview read: `legal_hold` (the covering matters, or a hold
 * on the record whose folder holds the file), `retention` (that record is
 * appraised to be kept permanently), whether the current version is final,
 * and why a file cannot be processed.
 *
 * Every read that fails refuses rather than clears: a hold register that cannot
 * be read marks every document held, and a finalisation record that cannot be
 * read marks the document unprocessable. An unverifiable state never counts as
 * a clean one.
 */
class SubjectErasureObligations {

	/**
	 * Formats whose content the erasure can rewrite and then check. Anything
	 * else is listed as unprocessable with the reason, never skipped silently.
	 *
	 * @var string[]
	 */
	public const ERASABLE_EXTENSIONS = ['pdf', 'docx', 'txt', 'md', 'csv', 'html', 'htm', 'xml', 'json'];

	/**
	 * Constructor.
	 *
	 * @param IRootFolder             $rootFolder     Every user's files.
	 * @param LegalHoldCaseRepository $holdCases      The hold register.
	 * @param FinalDocumentRepository $finalDocuments The finalisation records.
	 * @param LegalHoldRecordFreeze   $holdRecords    Loads a held record.
	 * @param LegalHoldFileFreeze     $holdFiles      The files behind a held record.
	 * @param SubjectErasureRecordStanding $records   The record behind each file.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly LegalHoldCaseRepository $holdCases,
		private readonly FinalDocumentRepository $finalDocuments,
		private readonly LegalHoldRecordFreeze $holdRecords,
		private readonly LegalHoldFileFreeze $holdFiles,
		private readonly SubjectErasureRecordStanding $records,
	) {

	}//end __construct()

	/**
	 * The document shape for each located file.
	 *
	 * @param array<int, array{fileId: int, occurrences: int, values: array<int, string>}> $located The locator's answer.
	 *
	 * @return array<int, array<string, mixed>> Per file: id, name, occurrences, values,
	 *                                          finalVersion, unreadable, legal_hold.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-4.1
	 */
	public function assess(array $located): array {
		$held = $this->heldFiles();
		$documents = [];
		foreach ($located as $file) {
			$fileId = (int) $file['fileId'];
			$node = $this->file(fileId: $fileId);
			$document = [
				'id' => (string) $fileId,
				'name' => '',
				'occurrences' => (int) $file['occurrences'],
				'values' => $file['values'],
				'finalVersion' => false,
				'unreadable' => '',
			];

			$document['legal_hold'] = $this->holdOn(held: $held, fileId: $fileId);
			$record = $this->records->forFile(node: $node);
			$document['legal_hold'] = $this->joined(first: $document['legal_hold'], second: $record['legal_hold']);
			$document['retention'] = $record['retention'];

			if ($node === null) {
				$document['unreadable'] = 'The file no longer exists.';
				$documents[] = $document;
				continue;
			}

			$document['name'] = $node->getName();
			$document['unreadable'] = $this->unreadableReason(node: $node);
			if ($document['unreadable'] === '') {
				$final = $this->isFinal(fileId: $fileId);
				if ($final === null) {
					$document['unreadable'] = 'The finalisation record of this document could not be read.';
				}

				$document['finalVersion'] = ($final === true);
			}

			$documents[] = $document;
		}//end foreach

		return $documents;

	}//end assess()

	/**
	 * Two standings as one, false when neither stands.
	 *
	 * @param string|false $first  One standing.
	 * @param string|false $second The other.
	 *
	 * @return string|false Both, joined.
	 */
	private function joined(string|false $first, string|false $second): string|false {
		$standing = array_filter([$first, $second], static fn (string|false $one): bool => $one !== false);
		if ($standing === []) {
			return false;
		}

		return implode('; ', $standing);

	}//end joined()

	/**
	 * The file behind an id, across every user, or null.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File|null The file.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function file(int $fileId): ?File {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

		if ($node instanceof File === false) {
			return null;
		}

		return $node;

	}//end file()

	/**
	 * Why this file cannot be erased, '' when it can.
	 *
	 * @param File $node The file.
	 *
	 * @return string The reason.
	 */
	private function unreadableReason(File $node): string {
		$extension = strtolower(pathinfo($node->getName(), PATHINFO_EXTENSION));
		if (in_array($extension, self::ERASABLE_EXTENSIONS, true) === false) {
			return 'Files of type "' . $extension . '" cannot be rewritten and checked, so the person may still be in it. Handle it by hand.';
		}

		if ($node->isUpdateable() === false) {
			return 'The file cannot be written.';
		}

		return '';

	}//end unreadableReason()

	/**
	 * Whether the file's current version is final; null when that cannot be read.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool|null The answer.
	 */
	private function isFinal(int $fileId): ?bool {
		try {
			$record = $this->finalDocuments->findCurrent(fileId: $fileId);
		} catch (Throwable) {
			return null;
		}

		return (strtolower((string) ($record['status'] ?? '')) === FinalDocumentRepository::STATUS_FINAL);

	}//end isFinal()

	/**
	 * File id => the names of the active hold cases whose placement locked or
	 * listed it; null when the hold register cannot be read.
	 *
	 * @return array<int, array<int, string>>|null The held files.
	 */
	private function heldFiles(): ?array {
		try {
			$cases = $this->holdCases->search(filters: ['status' => 'active']);
		} catch (Throwable) {
			return null;
		}

		$held = [];
		foreach ($cases as $case) {
			$name = (string) ($case['name'] ?? $case['uuid']);
			foreach ((array) ($case['fanOut'] ?? []) as $entry) {
				$fileIds = $this->entryFileIds(entry: (array) $entry);
				if ($fileIds === null) {
					return null;
				}

				foreach ($fileIds as $fileId) {
					$held[$fileId][] = $name;
				}
			}
		}

		return $held;

	}//end heldFiles()

	/**
	 * The hold standing over one file, false when none.
	 *
	 * @param array<int, array<int, string>>|null $held   The held files, null when unreadable.
	 * @param int                                 $fileId The file.
	 *
	 * @return string|false The covering matters, or false.
	 */
	private function holdOn(?array $held, int $fileId): string|false {
		if ($held === null) {
			return 'the hold register could not be read, so every document counts as held';
		}

		if (isset($held[$fileId]) === false) {
			return false;
		}

		return implode(', ', array_unique($held[$fileId]));

	}//end holdOn()

	/**
	 * The files one fan-out entry holds: the locked ids when the lock took,
	 * otherwise the files behind its record. Null when that record cannot be read.
	 *
	 * @param array<string, mixed> $entry The fan-out entry.
	 *
	 * @return array<int, int>|null The file ids.
	 */
	private function entryFileIds(array $entry): ?array {
		$fileIds = array_map('intval', (array) ($entry['fileIds'] ?? []));
		if ($fileIds !== [] || (string) ($entry['record'] ?? '') === 'released') {
			return $fileIds;
		}

		try {
			$entity = $this->holdRecords->load(ref: (string) ($entry['ref'] ?? ''));
		} catch (Throwable) {
			return null;
		}

		if ($entity === null) {
			return [];
		}

		return $this->holdFiles->fileIdsOf(entity: $entity);

	}//end entryFileIds()
}//end class
