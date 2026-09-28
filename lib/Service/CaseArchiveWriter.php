<?php

/**
 * Case Archive Writer
 *
 * Writes the one archive of a case bundle into the requester's Files: every
 * included file that can still be read, and `manifest.json`. A file that
 * cannot be read at that moment moves to the excluded list with reason
 * `missing`, so the manifest inside the archive and the recorded job say the
 * same thing (REQ-DFT-02, issue #1210).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Writes a case bundle's zip into the requester's `Case bundles` folder.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class CaseArchiveWriter {

	/**
	 * The folder in the requester's Files the archives are written to.
	 *
	 * @var string
	 */
	public const BUNDLE_FOLDER = 'Case bundles';

	/**
	 * Constructor.
	 *
	 * @param IRootFolder $rootFolder Files, to read the bundled files and write the archive.
	 * @param ITempManager $tempManager Temporary files for building the archive.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly ITempManager $tempManager,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Write the archive of one bundle.
	 *
	 * @param string $userId The requester, whose Files are read and written.
	 * @param array<string, mixed> $domain The object, as register, schema and id.
	 * @param array<string, mixed> $manifest The manifest to honour.
	 * @param int $ceiling The ceiling in force.
	 *
	 * @return array{file: File, manifest: array<string, mixed>, path: string} The archive, the manifest as written, and its path in Files.
	 *
	 * @throws RuntimeException When the archive cannot be written.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function write(string $userId, array $domain, array $manifest, int $ceiling): array {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$zipPath    = $this->tempPath(suffix: '.zip');

		$zip = new ZipArchive();
		if ($zip->open($zipPath, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== true) {
			throw new RuntimeException('Could not open the temporary archive', 500);
		}

		$manifest = $this->addFiles(zip: $zip, userFolder: $userFolder, manifest: $manifest);
		$zip->addFromString(
			'manifest.json',
			(string) json_encode(
				[
					'subject' => $domain,
					'requestedBy' => $userId,
					'requestedAt' => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
					'ceilingBytes' => $ceiling,
					'included' => $manifest['included'],
					'excluded' => ($manifest['excluded'] ?? []),
				],
				(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
			)
		);
		if ($zip->close() !== true) {
			throw new RuntimeException('Could not write the archive', 500);
		}

		try {
			$folder = $this->bundleFolder(userFolder: $userFolder);
			$file   = $folder->newFile($folder->getNonExistingName($this->archiveName(domain: $domain)));
			$file->putContent(fopen($zipPath, 'rb'));
		} catch (Throwable $e) {
			throw new RuntimeException('Could not store the archive', 500, $e);
		}

		return [
			'file' => $file,
			'manifest' => $manifest,
			'path' => (string) $userFolder->getRelativePath($file->getPath()),
		];

	}//end write()

	/**
	 * Delete an archive that must not be handed over, logging a failure.
	 *
	 * @param File $file The archive.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function discard(File $file): void {
		try {
			$file->delete();
		} catch (Throwable $e) {
			$this->logger->error(
				message: '[CaseArchiveWriter] could not remove an unrecorded archive',
				context: ['file' => __FILE__, 'line' => __LINE__, 'error' => $e->getMessage()]
			);
		}

	}//end discard()

	/**
	 * Add every included file that can still be read; move the others to excluded.
	 *
	 * @param ZipArchive $zip The open archive.
	 * @param Folder $userFolder The requester's Files root.
	 * @param array<string, mixed> $manifest The manifest.
	 *
	 * @return array<string, mixed> The manifest as written.
	 */
	private function addFiles(ZipArchive $zip, Folder $userFolder, array $manifest): array {
		$included = [];
		$taken    = ['manifest.json' => true];
		foreach (($manifest['included'] ?? []) as $entry) {
			$copy = $this->copyToTemp(userFolder: $userFolder, fileId: (int) ($entry['fileId'] ?? 0));
			if ($copy === null) {
				$manifest['excluded'][] = [
					'fileId' => ($entry['fileId'] ?? 0),
					'name' => ($entry['name'] ?? ''),
					'record' => ($entry['record'] ?? ''),
					'reason' => CaseArchiveService::REASON_MISSING,
					'explanation' => 'The file could not be read when the archive was written.',
				];
				$manifest['bytes'] = ((int) ($manifest['bytes'] ?? 0) - (int) ($entry['size'] ?? 0));
				continue;
			}

			$zip->addFile($copy, $this->uniqueName(name: (string) ($entry['name'] ?? ''), taken: $taken));
			$included[] = $entry;
		}

		$manifest['included'] = $included;

		return $manifest;

	}//end addFiles()

	/**
	 * The requester's bundle folder, created when missing.
	 *
	 * @param Folder $userFolder The requester's Files root.
	 *
	 * @return Folder The folder.
	 */
	private function bundleFolder(Folder $userFolder): Folder {
		if ($userFolder->nodeExists(self::BUNDLE_FOLDER) === true) {
			$node = $userFolder->get(self::BUNDLE_FOLDER);
			if ($node instanceof Folder) {
				return $node;
			}
		}

		return $userFolder->newFolder(self::BUNDLE_FOLDER);

	}//end bundleFolder()

	/**
	 * The archive's file name: schema, object id and the moment, safe for Files.
	 *
	 * @param array<string, mixed> $domain The object.
	 *
	 * @return string The file name.
	 */
	private function archiveName(array $domain): string {
		$base = trim(((string) ($domain['schema'] ?? 'object')).'-'.((string) ($domain['id'] ?? '')), '-');
		$base = (string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $base);

		return $base.'-'.(new DateTimeImmutable())->format('Ymd-His').'.zip';

	}//end archiveName()

	/**
	 * Stream one of the requester's files to a temporary file, so a large
	 * file never sits in memory.
	 *
	 * @param Folder $userFolder The requester's Files root.
	 * @param int $fileId The file id.
	 *
	 * @return string|null The temporary path, or null when the file cannot be read.
	 */
	private function copyToTemp(Folder $userFolder, int $fileId): ?string {
		if ($fileId <= 0) {
			return null;
		}

		try {
			foreach ($userFolder->getById($fileId) as $node) {
				if (($node instanceof File) === false) {
					continue;
				}

				$source = $node->fopen('rb');
				if ($source === false) {
					return null;
				}

				$path   = $this->tempPath(suffix: '');
				$target = fopen($path, 'wb');
				stream_copy_to_stream($source, $target);
				fclose($source);
				fclose($target);

				return $path;
			}
		} catch (Throwable $e) {
			return null;
		}

		return null;

	}//end copyToTemp()

	/**
	 * A Nextcloud-managed temporary file path.
	 *
	 * @param string $suffix The file name suffix.
	 *
	 * @return string The path.
	 *
	 * @throws RuntimeException When no temporary file can be created.
	 */
	private function tempPath(string $suffix): string {
		$path = $this->tempManager->getTemporaryFile($suffix);
		if ($path === false) {
			throw new RuntimeException('Could not create a temporary file', 500);
		}

		return $path;

	}//end tempPath()

	/**
	 * A name that is not yet taken inside the archive.
	 *
	 * @param string $name The wanted name.
	 * @param array<string, bool> $taken Names already used; updated.
	 *
	 * @return string The name to use.
	 */
	private function uniqueName(string $name, array &$taken): string {
		$name = str_replace(['/', '\\'], '-', $name);
		if ($name === '') {
			$name = 'file';
		}

		$dot  = strrpos($name, '.');
		$stem = $name;
		$ext  = '';
		if ($dot !== false) {
			$stem = substr($name, 0, $dot);
			$ext  = substr($name, $dot);
		}

		$candidate = $name;
		$counter   = 2;
		while (isset($taken[$candidate]) === true) {
			$candidate = $stem.' ('.$counter.')'.$ext;
			$counter++;
		}

		$taken[$candidate] = true;

		return $candidate;

	}//end uniqueName()
}//end class
