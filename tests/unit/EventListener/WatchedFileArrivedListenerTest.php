<?php

/**
 * Tests for WatchedFileArrivedListener.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\EventListener
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/scan-intake-from-a-watched-folder/specs/scan-intake/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\EventListener;

use OCA\Filinq\EventListener\WatchedFileArrivedListener;
use OCA\Filinq\Kernel\Contracts\ScanBatchRepositoryInterface;
use OCA\Filinq\Kernel\Contracts\ScanBatchServiceInterface;
use OCA\Filinq\Kernel\Contracts\ScanProfileServiceInterface;
use OCA\Integriq\Event\WatchedFileArrivedEvent;
use OCP\EventDispatcher\Event;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * The listener against integriq's event class (the sibling's own file when
 * present, its verbatim copy in tests/stubs otherwise): it accepts only a
 * batch it stored, each file once, and leaves other apps' files alone.
 *
 * @covers \OCA\Filinq\EventListener\WatchedFileArrivedListener
 */
class WatchedFileArrivedListenerTest extends TestCase {

	private const PROFILE = ['id' => 'post', 'watchedFolder' => 'Scans/post', 'separatorMode' => 'qr'];

	/** @var IRootFolder&MockObject */
	private IRootFolder $rootFolder;

	/** @var Folder&MockObject */
	private Folder $userFolder;

	/** @var ScanProfileServiceInterface&MockObject */
	private ScanProfileServiceInterface $profiles;

	/** @var ScanBatchServiceInterface&MockObject */
	private ScanBatchServiceInterface $batches;

	/** @var ScanBatchRepositoryInterface&MockObject */
	private ScanBatchRepositoryInterface $stored;

	/** @var LoggerInterface&MockObject */
	private LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->userFolder = $this->createMock(Folder::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturnCallback(
			fn (string $uid): Folder => $uid === 'alice' ? $this->userFolder : throw new RuntimeException('no user ' . $uid)
		);
		$this->profiles = $this->createMock(ScanProfileServiceInterface::class);
		$this->batches = $this->createMock(ScanBatchServiceInterface::class);
		$this->stored = $this->createMock(ScanBatchRepositoryInterface::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}//end setUp()

	private function listener(): WatchedFileArrivedListener {
		return new WatchedFileArrivedListener(
			rootFolder: $this->rootFolder,
			profiles: $this->profiles,
			batches: $this->batches,
			stored: $this->stored,
			logger: $this->logger
		);
	}//end listener()

	private function event(string $intakeApp = 'filinq', int $fileId = 42, string $owner = 'alice'): WatchedFileArrivedEvent {
		return new WatchedFileArrivedEvent(
			synchronizationId: 'sync-1',
			sourceId: 'source-1',
			intakeApp: $intakeApp,
			fileId: $fileId,
			path: 'Scans/post/batch.pdf',
			ownerUid: $owner
		);
	}//end event()

	/**
	 * The user folder answers the file id with a PDF file.
	 *
	 * @param string $mime The file's mime type.
	 *
	 * @return File&MockObject
	 */
	private function fileInFolder(string $mime = 'application/pdf'): File {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getMimetype')->willReturn($mime);
		$this->userFolder->method('getById')->with(42)->willReturn([$file]);
		return $file;
	}//end fileInFolder()

	public function testABatchIsTakenAndAccepted(): void {
		$file = $this->fileInFolder();
		$this->stored->method('findByFile')->with(42)->willReturn(null);
		$this->profiles->method('forPath')->with('Scans/post/batch.pdf')->willReturn(self::PROFILE);
		$this->batches->expects(self::once())->method('take')->with($file, self::PROFILE)
			->willReturn(['uuid' => 'b-1', 'status' => 'split']);

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertSame(['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-1'], $event->getResult());
	}//end testABatchIsTakenAndAccepted()

	public function testAnotherIntakesFileIsIgnored(): void {
		$this->rootFolder->expects(self::never())->method('getUserFolder');
		$this->batches->expects(self::never())->method('take');
		$this->stored->expects(self::never())->method('findByFile');

		$event = $this->event(intakeApp: 'dossiq');
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testAnotherIntakesFileIsIgnored()

	public function testAnUnrelatedEventIsIgnored(): void {
		$this->rootFolder->expects(self::never())->method('getUserFolder');
		$this->batches->expects(self::never())->method('take');

		$this->listener()->handle(new Event());
	}//end testAnUnrelatedEventIsIgnored()

	public function testARefusedAcceptIsLoggedNotRetried(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(self::PROFILE);
		$this->batches->expects(self::once())->method('take')->willReturn(['uuid' => 'b-2', 'status' => 'split']);
		$this->logger->expects(self::once())->method('warning')
			->with(self::stringContains('did not take the acceptance'));

		$event = $this->event();
		// Already accepted elsewhere, so accept() answers false for this listener.
		self::assertTrue($event->accept('filinq', 'earlier'));
		$this->listener()->handle($event);

		self::assertSame('earlier', $event->getResult()['reference']);
	}//end testARefusedAcceptIsLoggedNotRetried()

	public function testWithoutAProfileNothingIsAccepted(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(null);
		$this->batches->expects(self::never())->method('take');

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testWithoutAProfileNothingIsAccepted()

	public function testAFolderIdIsNotAccepted(): void {
		$this->userFolder->method('getById')->willReturn([$this->createMock(Folder::class)]);
		$this->batches->expects(self::never())->method('take');
		$this->stored->expects(self::never())->method('findByFile');

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testAFolderIdIsNotAccepted()

	public function testAMissingFileIsNotAccepted(): void {
		$this->userFolder->method('getById')->willReturn([]);
		$this->batches->expects(self::never())->method('take');

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testAMissingFileIsNotAccepted()

	public function testAnUnknownOwnerIsNotAccepted(): void {
		$this->batches->expects(self::never())->method('take');
		$this->logger->expects(self::once())->method('warning')
			->with(self::stringContains('could not be taken in'));

		$event = $this->event(owner: 'mallory');
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testAnUnknownOwnerIsNotAccepted()

	public function testANonPdfIsNotAccepted(): void {
		$this->fileInFolder(mime: 'image/png');
		$this->batches->expects(self::never())->method('take');
		$this->stored->expects(self::never())->method('findByFile');

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testANonPdfIsNotAccepted()

	public function testAStoreFailureIsNotAccepted(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(self::PROFILE);
		$this->batches->method('take')->willThrowException(new RuntimeException('store down'));
		$this->logger->expects(self::once())->method('warning')
			->with(self::stringContains('could not be taken in'), self::callback(
				static fn (array $context): bool => $context['error'] === 'store down'
			));

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testAStoreFailureIsNotAccepted()

	public function testAFailedCutIsAcceptedWithItsBatch(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(self::PROFILE);
		$this->batches->method('take')->willReturn(['uuid' => 'b-3', 'status' => 'failed']);

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertSame(['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-3'], $event->getResult());
	}//end testAFailedCutIsAcceptedWithItsBatch()

	public function testABatchWithoutAUuidIsNotAccepted(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(self::PROFILE);
		$this->batches->method('take')->willReturn(['uuid' => '  ', 'status' => 'split']);

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertFalse($event->getResult()['accepted']);
	}//end testABatchWithoutAUuidIsNotAccepted()

	public function testASecondHandOverIsAcceptedWithTheSameBatch(): void {
		$this->fileInFolder();
		$this->stored->method('findByFile')->with(42)->willReturn(['uuid' => 'b-1', 'status' => 'split']);
		$this->profiles->expects(self::never())->method('forPath');
		$this->batches->expects(self::never())->method('take');
		$this->batches->expects(self::never())->method('receive');
		$this->batches->expects(self::never())->method('split');

		$event = $this->event();
		$this->listener()->handle($event);

		self::assertSame(['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-1'], $event->getResult());
	}//end testASecondHandOverIsAcceptedWithTheSameBatch()
}//end class
