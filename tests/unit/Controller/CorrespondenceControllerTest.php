<?php

/**
 * Unit tests for CorrespondenceController
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
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\CorrespondenceController;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CorrespondenceController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class CorrespondenceControllerTest extends TestCase
{

    /**
     * Controller under test
     *
     * @var CorrespondenceController
     */
    private CorrespondenceController $controller;

    /**
     * Mock request
     *
     * @var IRequest&MockObject
     */
    private IRequest $mockRequest;

    /**
     * Mock correspondence service
     *
     * @var CorrespondenceService&MockObject
     */
    private CorrespondenceService $mockCorrSvc;

    /**
     * Mock user session
     *
     * @var IUserSession&MockObject
     */
    private IUserSession $mockUserSession;

    /**
     * Mock logger
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $mockLogger;

    /**
     * Mock l10n
     *
     * @var IL10N&MockObject
     */
    private IL10N $mockL10n;

    /**
     * Mock user
     *
     * @var IUser&MockObject
     */
    private IUser $mockUser;

    /**
     * Set up test fixtures
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRequest     = $this->createMock(originalClassName: IRequest::class);
        $this->mockCorrSvc     = $this->createMock(originalClassName: CorrespondenceService::class);
        $this->mockUserSession = $this->createMock(originalClassName: IUserSession::class);
        $this->mockLogger      = $this->createMock(originalClassName: LoggerInterface::class);
        $this->mockL10n        = $this->createMock(originalClassName: IL10N::class);
        $this->mockUser        = $this->createMock(originalClassName: IUser::class);

        $this->mockUser->method('getUID')->willReturn('user1');
        $this->mockUserSession->method('getUser')->willReturn($this->mockUser);
        $this->mockL10n->method('t')->willReturnArgument(0);

        $this->controller = new CorrespondenceController(
            appName: 'docudesk',
            request: $this->mockRequest,
            corrSvc: $this->mockCorrSvc,
            userSession: $this->mockUserSession,
            logger: $this->mockLogger,
            l10n: $this->mockL10n
        );

    }//end setUp()

    /**
     * Test generate returns a PDF download when format is pdf
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateReturnsPdfDownload(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['dataRefs', [], [['register' => 'brp', 'schema' => 'persoon', 'id' => 'p-uuid']]],
                    ['options', [], ['format' => 'pdf']],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $this->mockCorrSvc->method('generate')
            ->willReturn(
                [
                    'content'       => '%PDF-1.4 binary',
                    'format'        => 'pdf',
                    'warnings'      => [],
                    'registerEntry' => ['id' => 'log-1'],
                ]
            );

        $response = $this->controller->generate();

        $this->assertInstanceOf(DataDownloadResponse::class, $response);

    }//end testGenerateReturnsPdfDownload()

    /**
     * Test generate returns JSON when format is html
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateReturnsJsonForHtmlFormat(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['dataRefs', [], [['register' => 'brp', 'schema' => 'persoon', 'id' => 'p-uuid']]],
                    ['options', [], ['format' => 'html']],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $this->mockCorrSvc->method('generate')
            ->willReturn(
                [
                    'content'       => '<p>Hello</p>',
                    'format'        => 'html',
                    'warnings'      => [],
                    'registerEntry' => [],
                ]
            );

        $response = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_OK, $response->getStatus());

    }//end testGenerateReturnsJsonForHtmlFormat()

    /**
     * Test generate returns 400 when templateId is missing
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateMissingTemplateIdReturnsBadRequest(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, ''],
                    ['dataRefs', [], []],
                    ['options', [], []],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $response = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());

    }//end testGenerateMissingTemplateIdReturnsBadRequest()

    /**
     * Test generate returns 400 when dataRefs is missing
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateMissingDataRefsReturnsBadRequest(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['dataRefs', [], []],
                    ['options', [], []],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $response = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());

    }//end testGenerateMissingDataRefsReturnsBadRequest()

    /**
     * Test generate returns 500 on service exception
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateServiceExceptionReturnsError(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['dataRefs', [], [['register' => 'brp', 'schema' => 'persoon', 'id' => 'uuid']]],
                    ['options', [], []],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $this->mockCorrSvc->method('generate')
            ->willThrowException(new Exception('Render failed'));

        $response = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());

    }//end testGenerateServiceExceptionReturnsError()

    /**
     * Test generateBatch returns 202 Accepted for large batch
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateBatchReturns202ForAsyncJob(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['recipientIds', [], array_fill(0, 15, 'r-uuid')],
                    ['options', [], []],
                ]
            );

        $this->mockCorrSvc->method('generateBatch')
            ->willReturn(
                [
                    'jobId'           => 'job-uuid',
                    'status'          => 'queued',
                    'totalRecipients' => 15,
                ]
            );

        $response = $this->controller->generateBatch();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_ACCEPTED, $response->getStatus());

    }//end testGenerateBatchReturns202ForAsyncJob()

    /**
     * Test generateBatch returns 200 for synchronous small batch
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateBatchReturns200ForSyncBatch(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['recipientIds', [], ['r1', 'r2']],
                    ['options', [], []],
                ]
            );

        $this->mockCorrSvc->method('generateBatch')
            ->willReturn(
                [
                    'results'   => [],
                    'total'     => 2,
                    'completed' => 2,
                    'errors'    => 0,
                ]
            );

        $response = $this->controller->generateBatch();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_OK, $response->getStatus());

    }//end testGenerateBatchReturns200ForSyncBatch()

    /**
     * Test generateBatch returns 400 when recipientIds is missing
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateBatchMissingRecipientsReturnsBadRequest(): void
    {
        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['recipientIds', [], []],
                    ['options', [], []],
                ]
            );

        $response = $this->controller->generateBatch();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());

    }//end testGenerateBatchMissingRecipientsReturnsBadRequest()

    /**
     * Test jobStatus returns 200 with status data for valid job
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testJobStatusReturnsStatusData(): void
    {
        $this->mockCorrSvc->method('getJobStatus')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'total'       => 5,
                    'completed'   => 5,
                    'errors'      => 0,
                    'ownerUserId' => 'user1',
                ]
            );

        $response = $this->controller->jobStatus(jobId: 'job-uuid');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_OK, $response->getStatus());

        $data = $response->getData();
        $this->assertEquals('job-uuid', $data['jobId']);
        $this->assertEquals('completed', $data['status']);

    }//end testJobStatusReturnsStatusData()

    /**
     * Test jobStatus returns 404 when job is not found
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testJobStatusReturns404WhenNotFound(): void
    {
        $this->mockCorrSvc->method('getJobStatus')
            ->willReturn(null);

        $response = $this->controller->jobStatus(jobId: 'nonexistent-job');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());

    }//end testJobStatusReturns404WhenNotFound()

    /**
     * Test jobStatus returns 403 when job belongs to different user
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testJobStatusReturnsForbiddenForOtherUsersJob(): void
    {
        $this->mockCorrSvc->method('getJobStatus')
            ->willReturn(
                [
                    'status'      => 'completed',
                    'total'       => 3,
                    'completed'   => 3,
                    'errors'      => 0,
                    'ownerUserId' => 'other-user',
                ]
            );

        $response = $this->controller->jobStatus(jobId: 'job-uuid');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());

    }//end testJobStatusReturnsForbiddenForOtherUsersJob()

    /**
     * Test unauthenticated generate returns 401
     *
     * @return void
     *
     * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-4
     */
    public function testGenerateUnauthenticatedReturnsUnauthorized(): void
    {
        $unauthSession = $this->createMock(originalClassName: IUserSession::class);
        $unauthSession->method('getUser')->willReturn(null);

        $controller = new CorrespondenceController(
            appName: 'docudesk',
            request: $this->mockRequest,
            corrSvc: $this->mockCorrSvc,
            userSession: $unauthSession,
            logger: $this->mockLogger,
            l10n: $this->mockL10n
        );

        $this->mockRequest->method('getParam')
            ->willReturnMap(
                [
                    ['templateId', null, 'tmpl-uuid'],
                    ['dataRefs', [], [['register' => 'brp', 'schema' => 'p', 'id' => 'uuid']]],
                    ['options', [], []],
                    ['filename', 'correspondence.pdf', 'brief.pdf'],
                ]
            );

        $response = $controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());

    }//end testGenerateUnauthenticatedReturnsUnauthorized()
}//end class
