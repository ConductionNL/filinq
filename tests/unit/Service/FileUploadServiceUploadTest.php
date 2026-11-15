<?php

/**
 * Unit tests for FileUploadService: folder resolution and the upload path
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use ArrayIterator;
use Exception;
use OCA\Filinq\Exception\UploadRefusedException;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Service\UploadPolicyService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Uploads land in the user's DocuDesk folder under a free name, the policy
 * is asked before any byte is written, and a storage failure is wrapped.
 */
#[CoversClass(FileUploadService::class)]
#[UsesClass(UploadRefusedException::class)]
class FileUploadServiceUploadTest extends TestCase {

	/**
	 * The logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * The root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder $rootFolder;

	/**
	 * The upload policy.
	 *
	 * @var UploadPolicyService&MockObject
	 */
	private UploadPolicyService $policy;

	/**
	 * The user's home folder.
	 *
	 * @var Folder&MockObject
	 */
	private Folder $userFolder;

	/**
	 * The subject.
	 *
	 * @var FileUploadService
	 */
	private FileUploadService $service;

	/**
	 * Build the subject for a signed-in user "alice".
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->policy = $this->createMock(UploadPolicyService::class);
		$this->userFolder = $this->createMock(Folder::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($this->userFolder);

		$this->service = new FileUploadService(
			logger: $this->logger,
			rootFolder: $this->rootFolder,
			userSession: $session,
			uploadPolicy: $this->policy
		);
	}//end setUp()

	/**
	 * A missing DocuDesk folder is created and returned.
	 *
	 * @return void
	 */
	public function testAMissingFolderIsCreated(): void {
		$folder = $this->createMock(Folder::class);
		$this->userFolder->method('nodeExists')->with('DocuDesk')->willReturn(false);
		$this->userFolder->expects($this->once())->method('newFolder')->with('DocuDesk');
		$this->userFolder->method('get')->with('DocuDesk')->willReturn($folder);

		$this->assertSame($folder, $this->service->getFilinqFolder());
	}//end testAMissingFolderIsCreated()

	/**
	 * An existing DocuDesk folder is reused, never recreated.
	 *
	 * @return void
	 */
	public function testAnExistingFolderIsReused(): void {
		$folder = $this->createMock(Folder::class);
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->expects($this->never())->method('newFolder');
		$this->userFolder->method('get')->willReturn($folder);

		$this->assertSame($folder, $this->service->getFilinqFolder());
	}//end testAnExistingFolderIsReused()

	/**
	 * A DocuDesk node that is a file is refused.
	 *
	 * @return void
	 */
	public function testANonFolderNodeIsRefused(): void {
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->method('get')->willReturn($this->createMock(File::class));

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('DocuDesk path exists but is not a folder.');
		$this->service->getFilinqFolder();
	}//end testANonFolderNodeIsRefused()

	/**
	 * A name without an extension gets a counter suffix with no dot.
	 *
	 * @return void
	 */
	public function testAnExtensionlessNameGetsACounter(): void {
		$folder = $this->createMock(Folder::class);
		$folder->method('nodeExists')->willReturnMap([
			['README', true],
			['README_1', true],
			['README_2', false],
		]);

		$this->assertSame('README_2', $this->service->resolveUniqueFileName(folder: $folder, fileName: 'README'));
	}//end testAnExtensionlessNameGetsACounter()

	/**
	 * A successful upload is checked, stored under a free name, logged and described.
	 *
	 * @return void
	 */
	public function testUploadStoresUnderAFreeName(): void {
		$folder = $this->createMock(Folder::class);
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->method('get')->willReturn($folder);
		$folder->method('nodeExists')->willReturnMap([['scan.pdf', true], ['scan_1.pdf', false]]);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(321);
		$file->method('getPath')->willReturn('/alice/files/DocuDesk/scan_1.pdf');
		$file->method('getSize')->willReturn(7);

		$this->policy->expects($this->once())->method('check')->with('scan.pdf', '%PDF-1.')->willReturn([]);
		$folder->expects($this->once())->method('newFile')->with('scan_1.pdf', '%PDF-1.')->willReturn($file);
		$this->logger->expects($this->once())
			->method('info')
			->with('File uploaded to DocuDesk folder', ['fileName' => 'scan_1.pdf', 'fileId' => 321]);

		$this->assertSame(
			[
				'fileId' => 321,
				'filePath' => '/alice/files/DocuDesk/scan_1.pdf',
				'fileName' => 'scan_1.pdf',
				'fileSize' => 7,
			],
			$this->service->uploadFile(fileName: 'scan.pdf', fileContent: '%PDF-1.')
		);
	}//end testUploadStoresUnderAFreeName()

	/**
	 * A policy refusal propagates unwrapped and nothing is touched in storage.
	 *
	 * @return void
	 */
	public function testAPolicyRefusalStopsBeforeStorage(): void {
		$refusal = new UploadRefusedException('Executables are not allowed');
		$this->policy->method('check')->willThrowException($refusal);
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		try {
			$this->service->uploadFile(fileName: 'run.exe', fileContent: 'MZ');
			$this->fail('Expected UploadRefusedException');
		} catch (UploadRefusedException $e) {
			$this->assertSame($refusal, $e);
		}
	}//end testAPolicyRefusalStopsBeforeStorage()

	/**
	 * A storage failure is logged and rethrown wrapped, keeping its code.
	 *
	 * @return void
	 */
	public function testAStorageFailureIsWrapped(): void {
		$folder = $this->createMock(Folder::class);
		$this->userFolder->method('nodeExists')->willReturn(true);
		$this->userFolder->method('get')->willReturn($folder);
		$folder->method('nodeExists')->willReturn(false);
		$cause = new Exception('Quota exceeded', 507);
		$folder->method('newFile')->willThrowException($cause);
		$this->policy->method('check')->willReturn([]);
		$this->logger->expects($this->once())
			->method('error')
			->with('Failed to upload file: Quota exceeded', ['exception' => $cause]);

		try {
			$this->service->uploadFile(fileName: 'big.pdf', fileContent: 'x');
			$this->fail('Expected an Exception');
		} catch (Exception $e) {
			$this->assertSame('Failed to upload file: Quota exceeded', $e->getMessage());
			$this->assertSame(507, $e->getCode());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAStorageFailureIsWrapped()
}//end class
