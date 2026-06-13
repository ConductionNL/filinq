<?php

/**
 * Unit tests for BatchAnonymizeService — output folder layout post-processing
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BatchAnonymizeService post-process move behaviour.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchAnonymizeServiceOutputLayoutTest extends TestCase
{

    /**
     * The service under test.
     *
     * @var BatchAnonymizeService
     */
    private BatchAnonymizeService $service;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var AnonymizationService|MockObject
     */
    private AnonymizationService|MockObject $mockAnonService;

    /**
     * @var BatchStateService|MockObject
     */
    private BatchStateService|MockObject $mockStateService;

    /**
     * @var IRootFolder|MockObject
     */
    private IRootFolder|MockObject $mockRootFolder;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var OutputLayoutResolver|MockObject
     */
    private OutputLayoutResolver|MockObject $mockLayoutResolver;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger         = $this->createMock(LoggerInterface::class);
        $this->mockAnonService    = $this->createMock(AnonymizationService::class);
        $this->mockStateService   = $this->createMock(BatchStateService::class);
        $this->mockRootFolder     = $this->createMock(IRootFolder::class);
        $this->mockUserSession    = $this->createMock(IUserSession::class);
        $this->mockLayoutResolver = $this->createMock(OutputLayoutResolver::class);

        $this->service = new BatchAnonymizeService(
            logger: $this->mockLogger,
            anonService: $this->mockAnonService,
            stateService: $this->mockStateService,
            rootFolder: $this->mockRootFolder,
            userSession: $this->mockUserSession,
            layoutResolver: $this->mockLayoutResolver
        );

    }//end setUp()

    /**
     * Build a minimal batch fixture with one extracted file.
     *
     * @param int $fileId Source file ID.
     *
     * @return array<string, mixed> Batch data.
     */
    private function makeBatch(int $fileId=10): array
    {
        return [
            'batchId' => 'batch-layout-1',
            'status'  => 'review',
            'files'   => [
                ['fileId' => $fileId, 'status' => 'extracted'],
            ],
        ];

    }//end makeBatch()

    /**
     * Build a mock user for the user session.
     *
     * @return IUser|MockObject
     */
    private function makeUser(): IUser|MockObject
    {
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('admin');
        return $mockUser;

    }//end makeUser()

    /**
     * Build a mock source folder at a given path.
     *
     * @param string $path Folder path.
     *
     * @return Folder|MockObject
     */
    private function makeFolder(string $path='/admin/files/dossier'): Folder|MockObject
    {
        $mockFolder = $this->createMock(Folder::class);
        $mockFolder->method('getPath')->willReturn($path);
        $mockFolder->method('nodeExists')->willReturn(false);
        $mockFolder->method('newFolder')->willReturnSelf();
        return $mockFolder;

    }//end makeFolder()

    /**
     * Post-process move places file at expected subfolder path.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
     */
    public function testPostProcessMovePlacesFileAtExpectedPath(): void
    {
        $batch = $this->makeBatch(fileId: 10);
        $this->mockStateService->method('getBatch')->willReturn($batch);

        $targetPath   = '/admin/files/dossier/anonymised/foo.pdf';
        $mockFolder   = $this->makeFolder();
        $mockAnonFile = $this->createMock(File::class);
        $mockAnonFile->method('getName')->willReturn('foo_anonymized.pdf');
        $mockAnonFile->method('getParent')->willReturn($mockFolder);
        $mockAnonFile->expects($this->once())->method('move')->with(targetPath: $targetPath);

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('getById')->willReturn([$mockAnonFile]);

        $this->mockUserSession->method('getUser')->willReturn($this->makeUser());
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockLayoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $this->mockLayoutResolver->method('resolveBatchDestination')->willReturn($targetPath);

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 2, 'anonymizedFileId' => 99, 'anonymizedFilePath' => '/admin/files/dossier/foo_anonymized.pdf']);

        $result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

        $this->assertSame(1, $result['processedFiles']);

    }//end testPostProcessMovePlacesFileAtExpectedPath()

    /**
     * Move failure preserves file at legacy path with warning.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
     */
    public function testMoveFailurePreservesFileAtLegacyPathWithWarning(): void
    {
        $batch = $this->makeBatch(fileId: 10);
        $this->mockStateService->method('getBatch')->willReturn($batch);

        $legacyPath   = '/admin/files/dossier/foo_anonymized.pdf';
        $mockFolder   = $this->makeFolder();
        $mockAnonFile = $this->createMock(File::class);
        $mockAnonFile->method('getName')->willReturn('foo_anonymized.pdf');
        $mockAnonFile->method('getParent')->willReturn($mockFolder);
        $mockAnonFile->method('move')->willThrowException(new \Exception('Permission denied'));

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('getById')->willReturn([$mockAnonFile]);

        $this->mockUserSession->method('getUser')->willReturn($this->makeUser());
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockLayoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $this->mockLayoutResolver->method('resolveBatchDestination')
            ->willReturn('/admin/files/dossier/anonymised/foo.pdf');

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 99, 'anonymizedFilePath' => $legacyPath]);

        $this->mockLogger->expects($this->once())->method('warning');

        $result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

        $this->assertSame(1, $result['processedFiles']);

    }//end testMoveFailurePreservesFileAtLegacyPathWithWarning()

    /**
     * When anonymizedFileId is null, no move is attempted and legacy path is returned.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
     */
    public function testNoMoveWhenAnonymizedFileIdIsNull(): void
    {
        $batch = $this->makeBatch(fileId: 10);
        $this->mockStateService->method('getBatch')->willReturn($batch);

        $this->mockUserSession->method('getUser')->willReturn($this->makeUser());
        $this->mockRootFolder->expects($this->never())->method('getUserFolder');

        $this->mockAnonService->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 0, 'anonymizedFileId' => null, 'anonymizedFilePath' => null]);

        $result = $this->service->anonymizeBatch(batchId: 'batch-layout-1', entities: []);

        $this->assertSame(1, $result['processedFiles']);

    }//end testNoMoveWhenAnonymizedFileIdIsNull()

    /**
     * Source discovery excludes files whose base name ends with `_anonymized`.
     *
     * This test verifies the integration: the batch was created from a folder
     * that already had `_anonymized` files; they should never appear in the
     * batch's file list because FolderBatchService filtered them. Here we
     * confirm that extraction-only files in the batch are simply skipped, not
     * anonymized (status not 'extracted').
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-10
     */
    public function testSourceDiscoveryExcludedFilesAreSkipped(): void
    {
        $batch = [
            'batchId' => 'batch-layout-2',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 1, 'status' => 'extracted'],
                ['fileId' => 2, 'status' => 'uploaded'],
            ],
        ];
        $this->mockStateService->method('getBatch')->willReturn($batch);

        $mockAnonFile = $this->createMock(File::class);
        $mockAnonFile->method('getName')->willReturn('clean.pdf');
        $mockAnonFile->method('getParent')->willReturn($this->makeFolder());
        $mockAnonFile->method('move')->willReturnSelf();

        $mockUserFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('getById')->willReturn([$mockAnonFile]);

        $this->mockUserSession->method('getUser')->willReturn($this->makeUser());
        $this->mockRootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        $this->mockLayoutResolver->method('readSubfolderName')->willReturn('anonymised');
        $this->mockLayoutResolver->method('resolveBatchDestination')
            ->willReturn('/admin/files/dossier/anonymised/clean.pdf');

        $this->mockAnonService->expects($this->once())
            ->method('anonymizeDocument')
            ->willReturn(['replacementCount' => 1, 'anonymizedFileId' => 55, 'anonymizedFilePath' => '/src/clean_anonymized.pdf']);

        $result = $this->service->anonymizeBatch(batchId: 'batch-layout-2', entities: []);

        $this->assertSame(1, $result['processedFiles']);
        $this->assertSame(2, $result['totalFiles']);

    }//end testSourceDiscoveryExcludedFilesAreSkipped()
}//end class
