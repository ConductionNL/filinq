<?php

/**
 * Splits a mailbox archive into single messages
 *
 * MBOX is streamed and split on `From ` lines, ZIP is read entry by entry,
 * and a PST is first converted to `.eml` files by `readpst` (design D1).
 * Every read starts at a saved cursor and stops after a bounded number of
 * messages, so a large archive drains over many runs (design D3).
 *
 * @category  Service
 * @package   OCA\Filinq\Service\MailArchive
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\MailArchive;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Detects an archive's format, lists its folders and reads its messages in units.
 *
 * A source is `['format' => 'mbox'|'zip'|'dir', 'path' => string]`; a PST
 * becomes a `dir` source once converted.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/specs/mailbox-archive-import/spec.md#requirement-the-container-format-is-detected-and-split-on-the-server-req-mai-002
 */
class MailArchiveSplitter {

	public const FORMAT_MBOX = 'mbox';

	public const FORMAT_ZIP = 'zip';

	public const FORMAT_PST = 'pst';

	public const SOURCE_DIRECTORY = 'dir';

	public const READPST_MISSING = 'readpst is not installed on this server';

	public const READPST_FAILED = 'readpst could not read this file';

	public const UNKNOWN_FORMAT = 'not an MBOX, PST or ZIP file';

	public const UNREADABLE = 'the archive could not be opened';

	public const REASON_UNSUPPORTED_FORMAT = 'unsupported-format';

	/**
	 * Constructor.
	 *
	 * @param ReadpstCommand $readpst The PST converter.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ReadpstCommand $readpst,
	) {

	}//end __construct()

	/**
	 * Detect the archive's format from its first bytes, then its name.
	 *
	 * @param string $path     The local archive file.
	 * @param string $fileName The name the user gave it.
	 *
	 * @return string One of the FORMAT_ constants.
	 *
	 * @throws MailArchiveRefused When the format is none of the three.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function detectFormat(string $path, string $fileName): string {
		$head = (string) @file_get_contents($path, false, null, 0, 5);
		if (str_starts_with($head, '!BDN') === true) {
			return self::FORMAT_PST;
		}

		if (str_starts_with($head, "PK\x03\x04") === true || str_starts_with($head, "PK\x05\x06") === true) {
			return self::FORMAT_ZIP;
		}

		if ($head === 'From ') {
			return self::FORMAT_MBOX;
		}

		$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if (in_array($extension, ['mbox', 'mbx'], true) === true && $head === '') {
			return self::FORMAT_MBOX;
		}

		throw new MailArchiveRefused(self::UNKNOWN_FORMAT);

	}//end detectFormat()

	/**
	 * Whether a PST can be converted on this server.
	 *
	 * @return bool True when readpst is installed.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function canReadPst(): bool {
		return $this->readpst->isAvailable();

	}//end canReadPst()

	/**
	 * Turn an archive into a source the splitter reads in units.
	 *
	 * @param string $path    The local archive file.
	 * @param string $format  One of the FORMAT_ constants.
	 * @param string $workDir An empty directory a PST is converted into.
	 *
	 * @return array{format:string, path:string} The source.
	 *
	 * @throws MailArchiveRefused When a PST cannot be converted.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function prepare(string $path, string $format, string $workDir): array {
		if ($format !== self::FORMAT_PST) {
			return ['format' => $format, 'path' => $path];
		}

		if ($this->readpst->isAvailable() === false) {
			throw new MailArchiveRefused(self::READPST_MISSING);
		}

		if ($this->readpst->convert(pstPath: $path, outputDir: $workDir) === false) {
			throw new MailArchiveRefused(self::READPST_FAILED);
		}

		return ['format' => self::SOURCE_DIRECTORY, 'path' => $workDir];

	}//end prepare()

	/**
	 * The archive's folders with their message counts, for the mapping step.
	 *
	 * An MBOX holds one folder, named after the archive.
	 *
	 * @param array{format:string, path:string} $source   The source.
	 * @param string                            $rootName The folder name for messages outside any folder.
	 *
	 * @return array<string, int> Folder name to message count, sorted by name.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-2
	 */
	public function folders(array $source, string $rootName): array {
		$folders = [];
		$cursor = [];
		do {
			$unit = $this->next(source: $source, rootName: $rootName, cursor: $cursor, limit: 500, withContent: false);
			foreach ($unit['messages'] as $message) {
				$folders[$message['folder']] = (($folders[$message['folder']] ?? 0) + 1);
			}

			$cursor = $unit['cursor'];
		} while ($unit['done'] === false);

		ksort($folders, SORT_STRING);

		return $folders;

	}//end folders()

	/**
	 * Read the next unit of at most `$limit` messages from a saved cursor.
	 *
	 * A message is `{folder, name, raw}`, or `{folder, name, reason}` for an
	 * entry that is not an RFC 5322 message (a `.msg` fails visibly).
	 *
	 * @param array{format:string, path:string} $source      The source.
	 * @param string                            $rootName    The folder name for messages outside any folder.
	 * @param array<string, int>                $cursor      Where the previous unit stopped; [] to start.
	 * @param int                               $limit       The most messages in this unit.
	 * @param bool                              $withContent False to leave out `raw`, for counting.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 *
	 * @throws MailArchiveRefused When the archive cannot be opened.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function next(array $source, string $rootName, array $cursor, int $limit, bool $withContent = true): array {
		$limit = max(1, $limit);

		return match ($source['format']) {
			self::FORMAT_MBOX => $this->nextFromMbox(path: $source['path'], rootName: $rootName, cursor: $cursor, limit: $limit, withContent: $withContent),
			self::FORMAT_ZIP => $this->nextFromZip(path: $source['path'], rootName: $rootName, cursor: $cursor, limit: $limit, withContent: $withContent),
			self::SOURCE_DIRECTORY => $this->nextFromDirectory(
				path: $source['path'],
				rootName: $rootName,
				cursor: $cursor,
				limit: $limit,
				withContent: $withContent
			),
			default => throw new MailArchiveRefused(self::UNKNOWN_FORMAT),
		};

	}//end next()

	/**
	 * Stream an MBOX from a byte offset, splitting on `From ` at line start.
	 *
	 * Quoted `>From ` lines are unescaped by one level, which reads both
	 * mboxrd and mboxo correctly.
	 *
	 * @param string             $path        The MBOX file.
	 * @param string             $rootName    The archive's single folder.
	 * @param array<string, int> $cursor      `offset` and `ordinal`.
	 * @param int                $limit       The most messages.
	 * @param bool               $withContent Whether to return the message bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromMbox(string $path, string $rootName, array $cursor, int $limit, bool $withContent): array {
		$handle = @fopen($path, 'rb');
		if ($handle === false) {
			throw new MailArchiveRefused(self::UNREADABLE);
		}

		$position = (int) ($cursor['offset'] ?? 0);
		$ordinal = (int) ($cursor['ordinal'] ?? 0);
		fseek($handle, $position);

		$messages = [];
		$current = null;
		while (($line = fgets($handle)) !== false) {
			$lineStart = $position;
			$position += strlen($line);
			if (str_starts_with($line, 'From ') === true) {
				if ($current !== null) {
					$messages[] = $this->mboxMessage(rootName: $rootName, ordinal: ++$ordinal, raw: $current, withContent: $withContent);
					if (count($messages) >= $limit) {
						fclose($handle);
						return ['messages' => $messages, 'cursor' => ['offset' => $lineStart, 'ordinal' => $ordinal], 'done' => false];
					}
				}

				$current = '';
				continue;
			}

			if ($current !== null) {
				$current .= (string) preg_replace('/^>(>*From )/', '$1', $line);
			}
		}//end while

		fclose($handle);
		if ($current !== null) {
			$messages[] = $this->mboxMessage(rootName: $rootName, ordinal: ++$ordinal, raw: $current, withContent: $withContent);
		}

		return ['messages' => $messages, 'cursor' => ['offset' => $position, 'ordinal' => $ordinal], 'done' => true];

	}//end nextFromMbox()

	/**
	 * One MBOX message, without the blank separator line before the next `From `.
	 *
	 * @param string $rootName    The folder.
	 * @param int    $ordinal     The message's position in the archive, from 1.
	 * @param string $raw         The message lines.
	 * @param bool   $withContent Whether to return the bytes.
	 *
	 * @return array<string, string> The message.
	 */
	private function mboxMessage(string $rootName, int $ordinal, string $raw, bool $withContent): array {
		if (str_ends_with($raw, "\r\n\r\n") === true) {
			$raw = substr($raw, 0, -2);
		} else if (str_ends_with($raw, "\n\n") === true) {
			$raw = substr($raw, 0, -1);
		}

		$message = ['folder' => $rootName, 'name' => sprintf('%s-%06d.eml', $rootName, $ordinal)];
		if ($withContent === true) {
			$message['raw'] = $raw;
		}

		return $message;

	}//end mboxMessage()

	/**
	 * Read ZIP entries from an entry index; the entry's directory is its folder.
	 *
	 * @param string             $path        The ZIP file.
	 * @param string             $rootName    The folder for entries at the top.
	 * @param array<string, int> $cursor      `index`.
	 * @param int                $limit       The most messages.
	 * @param bool               $withContent Whether to return the message bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromZip(string $path, string $rootName, array $cursor, int $limit, bool $withContent): array {
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			throw new MailArchiveRefused(self::UNREADABLE);
		}

		$messages = [];
		$index = (int) ($cursor['index'] ?? 0);
		for (; $index < $zip->numFiles && count($messages) < $limit; $index++) {
			$name = (string) $zip->getNameIndex($index);
			if ($name === '' || str_ends_with($name, '/') === true) {
				continue;
			}

			$messages[] = $this->entry(
				relativePath: $name,
				rootName: $rootName,
				read: static fn (): string => (string) $zip->getFromIndex($index),
				withContent: $withContent
			);
		}

		$done = $index >= $zip->numFiles;
		$zip->close();

		return ['messages' => $messages, 'cursor' => ['index' => $index], 'done' => $done];

	}//end nextFromZip()

	/**
	 * Read the files under a converted PST's directory, sorted by path.
	 *
	 * @param string             $path        The directory.
	 * @param string             $rootName    The folder for files at the top.
	 * @param array<string, int> $cursor      `index`.
	 * @param int                $limit       The most messages.
	 * @param bool               $withContent Whether to return the message bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromDirectory(string $path, string $rootName, array $cursor, int $limit, bool $withContent): array {
		if (is_dir($path) === false) {
			throw new MailArchiveRefused(self::UNREADABLE);
		}

		$base = rtrim($path, '/') . '/';
		$files = [];
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
		foreach ($iterator as $file) {
			if ($file->isFile() === true) {
				$files[] = substr($file->getPathname(), strlen($base));
			}
		}

		sort($files, SORT_STRING);

		$index = (int) ($cursor['index'] ?? 0);
		$messages = [];
		for (; $index < count($files) && count($messages) < $limit; $index++) {
			$relative = $files[$index];
			$messages[] = $this->entry(
				relativePath: $relative,
				rootName: $rootName,
				read: static fn (): string => (string) file_get_contents($base . $relative),
				withContent: $withContent
			);
		}

		return ['messages' => $messages, 'cursor' => ['index' => $index], 'done' => $index >= count($files)];

	}//end nextFromDirectory()

	/**
	 * One archive entry: a message when it is `.eml`, else a visible failure.
	 *
	 * @param string   $relativePath The entry's path inside the archive.
	 * @param string   $rootName     The folder for entries at the top.
	 * @param callable $read         Returns the entry's bytes.
	 * @param bool     $withContent  Whether to return the bytes.
	 *
	 * @return array<string, string> The message.
	 */
	private function entry(string $relativePath, string $rootName, callable $read, bool $withContent): array {
		$folder = dirname($relativePath);
		if ($folder === '.' || $folder === '') {
			$folder = $rootName;
		}

		$message = ['folder' => $folder, 'name' => basename($relativePath)];

		if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'eml') {
			$message['reason'] = self::REASON_UNSUPPORTED_FORMAT;
			return $message;
		}

		if ($withContent === true) {
			$message['raw'] = $read();
		}

		return $message;

	}//end entry()
}//end class
