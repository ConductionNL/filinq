<?php

/**
 * Unit tests for IntakePasswordRemoval: the caller's right is checked before
 * the file is read, and an unlocked intake document names the copy while
 * keeping the file that arrived.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/intake-pdf-password-removal/tasks.md#2-screens
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Intake;

use OCA\Filinq\Exception\IntakeRefusedException;
use OCA\Filinq\Service\Intake\IntakePasswordRemoval;
use OCA\Filinq\Service\IntakeAuthorizationGate;
use OCA\Filinq\Service\IntakeRepository;
use OCA\Filinq\Service\PdfPasswordRemovalService;
use OCA\Filinq\Service\SessionUserFile;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OCA\Filinq\Service\Intake\IntakePasswordRemoval
 * @uses   \OCA\Filinq\Service\SessionUserFile
 * @uses   \OCA\Filinq\Exception\IntakeRefusedException
 */
class IntakePasswordRemovalTest extends TestCase {

	/**
	 * The intake documents.
	 *
	 * @var IntakeRepository&MockObject
	 */
	private IntakeRepository&MockObject $repository;

	/**
	 * The removal.
	 *
	 * @var PdfPasswordRemovalService&MockObject
	 */
	private PdfPasswordRemovalService&MockObject $removal;

	/**
	 * The file the caller's folder holds under id 41.
	 *
	 * @var File&MockObject
	 */
	private File&MockObject $arrived;

	/**
	 * The service over a document, with or without the right to change it.
	 *
	 * @param array<string, mixed>|null $document The stored document, null for none.
	 * @param bool                      $mayWrite Whether the caller may change intake documents.
	 *
	 * @return IntakePasswordRemoval
	 */
	private function service(?array $document, bool $mayWrite = true): IntakePasswordRemoval {
		$this->repository = $this->createMock(IntakeRepository::class);
		$this->repository->method('findByUuid')->with('doc-1')->willReturn($document);
		$gate = $this->createMock(IntakeAuthorizationGate::class);
		$gate->method('mayWrite')->with('filinq', 'intakeDocument')->willReturn($mayWrite);
		$this->removal = $this->createMock(PdfPasswordRemovalService::class);

		// SessionUserFile is final: a real one over the caller's own folder.
		$this->arrived = $this->createMock(File::class);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('clerk');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$folder = $this->createMock(Folder::class);
		$folder->method('getById')->willReturnCallback(fn (int $id): array => $id === 41 ? [$this->arrived] : []);
		$root = $this->createMock(IRootFolder::class);
		$root->method('getUserFolder')->with('clerk')->willReturn($folder);

		return new IntakePasswordRemoval(
			repository: $this->repository,
			gate: $gate,
			files: new SessionUserFile($root, $session),
			removal: $this->removal
		);
	}//end service()

	/**
	 * Unlocking points the document at the copy and keeps the arrived file.
	 *
	 * @return void
	 */
	public function testTheDocumentNamesTheCopyAndKeepsWhatArrived(): void {
		$service = $this->service(['uuid' => 'doc-1', 'status' => 'received', 'file' => 41, 'fileName' => 'scan.pdf']);
		$this->removal->expects($this->once())->method('unlock')->with($this->arrived, 'geheim')
			->willReturn(['outcome' => 'unlocked', 'fileId' => 77, 'name' => 'scan (unlocked).pdf', 'findings' => []]);
		$this->repository->expects($this->once())->method('save')
			->with(['uuid' => 'doc-1', 'status' => 'received', 'file' => 77, 'fileName' => 'scan.pdf', 'lockedOriginal' => 41], 'doc-1')
			->willReturn(['uuid' => 'doc-1', 'file' => 77, 'lockedOriginal' => 41]);

		$result = $service->unlock(uuid: 'doc-1', password: 'geheim');

		$this->assertSame('unlocked', $result['outcome']);
		$this->assertSame(77, $result['fileId']);
		$this->assertSame(['uuid' => 'doc-1', 'file' => 77, 'lockedOriginal' => 41], $result['intakeDocument']);
	}//end testTheDocumentNamesTheCopyAndKeepsWhatArrived()

	/**
	 * A document unlocked before keeps the file that first arrived.
	 *
	 * @return void
	 */
	public function testASecondUnlockKeepsTheFirstArrival(): void {
		$service = $this->service(['status' => 'detached', 'file' => 41, 'lockedOriginal' => 12]);
		$this->removal->method('unlock')->willReturn(['outcome' => 'unlocked', 'fileId' => 78, 'name' => 'x.pdf']);
		$this->repository->expects($this->once())->method('save')
			->with(['status' => 'detached', 'file' => 78, 'lockedOriginal' => 12], 'doc-1')
			->willReturn([]);

		$service->unlock(uuid: 'doc-1', password: 'geheim');
	}//end testASecondUnlockKeepsTheFirstArrival()

	/**
	 * A wrong password, a final document or no qpdf changes nothing on the record.
	 *
	 * @return void
	 */
	public function testAFailedUnlockLeavesTheDocumentAlone(): void {
		$service = $this->service(['status' => 'received', 'file' => 41]);
		$this->removal->method('unlock')->willReturn(['outcome' => 'wrong-password']);
		$this->repository->expects($this->never())->method('save');

		$this->assertSame(['outcome' => 'wrong-password'], $service->unlock(uuid: 'doc-1', password: 'fout'));
	}//end testAFailedUnlockLeavesTheDocumentAlone()

	/**
	 * A caller who may not change intake documents is refused before the file is read.
	 *
	 * @return void
	 */
	public function testACallerWithoutTheRightIsRefusedBeforeTheFileIsRead(): void {
		$service = $this->service(['status' => 'received', 'file' => 41], mayWrite: false);
		$this->removal->expects($this->never())->method('unlock');
		$this->arrived->expects($this->never())->method('getContent');

		$this->assertRefused(service: $service, status: 403);
	}//end testACallerWithoutTheRightIsRefusedBeforeTheFileIsRead()

	/**
	 * An unknown document, one that left the inbox, and a file the caller cannot open are refused.
	 *
	 * @return void
	 */
	public function testUnknownLeftAndUnreadableAreRefused(): void {
		$this->assertRefused(service: $this->service(null), status: 404);
		$this->assertRefused(service: $this->service(['status' => 'assigned', 'file' => 41]), status: 409);
		$this->assertRefused(service: $this->service(['status' => 'received', 'file' => 99]), status: 404);
		$this->assertRefused(service: $this->service(['status' => 'received']), status: 404);
	}//end testUnknownLeftAndUnreadableAreRefused()

	/**
	 * Assert the service refuses with a status and never unlocks.
	 *
	 * @param IntakePasswordRemoval $service The service.
	 * @param int                   $status  The expected status.
	 *
	 * @return void
	 */
	private function assertRefused(IntakePasswordRemoval $service, int $status): void {
		$this->removal->expects($this->never())->method('unlock');
		try {
			$service->unlock(uuid: 'doc-1', password: 'geheim');
			$this->fail('expected a refusal');
		} catch (IntakeRefusedException $e) {
			$this->assertSame($status, $e->getStatus());
		}
	}//end assertRefused()
}//end class
