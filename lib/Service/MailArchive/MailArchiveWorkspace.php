<?php

/**
 * Where a mail archive import reads and writes files
 *
 * Resolves the archive file (with the session user's access when an import
 * is started, by id from the job), gives the splitter a local path, keeps a
 * converted PST between cron runs, and writes each message as an `.eml`
 * into a folder beside the archive, from where it is filed or received.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use FilesystemIterator;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * The archive, its local copy, a converted PST and the folder messages are written to.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-a-large-archive-drains-in-resumable-units-req-mai-004
 */
class MailArchiveWorkspace {

	public const MESSAGES_FOLDER_SUFFIX = ' (mail import)';

	/**
	 * Local copies made of archives on storage without a local path, by file id.
	 *
	 * @var array<int, string>
	 */
	private array $copies = [];

	/**
	 * Constructor.
	 *
	 * @param IRootFolder         $rootFolder The file tree.
	 * @param MailArchiveSplitter $splitter   Converts a PST.
	 * @param string              $tempRoot   Where converted PSTs and local copies are kept; '' for the system temp directory.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IRootFolder $rootFolder,
		private readonly MailArchiveSplitter $splitter,
		private readonly string $tempRoot = '',
	) {

	}//end __construct()

	/**
	 * The archive as the user sees it, or null when they cannot read it.
	 *
	 * @param string $userId The user.
	 * @param int    $fileId The archive's file id.
	 *
	 * @return File|null The file.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-4
	 */
	public function userArchive(string $userId, int $fileId): ?File {
		try {
			foreach ($this->rootFolder->getUserFolder($userId)->getById($fileId) as $node) {
				if ($node instanceof File && $node->isReadable() === true) {
					return $node;
				}
			}
		} catch (Throwable $e) {
			return null;
		}

		return null;

	}//end userArchive()

	/**
	 * The archive by id, without a session, for the job.
	 *
	 * @param int $fileId The archive's file id.
	 *
	 * @return File|null The file, or null when it is gone.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function archive(int $fileId): ?File {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable $e) {
			return null;
		}

		if (($node instanceof File) === false) {
			return null;
		}

		return $node;

	}//end archive()

	/**
	 * The splitter's source for an archive; a PST is converted once and kept for the next runs.
	 *
	 * @param File   $archive   The archive.
	 * @param string $format    One of MailArchiveSplitter's FORMAT_ constants.
	 * @param string $importKey The import's uuid, naming its work directory.
	 *
	 * @return array{format:string, path:string} The source.
	 *
	 * @throws MailArchiveRefused When the archive cannot be read or a PST not converted.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function source(File $archive, string $format, string $importKey): array {
		if ($format !== MailArchiveSplitter::FORMAT_PST) {
			return ['format' => $format, 'path' => $this->localPath(archive: $archive)];
		}

		$workDir = $this->workDir(importKey: $importKey);
		if (is_dir($workDir) === true && (new FilesystemIterator($workDir))->valid() === true) {
			return ['format' => MailArchiveSplitter::SOURCE_DIRECTORY, 'path' => $workDir];
		}

		if (is_dir($workDir) === false && mkdir($workDir, 0700, true) === false) {
			throw new MailArchiveRefused(MailArchiveSplitter::UNREADABLE);
		}

		return $this->splitter->prepare(path: $this->localPath(archive: $archive), format: $format, workDir: $workDir);

	}//end source()

	/**
	 * A local path of the archive: its own when its stream is a plain file, else a copy.
	 *
	 * @param File $archive The archive.
	 *
	 * @return string The path.
	 *
	 * @throws MailArchiveRefused When the archive cannot be read.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function localPath(File $archive): string {
		$fileId = (int) $archive->getId();
		if (isset($this->copies[$fileId]) === true) {
			return $this->copies[$fileId];
		}

		try {
			$source = $archive->fopen('r');
			if (is_resource($source) === false) {
				throw new MailArchiveRefused(MailArchiveSplitter::UNREADABLE);
			}

			// On local storage the stream is the file itself: read it in place.
			$meta = stream_get_meta_data($source);
			$uri = (string) ($meta['uri'] ?? '');
			if ($meta['wrapper_type'] === 'plainfile' && $uri !== '' && is_file($uri) === true) {
				fclose($source);
				return $uri;
			}

			$copy = $this->root() . '/filinq-mail-archive-copy-' . $fileId . '-' . bin2hex(random_bytes(4));
			$target = fopen($copy, 'wb');
			if (is_resource($target) === false) {
				fclose($source);
				throw new MailArchiveRefused(MailArchiveSplitter::UNREADABLE);
			}

			stream_copy_to_stream($source, $target);
			fclose($source);
			fclose($target);
		} catch (MailArchiveRefused $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new MailArchiveRefused(MailArchiveSplitter::UNREADABLE);
		}//end try

		$this->copies[$fileId] = $copy;

		return $copy;

	}//end localPath()

	/**
	 * Write one message as an `.eml` into the archive's messages folder, below its archive folder.
	 *
	 * @param File   $archive The archive.
	 * @param string $folder  The message's folder in the archive.
	 * @param string $name    The message's name in the archive.
	 * @param string $raw     The message.
	 *
	 * @return File The written file.
	 *
	 * @throws MailArchiveRefused When the folder beside the archive cannot be written.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-2
	 */
	public function stage(File $archive, string $folder, string $name, string $raw): File {
		try {
			$parent = $archive->getParent();
			$target = $this->childFolder(parent: $parent, name: pathinfo($archive->getName(), PATHINFO_FILENAME) . self::MESSAGES_FOLDER_SUFFIX);
			foreach ($this->segments(path: $folder) as $segment) {
				$target = $this->childFolder(parent: $target, name: $segment);
			}

			$fileName = $this->safeName(name: $name);
			if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'eml') {
				$fileName .= '.eml';
			}

			return $target->newFile($target->getNonExistingName($fileName), $raw);
		} catch (Throwable $e) {
			throw new MailArchiveRefused(MailArchiveSplitter::UNREADABLE);
		}

	}//end stage()

	/**
	 * Remove an import's converted PST and the local copies made in this process.
	 *
	 * @param string $importKey The import's uuid.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function discard(string $importKey): void {
		$this->remove(path: $this->workDir(importKey: $importKey));
		$this->releaseCopies();

	}//end discard()

	/**
	 * Remove the local copies made in this process; a converted PST stays for the next run.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
	 */
	public function releaseCopies(): void {
		foreach ($this->copies as $copy) {
			if (is_file($copy) === true) {
				unlink($copy);
			}
		}

		$this->copies = [];

	}//end releaseCopies()

	/**
	 * The work directory of one import.
	 *
	 * @param string $importKey The import's uuid.
	 *
	 * @return string The path.
	 */
	private function workDir(string $importKey): string {
		return $this->root() . '/filinq-mail-archive-' . preg_replace('/[^A-Za-z0-9-]/', '', $importKey);

	}//end workDir()

	/**
	 * The temp root.
	 *
	 * @return string The directory.
	 */
	private function root(): string {
		if ($this->tempRoot !== '') {
			return rtrim($this->tempRoot, '/');
		}

		return rtrim(sys_get_temp_dir(), '/');

	}//end root()

	/**
	 * A child folder, made when it is not there.
	 *
	 * @param Folder $parent The parent.
	 * @param string $name   The child's name.
	 *
	 * @return Folder The child.
	 */
	private function childFolder(Folder $parent, string $name): Folder {
		$name = $this->safeName(name: $name);
		if ($parent->nodeExists($name) === true) {
			$node = $parent->get($name);
			if ($node instanceof Folder) {
				return $node;
			}

			return $parent->newFolder($parent->getNonExistingName($name));
		}

		return $parent->newFolder($name);

	}//end childFolder()

	/**
	 * An archive folder path as safe folder names.
	 *
	 * @param string $path The folder path, `/`-separated.
	 *
	 * @return list<string> The names; none for the archive's top.
	 */
	private function segments(string $path): array {
		$segments = [];
		foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
			$segment = trim($segment);
			if ($segment !== '' && $segment !== '.' && $segment !== '..') {
				$segments[] = $segment;
			}
		}

		return $segments;

	}//end segments()

	/**
	 * A name Nextcloud accepts: no separators or reserved characters, not empty.
	 *
	 * @param string $name The name.
	 *
	 * @return string The safe name.
	 */
	private function safeName(string $name): string {
		$safe = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/', '_', $name), " .");
		if ($safe === '') {
			return 'message';
		}

		return mb_substr($safe, 0, 200);

	}//end safeName()

	/**
	 * Remove a directory tree.
	 *
	 * @param string $path The directory.
	 *
	 * @return void
	 */
	private function remove(string $path): void {
		if (is_dir($path) === false) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $entry) {
			if ($entry->isDir() === true) {
				rmdir($entry->getPathname());
				continue;
			}

			unlink($entry->getPathname());
		}

		rmdir($path);

	}//end remove()
}//end class
