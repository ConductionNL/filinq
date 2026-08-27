<?php

/**
 * Unit tests for DocumentStorageService
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
 * @spec openspec/changes/document-output-destinations-and-bulk-retention/tasks.md#task-3
 */

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentStorageService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotPermittedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DocumentStorageService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class DocumentStorageServiceTest extends TestCase {

	/**
	 * The service under test.
	 *
	 * @var DocumentStorageService
	 */
	private DocumentStorageService $service;

	/**
	 * Mock root folder.
	 *
	 * @var IRootFolder&MockObject
	 */
	private IRootFolder $rootFolder;

	/**
	 * Mock logger.
	 *
	 * @var LoggerInterface&MockObject
	 */
	private LoggerInterface $logger;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->service = new DocumentStorageService(
			$this->rootFolder,
			$this->logger
		);

	}//end setUp()

	/**
	 * Test validateTargetPath rejects a leading slash.
	 *
	 * @return void
	 */
	public function testRejectsAbsoluteTargetPath(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->validateTargetPath('/DocuDesk/foo');

	}//end testRejectsAbsoluteTargetPath()

	/**
	 * Test validateTargetPath rejects a ".." path segment.
	 *
	 * @return void
	 */
	public function testRejectsPathTraversalTargetPath(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->validateTargetPath('DocuDesk/../../etc');

	}//end testRejectsPathTraversalTargetPath()

	/**
	 * Test validateTargetPath rejects a disallowed character.
	 *
	 * @return void
	 */
	public function testRejectsDisallowedCharset(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->validateTargetPath('DocuDesk/foo;rm -rf');

	}//end testRejectsDisallowedCharset()

	/**
	 * Test validateTargetPath rejects an empty path.
	 *
	 * @return void
	 */
	public function testRejectsEmptyTargetPath(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->validateTargetPath('');

	}//end testRejectsEmptyTargetPath()

	/**
	 * Test validateTargetPath rejects an empty path segment (double slash).
	 *
	 * @return void
	 */
	public function testRejectsEmptyPathSegment(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->validateTargetPath('DocuDesk//foo');

	}//end testRejectsEmptyPathSegment()

	/**
	 * Test validateTargetPath accepts a well-formed relative path.
	 *
	 * @return void
	 */
	public function testAcceptsWellFormedTargetPath(): void {
		$this->service->validateTargetPath('DocuDesk/procest');
		$this->addToAssertionCount(1);

	}//end testAcceptsWellFormedTargetPath()

	/**
	 * Test store() creates missing folder segments and reuses existing ones.
	 *
	 * @return void
	 */
	public function testStoreCreatesMissingFolderSegments(): void {
		$userFolder = $this->createMock(Folder::class);
		$filinq = $this->createMock(Folder::class);
		$namespace = $this->createMock(Folder::class);
		$file = $this->createMock(File::class);

		$this->rootFolder->method('getUserFolder')
			->with('alice')
			->willReturn($userFolder);

		// 'DocuDesk' does not exist yet -> created. The folder name keeps its
		// pre-rename spelling on purpose (see DocumentService::DEFAULT_OUTPUT_FOLDER_PREFIX):
		// it is a real directory in the user's Files tree holding every document
		// already generated, so renaming it would orphan them.
		$userFolder->method('nodeExists')->with('DocuDesk')->willReturn(false);
		$userFolder->expects($this->once())->method('newFolder')->with('DocuDesk');
		$userFolder->method('get')->with('DocuDesk')->willReturn($filinq);

		// 'procest' already exists -> reused, not recreated.
		$filinq->method('nodeExists')->with('procest')->willReturn(true);
		$filinq->expects($this->never())->method('newFolder');
		$filinq->method('get')->with('procest')->willReturn($namespace);

		$namespace->method('getNonExistingName')->with('beschikking.pdf')->willReturn('beschikking.pdf');
		$namespace->method('newFile')->with('beschikking.pdf', '%PDF%')->willReturn($file);

		$file->method('getId')->willReturn(42);
		$file->method('getPath')->willReturn('/alice/files/DocuDesk/procest/beschikking.pdf');
		$file->method('getSize')->willReturn(1234);

		$result = $this->service->store(
			userId: 'alice',
			targetPath: 'DocuDesk/procest',
			filename: 'beschikking.pdf',
			content: '%PDF%'
		);

		$this->assertEquals(42, $result['fileId']);
		$this->assertEquals('/alice/files/DocuDesk/procest/beschikking.pdf', $result['path']);
		$this->assertEquals('beschikking.pdf', $result['name']);
		$this->assertEquals(1234, $result['size']);

	}//end testStoreCreatesMissingFolderSegments()

	/**
	 * Test store() dedupes a filename on collision via getNonExistingName().
	 *
	 * @return void
	 */
	public function testDedupesFilenameOnCollision(): void {
		$userFolder = $this->createMock(Folder::class);
		$target = $this->createMock(Folder::class);
		$file = $this->createMock(File::class);

		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($target);

		$target->method('getNonExistingName')
			->with('beschikking.pdf')
			->willReturn('beschikking (2).pdf');
		$target->method('newFile')
			->with('beschikking (2).pdf', '%PDF%')
			->willReturn($file);

		$file->method('getId')->willReturn(43);
		$file->method('getPath')->willReturn('/alice/files/DocuDesk/beschikking (2).pdf');
		$file->method('getSize')->willReturn(1000);

		$result = $this->service->store(
			userId: 'alice',
			targetPath: 'Filinq',
			filename: 'beschikking.pdf',
			content: '%PDF%'
		);

		$this->assertEquals('beschikking (2).pdf', $result['name']);

	}//end testDedupesFilenameOnCollision()

	/**
	 * Test store() rejects an invalid targetPath before touching the filesystem.
	 *
	 * @return void
	 */
	public function testStoreRejectsInvalidTargetPathBeforeAnyFilesystemCall(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->store(
			userId: 'alice',
			targetPath: '../etc',
			filename: 'x.pdf',
			content: 'x'
		);

	}//end testStoreRejectsInvalidTargetPathBeforeAnyFilesystemCall()

	/**
	 * Test store() wraps a Files-layer exception as a code-507 failure.
	 *
	 * @return void
	 */
	public function testStoreWrapsFilesystemFailureAs507(): void {
		$userFolder = $this->createMock(Folder::class);
		$target = $this->createMock(Folder::class);

		$this->rootFolder->method('getUserFolder')->willReturn($userFolder);
		$userFolder->method('nodeExists')->willReturn(true);
		$userFolder->method('get')->willReturn($target);

		$target->method('getNonExistingName')->willReturn('x.pdf');
		$target->method('newFile')
			->willThrowException(new NotPermittedException('quota exceeded'));

		$this->expectException(Exception::class);
		$this->expectExceptionCode(507);

		$this->service->store(
			userId: 'alice',
			targetPath: 'Filinq',
			filename: 'x.pdf',
			content: 'x'
		);

	}//end testStoreWrapsFilesystemFailureAs507()
}//end class
