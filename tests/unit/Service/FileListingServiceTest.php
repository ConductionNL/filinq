<?php

/**
 * Unit tests for FileListingService
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

use OCA\DocuDesk\Service\FileEntityStatsService;
use OCA\DocuDesk\Service\FileListingService;
use OCA\DocuDesk\Service\FileUploadService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FileListingService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FileListingServiceTest extends TestCase
{

    /**
     * @var FileListingService
     */
    private FileListingService $service;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var FileUploadService|MockObject
     */
    private FileUploadService|MockObject $mockFileUploadService;

    /**
     * @var FileEntityStatsService|MockObject
     */
    private FileEntityStatsService|MockObject $mockEntityStatsService;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger             = $this->createMock(LoggerInterface::class);
        $this->mockFileUploadService  = $this->createMock(FileUploadService::class);
        $this->mockEntityStatsService = $this->createMock(FileEntityStatsService::class);

        $this->service = new FileListingService(
            $this->mockLogger,
            $this->mockFileUploadService,
            $this->mockEntityStatsService
        );

    }//end setUp()


    /**
     * Test uploadFile delegates to FileUploadService
     *
     * @return void
     */
    public function testUploadFileDelegates(): void
    {
        $expected = [
            'fileId'   => 1,
            'filePath' => '/path/test.pdf',
            'fileName' => 'test.pdf',
            'fileSize' => 1024,
        ];

        $this->mockFileUploadService->method('uploadFile')
            ->with('test.pdf', 'content')
            ->willReturn($expected);

        $result = $this->service->uploadFile('test.pdf', 'content');
        $this->assertEquals($expected, $result);

    }//end testUploadFileDelegates()


    /**
     * Test listProcessedFiles throws when user not logged in
     *
     * @return void
     */
    public function testListProcessedFilesThrowsOnError(): void
    {
        $this->expectException(\Exception::class);

        $this->mockFileUploadService->method('getCurrentUserId')
            ->willThrowException(new \Exception('No user is currently logged in.', 401));

        $this->service->listProcessedFiles();

    }//end testListProcessedFilesThrowsOnError()


    /**
     * Test listProcessedFiles returns empty for empty folder
     *
     * @return void
     */
    public function testListProcessedFilesReturnsEmptyForEmptyFolder(): void
    {
        $this->mockFileUploadService->method('getCurrentUserId')
            ->willReturn('admin');

        $mockFolder = $this->createMock(\OCP\Files\Folder::class);
        $mockFolder->method('getDirectoryListing')
            ->willReturn([]);

        $this->mockFileUploadService->method('getDocuDeskFolder')
            ->willReturn($mockFolder);

        $this->mockEntityStatsService->method('tryGetEntityRelationMapper')
            ->willReturn(null);
        $this->mockEntityStatsService->method('tryGetRiskLevelService')
            ->willReturn(null);

        $result = $this->service->listProcessedFiles();
        $this->assertIsArray($result);
        $this->assertEmpty($result);

    }//end testListProcessedFilesReturnsEmptyForEmptyFolder()


}//end class
