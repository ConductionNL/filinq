<?php

/**
 * Final Document Correction Service
 *
 * Correcting a final document does not edit it. It produces a new document
 * that says which one it supersedes, and leaves the superseded one readable
 * and still final. That is the difference between a correction and a rewrite,
 * and it is what makes the correction explicable years later.
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

use OCP\Files\File;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Creates the correction of a final document and links the two.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
 */
class FinalDocumentCorrectionService {

	/**
	 * Constructor.
	 *
	 * @param FinalDocumentRepository $repository Store of the finalisation records.
	 * @param FinalDocumentService $finalDocuments The finalisation service.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly FinalDocumentRepository $repository,
		private readonly FinalDocumentService $finalDocuments,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Correct a final document by superseding it.
	 *
	 * The frozen file is copied to a sibling, the copy gets a draft record
	 * naming the record it supersedes, and the frozen record is pointed back at
	 * the copy. A reader of either end sees the chain.
	 *
	 * The frozen file is never opened for writing, which is why this is safe to
	 * offer on a document the guard refuses: the correction is a different file.
	 *
	 * @param int $fileId The Nextcloud file id of the final document.
	 * @param string $reason Why the correction was issued.
	 *
	 * @return array<string, mixed> `{fileId, name, path, version, supersedes}` of the correction.
	 *
	 * @throws RuntimeException When the document is not final, or the copy cannot be written.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function correct(int $fileId, string $reason): array {
		$superseded = $this->repository->findCurrent(fileId: $fileId);
		if ($superseded === null || $this->finalDocuments->isFinal(record: $superseded) === false) {
			throw new RuntimeException(
				message: 'Only a final document is corrected by superseding it. This one is not final, '
					. 'so edit it directly.'
			);
		}

		$file = $this->finalDocuments->resolveFile(fileId: $fileId);
		$copy = $this->copyBeside(file: $file);

		$actor = $this->finalDocuments->resolveActor(actorId: null, actorName: null);

		$correction = $this->repository->save(
			record: [
				'fileId' => $copy->getId(),
				'versionLabel' => '',
				'documentName' => $copy->getName(),
				'status' => FinalDocumentRepository::STATUS_DRAFT,
				'supersedes' => (string)($superseded['uuid'] ?? ''),
				'finalReason' => $reason,
				'unfrozen' => false,
			]
		);

		$this->linkBack(superseded: $superseded, correctionUuid: (string)($correction['uuid'] ?? ''));

		$this->logger->info(
			message: '[FinalDocumentCorrectionService] a final document was superseded by a correction',
			context: [
				'file' => __FILE__,
				'line' => __LINE__,
				'supersededFileId' => $fileId,
				'correctionFileId' => $copy->getId(),
				'actor' => $actor['id'],
			]
		);

		return [
			'fileId' => $copy->getId(),
			'name' => $copy->getName(),
			'path' => $copy->getPath(),
			'version' => $correction,
			'supersedes' => $superseded,
		];

	}//end correct()

	/**
	 * The chain a version belongs to, read from either end.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return array<string, mixed> `{version, supersedes, supersededBy}`, each null when absent.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	public function chainFor(int $fileId): array {
		$record = $this->repository->findCurrent(fileId: $fileId);
		if ($record === null) {
			return ['version' => null, 'supersedes' => null, 'supersededBy' => null];
		}

		return [
			'version' => $record,
			'supersedes' => $this->repository->findByUuid(uuid: (string)($record['supersedes'] ?? '')),
			'supersededBy' => $this->repository->findByUuid(uuid: (string)($record['supersededBy'] ?? '')),
		];

	}//end chainFor()

	/**
	 * Copy the frozen file to a sibling the correction can be written into.
	 *
	 * @param File $file The frozen file.
	 *
	 * @return File The copy.
	 *
	 * @throws RuntimeException When the copy cannot be written.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	private function copyBeside(File $file): File {
		try {
			$parent = $file->getParent();
			$name = $parent->getNonExistingName($this->correctionName(name: $file->getName()));

			return $parent->newFile($name, $file->getContent());
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not write the correction beside the final document: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

	}//end copyBeside()

	/**
	 * The name a correction is offered under.
	 *
	 * @param string $name The frozen document's name.
	 *
	 * @return string The correction's name, keeping the extension.
	 *
	 * @spec exclude Naming helper; the uniqueness that matters is the folder's own.
	 */
	private function correctionName(string $name): string {
		$dot = strrpos($name, '.');
		if ($dot === false || $dot === 0) {
			return $name . ' correction';
		}

		return substr($name, 0, $dot) . ' correction' . substr($name, $dot);

	}//end correctionName()

	/**
	 * Point the superseded record at its correction.
	 *
	 * A half-written chain is worse than none: a reader of the frozen version
	 * would never learn a correction exists. The write is therefore loud when
	 * it fails rather than logged and swallowed.
	 *
	 * @param array<string, mixed> $superseded The superseded record.
	 * @param string $correctionUuid The correction's record uuid.
	 *
	 * @return void
	 *
	 * @throws RuntimeException When the back reference cannot be written.
	 *
	 * @spec openspec/changes/final-documents-frozen/specs/document-versions/spec.md
	 */
	private function linkBack(array $superseded, string $correctionUuid): void {
		$uuid = (string)($superseded['uuid'] ?? '');
		if ($uuid === '' || $correctionUuid === '') {
			throw new RuntimeException(
				message: 'The correction was written but the chain could not be closed, because one of '
					. 'the two versions has no identifier.'
			);
		}

		$record = $superseded;
		unset($record['uuid']);
		$record['supersededBy'] = $correctionUuid;

		$this->repository->save(record: $record, uuid: $uuid);

	}//end linkBack()
}//end class
