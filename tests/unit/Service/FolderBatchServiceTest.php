<?php

/**
 * Unit tests for FolderBatchService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\FolderBatchService;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FolderBatchService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class FolderBatchServiceTest extends TestCase
{

    /**
     * The FolderBatchService under test
     *
     * @var FolderBatchService
     */
    private FolderBatchService $service;

    /**
     * Mocked LoggerInterface
     *
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Mocked IRootFolder
     *
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * Mocked IUserSession
     *
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * Mocked BatchStateService
     *
     * @var BatchStateService|MockObject
     */
    private BatchStateService|MockObject $mockStateService;

    /**
     * Mocked IJobList
     *
     * @var IJobList|MockObject
     */
    private IJobList|MockObject $mockJobList;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger       = $this->createMock(LoggerInterface::class);
        $this->mockRootFolder   = $this->createMock(IRootFolder::class);
        $this->mockUserSession  = $this->createMock(IUserSession::class);
        $this->mockStateService = $this->createMock(BatchStateService::class);
        $this->mockJobList      = $this->createMock(IJobList::class);

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->service = new FolderBatchService(
            $this->mockLogger,
            $this->mockRootFolder,
            $this->mockUserSession,
            $this->mockStateService,
            $this->mockJobList
        );

    }//end setUp()


    /**
     * Test successful folder batch creation
     *
     * @return void
     */
    public function testCreateFolderBatchSuccess(): void
    {
        $mockFile1 = $this->createMock(File::class);
        $mockFile1->method('getId')->willReturn(101);
        $mockFile1->method('getName')->willReturn('report.pdf');

        $mockFile2 = $this->createMock(File::class);
        $mockFile2->method('getId')->willReturn(102);
        $mockFile2->method('getName')->willReturn('letter.docx');

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getDirectoryListing')->willReturn([$mockFile1, $mockFile2]);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->with('/Documents/WOB')->willReturn($mockFolder);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturn([
            'batchId' => 'test-uuid',
            'userId'  => 'testuser',
            'status'  => 'uploading',
            'files'   => [
                ['fileId' => 101, 'fileName' => 'report.pdf', 'status' => 'uploaded'],
                ['fileId' => 102, 'fileName' => 'letter.docx', 'status' => 'uploaded'],
            ],
        ]);

        $this->mockStateService->expects($this->once())->method('updateBatch');
        $this->mockJobList->expects($this->once())->method('add')
            ->with(FolderExtractionJob::class, ['batchId' => 'test-uuid']);

        $result = $this->service->createFolderBatch('/Documents/WOB');

        $this->assertEquals('test-uuid', $result['batchId']);
        $this->assertEquals('folder', $result['sourceType']);
        $this->assertEquals('/Documents/WOB', $result['folderPath']);

    }//end testCreateFolderBatchSuccess()


    /**
     * Test that directories inside the folder are skipped
     *
     * @return void
     */
    public function testSkipsSubdirectories(): void
    {
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getId')->willReturn(101);
        $mockFile->method('getName')->willReturn('report.pdf');

        $mockSubFolder = $this->createMock(Folder::class);

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getDirectoryListing')->willReturn([$mockFile, $mockSubFolder]);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->willReturn($mockFolder);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturnCallback(
            function (string $userId, array $files) {
                $this->assertCount(1, $files);
                $this->assertEquals(101, $files[0]['fileId']);
                return ['batchId' => 'uuid', 'userId' => $userId, 'status' => 'uploading', 'files' => $files];
            }
        );

        $this->service->createFolderBatch('/test');

    }//end testSkipsSubdirectories()


    /**
     * Test folder not found throws 404
     *
     * @return void
     */
    public function testFolderNotFoundThrows404(): void
    {
        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->willThrowException(new NotFoundException());
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);

        $this->service->createFolderBatch('/nonexistent');

    }//end testFolderNotFoundThrows404()


    /**
     * Test path pointing to a file throws 400
     *
     * @return void
     */
    public function testPathIsFileThrows400(): void
    {
        $mockFile = $this->createMock(File::class);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->willReturn($mockFile);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->service->createFolderBatch('/somefile.pdf');

    }//end testPathIsFileThrows400()


    /**
     * Test empty folder throws 400
     *
     * @return void
     */
    public function testEmptyFolderThrows400(): void
    {
        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getDirectoryListing')->willReturn([]);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->willReturn($mockFolder);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->service->createFolderBatch('/empty-folder');

    }//end testEmptyFolderThrows400()


    /**
     * Test folder exceeding max files throws 400
     *
     * @return void
     */
    public function testExceedsMaxFilesThrows400(): void
    {
        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $mockFile = $this->createMock(File::class);
            $mockFile->method('getId')->willReturn($i);
            $mockFile->method('getName')->willReturn("file{$i}.pdf");
            $files[] = $mockFile;
        }

        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getDirectoryListing')->willReturn($files);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->willReturn($mockFolder);
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(2);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->service->createFolderBatch('/too-many');

    }//end testExceedsMaxFilesThrows400()


    /**
     * Test no user session throws 401
     *
     * @return void
     */
    public function testNoUserThrows401(): void
    {
        $mockUserSession = $this->createMock(IUserSession::class);
        $mockUserSession->method('getUser')->willReturn(null);

        $service = new FolderBatchService(
            $this->mockLogger,
            $this->mockRootFolder,
            $mockUserSession,
            $this->mockStateService,
            $this->mockJobList
        );

        $this->expectException(Exception::class);
        $this->expectExceptionCode(401);

        $service->createFolderBatch('/any');

    }//end testNoUserThrows401()


}//end class
