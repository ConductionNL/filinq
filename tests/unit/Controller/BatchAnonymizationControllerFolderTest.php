<?php

/**
 * Unit tests for BatchAnonymizationController folder-related features
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\BatchAnonymizationController;
use OCA\DocuDesk\Service\BatchAnonymizeService;
use OCA\DocuDesk\Service\BatchExtractionService;
use OCA\DocuDesk\Service\BatchReportService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\BatchUploadService;
use OCA\DocuDesk\Service\EntityConsolidationService;
use OCA\DocuDesk\Service\FolderBatchService;
use OCA\DocuDesk\Service\WooProfileService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for folder batch endpoint and modified entity endpoint
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BatchAnonymizationControllerFolderTest extends TestCase
{

    /**
     * The controller under test
     *
     * @var BatchAnonymizationController
     */
    private BatchAnonymizationController $controller;

    /**
     * Mocked IRequest
     *
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * Mocked BatchStateService
     *
     * @var BatchStateService|MockObject
     */
    private BatchStateService|MockObject $mockStateService;

    /**
     * Mocked FolderBatchService
     *
     * @var FolderBatchService|MockObject
     */
    private FolderBatchService|MockObject $mockFolderService;

    /**
     * Mocked EntityConsolidationService
     *
     * @var EntityConsolidationService|MockObject
     */
    private EntityConsolidationService|MockObject $mockEntityService;

    /**
     * Mocked IL10N
     *
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;


    /**
     * Set up test environment
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest       = $this->createMock(IRequest::class);
        $this->mockStateService  = $this->createMock(BatchStateService::class);
        $this->mockFolderService = $this->createMock(FolderBatchService::class);
        $this->mockEntityService = $this->createMock(EntityConsolidationService::class);
        $this->mockL10n          = $this->createMock(IL10N::class);

        $this->mockL10n->method('t')->willReturnCallback(fn($s) => $s);

        $this->controller = new BatchAnonymizationController(
            'docudesk',
            $this->mockRequest,
            $this->createMock(LoggerInterface::class),
            $this->mockStateService,
            $this->createMock(BatchUploadService::class),
            $this->createMock(BatchExtractionService::class),
            $this->createMock(BatchAnonymizeService::class),
            $this->createMock(BatchReportService::class),
            $this->mockEntityService,
            $this->createMock(WooProfileService::class),
            $this->mockFolderService,
            $this->mockL10n
        );

    }//end setUp()


    /**
     * Test folderBatch returns batch data on success
     *
     * @return void
     */
    public function testFolderBatchSuccess(): void
    {
        $this->mockRequest->method('getParam')->with('folderPath', '')->willReturn('/Documents/WOB');

        $this->mockFolderService->method('createFolderBatch')->willReturn([
            'batchId' => 'uuid-1',
            'files'   => [
                ['fileId' => 1, 'fileName' => 'a.pdf'],
                ['fileId' => 2, 'fileName' => 'b.pdf'],
            ],
        ]);

        $response = $this->controller->folderBatch();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals('uuid-1', $data['batchId']);
        $this->assertEquals(2, $data['fileCount']);

    }//end testFolderBatchSuccess()


    /**
     * Test folderBatch returns 400 when no path provided
     *
     * @return void
     */
    public function testFolderBatchMissingPath(): void
    {
        $this->mockRequest->method('getParam')->with('folderPath', '')->willReturn('');

        $response = $this->controller->folderBatch();

        $this->assertEquals(400, $response->getStatus());

    }//end testFolderBatchMissingPath()


    /**
     * Test folderBatch propagates error codes
     *
     * @return void
     */
    public function testFolderBatchNotFound(): void
    {
        $this->mockRequest->method('getParam')->with('folderPath', '')->willReturn('/nonexistent');
        $this->mockFolderService->method('createFolderBatch')
            ->willThrowException(new Exception('Folder not found', 404));

        $response = $this->controller->folderBatch();

        $this->assertEquals(404, $response->getStatus());

    }//end testFolderBatchNotFound()


    /**
     * Test batchEntities allows extracting status with partial results
     *
     * @return void
     */
    public function testBatchEntitiesDuringExtracting(): void
    {
        $batch = [
            'batchId' => 'b1',
            'status'  => 'extracting',
            'files'   => [
                ['fileId' => 1, 'status' => 'extracted', 'entityCount' => 5],
                ['fileId' => 2, 'status' => 'uploaded', 'entityCount' => 0],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockRequest->method('getParam')->willReturn('0.0');
        $this->mockEntityService->method('consolidateEntities')->willReturn([
            ['type' => 'PERSON', 'value' => 'Jan', 'highestConfidence' => 0.9, 'fileCount' => 1],
        ]);

        $response = $this->controller->batchEntities('b1');
        $data     = $response->getData();

        $this->assertEquals(200, $response->getStatus());
        $this->assertFalse($data['complete']);
        $this->assertEquals(1, $data['filesProcessed']);
        $this->assertEquals(1, $data['entityCount']);

    }//end testBatchEntitiesDuringExtracting()


    /**
     * Test batchEntities returns complete=true for review status
     *
     * @return void
     */
    public function testBatchEntitiesCompleteOnReview(): void
    {
        $batch = [
            'batchId' => 'b2',
            'status'  => 'review',
            'files'   => [
                ['fileId' => 1, 'status' => 'extracted', 'entityCount' => 5],
                ['fileId' => 2, 'status' => 'extracted', 'entityCount' => 3],
            ],
        ];

        $this->mockStateService->method('getBatch')->willReturn($batch);
        $this->mockRequest->method('getParam')->willReturn('0.0');
        $this->mockEntityService->method('consolidateEntities')->willReturn([]);

        $response = $this->controller->batchEntities('b2');
        $data     = $response->getData();

        $this->assertTrue($data['complete']);
        $this->assertEquals(2, $data['filesProcessed']);

    }//end testBatchEntitiesCompleteOnReview()


    /**
     * Test batchEntities returns 409 for uploading status
     *
     * @return void
     */
    public function testBatchEntitiesRejects409ForUploadingStatus(): void
    {
        $batch = ['batchId' => 'b3', 'status' => 'uploading', 'files' => []];
        $this->mockStateService->method('getBatch')->willReturn($batch);

        $response = $this->controller->batchEntities('b3');

        $this->assertEquals(409, $response->getStatus());

    }//end testBatchEntitiesRejects409ForUploadingStatus()


}//end class
