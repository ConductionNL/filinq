<?php

/**
 * A person's decision on a classification suggestion
 *
 * The only way a suggestion has any effect. Confirming writes the type and
 * the correspondent onto the document object through the normal save path,
 * and, when a dossier is part of the confirmation, moves the file into that
 * dossier's folder. Correcting is confirming with other values: the record
 * keeps what the classifier said beside what the person decided. Rejecting
 * closes the suggestion and changes nothing else.
 *
 * The file is resolved in the reviewer's own folder, so a person can only
 * decide on documents they can reach; the dossier folder too, so a file is
 * only moved where the reviewer could have moved it by hand. A dossier
 * whose folder the reviewer cannot reach is recorded, and the response says
 * so (`filing: recorded`), never silently.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Classification;

use DateTimeInterface;
use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\MetadataService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use Throwable;

/**
 * Confirm, correct or reject one suggestion.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
 */
class ClassificationDecisionService {

	/**
	 * The types a confirmation may set.
	 *
	 * @var string[]
	 */
	public const TYPES = ['brief', 'besluit', 'factuur', 'rapport', 'contract', 'formulier', DocumentTypeClassifier::FALLBACK];

	/**
	 * Constructor.
	 *
	 * @param ClassificationResultRepository $results   The classificationResult records.
	 * @param IRootFolder                    $root      Resolves files and folders in the reviewer's folder.
	 * @param MetadataService                $metadata  Writes the confirmed type onto the document object.
	 * @param DossierObjectRepository        $dossiers  Reads a dossier's folder binding.
	 * @param ITimeFactory                   $time      The decision time.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ClassificationResultRepository $results,
		private readonly IRootFolder $root,
		private readonly MetadataService $metadata,
		private readonly DossierObjectRepository $dossiers,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * The suggestions still waiting, limited to files the reviewer can reach.
	 *
	 * @param string $userId The reviewer.
	 *
	 * @return array<int, array<string, mixed>> The records.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function pending(string $userId): array {
		$reachable = [];
		foreach ($this->results->search(filters: ['status' => 'suggested']) as $record) {
			if ($this->nodeFor(userId: $userId, fileId: (int) ($record['fileId'] ?? 0)) !== null) {
				$reachable[] = $record;
			}
		}

		return $reachable;

	}//end pending()

	/**
	 * The active record of one file, decided or not, for the document card.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return array<string, mixed>|null The record, or null for a file the reviewer cannot reach or without one.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#3-2
	 */
	public function forFile(int $fileId, string $userId): ?array {
		if ($this->nodeFor(userId: $userId, fileId: $fileId) === null) {
			return null;
		}

		return $this->results->activeFor(fileId: $fileId);

	}//end forFile()

	/**
	 * Confirm a suggestion, with the reviewer's corrections if any.
	 *
	 * @param int                  $fileId  The file.
	 * @param string               $userId  The reviewer.
	 * @param array<string, mixed> $choices documentType, correspondent ({name, entityType}) and dossier
	 *                                      (a uuid, or null for none); a missing key keeps the suggestion.
	 *
	 * @return array<string, mixed> The confirmed record, with `filing` moved or recorded.
	 *
	 * @throws ClassificationRefused 404 for a file the reviewer cannot reach or without a suggestion,
	 *                               409 when it was already decided, 422 for an unknown type.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function confirm(int $fileId, string $userId, array $choices): array {
		[$record, $node] = $this->open(fileId: $fileId, userId: $userId);

		$type = (string) ($choices['documentType'] ?? $record['suggestedDocumentType']);
		if (in_array($type, self::TYPES, true) === false) {
			throw new ClassificationRefused(message: 'Unknown document type', code: 422);
		}

		$correspondent = $this->correspondentOf(choices: $choices, record: $record);
		$dossier = $record['suggestedDossier'] ?? null;
		if (array_key_exists('dossier', $choices) === true) {
			$dossier = $choices['dossier'];
		}

		$this->writeCanonical(record: $record, type: $type, correspondent: $correspondent);

		$filing = null;
		if (is_string($dossier) === true && $dossier !== '') {
			$filing = $this->file(node: $node, userId: $userId, dossier: $dossier);
		}

		return $this->results->save(
			record: [
				'status' => 'confirmed',
				'confirmedDocumentType' => $type,
				'confirmedCorrespondent' => $correspondent,
				'confirmedDossier' => $dossier,
				'filing' => $filing,
				'confirmedBy' => $userId,
				'confirmedAt' => $this->time->now()->format(DateTimeInterface::ATOM),
			] + $record,
			uuid: (string) $record['uuid']
		);

	}//end confirm()

	/**
	 * Reject a suggestion: the record closes, nothing else changes.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return array<string, mixed> The rejected record.
	 *
	 * @throws ClassificationRefused As for confirm().
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-4
	 */
	public function reject(int $fileId, string $userId): array {
		[$record] = $this->open(fileId: $fileId, userId: $userId);

		return $this->results->save(
			record: [
				'status' => 'rejected',
				'confirmedBy' => $userId,
				'confirmedAt' => $this->time->now()->format(DateTimeInterface::ATOM),
			] + $record,
			uuid: (string) $record['uuid']
		);

	}//end reject()

	/**
	 * The open suggestion of a file the reviewer can reach, and the file.
	 *
	 * @param int    $fileId The file.
	 * @param string $userId The reviewer.
	 *
	 * @return array{0: array<string, mixed>, 1: Node} The record and the file node.
	 *
	 * @throws ClassificationRefused 404 or 409.
	 */
	private function open(int $fileId, string $userId): array {
		$node = $this->nodeFor(userId: $userId, fileId: $fileId);
		$record = $this->results->activeFor(fileId: $fileId);
		if ($node === null || $record === null) {
			// The same answer for "not yours" and "not there": no probing for file ids.
			throw new ClassificationRefused(message: 'No suggestion for this file', code: 404);
		}

		if (($record['status'] ?? '') !== 'suggested') {
			throw new ClassificationRefused(message: 'This suggestion was already decided', code: 409);
		}

		return [$record, $node];

	}//end open()

	/**
	 * The confirmed correspondent: the reviewer's, else the suggested one.
	 *
	 * @param array<string, mixed> $choices The reviewer's choices.
	 * @param array<string, mixed> $record  The record.
	 *
	 * @return array{name: string, entityType: string, source: string}|null The correspondent.
	 */
	private function correspondentOf(array $choices, array $record): ?array {
		if (array_key_exists('correspondent', $choices) === false) {
			return $record['suggestedCorrespondent'] ?? null;
		}

		$given = $choices['correspondent'];
		if (is_array($given) === false || trim((string) ($given['name'] ?? '')) === '') {
			return null;
		}

		$entityType = 'PERSON';
		if (($given['entityType'] ?? '') === 'ORGANIZATION') {
			$entityType = 'ORGANIZATION';
		}

		return ['name' => trim((string) $given['name']), 'entityType' => $entityType, 'source' => 'person'];

	}//end correspondentOf()

	/**
	 * Write the confirmed type and correspondent onto the document object.
	 *
	 * @param array<string, mixed>      $record        The record.
	 * @param string                    $type          The confirmed type.
	 * @param array<string, mixed>|null $correspondent The confirmed correspondent.
	 *
	 * @return void
	 */
	private function writeCanonical(array $record, string $type, ?array $correspondent): void {
		$objectId = (string) ($record['objectId'] ?? '');
		if ($objectId === '') {
			return;
		}

		$this->metadata->saveEnrichedMetadata(
			objectId: $objectId,
			register: (string) ($record['objectRegister'] ?? ''),
			schema: (string) ($record['objectSchema'] ?? ''),
			metadata: ['documentType' => $type, 'correspondent' => $correspondent]
		);

	}//end writeCanonical()

	/**
	 * Move the file into the dossier's folder, as far as the reviewer can reach it.
	 *
	 * @param Node   $node    The file.
	 * @param string $userId  The reviewer.
	 * @param string $dossier The dossier uuid.
	 *
	 * @return string `moved`, or `recorded` when the folder cannot be reached.
	 */
	private function file(Node $node, string $userId, string $dossier): string {
		try {
			$folderRef = (int) ($this->dossiers->loadDossierContext(dossierUuid: $dossier)['folderRef'] ?? 0);
			$folder = $this->root->getUserFolder($userId)->getFirstNodeById($folderRef);
			if (($folder instanceof Folder) === false) {
				return 'recorded';
			}

			$node->move(rtrim($folder->getPath(), '/') . '/' . $folder->getNonExistingName($node->getName()));
		} catch (Throwable) {
			return 'recorded';
		}

		return 'moved';

	}//end file()

	/**
	 * The file as the reviewer sees it, or null.
	 *
	 * @param string $userId The reviewer.
	 * @param int    $fileId The file id.
	 *
	 * @return Node|null The node.
	 */
	private function nodeFor(string $userId, int $fileId): ?Node {
		if ($fileId <= 0) {
			return null;
		}

		try {
			return $this->root->getUserFolder($userId)->getFirstNodeById($fileId);
		} catch (Throwable) {
			return null;
		}

	}//end nodeFor()
}//end class
