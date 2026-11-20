<?php

/**
 * Unit tests for MailArchiveWorkspace
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\MailArchive
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\MailArchive;


use OCA\Filinq\Service\MailArchive\MailArchiveRefused;
use OCA\Filinq\Service\MailArchive\MailArchiveSplitter;
use OCA\Filinq\Service\MailArchive\MailArchiveWorkspace;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The archive with the user's access, its local path, a kept PST conversion and the messages folder.
 *
 * @spec openspec/changes/intake-mailbox-archive-import/tasks.md#3-1
 */
class MailArchiveWorkspaceTest extends TestCase {

	/**
	 * The scratch directory.
	 *
	 * @var string
	 */
	private string $dir;

	/**
	 * The file tree.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder $root;

	/**
	 * The splitter.
	 *
	 * @var MailArchiveSplitter&MockObject
	 */
	private MailArchiveSplitter $splitter;

	/**
	 * Folders made: path => children.
	 *
	 * @var array<string, array<string, Node>>
	 */
	private array $tree = [];

	/**
	 * Files written: path => content.
	 *
	 * @var array<string, string>
	 */
	private array $written = [];

	/**
	 * Make the scratch directory and the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/filinq-workspace-' . bin2hex(random_bytes(6));
		mkdir($this->dir);
		$this->root = $this->createMock(IRootFolder::class);
		$this->splitter = $this->createMock(MailArchiveSplitter::class);

	}//end setUp()

	/**
	 * Remove the scratch directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		exec('rm -rf ' . escapeshellarg($this->dir));

	}//end tearDown()

	/**
	 * The workspace under test.
	 *
	 * @return MailArchiveWorkspace The workspace.
	 */
	private function workspace(): MailArchiveWorkspace {
		return new MailArchiveWorkspace(rootFolder: $this->root, splitter: $this->splitter, tempRoot: $this->dir);

	}//end workspace()

	/**
	 * A folder double that makes children and records written files.
	 *
	 * @param string $path The folder's path.
	 *
	 * @return Folder The folder.
	 */
	private function folder(string $path): Folder {
		$this->tree[$path] ??= [];
		$folder = $this->createMock(Folder::class);
		$folder->method('nodeExists')->willReturnCallback(fn (string $name): bool => isset($this->tree[$path][$name]));
		$folder->method('get')->willReturnCallback(fn (string $name): Node => $this->tree[$path][$name]);
		$folder->method('getNonExistingName')->willReturnCallback(
			function (string $name) use ($path): string {
				$candidate = $name;
				for ($i = 2; isset($this->tree[$path][$candidate]) === true; $i++) {
					$candidate = pathinfo($name, PATHINFO_FILENAME) . ' (' . $i . ')' . (pathinfo($name, PATHINFO_EXTENSION) !== '' ? '.' . pathinfo($name, PATHINFO_EXTENSION) : '');
				}

				return $candidate;
			}
		);
		$folder->method('newFolder')->willReturnCallback(
			function (string $name) use ($path): Folder {
				$child = $this->folder(path: $path . '/' . $name);
				$this->tree[$path][$name] = $child;

				return $child;
			}
		);
		$folder->method('newFile')->willReturnCallback(
			function (string $name, ?string $content = null) use ($path): File {
				$this->written[$path . '/' . $name] = (string) $content;
				$file = $this->createMock(File::class);
				$file->method('getName')->willReturn($name);
				$this->tree[$path][$name] = $file;

				return $file;
			}
		);

		return $folder;

	}//end folder()

	/**
	 * An archive file in /archivaris/files.
	 *
	 * @param string      $name      The name.
	 * @param string|null $localPath A file on disk its stream opens, as on local storage; null for a remote stream.
	 * @param string      $content   What a remote stream holds.
	 *
	 * @return File&MockObject The archive.
	 */
	private function archive(string $name, ?string $localPath = null, string $content = ''): File {
		$archive = $this->createMock(File::class);
		$archive->method('getId')->willReturn(77);
		$archive->method('getName')->willReturn($name);
		$archive->method('getParent')->willReturn($this->folder(path: '/archivaris/files'));
		$archive->method('fopen')->willReturnCallback(
			static function () use ($localPath, $content) {
				if ($localPath !== null) {
					return fopen($localPath, 'rb');
				}

				$stream = fopen('php://memory', 'w+b');
				fwrite($stream, $content);
				rewind($stream);

				return $stream;
			}
		);

		return $archive;

	}//end archive()

	/**
	 * A message lands below the archive's messages folder, in its archive folder, with safe names and an .eml extension.
	 *
	 * @return void
	 */
	public function testAMessageIsWrittenBesideTheArchiveInItsFolder(): void {
		$workspace = $this->workspace();
		$archive = $this->archive(name: 'mailbox.pst');

		$workspace->stage(archive: $archive, folder: 'Postvak IN/../Bezwaren: 2024', name: 'bericht?1', raw: 'raw one');
		$workspace->stage(archive: $archive, folder: 'Postvak IN/../Bezwaren: 2024', name: 'bericht?1', raw: 'raw two');

		$this->assertSame(
			[
				'/archivaris/files/mailbox (mail import)/Postvak IN/Bezwaren_ 2024/bericht_1.eml' => 'raw one',
				'/archivaris/files/mailbox (mail import)/Postvak IN/Bezwaren_ 2024/bericht_1 (2).eml' => 'raw two',
			],
			$this->written
		);

	}//end testAMessageIsWrittenBesideTheArchiveInItsFolder()

	/**
	 * A folder that cannot be written is a refusal the filer can count as a failure.
	 *
	 * @return void
	 */
	public function testAnUnwritableFolderIsARefusal(): void {
		$archive = $this->createMock(File::class);
		$archive->method('getParent')->willThrowException(new RuntimeException('no parent'));

		$this->expectException(MailArchiveRefused::class);
		$this->workspace()->stage(archive: $archive, folder: 'Inbox', name: 'a.eml', raw: 'raw');

	}//end testAnUnwritableFolderIsARefusal()

	/**
	 * A local storage path is used as it is; otherwise the archive is copied once and the copy removed on release.
	 *
	 * @return void
	 */
	public function testTheLocalPathIsTheStoragePathOrOneCopy(): void {
		$local = $this->dir . '/on-disk.mbox';
		file_put_contents($local, 'From x');
		$this->assertSame($local, $this->workspace()->localPath(archive: $this->archive(name: 'on-disk.mbox', localPath: $local)));

		$workspace = $this->workspace();
		$remote = $this->archive(name: 'remote.mbox', content: 'From remote');
		$copy = $workspace->localPath(archive: $remote);
		$this->assertSame('From remote', file_get_contents($copy));
		$this->assertSame($copy, $workspace->localPath(archive: $remote), 'one copy per process');

		$workspace->releaseCopies();
		$this->assertFileDoesNotExist($copy);

	}//end testTheLocalPathIsTheStoragePathOrOneCopy()

	/**
	 * A PST is converted once into the import's work directory and reused on the next runs, until discarded.
	 *
	 * @return void
	 */
	public function testAPstIsConvertedOnceAndKeptUntilDiscarded(): void {
		$local = $this->dir . '/archief.pst';
		file_put_contents($local, '!BDN');
		$workDir = $this->dir . '/filinq-mail-archive-import-1';
		$this->splitter->expects($this->once())->method('prepare')
			->with($local, MailArchiveSplitter::FORMAT_PST, $workDir)
			->willReturnCallback(
				static function (string $path, string $format, string $dir): array {
					file_put_contents($dir . '/1.eml', 'converted');

					return ['format' => MailArchiveSplitter::SOURCE_DIRECTORY, 'path' => $dir];
				}
			);

		$workspace = $this->workspace();
		$archive = $this->archive(name: 'archief.pst', localPath: $local);
		$first = $workspace->source(archive: $archive, format: MailArchiveSplitter::FORMAT_PST, importKey: 'import-1');
		$second = $workspace->source(archive: $archive, format: MailArchiveSplitter::FORMAT_PST, importKey: 'import-1');

		$this->assertSame(['format' => 'dir', 'path' => $workDir], $first);
		$this->assertSame($first, $second);

		$workspace->discard(importKey: 'import-1');
		$this->assertDirectoryDoesNotExist($workDir);

	}//end testAPstIsConvertedOnceAndKeptUntilDiscarded()

	/**
	 * An MBOX or ZIP source is the archive's local path, not converted.
	 *
	 * @return void
	 */
	public function testAnMboxIsReadInPlace(): void {
		$local = $this->dir . '/a.mbox';
		file_put_contents($local, 'From x');
		$this->splitter->expects($this->never())->method('prepare');

		$source = $this->workspace()->source(archive: $this->archive(name: 'a.mbox', localPath: $local), format: MailArchiveSplitter::FORMAT_MBOX, importKey: 'import-2');

		$this->assertSame(['format' => 'mbox', 'path' => $local], $source);

	}//end testAnMboxIsReadInPlace()

	/**
	 * The user's archive is found in their own files only, and only when readable.
	 *
	 * @return void
	 */
	public function testTheUserArchiveNeedsTheUsersReadAccess(): void {
		$readable = $this->createMock(File::class);
		$readable->method('isReadable')->willReturn(true);
		$hidden = $this->createMock(File::class);
		$hidden->method('isReadable')->willReturn(false);
		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')->willReturnMap([[1, [$readable]], [2, [$hidden]], [3, []]]);
		$this->root->method('getUserFolder')->willReturnCallback(
			static function (string $userId) use ($userFolder): Folder {
				if ($userId !== 'archivaris') {
					throw new RuntimeException('no such user');
				}

				return $userFolder;
			}
		);

		$workspace = $this->workspace();
		$this->assertSame($readable, $workspace->userArchive(userId: 'archivaris', fileId: 1));
		$this->assertNull($workspace->userArchive(userId: 'archivaris', fileId: 2));
		$this->assertNull($workspace->userArchive(userId: 'archivaris', fileId: 3));
		$this->assertNull($workspace->userArchive(userId: 'ghost', fileId: 1));

	}//end testTheUserArchiveNeedsTheUsersReadAccess()

	/**
	 * The job finds the archive by id, and nothing for a folder or a gone file.
	 *
	 * @return void
	 */
	public function testTheJobFindsTheArchiveById(): void {
		$file = $this->createMock(File::class);
		$this->root->method('getFirstNodeById')->willReturnMap([[1, $file], [2, $this->createMock(Folder::class)], [3, null]]);

		$workspace = $this->workspace();
		$this->assertSame($file, $workspace->archive(fileId: 1));
		$this->assertNull($workspace->archive(fileId: 2));
		$this->assertNull($workspace->archive(fileId: 3));

	}//end testTheJobFindsTheArchiveById()
}//end class
