<?php

/**
 * Unit tests for LegalHoldFileFreeze
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\LegalHold
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

namespace OCA\Filinq\Tests\Unit\Service\LegalHold;

use OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\LockContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * A held case's files (its named file and the files directly in its folder)
 * are app-locked; a release only unlocks what this app locked, and failures
 * report what was done so far.
 *
 * @covers \OCA\Filinq\Service\LegalHold\LegalHoldFileFreeze
 */
class LegalHoldFileFreezeTest extends TestCase {

	/**
	 * The lock manager.
	 *
	 * @var ILockManager&MockObject
	 */
	private ILockManager&MockObject $lockManager;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder&MockObject $rootFolder;

	/**
	 * File ids this app holds an app lock on.
	 *
	 * @var list<int>
	 */
	private array $ourLocks = [];

	/**
	 * Set up the lock manager and file tree.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->lockManager = $this->createMock(ILockManager::class);
		$this->lockManager->method('isLockProviderAvailable')->willReturn(true);
		$this->lockManager->method('getLocks')->willReturnCallback(function (int $fileId): array {
			if ($fileId === 666) {
				throw new RuntimeException('lock store down');
			}

			$foreign = $this->createMock(ILock::class);
			$foreign->method('getType')->willReturn(ILock::TYPE_USER);
			$foreign->method('getOwner')->willReturn('filinq');
			$locks = [$foreign];
			if (in_array($fileId, $this->ourLocks, true) === true) {
				$ours = $this->createMock(ILock::class);
				$ours->method('getType')->willReturn(ILock::TYPE_APP);
				$ours->method('getOwner')->willReturn('filinq');
				$locks[] = $ours;
			}

			return $locks;
		});

		$this->rootFolder = $this->createMock(IRootFolder::class);

	}//end setUp()

	/**
	 * A file with the given id.
	 *
	 * @param int $id The file id.
	 *
	 * @return File
	 */
	private function file(int $id): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn($id);

		return $file;

	}//end file()

	/**
	 * Serve these nodes by id from the root folder.
	 *
	 * @param array<int, mixed> $nodes Id => node.
	 *
	 * @return void
	 */
	private function nodes(array $nodes): void {
		$this->rootFolder->method('getFirstNodeById')->willReturnCallback(static function (int $id) use ($nodes): mixed {
			if ($id === 13) {
				throw new RuntimeException('storage gone');
			}

			return ($nodes[$id] ?? null);
		});

	}//end nodes()

	/**
	 * A case entity naming a file and a folder.
	 *
	 * @param int        $fileId   The named file id.
	 * @param mixed|null $folderId The folder id.
	 *
	 * @return object
	 */
	private function entity(int $fileId, mixed $folderId = null): object {
		return new class($fileId, $folderId) {

			/**
			 * Constructor.
			 *
			 * @param int   $fileId   The named file id.
			 * @param mixed $folderId The folder id.
			 */
			public function __construct(private readonly int $fileId, private readonly mixed $folderId) {
			}

			/**
			 * The case payload.
			 *
			 * @return array
			 */
			public function getObject(): array {
				return ['fileId' => $this->fileId];
			}

			/**
			 * The case folder.
			 *
			 * @return mixed
			 */
			public function getFolder(): mixed {
				return $this->folderId;
			}
		};

	}//end entity()

	/**
	 * The named file and the folder's direct files are listed once each.
	 *
	 * @return void
	 */
	public function testFileIdsOfCombinesNamedFileAndFolderFiles(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$this->file(5), $this->createMock(Folder::class), $this->file(6)]);
		$this->nodes([5 => $this->file(5), 20 => $folder]);

		$this->assertSame([5, 6], $this->freeze()->fileIdsOf($this->entity(5, '20')));

	}//end testFileIdsOfCombinesNamedFileAndFolderFiles()

	/**
	 * A folder id that is not numeric, not a folder, or unreadable contributes nothing.
	 *
	 * @return void
	 */
	public function testUnusableFoldersContributeNothing(): void {
		$this->nodes([5 => $this->file(5), 30 => $this->file(30)]);

		$this->assertSame([5], $this->freeze()->fileIdsOf($this->entity(5, 'abc')));
		$this->assertSame([5], $this->freeze()->fileIdsOf($this->entity(5, 30)));
		$this->assertSame([5], $this->freeze()->fileIdsOf($this->entity(5, 13)));
		$this->assertSame([], $this->freeze()->fileIdsOf(new \stdClass()));

	}//end testUnusableFoldersContributeNothing()

	/**
	 * A lock provider that throws counts as unavailable.
	 *
	 * @return void
	 */
	public function testAThrowingProviderIsUnavailable(): void {
		$lockManager = $this->createMock(ILockManager::class);
		$lockManager->method('isLockProviderAvailable')->willThrowException(new RuntimeException('no provider'));
		$lockManager->expects($this->never())->method('lock');

		$freeze = new LegalHoldFileFreeze($lockManager, $this->rootFolder);

		$this->assertFalse($freeze->available());
		$this->assertSame(['file' => 'unavailable', 'fileIds' => []], $freeze->lock($this->entity(5)));
		$this->assertSame(['file' => 'unavailable', 'fileIds' => []], $freeze->unlock($this->entity(5), null));

	}//end testAThrowingProviderIsUnavailable()

	/**
	 * A file already app-locked by us counts as locked; any other lock failure stops.
	 *
	 * @return void
	 */
	public function testLockToleratesOurOwnExistingLockOnly(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$this->file(6), $this->file(7)]);
		$this->nodes([5 => $this->file(5), 20 => $folder]);
		$this->ourLocks = [6];
		$this->lockManager->method('lock')->willReturnCallback(static function (LockContext $context): ILock {
			$id = $context->getNode()->getId();
			if ($id !== 5) {
				throw new RuntimeException('already locked ' . $id);
			}

			return TestCase::createStub(ILock::class);
		});

		$this->assertSame(
			['file' => 'failed', 'fileIds' => [5, 6], 'fileError' => 'already locked 7'],
			$this->freeze()->lock($this->entity(5, 20))
		);

	}//end testLockToleratesOurOwnExistingLockOnly()

	/**
	 * Releasing skips files this app did not lock and unlocks the rest.
	 *
	 * @return void
	 */
	public function testUnlockOnlyReleasesOurLocks(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$this->file(6), $this->file(666)]);
		$this->nodes([5 => $this->file(5), 20 => $folder]);
		$this->ourLocks = [5];
		$unlocked = [];
		$this->lockManager->method('unlock')->willReturnCallback(static function (LockContext $context) use (&$unlocked): void {
			TestCase::assertSame(ILock::TYPE_APP, $context->getType());
			TestCase::assertSame('filinq', $context->getOwner());
			$unlocked[] = $context->getNode()->getId();
		});

		$this->assertSame(['file' => 'unlocked', 'fileIds' => [5]], $this->freeze()->unlock($this->entity(5, 20), null));
		$this->assertSame([5], $unlocked);

	}//end testUnlockOnlyReleasesOurLocks()

	/**
	 * A failed unlock reports what was unlocked before it.
	 *
	 * @return void
	 */
	public function testAFailedUnlockReportsProgress(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('getDirectoryListing')->willReturn([$this->file(6)]);
		$this->nodes([5 => $this->file(5), 20 => $folder]);
		$this->ourLocks = [5, 6];
		$this->lockManager->method('unlock')->willReturnCallback(static function (LockContext $context): void {
			if ($context->getNode()->getId() === 6) {
				throw new RuntimeException('busy');
			}
		});

		$this->assertSame(
			['file' => 'failed', 'fileIds' => [5], 'fileError' => 'busy'],
			$this->freeze()->unlock($this->entity(5, 20), null)
		);

	}//end testAFailedUnlockReportsProgress()

	/**
	 * Another case holding the files keeps them locked.
	 *
	 * @return void
	 */
	public function testAnotherCaseKeepsTheFilesLocked(): void {
		$this->lockManager->expects($this->never())->method('unlock');

		$this->assertSame(['file' => 'kept_for_other_case', 'fileIds' => []], $this->freeze()->unlock($this->entity(5), 'case-2'));

	}//end testAnotherCaseKeepsTheFilesLocked()

	/**
	 * The freeze under test.
	 *
	 * @return LegalHoldFileFreeze
	 */
	private function freeze(): LegalHoldFileFreeze {
		return new LegalHoldFileFreeze($this->lockManager, $this->rootFolder);

	}//end freeze()
}//end class
