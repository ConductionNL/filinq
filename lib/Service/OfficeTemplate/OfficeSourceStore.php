<?php

/**
 * Office source store
 *
 * Keeps the binary sources of office templates as Nextcloud files in the
 * app's own data folder, referenced from the template object by file id.
 * A file is never overwritten: every upload, conversion and duplicate is a
 * new file, so the file id a version snapshot records always points at the
 * exact revision it saw.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;

/**
 * Stores and reads office template sources by file id.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
 */
class OfficeSourceStore {

	/**
	 * The folder below the app's data folder.
	 *
	 * @var string
	 */
	private const FOLDER = 'filinq/office-templates';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder The file system root.
	 * @param IConfig     $config     Reads the instance id that names the app data folder.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly IConfig $config,
	) {

	}//end __construct()

	/**
	 * Store bytes as a new file.
	 *
	 * @param string $bytes     The content.
	 * @param string $extension docx, odt or zip.
	 *
	 * @return int The new file's id.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-1
	 */
	public function put(string $bytes, string $extension): int {
		$name = bin2hex(random_bytes(16)) . '.' . $extension;
		$file = $this->folder()->newFile($name, $bytes);

		return (int) $file->getId();

	}//end put()

	/**
	 * The content of a stored file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return string The bytes.
	 *
	 * @throws OfficeTemplateRefused 404 when the file is gone.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function read(int $fileId): string {
		return (string) $this->file(fileId: $fileId)->getContent();

	}//end read()

	/**
	 * Copy a stored file to a new one, for a duplicated template.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return int The copy's id.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-6
	 */
	public function copy(int $fileId): int {
		$file = $this->file(fileId: $fileId);

		return $this->put(bytes: (string) $file->getContent(), extension: strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION)));

	}//end copy()

	/**
	 * Remove a stored file, such as an import ZIP once the job is done.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-5
	 */
	public function remove(int $fileId): void {
		$node = $this->rootFolder->getFirstNodeById($fileId);
		if ($node !== null) {
			$node->delete();
		}

	}//end remove()

	/**
	 * A stored file inside the store's folder.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return File The file.
	 *
	 * @throws OfficeTemplateRefused 404 when it is not a file of this store.
	 */
	private function file(int $fileId): File {
		$node = null;
		if ($fileId > 0) {
			$node = $this->folder()->getFirstNodeById($fileId);
		}

		if (($node instanceof File) === false) {
			throw new OfficeTemplateRefused(message: 'The office source file ' . $fileId . ' no longer exists.', reason: 'source-missing', code: 404);
		}

		return $node;

	}//end file()

	/**
	 * The store's folder, created on first use.
	 *
	 * @return Folder The folder.
	 */
	private function folder(): Folder {
		$path = 'appdata_' . $this->config->getSystemValueString('instanceid', '') . '/' . self::FOLDER;
		if ($this->rootFolder->nodeExists($path) === false) {
			return $this->rootFolder->newFolder($path);
		}

		$folder = $this->rootFolder->get($path);
		if (($folder instanceof Folder) === false) {
			throw new OfficeTemplateRefused(message: 'The office template folder is not a folder.', reason: 'store', code: 500);
		}

		return $folder;

	}//end folder()
}//end class
