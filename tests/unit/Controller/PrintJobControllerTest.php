<?php

/**
 * Unit tests for PrintJobController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\PrintJobController;
use OCA\DocuDesk\Service\PrintJobService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PrintJobController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PrintJobControllerTest extends TestCase
{

    /**
     * The controller under test.
     *
     * @var PrintJobController
     */
    private PrintJobController $controller;

    /**
     * Mock request.
     *
     * @var IRequest&MockObject
     */
    private IRequest $mockRequest;

    /**
     * Mock print job service.
     *
     * @var PrintJobService&MockObject
     */
    private PrintJobService $mockPrintJobSvc;

    /**
     * Mock user session.
     *
     * @var IUserSession&MockObject
     */
    private IUserSession $mockUserSession;

    /**
     * Mock group manager.
     *
     * @var IGroupManager&MockObject
     */
    private IGroupManager $mockGroupManager;

    /**
     * Mock logger.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $mockLogger;

    /**
     * Mock user.
     *
     * @var IUser&MockObject
     */
    private IUser $mockUser;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest      = $this->createMock(originalClassName: IRequest::class);
        $this->mockPrintJobSvc  = $this->createMock(originalClassName: PrintJobService::class);
        $this->mockUserSession  = $this->createMock(originalClassName: IUserSession::class);
        $this->mockGroupManager = $this->createMock(originalClassName: IGroupManager::class);
        $this->mockLogger       = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockUser         = $this->createMock(originalClassName: IUser::class);

        $this->mockUser->method('getUID')->willReturn('test-user');
        $this->mockUserSession->method('getUser')->willReturn($this->mockUser);

        $this->controller = new PrintJobController(
            appName: 'docudesk',
            request: $this->mockRequest,
            printJobSvc: $this->mockPrintJobSvc,
            userSession: $this->mockUserSession,
            groupManager: $this->mockGroupManager,
            logger: $this->mockLogger
        );

    }//end setUp()


    /**
     * Test create returns 400 when templateId is missing
     *
     * @return void
     */
    public function testCreateReturns400WhenTemplateIdMissing(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', '', ''],
                    ['data', [], []],
                    ['options', [], []],
                    ['filename', 'document.pdf', 'document.pdf'],
                ]
            );

        $result = $this->controller->create();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());

    }//end testCreateReturns400WhenTemplateIdMissing()


    /**
     * Test create returns 201 on success
     *
     * @return void
     */
    public function testCreateReturns201OnSuccess(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', '', 'template-uuid'],
                    ['data', [], []],
                    ['options', [], []],
                    ['filename', 'document.pdf', 'output.pdf'],
                ]
            );

        $this->mockPrintJobSvc->method('createJob')
            ->willReturn(
                [
                    'jobId'       => 'job-123',
                    'status'      => 'completed',
                    'printConfig' => ['duplex' => false, 'color' => true],
                ]
            );

        $result = $this->controller->create();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_CREATED, $result->getStatus());
        $this->assertEquals('job-123', $result->getData()['jobId']);

    }//end testCreateReturns201OnSuccess()


    /**
     * Test show returns 404 when job not found
     *
     * @return void
     */
    public function testShowReturns404WhenJobNotFound(): void
    {
        $this->mockPrintJobSvc->method('getJob')->willReturn(null);

        $result = $this->controller->show(id: 'nonexistent');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_NOT_FOUND, $result->getStatus());

    }//end testShowReturns404WhenJobNotFound()


    /**
     * Test show returns 403 when user is not owner and not admin
     *
     * @return void
     */
    public function testShowReturns403WhenNotAuthorized(): void
    {
        $this->mockPrintJobSvc->method('getJob')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'ownerUserId' => 'other-user',
                ]
            );

        $this->mockGroupManager->method('isAdmin')->willReturn(false);

        $result = $this->controller->show(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_FORBIDDEN, $result->getStatus());

    }//end testShowReturns403WhenNotAuthorized()


    /**
     * Test show returns job data when user is owner
     *
     * @return void
     */
    public function testShowReturnsDataWhenUserIsOwner(): void
    {
        $jobData = [
            'status'      => 'completed',
            'ownerUserId' => 'test-user',
            'printConfig' => [],
        ];

        $this->mockPrintJobSvc->method('getJob')->willReturn($jobData);

        $result = $this->controller->show(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testShowReturnsDataWhenUserIsOwner()


    /**
     * Test show returns data when user is admin
     *
     * @return void
     */
    public function testShowReturnsDataWhenUserIsAdmin(): void
    {
        $this->mockPrintJobSvc->method('getJob')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'ownerUserId' => 'other-user',
                ]
            );

        $this->mockGroupManager->method('isAdmin')->willReturn(true);

        $result = $this->controller->show(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testShowReturnsDataWhenUserIsAdmin()


    /**
     * Test download returns 409 when job not completed
     *
     * @return void
     */
    public function testDownloadReturns409WhenJobNotCompleted(): void
    {
        $this->mockPrintJobSvc->method('getJob')
            ->willReturn(
                [
                    'status'      => 'processing',
                    'ownerUserId' => 'test-user',
                ]
            );

        $result = $this->controller->download(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_CONFLICT, $result->getStatus());

    }//end testDownloadReturns409WhenJobNotCompleted()


    /**
     * Test download returns PDF binary on success
     *
     * @return void
     */
    public function testDownloadReturnsPdfOnSuccess(): void
    {
        $this->mockPrintJobSvc->method('getJob')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'ownerUserId' => 'test-user',
                    'filename'    => 'output.pdf',
                ]
            );

        $this->mockPrintJobSvc->method('loadJobPdf')
            ->willReturn('%PDF-1.4 fake content');

        $result = $this->controller->download(id: 'job-123');

        $this->assertInstanceOf(DataDownloadResponse::class, $result);

    }//end testDownloadReturnsPdfOnSuccess()


    /**
     * Test updateStatus returns 400 when status is invalid
     *
     * @return void
     */
    public function testUpdateStatusReturns400WhenStatusInvalid(): void
    {
        $this->mockPrintJobSvc->method('getJob')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'ownerUserId' => 'test-user',
                ]
            );

        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['status', '', 'invalid-status'],
                    ['details', null, null],
                ]
            );

        $result = $this->controller->updateStatus(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());

    }//end testUpdateStatusReturns400WhenStatusInvalid()


    /**
     * Test updateStatus returns updated job on valid status
     *
     * @return void
     */
    public function testUpdateStatusReturnsUpdatedJobOnValidStatus(): void
    {
        $jobData = [
            'status'      => 'completed',
            'ownerUserId' => 'test-user',
        ];

        $this->mockPrintJobSvc->method('getJob')->willReturn($jobData);

        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['status', '', 'printing'],
                    ['details', null, null],
                ]
            );

        $this->mockPrintJobSvc->expects($this->once())->method('storeJobStatus');

        $result = $this->controller->updateStatus(id: 'job-123');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        $this->assertEquals('printing', $result->getData()['externalStatus']);

    }//end testUpdateStatusReturnsUpdatedJobOnValidStatus()


    /**
     * Test batch returns 400 when items is empty
     *
     * @return void
     */
    public function testBatchReturns400WhenItemsEmpty(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', '', 'template-uuid'],
                    ['items', [], []],
                    ['options', [], []],
                ]
            );

        $result = $this->controller->batch();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());

    }//end testBatchReturns400WhenItemsEmpty()
}//end class
