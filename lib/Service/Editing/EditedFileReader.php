<?php

/**
 * Filinq EditedFileReader
 *
 * Reads a document that is about to be edited, and names it for an answer:
 * the bytes, refused when the file is too large to hold in memory or cannot
 * be read, and the path relative to the acting user's root. Shared by
 * EditSessionService (the reads) and GuardedWriter (the writes), which each
 * carried a copy.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use RuntimeException;
use Throwable;

/**
 * Reads an edited document's bytes and its user-relative path.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
final class EditedFileReader {

	/**
	 * Largest package that is loaded into memory for editing.
	 *
	 * @var int
	 */
	private const MAX_BYTES = 26214400;

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The Nextcloud root folder.
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
	) {
	}//end __construct()

	/**
	 * Read a file's bytes, refusing anything too large to hold in memory.
	 *
	 * @param File $file The file.
	 *
	 * @return string The bytes.
	 *
	 * @throws RuntimeException When the file is too large or unreadable.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 */
	public function readBytes(File $file): string {
		if ($file->getSize() > self::MAX_BYTES) {
			throw new RuntimeException(
				sprintf('"%s" is larger than %d MB and is not opened for editing.', $file->getName(), (int)(self::MAX_BYTES / 1048576))
			);
		}

		$bytes = $file->getContent();
		if (is_string($bytes) === false || $bytes === '') {
			throw new RuntimeException(sprintf('"%s" could not be read.', $file->getName()));
		}

		return $bytes;
	}//end readBytes()

	/**
	 * Express a file's path relative to the acting user's root, for a human-readable answer.
	 *
	 * @param string $uid  The acting user id.
	 * @param File   $file The file.
	 *
	 * @return string The relative path, or the file name when it cannot be derived.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
	 */
	public function userPath(string $uid, File $file): string {
		try {
			return $this->rootFolder->getUserFolder($uid)->getRelativePath($file->getPath()) ?? $file->getName();
		} catch (Throwable) {
			return $file->getName();
		}

	}//end userPath()
}//end class
