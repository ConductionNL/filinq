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

	/**
	 * The profile that watches `Scans/post`.
	 *
	 * @var array<string, string>
	 */
	private const PROFILE = ['id' => 'post', 'watchedFolder' => 'Scans/post', 'separatorMode' => 'qr'];

	/**
	 * Alice's user folder.
	 *
	 * @var Folder&MockObject
	 */
	private Folder $userFolder;

	/**
	 * The root folder; it knows only alice.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder $rootFolder;

	/**
	 * The scan profiles.
	 *
	 * @var ScanProfileServiceInterface&MockObject
	 */
	private ScanProfileServiceInterface $profiles;

	/**
	 * The batch service.
	 *
	 * @var ScanBatchServiceInterface&MockObject
	 */
	private ScanBatchServiceInterface $batches;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Fresh doubles per test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->userFolder = $this->createMock(originalClassName: Folder::class);
		$this->rootFolder = $this->createMock(originalClassName: IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->willReturnCallback(
			function (string $uid): Folder {
				if ($uid !== 'alice') {
					throw new RuntimeException(message: 'no user ' . $uid);
				}

				return $this->userFolder;
			}
		);
		$this->profiles = $this->createMock(originalClassName: ScanProfileServiceInterface::class);
		$this->batches = $this->createMock(originalClassName: ScanBatchServiceInterface::class);
		$this->logger = $this->createMock(originalClassName: LoggerInterface::class);
	}//end setUp()

	/**
	 * The listener over the doubles.
	 *
	 * @return WatchedFileArrivedListener
	 */
	private function listener(): WatchedFileArrivedListener {
		return new WatchedFileArrivedListener(
			rootFolder: $this->rootFolder,
			profiles: $this->profiles,
			batches: $this->batches,
			logger: $this->logger
		);
	}//end listener()

	/**
	 * A hand-over of file 42 at `Scans/post/batch.pdf`.
	 *
	 * @param string $intakeApp The app that may accept.
	 * @param string $owner     The owner of the watched folder.
	 *
	 * @return WatchedFileArrivedEvent
	 */
	private function event(string $intakeApp = 'filinq', string $owner = 'alice'): WatchedFileArrivedEvent {
		return new WatchedFileArrivedEvent(
			synchronizationId: 'sync-1',
			sourceId: 'source-1',
			intakeApp: $intakeApp,
			fileId: 42,
			path: 'Scans/post/batch.pdf',
			ownerUid: $owner
		);
	}//end event()

	/**
	 * The user folder answers file id 42 with a file of the given type.
	 *
	 * @param string $mime The file's mime type.
	 *
	 * @return File&MockObject
	 */
	private function fileInFolder(string $mime = 'application/pdf'): File {
		$file = $this->createMock(originalClassName: File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getMimetype')->willReturn($mime);
		$this->userFolder->method('getById')->with(42)->willReturn([$file]);
		return $file;
	}//end fileInFolder()

	/**
	 * A new PDF in a profile folder: no batch yet, a profile, and take() answers the batch.
	 *
	 * @param array<string, mixed> $taken What take() answers.
	 *
	 * @return File&MockObject
	 */
	private function newBatch(array $taken): File {
		$file = $this->fileInFolder();
		$this->batches->method('storedFor')->with(42)->willReturn(null);
		$this->profiles->method('forPath')->with('Scans/post/batch.pdf')->willReturn(self::PROFILE);
		$this->batches->method('take')->willReturn($taken);
		return $file;
	}//end newBatch()

	/**
	 * The result of handling the event.
	 *
	 * @param Event $event The event.
	 *
	 * @return void
	 */
	private function handle(Event $event): void {
		$this->listener()->handle(event: $event);
	}//end handle()

	/**
	 * A PDF in a profile folder is taken and accepted with the batch uuid.
	 *
	 * @return void
	 */
	public function testABatchIsTakenAndAccepted(): void {
		$file = $this->fileInFolder();
		$this->batches->method('storedFor')->with(42)->willReturn(null);
		$this->profiles->method('forPath')->with('Scans/post/batch.pdf')->willReturn(self::PROFILE);
		$this->batches->expects(self::once())->method('take')->with($file, self::PROFILE)
			->willReturn(['uuid' => 'b-1', 'status' => 'split']);

		$event = $this->event();
		$this->handle(event: $event);

		self::assertSame(
			expected: ['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-1'],
			actual: $event->getResult()
		);
	}//end testABatchIsTakenAndAccepted()

	/**
	 * A hand-over to another app reads and writes nothing.
	 *
	 * @return void
	 */
	public function testAnotherIntakesFileIsIgnored(): void {
		$this->rootFolder->expects(self::never())->method('getUserFolder');
		$this->batches->expects(self::never())->method('take');
		$this->batches->expects(self::never())->method('storedFor');

		$event = $this->event(intakeApp: 'dossiq');
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testAnotherIntakesFileIsIgnored()

	/**
	 * Any other event is left alone.
	 *
	 * @return void
	 */
	public function testAnUnrelatedEventIsIgnored(): void {
		$this->rootFolder->expects(self::never())->method('getUserFolder');
		$this->batches->expects(self::never())->method('take');

		$this->handle(event: new Event());
	}//end testAnUnrelatedEventIsIgnored()

	/**
	 * A hand-over that was already accepted is logged once and not retried.
	 *
	 * @return void
	 */
	public function testARefusedAcceptIsLoggedNotRetried(): void {
		$this->newBatch(taken: ['uuid' => 'b-2', 'status' => 'split']);
		$this->logger->expects(self::once())->method('warning')
			->with(self::stringContains(string: 'did not take the acceptance'));

		$event = $this->event();
		self::assertTrue(condition: $event->accept(appId: 'filinq', reference: 'earlier'));
		$this->handle(event: $event);

		self::assertSame(expected: 'earlier', actual: $event->getResult()['reference']);
	}//end testARefusedAcceptIsLoggedNotRetried()

	/**
	 * A PDF that no profile watches is not taken.
	 *
	 * @return void
	 */
	public function testWithoutAProfileNothingIsAccepted(): void {
		$this->fileInFolder();
		$this->batches->method('storedFor')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(null);
		$this->batches->expects(self::never())->method('take');

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testWithoutAProfileNothingIsAccepted()

	/**
	 * A folder id is not taken.
	 *
	 * @return void
	 */
	public function testAFolderIdIsNotAccepted(): void {
		$this->userFolder->method('getById')->willReturn([$this->createMock(originalClassName: Folder::class)]);
		$this->batches->expects(self::never())->method('take');
		$this->batches->expects(self::never())->method('storedFor');

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testAFolderIdIsNotAccepted()

	/**
	 * An id the owner's folder does not know is not taken.
	 *
	 * @return void
	 */
	public function testAMissingFileIsNotAccepted(): void {
		$this->userFolder->method('getById')->willReturn([]);
		$this->batches->expects(self::never())->method('take');

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testAMissingFileIsNotAccepted()

	/**
	 * An owner without a user folder is logged and not taken.
	 *
	 * @return void
	 */
	public function testAnUnknownOwnerIsNotAccepted(): void {
		$this->batches->expects(self::never())->method('take');
		$this->logger->expects(self::once())->method('warning')
			->with(self::stringContains(string: 'could not be taken in'));

		$event = $this->event(owner: 'mallory');
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testAnUnknownOwnerIsNotAccepted()

	/**
	 * A file that is no PDF is not taken.
	 *
	 * @return void
	 */
	public function testANonPdfIsNotAccepted(): void {
		$this->fileInFolder(mime: 'image/png');
		$this->batches->expects(self::never())->method('take');
		$this->batches->expects(self::never())->method('storedFor');

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testANonPdfIsNotAccepted()

	/**
	 * A store that fails is logged and nothing is accepted.
	 *
	 * @return void
	 */
	public function testAStoreFailureIsNotAccepted(): void {
		$this->fileInFolder();
		$this->batches->method('storedFor')->willReturn(null);
		$this->profiles->method('forPath')->willReturn(self::PROFILE);
		$this->batches->method('take')->willThrowException(new RuntimeException(message: 'store down'));
		$this->logger->expects(self::once())->method('warning')->with(
			self::stringContains(string: 'could not be taken in'),
			self::callback(callback: static fn (array $context): bool => $context['error'] === 'store down')
		);

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testAStoreFailureIsNotAccepted()

	/**
	 * A batch that is stored but failed to cut owns the file, so it is accepted.
	 *
	 * @return void
	 */
	public function testAFailedCutIsAcceptedWithItsBatch(): void {
		$this->newBatch(taken: ['uuid' => 'b-3', 'status' => 'failed']);

		$event = $this->event();
		$this->handle(event: $event);

		self::assertSame(
			expected: ['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-3'],
			actual: $event->getResult()
		);
	}//end testAFailedCutIsAcceptedWithItsBatch()

	/**
	 * A batch that comes back without a uuid is not accepted.
	 *
	 * @return void
	 */
	public function testABatchWithoutAUuidIsNotAccepted(): void {
		$this->newBatch(taken: ['uuid' => '  ', 'status' => 'split']);

		$event = $this->event();
		$this->handle(event: $event);

		self::assertFalse(condition: $event->getResult()['accepted']);
	}//end testABatchWithoutAUuidIsNotAccepted()

	/**
	 * A file that already has a batch is accepted with it and not taken again.
	 *
	 * @return void
	 */
	public function testASecondHandOverIsAcceptedWithTheSameBatch(): void {
		$this->fileInFolder();
		$this->batches->method('storedFor')->with(42)->willReturn(['uuid' => 'b-1', 'status' => 'split']);
		$this->profiles->expects(self::never())->method('forPath');
		$this->batches->expects(self::never())->method('take');
		$this->batches->expects(self::never())->method('receive');
		$this->batches->expects(self::never())->method('split');

		$event = $this->event();
		$this->handle(event: $event);

		self::assertSame(
			expected: ['accepted' => true, 'intakeApp' => 'filinq', 'reference' => 'b-1'],
			actual: $event->getResult()
		);
	}//end testASecondHandOverIsAcceptedWithTheSameBatch()
}//end class
