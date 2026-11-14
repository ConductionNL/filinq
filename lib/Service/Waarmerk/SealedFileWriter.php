<?php

/**
 * Sealed File Writer
 *
 * Writes a sealed PDF next to its source and records its seal, or leaves nothing.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Waarmerk
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Waarmerk;
use OCA\Filinq\Service\WaarmerkService;
use OCP\Files\Folder;
use Throwable;

/**
 * The last step of sealing: the sealed file, then its record.
 *
 * When the record cannot be stored the written file is deleted again, so a
 * failed seal never leaves an unrecorded sealed artifact behind.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Waarmerk
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
 */
class SealedFileWriter {

	/**
	 * Constructor.
	 *
	 * @param WaarmerkService $waarmerk Records the seal.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WaarmerkService $waarmerk,
	) {

	}//end __construct()

	/**
	 * Write the sealed PDF into a folder and record its seal.
	 *
	 * @param Folder $folder Where the sealed file goes.
	 * @param string $name The wanted file name (made unique in the folder).
	 * @param SealedArtifact $artifact The prepared artifact.
	 *
	 * @return array{waarmerk: array<string, mixed>, fileId: int, fileName: string}
	 *
	 * @throws Throwable When the record cannot be stored (the file is removed first).
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public function write(Folder $folder, string $name, SealedArtifact $artifact): array {
		$file = $folder->newFile($folder->getNonExistingName($name), $artifact->pdf);
		try {
			$record = $this->waarmerk->record(artifact: $artifact, documentFileId: (int) $file->getId());
		} catch (Throwable $e) {
			try {
				$file->delete();
			} catch (Throwable) {
				// The record failure is what the caller must see.
			}

			throw $e;
		}

		return ['waarmerk' => $record, 'fileId' => (int) $file->getId(), 'fileName' => $file->getName()];

	}//end write()

	/**
	 * A sibling name: `<base>-<suffix>.pdf`.
	 *
	 * @param string $sourceName The source file name.
	 * @param string $suffix The suffix.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md#requirement-a-processed-pdf-can-be-sealed-with-a-waarmerk-req-ddwmk-002
	 */
	public static function siblingName(string $sourceName, string $suffix): string {
		$base = (string) pathinfo($sourceName, PATHINFO_FILENAME);
		if ($base === '') {
			$base = 'document';
		}

		return $base . '-' . $suffix . '.pdf';

	}//end siblingName()
}//end class
