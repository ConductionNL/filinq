<?php

/**
 * Unit tests for GuardedWriter's file read and user path — the bytes and
 * the user-relative path of a document about to be edited, which
 * EditSessionService shares instead of keeping its own copy.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Editing;

use OCA\Filinq\Service\Editing\AgentArtefactMarker;
use OCA\Filinq\Service\Editing\GuardedWriter;
use OCP\Files\Lock\ILockManager;
use Psr\Log\NullLogger;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the guarded writer's file read and user path.
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-an-in-place-write-is-guarded-by-the-lock-and-a-version-precondition
 */
class GuardedWriterFileReadTest extends TestCase {
	/**
	 * A file double.
	 *
	 * @param int    $size    Its size in bytes.
	 * @param string $content What getContent() answers.
	 *
	 * @return File The file.
	 */
	private function file(int $size, string $content): File {
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn($size);
		$file->method('getContent')->willReturn($content);
		$file->method('getName')->willReturn('letter.docx');
		$file->method('getPath')->willReturn('/anna/files/Docs/letter.docx');
		return $file;

	}//end file()

	/**
	 * A writer whose user folder answers the given relative path, or throws.
	 *
	 * @param string|null $relative What getRelativePath() answers; null with $throws.
	 * @param bool        $throws   Whether getUserFolder() throws.
	 *
	 * @return GuardedWriter The writer.
	 */
	private function reader(?string $relative = null, bool $throws = false): GuardedWriter {
		$root = $this->createMock(IRootFolder::class);
		if ($throws === true) {
			$root->method('getUserFolder')->willThrowException(new RuntimeException('no user'));
			return new GuardedWriter($this->createMock(ILockManager::class), $this->createMock(AgentArtefactMarker::class), new NullLogger(), $root);
		}

		$folder = $this->createMock(Folder::class);
		$folder->method('getRelativePath')->willReturn($relative);
		$root->method('getUserFolder')->willReturn($folder);
		return new GuardedWriter($this->createMock(ILockManager::class), $this->createMock(AgentArtefactMarker::class), new NullLogger(), $root);

	}//end reader()

	/**
	 * The bytes come back for a readable file at the size limit.
	 *
	 * @return void
	 */
	public function testReadsTheBytesUpToTheLimit(): void {
		$this->assertSame('PK-bytes', $this->reader()->readBytes(file: $this->file(26214400, 'PK-bytes')));

	}//end testReadsTheBytesUpToTheLimit()

	/**
	 * A file over 25 MB is refused with its name and the limit.
	 *
	 * @return void
	 */
	public function testRefusesAFileOverTheLimit(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('"letter.docx" is larger than 25 MB and is not opened for editing.');
		$this->reader()->readBytes(file: $this->file(26214401, 'PK'));

	}//end testRefusesAFileOverTheLimit()

	/**
	 * Empty content is refused as unreadable.
	 *
	 * @return void
	 */
	public function testRefusesEmptyContent(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('"letter.docx" could not be read.');
		$this->reader()->readBytes(file: $this->file(10, ''));

	}//end testRefusesEmptyContent()

	/**
	 * The path is relative to the acting user's root.
	 *
	 * @return void
	 */
	public function testUserPathIsRelativeToTheUserRoot(): void {
		$this->assertSame('/Docs/letter.docx', $this->reader(relative: '/Docs/letter.docx')->userPath(uid: 'anna', file: $this->file(1, 'x')));

	}//end testUserPathIsRelativeToTheUserRoot()

	/**
	 * Without a relative path the file name is answered.
	 *
	 * @return void
	 */
	public function testUserPathFallsBackToTheName(): void {
		$this->assertSame('letter.docx', $this->reader(relative: null)->userPath(uid: 'anna', file: $this->file(1, 'x')));

	}//end testUserPathFallsBackToTheName()

	/**
	 * When the user folder cannot be reached the file name is answered.
	 *
	 * @return void
	 */
	public function testUserPathFallsBackToTheNameOnFailure(): void {
		$this->assertSame('letter.docx', $this->reader(throws: true)->userPath(uid: 'anna', file: $this->file(1, 'x')));

	}//end testUserPathFallsBackToTheNameOnFailure()
}//end class
