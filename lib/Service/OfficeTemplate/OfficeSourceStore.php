<?php

/**
 * Filinq Office Source Store
 *
 * Keeps the Word files of office templates in Filinq's own app data folder,
 * one file per distinct content, named by its SHA-256 checksum.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use RuntimeException;

/**
 * Stores office template sources and reads them back by file id.
 *
 * A file named by its checksum never changes, so every template version can
 * point at its own source and the same content uploaded twice is stored once.
 * The files live in the app data folder, not in anyone's home, so a template
 * does not depend on the account of the person who uploaded it.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeSourceStore {

	/**
	 * The folder under Filinq's app data that holds the sources.
	 */
	private const FOLDER = ['filinq', 'office-templates'];

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The file system root.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
	) {
	}//end __construct()

	/**
	 * Store a source file.
	 *
	 * @param string $bytes     The file's content.
	 * @param string $extension The file's extension, such as docx.
	 * @param string $name      The checksum the file is named by; '' names it by its own content.
	 *
	 * @return array{fileId: int, contentHash: string} The stored file's id and the content's checksum.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function store(string $bytes, string $extension, string $name=''): array {
		$hash = hash('sha256', $bytes);
		if ($name === '') {
			$name = $hash;
		}

		$folder = $this->folder();
		$fileName = $name.'.'.$extension;
		try {
			$file = $folder->get($fileName);
		} catch (NotFoundException) {
			$file = $folder->newFile($fileName, $bytes);
		}

		return ['fileId' => (int) $file->getId(), 'contentHash' => $hash];
	}//end store()

	/**
	 * The content of a stored source.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return string|null The content, or null when no source has that id.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function read(int $fileId): ?string {
		foreach ($this->folder()->getById($fileId) as $node) {
			if ($node instanceof File) {
				return $node->getContent();
			}
		}

		return null;
	}//end read()

	/**
	 * The sources folder, made when it does not exist yet.
	 *
	 * @return Folder
	 */
	private function folder(): Folder {
		$path = $this->rootFolder->getAppDataDirectoryName();
		try {
			$current = $this->rootFolder->get($path);
		} catch (NotFoundException) {
			$current = $this->rootFolder->newFolder($path);
		}

		if (($current instanceof Folder) === false) {
			throw new RuntimeException(message: 'The app data directory is not a folder.');
		}

		foreach (self::FOLDER as $name) {
			$current = $this->child(parent: $current, name: $name);
		}

		return $current;
	}//end folder()

	/**
	 * A child folder, made when it does not exist yet.
	 *
	 * @param Folder $parent The parent.
	 * @param string $name   The child's name.
	 *
	 * @return Folder
	 */
	private function child(Folder $parent, string $name): Folder {
		try {
			$node = $parent->get($name);
			if ($node instanceof Folder) {
				return $node;
			}
		} catch (NotFoundException) {
			// Made below.
		}

		return $parent->newFolder($name);
	}//end child()
}//end class
