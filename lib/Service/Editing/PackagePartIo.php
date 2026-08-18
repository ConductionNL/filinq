<?php

/**
 * DocuDesk PackagePartIo
 *
 * Reads and writes a single entry of an ODF/OOXML ZIP package while leaving every
 * other entry byte-identical.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service\Editing;

use RuntimeException;
use ZipArchive;

/**
 * Single-entry package IO, shared by every codec that edits a document in place.
 *
 * Extracted from {@see PackageCodec} when metadata editing arrived: metadata lives
 * in a DIFFERENT part (`docProps/core.xml`, `meta.xml`) from the body, so the
 * choice was one shared reader or two divergent copies of the same ZipArchive
 * dance. Divergence here would be expensive — the reason untouched parts survive
 * an edit is a property of exactly this code.
 *
 * `ZipArchive` copies untouched entries' raw compressed data rather than
 * recompressing them, which is what keeps the rest of the package — and ODF's
 * uncompressed leading `mimetype` entry — intact.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 *
 * @spec openspec/specs/document-editing/spec.md
 */
class PackagePartIo {

	/**
	 * List every entry in a ZIP package.
	 *
	 * Multi-part formats need this: a `.pptx` keeps one part PER SLIDE and one
	 * per notes page, so "which slides exist" is a question about the package's
	 * contents rather than something a caller can be asked to know.
	 *
	 * @param string $packageBytes The raw package bytes.
	 *
	 * @return array<int, string> The entry names.
	 *
	 * @throws RuntimeException When the package cannot be read.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#21
	 */
	public function listParts(string $packageBytes): array {
		$path = $this->spill(bytes: $packageBytes);

		try {
			$zip = new ZipArchive();
			if ($zip->open($path) !== true) {
				throw new RuntimeException('The file is not a readable document package.');
			}

			$names = [];
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$name = $zip->getNameIndex($i);
				if ($name !== false) {
					$names[] = $name;
				}
			}

			$zip->close();

			return $names;
		} finally {
			if (file_exists($path) === true) {
				unlink($path);
			}
		}
	}//end listParts()

	/**
	 * Read one entry from a ZIP package.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $part         The entry name.
	 *
	 * @return string The entry contents.
	 *
	 * @throws RuntimeException When the package or the entry cannot be read.
	 *
	 * @spec openspec/specs/document-editing/spec.md
	 */
	public function readPart(string $packageBytes, string $part): string {
		$path = $this->spill(bytes: $packageBytes);

		try {
			$zip = new ZipArchive();
			if ($zip->open($path) !== true) {
				throw new RuntimeException('The file is not a readable document package.');
			}

			$xml = $zip->getFromName($part);
			$zip->close();

			if ($xml === false) {
				throw new RuntimeException(
					sprintf('The document package has no "%s" part; it may be corrupt.', $part)
				);
			}

			return $xml;
		} finally {
			unlink($path);
		}
	}//end readPart()

	/**
	 * Whether a package carries an entry.
	 *
	 * Distinct from catching {@see readPart()}'s exception, because "this document
	 * has no metadata part yet" is an ordinary state that must be handled by
	 * creating one — not an error to be reported to a user.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $part         The entry name.
	 *
	 * @return bool True when the entry exists.
	 *
	 * @spec openspec/specs/document-editing/spec.md
	 */
	public function hasPart(string $packageBytes, string $part): bool {
		$path = $this->spill(bytes: $packageBytes);

		try {
			$zip = new ZipArchive();
			if ($zip->open($path) !== true) {
				return false;
			}

			$found = ($zip->locateName($part) !== false);
			$zip->close();

			return $found;
		} finally {
			unlink($path);
		}
	}//end hasPart()

	/**
	 * Write one entry back into a ZIP package, leaving every other entry as-is.
	 *
	 * @param string $packageBytes The raw package bytes.
	 * @param string $part         The entry name.
	 * @param string $xml          The new entry contents.
	 *
	 * @return string The rewritten package bytes.
	 *
	 * @throws RuntimeException When the package cannot be rewritten.
	 *
	 * @spec openspec/specs/document-editing/spec.md
	 */
	public function writePart(string $packageBytes, string $part, string $xml): string {
		$path = $this->spill(bytes: $packageBytes);

		try {
			$zip = new ZipArchive();
			if ($zip->open($path) !== true) {
				throw new RuntimeException('The file is not a writable document package.');
			}

			if ($zip->addFromString($part, $xml) === false) {
				$zip->close();
				throw new RuntimeException(sprintf('Could not rewrite the "%s" part.', $part));
			}

			$zip->close();

			$bytes = file_get_contents($path);
			if ($bytes === false) {
				throw new RuntimeException('Could not read the rewritten document package.');
			}

			return $bytes;
		} finally {
			unlink($path);
		}
	}//end writePart()

	/**
	 * Spill bytes to a temporary file, because `ZipArchive` has no in-memory mode.
	 *
	 * @param string $bytes The bytes to spill.
	 *
	 * @return string The temporary file path.
	 *
	 * @throws RuntimeException When no temporary file can be created.
	 */
	private function spill(string $bytes): string {
		$path = tempnam(sys_get_temp_dir(), 'docudesk-pkg-');
		if ($path === false) {
			throw new RuntimeException('Could not create a temporary file for the document package.');
		}

		file_put_contents($path, $bytes);

		return $path;
	}//end spill()
}//end class
