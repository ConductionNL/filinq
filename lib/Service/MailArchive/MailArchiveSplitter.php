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
		$read = (new MailArchiveRead(rootName: $rootName, limit: 500))->countingOnly();
		do {
			$unit = $this->next(source: $source, read: $read);
			foreach ($unit['messages'] as $message) {
				$folders[$message['folder']] = (($folders[$message['folder']] ?? 0) + 1);
			}

			$read = $read->from(cursor: $unit['cursor']);
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
	 * @param array{format:string, path:string} $source The source.
	 * @param MailArchiveRead                   $read   The folder name, limit, cursor and whether to read the bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 *
	 * @throws MailArchiveRefused When the archive cannot be opened.
	 *
	 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#2-1
	 */
	public function next(array $source, MailArchiveRead $read): array {
		return match ($source['format']) {
			self::FORMAT_MBOX => $this->nextFromMbox(path: $source['path'], read: $read),
			self::FORMAT_ZIP => $this->nextFromZip(path: $source['path'], read: $read),
			self::SOURCE_DIRECTORY => $this->nextFromDirectory(path: $source['path'], read: $read),
			default => throw new MailArchiveRefused(self::UNKNOWN_FORMAT),
		};

	}//end next()

	/**
	 * Stream an MBOX from a byte offset, splitting on `From ` at line start.
	 *
	 * Quoted `>From ` lines are unescaped by one level, which reads both
	 * mboxrd and mboxo correctly.
	 *
	 * @param string          $path The MBOX file.
	 * @param MailArchiveRead $read The archive's single folder, the limit, the cursor (`offset` and `ordinal`) and whether to return the bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromMbox(string $path, MailArchiveRead $read): array {
		$handle = @fopen($path, 'rb');
		if ($handle === false) {
			throw new MailArchiveRefused(self::UNREADABLE);
		}

		$position = (int) ($read->cursor()['offset'] ?? 0);
		$ordinal = (int) ($read->cursor()['ordinal'] ?? 0);
		fseek($handle, $position);

		$messages = [];
		$current = null;
		while (($line = fgets($handle)) !== false) {
			$lineStart = $position;
			$position += strlen($line);
			if (str_starts_with($line, 'From ') === true) {
				if ($current !== null) {
					$messages[] = $this->mboxMessage(read: $read, ordinal: ++$ordinal, raw: $current);
					if (count($messages) >= $read->limit) {
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
			$messages[] = $this->mboxMessage(read: $read, ordinal: ++$ordinal, raw: $current);
		}

		return ['messages' => $messages, 'cursor' => ['offset' => $position, 'ordinal' => $ordinal], 'done' => true];

	}//end nextFromMbox()

	/**
	 * One MBOX message, without the blank separator line before the next `From `.
	 *
	 * @param MailArchiveRead $read    The folder, and whether to return the bytes.
	 * @param int             $ordinal The message's position in the archive, from 1.
	 * @param string          $raw     The message lines.
	 *
	 * @return array<string, string> The message.
	 */
	private function mboxMessage(MailArchiveRead $read, int $ordinal, string $raw): array {
		if (str_ends_with($raw, "\r\n\r\n") === true) {
			$raw = substr($raw, 0, -2);
		} else if (str_ends_with($raw, "\n\n") === true) {
			$raw = substr($raw, 0, -1);
		}

		$message = ['folder' => $read->rootName, 'name' => sprintf('%s-%06d.eml', $read->rootName, $ordinal)];
		if ($read->withContent() === true) {
			$message['raw'] = $raw;
		}

		return $message;

	}//end mboxMessage()

	/**
	 * Read ZIP entries from an entry index; the entry's directory is its folder.
	 *
	 * @param string          $path The ZIP file.
	 * @param MailArchiveRead $read The folder for entries at the top, the limit, the cursor (`index`) and whether to return the bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromZip(string $path, MailArchiveRead $read): array {
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			throw new MailArchiveRefused(self::UNREADABLE);
		}

		$messages = [];
		$index = (int) ($read->cursor()['index'] ?? 0);
		for (; $index < $zip->numFiles && count($messages) < $read->limit; $index++) {
			$name = (string) $zip->getNameIndex($index);
			if ($name === '' || str_ends_with($name, '/') === true) {
				continue;
			}

			$messages[] = $this->entry(
				relativePath: $name,
				read: $read,
				bytes: static fn (): string => (string) $zip->getFromIndex($index)
			);
		}

		$done = $index >= $zip->numFiles;
		$zip->close();

		return ['messages' => $messages, 'cursor' => ['index' => $index], 'done' => $done];

	}//end nextFromZip()

	/**
	 * Read the files under a converted PST's directory, sorted by path.
	 *
	 * @param string          $path The directory.
	 * @param MailArchiveRead $read The folder for files at the top, the limit, the cursor (`index`) and whether to return the bytes.
	 *
	 * @return array{messages: list<array<string, string>>, cursor: array<string, int>, done: bool} The unit.
	 */
	private function nextFromDirectory(string $path, MailArchiveRead $read): array {
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

		$index = (int) ($read->cursor()['index'] ?? 0);
		$messages = [];
		for (; $index < count($files) && count($messages) < $read->limit; $index++) {
			$relative = $files[$index];
			$messages[] = $this->entry(
				relativePath: $relative,
				read: $read,
				bytes: static fn (): string => (string) file_get_contents($base . $relative)
			);
		}

		return ['messages' => $messages, 'cursor' => ['index' => $index], 'done' => $index >= count($files)];

	}//end nextFromDirectory()

	/**
	 * One archive entry: a message when it is `.eml`, else a visible failure.
	 *
	 * @param string          $relativePath The entry's path inside the archive.
	 * @param MailArchiveRead $read         The folder for entries at the top, and whether to return the bytes.
	 * @param callable        $bytes        Returns the entry's bytes.
	 *
	 * @return array<string, string> The message.
	 */
	private function entry(string $relativePath, MailArchiveRead $read, callable $bytes): array {
		$folder = dirname($relativePath);
		if ($folder === '.' || $folder === '') {
			$folder = $read->rootName;
		}

		$message = ['folder' => $folder, 'name' => basename($relativePath)];

		if (strtolower(pathinfo($relativePath, PATHINFO_EXTENSION)) !== 'eml') {
			$message['reason'] = self::REASON_UNSUPPORTED_FORMAT;
			return $message;
		}

		if ($read->withContent() === true) {
			$message['raw'] = $bytes();
		}

		return $message;

	}//end entry()
}//end class
