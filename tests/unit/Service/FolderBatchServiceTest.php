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
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-3
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCA\DocuDesk\Service\FolderBatchService;
use OCP\BackgroundJob\IJobList;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
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
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @phpstan-extends                                TestCase
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
     * Mocked OutputLayoutResolver
     *
     * @var OutputLayoutResolver|MockObject
     */
    private OutputLayoutResolver|MockObject $mockLayoutResolver;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger         = $this->createMock(LoggerInterface::class);
        $this->mockRootFolder     = $this->createMock(IRootFolder::class);
        $this->mockUserSession    = $this->createMock(IUserSession::class);
        $this->mockStateService   = $this->createMock(BatchStateService::class);
        $this->mockJobList        = $this->createMock(IJobList::class);
        $this->mockLayoutResolver = $this->createMock(OutputLayoutResolver::class);

        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('testuser');
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->service = new FolderBatchService(
            $this->mockLogger,
            $this->mockRootFolder,
            $this->mockUserSession,
            $this->mockStateService,
            $this->mockJobList,
            $this->createMock(AnonymizationService::class),
            $this->mockLayoutResolver
        );

    }//end setUp()

    /**
     * Build a mocked Folder with a given directory listing, path, id and permissions.
     *
     * @param array  $children     Child nodes for getDirectoryListing()
     * @param int    $id           The node id
     * @param int    $permissions  Folder permissions (default: all)
     * @param string $absolutePath Absolute path returned by getPath()
     *
     * @return Folder|MockObject
     */
    private function buildFolder(
        array $children,
        int $id=500,
        int $permissions=Constants::PERMISSION_ALL,
        string $absolutePath='/testuser/files/Documents/WOB'
    ): Folder|MockObject {
        $folder = $this->createMock(Folder::class);
        $folder->method('getDirectoryListing')->willReturn($children);
        $folder->method('getId')->willReturn($id);
        $folder->method('getPermissions')->willReturn($permissions);
        $folder->method('getPath')->willReturn($absolutePath);
        return $folder;

    }//end buildFolder()

    /**
     * Build a mocked File child.
     *
     * @param int    $id   File id
     * @param string $name File name
     *
     * @return File|MockObject
     */
    private function buildFile(int $id, string $name): File|MockObject
    {
        $file = $this->createMock(File::class);
        $file->method('getId')->willReturn($id);
        $file->method('getName')->willReturn($name);
        return $file;

    }//end buildFile()

    /**
     * Build a user folder mock wrapping a target folder for get()/getById() lookups.
     *
     * @param array     $getByIdNodes       Array returned by getById(), or null to not stub
     * @param Node|null $getPathNode        Node returned by get(), or null to not stub
     * @param bool      $pathThrowsNotFound When true, get() throws NotFoundException
     * @param string    $relativePath       The relative path reported by getRelativePath()
     *
     * @return Folder|MockObject
     */
    private function buildUserFolder(
        ?array $getByIdNodes=null,
        ?Node $getPathNode=null,
        bool $pathThrowsNotFound=false,
        string $relativePath='/Documents/WOB'
    ): Folder|MockObject {
        $userFolder = $this->createMock(Folder::class);

        if ($getByIdNodes !== null) {
            $userFolder->method('getById')->willReturn($getByIdNodes);
        }

        if ($pathThrowsNotFound === true) {
            $userFolder->method('get')->willThrowException(new NotFoundException());
        } else if ($getPathNode !== null) {
            $userFolder->method('get')->willReturn($getPathNode);
        }

        $userFolder->method('getRelativePath')->willReturn($relativePath);

        return $userFolder;

    }//end buildUserFolder()

    /**
     * Test successful folder batch creation via path — existing behaviour preserved,
     * now also stores the resolved folderId alongside folderPath.
     *
     * @return void
     */
    public function testCreateFolderBatchByPathHappyPath(): void
    {
        $file1 = $this->buildFile(101, 'report.pdf');
        $file2 = $this->buildFile(102, 'letter.docx');

        $folder = $this->buildFolder([$file1, $file2], 500);

        $userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturn(
                [
                    'batchId' => 'test-uuid',
                    'userId'  => 'testuser',
                    'status'  => 'uploading',
                    'files'   => [
                        ['fileId' => 101, 'fileName' => 'report.pdf', 'status' => 'uploaded'],
                        ['fileId' => 102, 'fileName' => 'letter.docx', 'status' => 'uploaded'],
                    ],
                ]
                );

        $this->mockStateService->expects($this->once())->method('updateBatch');
        $this->mockJobList->expects($this->once())->method('add')
            ->with(FolderExtractionJob::class, ['batchId' => 'test-uuid']);

        $result = $this->service->createFolderBatch(null, '/Documents/WOB');

        $this->assertEquals('test-uuid', $result['batchId']);
        $this->assertEquals('folder', $result['sourceType']);
        $this->assertEquals('/Documents/WOB', $result['folderPath']);
        $this->assertEquals(500, $result['folderId']);

    }//end testCreateFolderBatchByPathHappyPath()

    /**
     * Test successful folder batch creation via ID — node resolved via getById,
     * both identifiers captured on the batch.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdHappyPath(): void
    {
        $file1  = $this->buildFile(201, 'a.pdf');
        $folder = $this->buildFolder([$file1], 12345);

        $userFolder = $this->buildUserFolder([$folder], null, false, '/Shared/Cases');
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturn(
                [
                    'batchId' => 'id-uuid',
                    'userId'  => 'testuser',
                    'status'  => 'uploading',
                    'files'   => [['fileId' => 201, 'fileName' => 'a.pdf', 'status' => 'uploaded']],
                ]
                );

        $result = $this->service->createFolderBatch(12345, null);

        $this->assertEquals('id-uuid', $result['batchId']);
        $this->assertEquals(12345, $result['folderId']);
        $this->assertEquals('/Shared/Cases', $result['folderPath']);

    }//end testCreateFolderBatchByIdHappyPath()

    /**
     * Test that when getById returns multiple mounts (read-only + writable)
     * the writable one is preferred and its path is stored.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdPrefersWritableMount(): void
    {
        $file1 = $this->buildFile(301, 'x.pdf');

        $readOnlyFolder = $this->buildFolder(
            [$file1],
            12345,
            Constants::PERMISSION_READ,
            '/testuser/files/Groupfolders/Legal'
        );
        $writableFolder = $this->buildFolder(
            [$file1],
            12345,
            Constants::PERMISSION_ALL,
            '/testuser/files/Shared/Cases'
        );

        // Return read-only first so the code actually has to iterate to find the writable one.
        $userFolder = $this->buildUserFolder([$readOnlyFolder, $writableFolder], null, false, '/Shared/Cases');
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        // Assert that getRelativePath is called against the WRITABLE mount's path,
        // not the read-only one.
        $userFolder->expects($this->atLeastOnce())
            ->method('getRelativePath')
            ->with('/testuser/files/Shared/Cases')
            ->willReturn('/Shared/Cases');

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturn(
                [
                    'batchId' => 'uuid',
                    'userId'  => 'testuser',
                    'status'  => 'uploading',
                    'files'   => [['fileId' => 301, 'fileName' => 'x.pdf', 'status' => 'uploaded']],
                ]
                );

        $result = $this->service->createFolderBatch(12345, null);

        $this->assertEquals('/Shared/Cases', $result['folderPath']);

    }//end testCreateFolderBatchByIdPrefersWritableMount()

    /**
     * Test that when no writable mount exists, the first readable node is used.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdFallsBackToReadableWhenNoneWritable(): void
    {
        $file1 = $this->buildFile(401, 'y.pdf');

        $readOnlyFolder = $this->buildFolder(
            [$file1],
            12345,
            Constants::PERMISSION_READ,
            '/testuser/files/Readonly'
        );

        $userFolder = $this->buildUserFolder([$readOnlyFolder], null, false, '/Readonly');
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturn(
                [
                    'batchId' => 'uuid',
                    'userId'  => 'testuser',
                    'status'  => 'uploading',
                    'files'   => [['fileId' => 401, 'fileName' => 'y.pdf', 'status' => 'uploaded']],
                ]
                );

        $result = $this->service->createFolderBatch(12345, null);

        $this->assertEquals('/Readonly', $result['folderPath']);
        $this->assertEquals(12345, $result['folderId']);

    }//end testCreateFolderBatchByIdFallsBackToReadableWhenNoneWritable()

    /**
     * Test that a folder id that resolves to no nodes returns 404.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdReturns404WhenIdNotResolvable(): void
    {
        $userFolder = $this->buildUserFolder([], null);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Folder not found');

        $this->service->createFolderBatch(99999, null);

    }//end testCreateFolderBatchByIdReturns404WhenIdNotResolvable()

    /**
     * Test that a folder id resolving to a File (not Folder) returns 400.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdReturns400WhenNodeIsFile(): void
    {
        $fileNode = $this->createMock(File::class);
        $fileNode->method('getId')->willReturn(12345);
        $fileNode->method('getPermissions')->willReturn(Constants::PERMISSION_ALL);

        $userFolder = $this->buildUserFolder([$fileNode], null);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Path is not a folder');

        $this->service->createFolderBatch(12345, null);

    }//end testCreateFolderBatchByIdReturns400WhenNodeIsFile()

    /**
     * Test that a folder id resolving to an empty folder returns 400.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdReturns400WhenFolderEmpty(): void
    {
        $folder = $this->buildFolder([], 12345);

        $userFolder = $this->buildUserFolder([$folder], null);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('No files found in folder');

        $this->service->createFolderBatch(12345, null);

    }//end testCreateFolderBatchByIdReturns400WhenFolderEmpty()

    /**
     * Test that a folder id resolving to too many files returns 400.
     *
     * @return void
     */
    public function testCreateFolderBatchByIdReturns400WhenTooManyFiles(): void
    {
        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $children[] = $this->buildFile($i, "file{$i}.pdf");
        }

        $folder = $this->buildFolder($children, 12345);

        $userFolder = $this->buildUserFolder([$folder], null);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(2);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/too many files/i');

        $this->service->createFolderBatch(12345, null);

    }//end testCreateFolderBatchByIdReturns400WhenTooManyFiles()

    /**
     * Test that providing both folderId and folderPath is rejected with 400.
     *
     * @return void
     */
    public function testCreateFolderBatchRejectsBothParams(): void
    {
        $userFolder = $this->createMock(Folder::class);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Provide only one of folderId or folderPath');

        $this->service->createFolderBatch(12345, '/Documents/WOB');

    }//end testCreateFolderBatchRejectsBothParams()

    /**
     * Test that providing neither folderId nor folderPath is rejected with 400.
     *
     * @return void
     */
    public function testCreateFolderBatchRejectsNeitherParam(): void
    {
        $userFolder = $this->createMock(Folder::class);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Either folderId or folderPath must be provided');

        $this->service->createFolderBatch(null, null);

    }//end testCreateFolderBatchRejectsNeitherParam()

    /**
     * Test that directories inside the folder are skipped when enumerating.
     *
     * @return void
     */
    public function testSkipsSubdirectories(): void
    {
        $file = $this->buildFile(101, 'report.pdf');

        $subFolder = $this->createMock(Folder::class);

        $folder = $this->buildFolder([$file, $subFolder], 500);

        $userFolder = $this->buildUserFolder(null, $folder);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturnCallback(
            function (string $userId, array $files) {
                $this->assertCount(1, $files);
                $this->assertEquals(101, $files[0]['fileId']);
                return ['batchId' => 'uuid', 'userId' => $userId, 'status' => 'uploading', 'files' => $files];
            }
        );

        $this->service->createFolderBatch(null, '/test');

    }//end testSkipsSubdirectories()

    /**
     * Test folder-path not found throws 404.
     *
     * @return void
     */
    public function testFolderPathNotFoundThrows404(): void
    {
        $userFolder = $this->buildUserFolder(null, null, true);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);

        $this->service->createFolderBatch(null, '/nonexistent');

    }//end testFolderPathNotFoundThrows404()

    /**
     * Test path pointing to a file throws 400.
     *
     * @return void
     */
    public function testFolderPathIsFileThrows400(): void
    {
        $fileNode = $this->createMock(File::class);

        $userFolder = $this->buildUserFolder(null, $fileNode);
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->service->createFolderBatch(null, '/somefile.pdf');

    }//end testFolderPathIsFileThrows400()

    /**
     * Test no user session throws 401.
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
            $this->mockJobList,
            $this->createMock(AnonymizationService::class),
            $this->mockLayoutResolver
        );

        $this->expectException(Exception::class);
        $this->expectExceptionCode(401);

        $service->createFolderBatch(null, '/any');

    }//end testNoUserThrows401()

    /**
     * Test that _anonymized-suffixed files are excluded from source discovery.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
     */
    public function testSourceDiscoveryExcludesAnonymizedSuffixedFiles(): void
    {
        // phpcs:disable CustomSn.Functions.NamedParameters
        $cleanFile    = $this->buildFile(101, 'report.pdf');
        $legacyFile   = $this->buildFile(102, 'report_anonymized.pdf');
        $anotherClean = $this->buildFile(103, 'letter.docx');
        // phpcs:enable CustomSn.Functions.NamedParameters

        // HasAnonymizedSuffix returns true only for the legacy file.
        $this->mockLayoutResolver->method('hasAnonymizedSuffix')
            ->willReturnCallback(
                    function (string $fileName): bool {
                        return str_ends_with($fileName, '_anonymized.pdf')
                        || str_ends_with(pathinfo($fileName, PATHINFO_FILENAME), '_anonymized');
                    }
                    );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $folder     = $this->buildFolder([$cleanFile, $legacyFile, $anotherClean], 500);
        $userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
        // phpcs:enable CustomSn.Functions.NamedParameters
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);
        $this->mockStateService->method('createBatch')->willReturnCallback(
            function (string $userId, array $files) {
                // Only the two clean files should have been passed.
                // phpcs:disable CustomSn.Functions.NamedParameters
                $this->assertCount(2, $files);
                $fileIds = array_column($files, 'fileId');
                $this->assertContains(101, $fileIds);
                $this->assertContains(103, $fileIds);
                $this->assertNotContains(102, $fileIds);
                // phpcs:enable CustomSn.Functions.NamedParameters
                return [
                    'batchId' => 'uuid',
                    'userId'  => $userId,
                    'status'  => 'uploading',
                    'files'   => $files,
                ];
            }
        );

        $this->service->createFolderBatch(null, '/Documents/WOB');

    }//end testSourceDiscoveryExcludesAnonymizedSuffixedFiles()

    /**
     * Test that a folder containing only _anonymized files throws 400 (no files found).
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
     */
    public function testSourceDiscoveryAllFilteredThrowsNoFiles(): void
    {
        // phpcs:disable CustomSn.Functions.NamedParameters
        $legacyFile = $this->buildFile(102, 'report_anonymized.pdf');
        // phpcs:enable CustomSn.Functions.NamedParameters

        $this->mockLayoutResolver->method('hasAnonymizedSuffix')->willReturn(true);

        // phpcs:disable CustomSn.Functions.NamedParameters
        $folder     = $this->buildFolder([$legacyFile], 500);
        $userFolder = $this->buildUserFolder(null, $folder, false, '/Documents/WOB');
        // phpcs:enable CustomSn.Functions.NamedParameters
        $this->mockRootFolder->method('getUserFolder')->willReturn($userFolder);

        $this->mockStateService->method('getMaxFiles')->willReturn(100);

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('No files found in folder');
        // phpcs:enable CustomSn.Functions.NamedParameters

        $this->service->createFolderBatch(null, '/Documents/WOB');

    }//end testSourceDiscoveryAllFilteredThrowsNoFiles()

    // ----------------------------------------------------------------
    // applyOutputLayout() static helper — post-process move tests
    // ----------------------------------------------------------------

    /**
     * Build a mock File for use in applyOutputLayout tests.
     *
     * @param string $name       File name returned by getName().
     * @param string $parentPath Absolute path of the parent folder.
     *
     * @return File|MockObject
     */
    private function buildAnonFileNode(string $name, string $parentPath): File|MockObject
    {
        $parentFolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $parentFolder->method('getPath')->willReturn($parentPath);
        $parentFolder->method('nodeExists')->willReturn(false);
        $parentFolder->method('newFolder')->willReturn($this->createMock(Folder::class));
        // phpcs:enable CustomSn.Functions.NamedParameters

        $file = $this->createMock(File::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $file->method('getName')->willReturn($name);
        $file->method('getParent')->willReturn($parentFolder);
        // phpcs:enable CustomSn.Functions.NamedParameters

        return $file;

    }//end buildAnonFileNode()

    /**
     * Build a minimal IRootFolder stub for applyOutputLayout lookups.
     *
     * @param int       $anonFileId ID returned from getById when queried.
     * @param File|null $anonFile   File node to return, or null for empty.
     *
     * @return IRootFolder|MockObject
     */
    private function buildRootFolderStub(int $anonFileId, ?File $anonFile): IRootFolder|MockObject
    {
        $userFolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $userFolder->method('getById')->willReturnCallback(
            function (int $id) use ($anonFileId, $anonFile) {
                if ($id === $anonFileId) {
                    return $anonFile !== null ? [$anonFile] : [];
                }

                return [];
            }
        );
        // phpcs:enable CustomSn.Functions.NamedParameters

        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->willReturn($userFolder);

        return $rootFolder;

    }//end buildRootFolderStub()

    /**
     * applyOutputLayout: moves the anonymized file to the configured subfolder
     * and returns the target path.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-1
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
     */
    public function testApplyOutputLayoutMovesFileToSubfolder(): void
    {
        $anonFileId = 500;
        $legacyPath = '/testuser/files/Dossier/report_anonymized.pdf';
        $targetPath = '/testuser/files/Dossier/anonymised/report.pdf';

        $anonFile = $this->buildAnonFileNode('report_anonymized.pdf', '/testuser/files/Dossier');
        // phpcs:disable CustomSn.Functions.NamedParameters
        $anonFile->expects($this->once())->method('move')->with($targetPath);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $rootFolder = $this->buildRootFolderStub(anonFileId: $anonFileId, anonFile: $anonFile);

        $layoutResolver = $this->createMock(OutputLayoutResolver::class);
        $layoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $layoutResolver->method('resolveBatchDestination')->willReturn($targetPath);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $result = FolderBatchService::applyOutputLayout(
            sourceFileId: 10,
            anonymizationResult: ['anonymizedFileId' => $anonFileId, 'anonymizedFilePath' => $legacyPath],
            userId: 'testuser',
            rootFolder: $rootFolder,
            layoutResolver: $layoutResolver,
            logger: $logger
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->assertEquals($targetPath, $result['anonymizedFilePath']);
        $this->assertArrayNotHasKey('warning', $result);
        // phpcs:enable CustomSn.Functions.NamedParameters

    }//end testApplyOutputLayoutMovesFileToSubfolder()

    /**
     * applyOutputLayout: when the subfolder already exists it is reused.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
     */
    public function testApplyOutputLayoutReusesExistingSubfolder(): void
    {
        $anonFileId = 501;
        $targetPath = '/testuser/files/Dossier/anonymised/report.pdf';

        // Parent folder with an existing 'anonymised' subfolder node.
        $existingSubfolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $existingSubfolder->method('nodeExists')->willReturn(false);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $parentFolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $parentFolder->method('getPath')->willReturn('/testuser/files/Dossier');
        $parentFolder->method('nodeExists')->willReturn(true);
        $parentFolder->method('get')->willReturn($existingSubfolder);
        $parentFolder->expects($this->never())->method('newFolder');
        // phpcs:enable CustomSn.Functions.NamedParameters

        $anonFile = $this->createMock(File::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $anonFile->method('getName')->willReturn('report_anonymized.pdf');
        $anonFile->method('getParent')->willReturn($parentFolder);
        $anonFile->expects($this->once())->method('move')->with($targetPath);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $rootFolder = $this->buildRootFolderStub(anonFileId: $anonFileId, anonFile: $anonFile);

        $layoutResolver = $this->createMock(OutputLayoutResolver::class);
        $layoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $layoutResolver->method('resolveBatchDestination')->willReturn($targetPath);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $result = FolderBatchService::applyOutputLayout(
            sourceFileId: 11,
            anonymizationResult: ['anonymizedFileId' => $anonFileId, 'anonymizedFilePath' => '/legacy.pdf'],
            userId: 'testuser',
            rootFolder: $rootFolder,
            layoutResolver: $layoutResolver,
            logger: $logger
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->assertEquals($targetPath, $result['anonymizedFilePath']);
        // phpcs:enable CustomSn.Functions.NamedParameters

    }//end testApplyOutputLayoutReusesExistingSubfolder()

    /**
     * applyOutputLayout: when a collision exists at the target, it is overwritten.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
     */
    public function testApplyOutputLayoutOverwritesCollision(): void
    {
        $anonFileId = 502;
        $targetPath = '/testuser/files/Dossier/anonymised/report.pdf';

        $existingTarget = $this->createMock(File::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $existingTarget->expects($this->once())->method('delete');
        // phpcs:enable CustomSn.Functions.NamedParameters

        $subfolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $subfolder->method('nodeExists')->willReturn(true);
        $subfolder->method('get')->willReturn($existingTarget);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $parentFolder = $this->createMock(Folder::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $parentFolder->method('getPath')->willReturn('/testuser/files/Dossier');
        $parentFolder->method('nodeExists')->willReturn(true);
        $parentFolder->method('get')->willReturn($subfolder);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $anonFile = $this->createMock(File::class);
        // phpcs:disable CustomSn.Functions.NamedParameters
        $anonFile->method('getName')->willReturn('report_anonymized.pdf');
        $anonFile->method('getParent')->willReturn($parentFolder);
        $anonFile->expects($this->once())->method('move')->with($targetPath);
        // phpcs:enable CustomSn.Functions.NamedParameters

        $rootFolder = $this->buildRootFolderStub(anonFileId: $anonFileId, anonFile: $anonFile);

        $layoutResolver = $this->createMock(OutputLayoutResolver::class);
        $layoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $layoutResolver->method('resolveBatchDestination')->willReturn($targetPath);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $result = FolderBatchService::applyOutputLayout(
            sourceFileId: 12,
            anonymizationResult: ['anonymizedFileId' => $anonFileId, 'anonymizedFilePath' => '/legacy.pdf'],
            userId: 'testuser',
            rootFolder: $rootFolder,
            layoutResolver: $layoutResolver,
            logger: $logger
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->assertEquals($targetPath, $result['anonymizedFilePath']);
        // phpcs:enable CustomSn.Functions.NamedParameters

    }//end testApplyOutputLayoutOverwritesCollision()

    /**
     * applyOutputLayout: when the move throws, the legacy path is returned with a warning.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
     */
    public function testApplyOutputLayoutMoveFailurePreservesLegacyPath(): void
    {
        $anonFileId = 503;
        $legacyPath = '/testuser/files/Dossier/report_anonymized.pdf';
        $targetPath = '/testuser/files/Dossier/anonymised/report.pdf';

        $anonFile = $this->buildAnonFileNode('report_anonymized.pdf', '/testuser/files/Dossier');
        // phpcs:disable CustomSn.Functions.NamedParameters
        $anonFile->method('move')->willThrowException(new \Exception('Filesystem error'));
        // phpcs:enable CustomSn.Functions.NamedParameters

        $rootFolder = $this->buildRootFolderStub(anonFileId: $anonFileId, anonFile: $anonFile);

        $layoutResolver = $this->createMock(OutputLayoutResolver::class);
        $layoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $layoutResolver->method('resolveBatchDestination')->willReturn($targetPath);
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())->method('warning');

        $result = FolderBatchService::applyOutputLayout(
            sourceFileId: 13,
            anonymizationResult: ['anonymizedFileId' => $anonFileId, 'anonymizedFilePath' => $legacyPath],
            userId: 'testuser',
            rootFolder: $rootFolder,
            layoutResolver: $layoutResolver,
            logger: $logger
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->assertEquals($legacyPath, $result['anonymizedFilePath']);
        $this->assertArrayHasKey('warning', $result);
        $this->assertEquals('MOVE_FAILED', $result['warning']['code']);
        // phpcs:enable CustomSn.Functions.NamedParameters

    }//end testApplyOutputLayoutMoveFailurePreservesLegacyPath()

    /**
     * applyOutputLayout: when anonymizedFileId is null (no output file), returns legacy path as-is.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-6
     */
    public function testApplyOutputLayoutReturnsLegacyWhenNoFileId(): void
    {
        $legacyPath = '/testuser/files/Dossier/report_anonymized.pdf';

        $rootFolder     = $this->createMock(IRootFolder::class);
        $layoutResolver = $this->createMock(OutputLayoutResolver::class);
        $logger         = $this->createMock(\Psr\Log\LoggerInterface::class);

        $result = FolderBatchService::applyOutputLayout(
            sourceFileId: 99,
            anonymizationResult: ['anonymizedFileId' => null, 'anonymizedFilePath' => $legacyPath],
            userId: 'testuser',
            rootFolder: $rootFolder,
            layoutResolver: $layoutResolver,
            logger: $logger
        );

        // phpcs:disable CustomSn.Functions.NamedParameters
        $this->assertEquals($legacyPath, $result['anonymizedFilePath']);
        // phpcs:enable CustomSn.Functions.NamedParameters

    }//end testApplyOutputLayoutReturnsLegacyWhenNoFileId()
}//end class
