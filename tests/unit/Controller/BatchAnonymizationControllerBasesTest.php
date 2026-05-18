<?php

/**
 * Unit tests for BatchAnonymizationController bases-passthrough validation
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
 * Tests that batchAnonymize validates per-entity bases[] per the spec
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BatchAnonymizationControllerBasesTest extends TestCase
{

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var BatchAnonymizeService|MockObject
     */
    private BatchAnonymizeService|MockObject $mockAnonService;

    /**
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    /**
     * @var BatchAnonymizationController
     */
    private BatchAnonymizationController $controller;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(IRequest::class);
        $this->mockAnonService = $this->createMock(BatchAnonymizeService::class);
        $this->mockL10n        = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')->willReturnCallback(fn($s) => $s);

        $this->controller = new BatchAnonymizationController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->createMock(LoggerInterface::class),
            stateService: $this->createMock(BatchStateService::class),
            uploadService: $this->createMock(BatchUploadService::class),
            extractService: $this->createMock(BatchExtractionService::class),
            anonService: $this->mockAnonService,
            reportService: $this->createMock(BatchReportService::class),
            entityService: $this->createMock(EntityConsolidationService::class),
            profileService: $this->createMock(WooProfileService::class),
            folderBatchService: $this->createMock(FolderBatchService::class),
            l10n: $this->mockL10n
        );

    }//end setUp()


    /**
     * Test batchAnonymize returns 400 when bases is not an array
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testBatchAnonymizeReturns400WhenBasesIsNotArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => 'not-an-array'],
                ],
            ]
        );

        $response = $this->controller->batchAnonymize(batchId: 'batch-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testBatchAnonymizeReturns400WhenBasesIsNotArray()


    /**
     * Test batchAnonymize returns 400 when bases contains a non-string
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testBatchAnonymizeReturns400WhenBasesContainsNonString(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => [true, 'uuid-b']],
                ],
            ]
        );

        $response = $this->controller->batchAnonymize(batchId: 'batch-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(400, $response->getStatus());

    }//end testBatchAnonymizeReturns400WhenBasesContainsNonString()


    /**
     * Test batchAnonymize succeeds when bases is a valid array of strings
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testBatchAnonymizeSucceedsWhenBasesIsValidStringArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => ['uuid-a']],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeBatch')
            ->willReturn(
                [
                    'batchId'        => 'batch-1',
                    'batchStatus'    => 'completed',
                    'processedFiles' => 1,
                    'skippedFiles'   => [],
                    'totalFiles'     => 1,
                ]
            );

        $response = $this->controller->batchAnonymize(batchId: 'batch-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testBatchAnonymizeSucceedsWhenBasesIsValidStringArray()


    /**
     * Test batchAnonymize succeeds when entities have no bases field (backward-compat)
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testBatchAnonymizeSucceedsWhenBasesAbsent(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Amsterdam', 'type' => 'LOCATION'],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeBatch')
            ->willReturn(
                [
                    'batchId'        => 'batch-2',
                    'batchStatus'    => 'completed',
                    'processedFiles' => 1,
                    'skippedFiles'   => [],
                    'totalFiles'     => 1,
                ]
            );

        $response = $this->controller->batchAnonymize(batchId: 'batch-2');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testBatchAnonymizeSucceedsWhenBasesAbsent()


    /**
     * Test batchAnonymize succeeds when bases is an empty array
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-bases-passthrough/tasks.md#task-4
     */
    public function testBatchAnonymizeSucceedsWhenBasesIsEmptyArray(): void
    {
        $this->mockRequest->method('getParams')->willReturn(
            [
                'entities' => [
                    ['text' => 'Jan Janssen', 'type' => 'PERSON', 'bases' => []],
                ],
            ]
        );

        $this->mockAnonService->method('anonymizeBatch')
            ->willReturn(
                [
                    'batchId'        => 'batch-3',
                    'batchStatus'    => 'completed',
                    'processedFiles' => 1,
                    'skippedFiles'   => [],
                    'totalFiles'     => 1,
                ]
            );

        $response = $this->controller->batchAnonymize(batchId: 'batch-3');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(200, $response->getStatus());

    }//end testBatchAnonymizeSucceedsWhenBasesIsEmptyArray()


}//end class
