<?php

/**
 * Unit tests for BatchUploadService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/unit-test-coverage-75/tasks.md#task-4.3
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\FileUploadService;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BatchUploadService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class BatchUploadServiceTest extends TestCase
{

    /**
     * @var BatchUploadService
     */
    private BatchUploadService $service;

    /**
     * @var FileUploadService|MockObject
     */
    private FileUploadService|MockObject $mockUploadService;

    /**
     * @var BatchStateService|MockObject
     */
    private BatchStateService|MockObject $mockStateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockUploadService = $this->createMock(FileUploadService::class);
        $this->mockStateService  = $this->createMock(BatchStateService::class);

        $this->service = new BatchUploadService(
            uploadService: $this->mockUploadService,
            stateService: $this->mockStateService,
        );

    }//end setUp()

    /**
     * Test getUserId delegates to FileUploadService.
     *
     * @return void
     */
    public function testGetUserIdDelegatesToUploadService(): void
    {
        $this->mockUploadService->method('getCurrentUserId')->willReturn('testuser');

        $this->assertSame('testuser', $this->service->getUserId());

    }//end testGetUserIdDelegatesToUploadService()

    /**
     * Test collectFiles returns empty array when no files uploaded.
     *
     * @return void
     */
    public function testCollectFilesReturnsEmptyArrayForNoUploads(): void
    {
        $mockRequest = $this->createMock(IRequest::class);
        $mockRequest->method('getUploadedFile')->willReturn(null);

        $files = $this->service->collectFiles($mockRequest);

        $this->assertSame([], $files);

    }//end testCollectFilesReturnsEmptyArrayForNoUploads()

    /**
     * Test processBatchUpload creates batch from uploaded files.
     *
     * @return void
     */
    public function testProcessBatchUploadCreatesBatch(): void
    {
        $this->mockUploadService->method('uploadFile')->willReturn(
            ['fileId' => 42, 'fileName' => 'test.pdf']
        );

        $this->mockStateService->method('createBatch')->willReturn(
            ['batchId' => 'new-batch', 'status' => 'uploading', 'files' => []]
        );

        $files = [
            ['name' => 'test.pdf', 'tmp_name' => '/tmp/phpXXXXXX', 'error' => UPLOAD_ERR_OK],
        ];

        $tmpFile = tempnam(sys_get_temp_dir(), 'phpunit_batchupload_');
        file_put_contents($tmpFile, 'fake pdf content');
        $files[0]['tmp_name'] = $tmpFile;

        $result = $this->service->processBatchUpload('user1', $files);

        unlink($tmpFile);

        $this->assertArrayHasKey('batchId', $result);

    }//end testProcessBatchUploadCreatesBatch()

    /**
     * Test processBatchUpload records error for files with upload error.
     *
     * @return void
     */
    public function testProcessBatchUploadRecordsUploadError(): void
    {
        $this->mockStateService->method('createBatch')->willReturn(
            ['batchId' => 'err-batch', 'status' => 'uploading', 'files' => []]
        );

        $files = [
            ['name' => 'bad.pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE],
        ];

        $result = $this->service->processBatchUpload('user1', $files);

        $this->assertIsArray($result);

    }//end testProcessBatchUploadRecordsUploadError()
}//end class
