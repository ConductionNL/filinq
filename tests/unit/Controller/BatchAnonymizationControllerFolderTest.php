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
 * @psalm-suppress                                 PropertyNotSetInConstructor
 * @phpstan-extends                                TestCase
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
            $this->mockL10n,
            $this->createMock(\OCP\IAppConfig::class)
        );

    }//end setUp()


    /**
     * Stub IRequest::getParam() to return given values for folderId and folderPath.
     *
     * Matches the controller's call pattern:
     *   getParam('folderId')           => $folderIdReturn
     *   getParam('folderPath', '')     => $folderPathReturn
     *
     * @param mixed $folderIdReturn   Value to return when getParam('folderId') is called
     * @param mixed $folderPathReturn Value to return when getParam('folderPath', '') is called
     *
     * @return void
     */
    private function stubFolderParams(mixed $folderIdReturn, mixed $folderPathReturn): void
    {
        $this->mockRequest->method('getParam')->willReturnCallback(
            function (string $key, mixed $default=null) use ($folderIdReturn, $folderPathReturn) {
                if ($key === 'folderId') {
                    return $folderIdReturn;
                }

                if ($key === 'folderPath') {
                    return $folderPathReturn;
                }

                return $default;
            }
        );

    }//end stubFolderParams()


    /**
     * Existing path flow still works; response now also includes folderId/folderPath.
     *
     * @return void
     */
    public function testFolderBatchAcceptsFolderPath(): void
    {
        $this->stubFolderParams(null, '/Documents/WOB');

        $this->mockFolderService->expects($this->once())
            ->method('createFolderBatch')
            ->with(null, '/Documents/WOB')
            ->willReturn(
                    [
                        'batchId'    => 'uuid-1',
                        'folderId'   => 500,
                        'folderPath' => '/Documents/WOB',
                        'files'      => [
                            ['fileId' => 1, 'fileName' => 'a.pdf'],
                            ['fileId' => 2, 'fileName' => 'b.pdf'],
                        ],
                    ]
                    );

        $response = $this->controller->folderBatch();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals('uuid-1', $data['batchId']);
        $this->assertEquals(500, $data['folderId']);
        $this->assertEquals('/Documents/WOB', $data['folderPath']);
        $this->assertEquals(2, $data['fileCount']);

    }//end testFolderBatchAcceptsFolderPath()


    /**
     * folderId-only request: controller delegates with int id and null path,
     * response includes both identifiers.
     *
     * @return void
     */
    public function testFolderBatchAcceptsFolderId(): void
    {
        $this->stubFolderParams('12345', '');

        $this->mockFolderService->expects($this->once())
            ->method('createFolderBatch')
            ->with(12345, null)
            ->willReturn(
                    [
                        'batchId'    => 'uuid-id',
                        'folderId'   => 12345,
                        'folderPath' => '/Shared/Cases',
                        'files'      => [['fileId' => 1, 'fileName' => 'a.pdf']],
                    ]
                    );

        $response = $this->controller->folderBatch();
        $data     = $response->getData();

        $this->assertEquals('uuid-id', $data['batchId']);
        $this->assertEquals(12345, $data['folderId']);
        $this->assertEquals('/Shared/Cases', $data['folderPath']);
        $this->assertEquals(1, $data['fileCount']);

    }//end testFolderBatchAcceptsFolderId()


    /**
     * Request with both folderId and folderPath is rejected with 400 at the
     * controller boundary and the service is never invoked.
     *
     * @return void
     */
    public function testFolderBatchRejectsBothIdAndPath(): void
    {
        $this->stubFolderParams('12345', '/Documents/WOB');

        $this->mockFolderService->expects($this->never())->method('createFolderBatch');

        $response = $this->controller->folderBatch();
        $data     = $response->getData();

        $this->assertEquals(400, $response->getStatus());
        $this->assertStringContainsString('Provide only one of folderId or folderPath', $data['error']);

    }//end testFolderBatchRejectsBothIdAndPath()


    /**
     * Request with neither id nor path is rejected with 400 at the controller
     * boundary.
     *
     * @return void
     */
    public function testFolderBatchRejectsNeitherIdNorPath(): void
    {
        $this->stubFolderParams(null, '');

        $this->mockFolderService->expects($this->never())->method('createFolderBatch');

        $response = $this->controller->folderBatch();
        $data     = $response->getData();

        $this->assertEquals(400, $response->getStatus());
        $this->assertStringContainsString('Either folderId or folderPath must be provided', $data['error']);

    }//end testFolderBatchRejectsNeitherIdNorPath()


    /**
     * Request param arrives as a string (HTTP default) — controller coerces to int
     * before passing to the service.
     *
     * @return void
     */
    public function testFolderBatchCoercesFolderIdFromString(): void
    {
        $this->stubFolderParams('12345', '');

        $this->mockFolderService->expects($this->once())
            ->method('createFolderBatch')
            ->with(
                $this->identicalTo(12345),
                $this->identicalTo(null)
            )
            ->willReturn(
                    [
                        'batchId'    => 'uuid',
                        'folderId'   => 12345,
                        'folderPath' => '/Shared/Cases',
                        'files'      => [],
                    ]
                    );

        $this->controller->folderBatch();

    }//end testFolderBatchCoercesFolderIdFromString()


    /**
     * Empty-string folderId with a real folderPath: the empty id must be treated
     * as unset (null) rather than coerced to int(0).
     *
     * @return void
     */
    public function testFolderBatchTreatsEmptyStringAsUnset(): void
    {
        $this->stubFolderParams('', '/Documents/WOB');

        $this->mockFolderService->expects($this->once())
            ->method('createFolderBatch')
            ->with(
                $this->identicalTo(null),
                $this->identicalTo('/Documents/WOB')
            )
            ->willReturn(
                    [
                        'batchId'    => 'uuid',
                        'folderId'   => 500,
                        'folderPath' => '/Documents/WOB',
                        'files'      => [],
                    ]
                    );

        $this->controller->folderBatch();

    }//end testFolderBatchTreatsEmptyStringAsUnset()


    /**
     * Test folderBatch propagates error codes from the service (e.g. 404).
     *
     * @return void
     */
    public function testFolderBatchNotFound(): void
    {
        $this->stubFolderParams(null, '/nonexistent');

        $this->mockFolderService->method('createFolderBatch')
            ->willThrowException(new Exception('Folder not found', 404));

        $response = $this->controller->folderBatch();

        $this->assertEquals(404, $response->getStatus());

    }//end testFolderBatchNotFound()


    /**
     * Test batchEntities allows extracting status with partial results.
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
        $this->mockEntityService->method('consolidateEntities')->willReturn(
                [
                    ['type' => 'PERSON', 'value' => 'Jan', 'highestConfidence' => 0.9, 'fileCount' => 1],
                ]
                );

        $response = $this->controller->batchEntities('b1');
        $data     = $response->getData();

        $this->assertEquals(200, $response->getStatus());
        $this->assertFalse($data['complete']);
        $this->assertEquals(1, $data['filesProcessed']);
        $this->assertEquals(1, $data['entityCount']);

    }//end testBatchEntitiesDuringExtracting()


    /**
     * Test batchEntities returns complete=true for review status.
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
     * Test batchEntities returns 409 for uploading status.
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
