<?php

/**
 * Unit tests for DocumentController
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
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-2
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use Exception;
use OCA\DocuDesk\Controller\DocumentController;
use OCA\DocuDesk\Service\DocumentService;
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
 * Unit tests for DocumentController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class DocumentControllerTest extends TestCase
{

    /**
     * The controller under test.
     *
     * @var DocumentController
     */
    private DocumentController $controller;

    /**
     * Mock request.
     *
     * @var IRequest&MockObject
     */
    private IRequest $request;

    /**
     * Mock document service.
     *
     * @var DocumentService&MockObject
     */
    private DocumentService $documentSvc;

    /**
     * Mock user session.
     *
     * @var IUserSession&MockObject
     */
    private IUserSession $userSession;

    /**
     * Mock logger.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * Mock localization.
     *
     * @var IL10N&MockObject
     */
    private IL10N $l10n;

    /**
     * Mock user.
     *
     * @var IUser&MockObject
     */
    private IUser $user;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->request     = $this->createMock(IRequest::class);
        $this->documentSvc = $this->createMock(DocumentService::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->logger      = $this->createMock(LoggerInterface::class);
        $this->l10n        = $this->createMock(IL10N::class);
        $this->user        = $this->createMock(IUser::class);

        $this->l10n->method('t')
            ->willReturnCallback(static function (string $text): string {
                return $text;
            });

        $this->user->method('getUID')
            ->willReturn('test-user');

        $this->userSession->method('getUser')
            ->willReturn($this->user);

        $this->controller = new DocumentController(
            'docudesk',
            $this->request,
            $this->documentSvc,
            $this->userSession,
            $this->logger,
            $this->l10n
        );

    }//end setUp()

    /**
     * Test generate returns 400 when templateId is missing.
     *
     * @return void
     */
    public function testGenerateReturns400WhenTemplateIdMissing(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, ''],
                ['dataRefs', [], []],
                ['options', [], []],
                ['filename', 'document', 'document'],
            ]);

        $result = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testGenerateReturns400WhenTemplateIdMissing()

    /**
     * Test generate returns PDF download on success.
     *
     * @return void
     */
    public function testGenerateReturnsPdfDownload(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['dataRefs', [], [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'abc']]],
                ['options', [], []],
                ['filename', 'document', 'beschikking'],
            ]);

        $this->documentSvc->method('generateDocument')
            ->willReturn([
                'content'  => '%PDF-binary%',
                'format'   => 'pdf',
                'metadata' => ['id' => 'doc-1'],
                'warnings' => [],
            ]);

        $result = $this->controller->generate();

        $this->assertInstanceOf(DataDownloadResponse::class, $result);

    }//end testGenerateReturnsPdfDownload()

    /**
     * Test generate returns ODF download for odf format.
     *
     * @return void
     */
    public function testGenerateReturnsOdfDownload(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['dataRefs', [], []],
                ['options', [], ['format' => 'odf']],
                ['filename', 'document', 'besluit'],
            ]);

        $this->documentSvc->method('generateDocument')
            ->willReturn([
                'content'  => 'ODF-binary-content',
                'format'   => 'odf',
                'metadata' => ['id' => 'doc-2'],
                'warnings' => [],
            ]);

        $result = $this->controller->generate();

        $this->assertInstanceOf(DataDownloadResponse::class, $result);

    }//end testGenerateReturnsOdfDownload()

    /**
     * Test generate returns JSON for HTML format.
     *
     * @return void
     */
    public function testGenerateReturnsJsonForHtmlFormat(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['dataRefs', [], []],
                ['options', [], ['format' => 'html']],
                ['filename', 'document', 'document'],
            ]);

        $this->documentSvc->method('generateDocument')
            ->willReturn([
                'content'  => '<html><body>test</body></html>',
                'format'   => 'html',
                'metadata' => ['id' => 'doc-3'],
                'warnings' => [],
            ]);

        $result = $this->controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());

    }//end testGenerateReturnsJsonForHtmlFormat()

    /**
     * Test preview returns HTML with warnings.
     *
     * @return void
     */
    public function testPreviewReturnsHtmlAndWarnings(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['dataRefs', [], []],
                ['options', [], []],
            ]);

        $this->documentSvc->method('generatePreview')
            ->willReturn([
                'html'     => '<p>Preview content</p>',
                'warnings' => ['Missing field: naam'],
            ]);

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        $data = $result->getData();
        $this->assertArrayHasKey('html', $data);
        $this->assertArrayHasKey('warnings', $data);

    }//end testPreviewReturnsHtmlAndWarnings()

    /**
     * Test preview returns 400 when templateId missing.
     *
     * @return void
     */
    public function testPreviewReturns400WhenTemplateIdMissing(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, ''],
                ['dataRefs', [], []],
                ['options', [], []],
            ]);

        $result = $this->controller->preview();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testPreviewReturns400WhenTemplateIdMissing()

    /**
     * Test generateBulk returns 202 for large batch (async).
     *
     * @return void
     */
    public function testGenerateBulkReturns202ForLargeBatch(): void
    {
        $objectIds = array_fill(0, 15, 'obj-id');

        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['objectIds', [], $objectIds],
                ['options', [], ['register' => 'brp', 'schema' => 'persoon']],
            ]);

        $this->documentSvc->method('generateBulk')
            ->willReturn([
                'jobId'  => 'job-uuid-123',
                'status' => 'queued',
                'total'  => 15,
            ]);

        $result = $this->controller->generateBulk();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(202, $result->getStatus());
        $data = $result->getData();
        $this->assertArrayHasKey('jobId', $data);

    }//end testGenerateBulkReturns202ForLargeBatch()

    /**
     * Test generateBulk returns 400 when objectIds missing.
     *
     * @return void
     */
    public function testGenerateBulkReturns400WhenObjectIdsMissing(): void
    {
        $this->request->method('getParam')
            ->willReturnMap([
                ['templateId', null, 'tmpl-1'],
                ['objectIds', [], []],
                ['options', [], []],
            ]);

        $result = $this->controller->generateBulk();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(400, $result->getStatus());

    }//end testGenerateBulkReturns400WhenObjectIdsMissing()

    /**
     * Test jobStatus returns 404 when job not found.
     *
     * @return void
     */
    public function testJobStatusReturns404WhenNotFound(): void
    {
        $this->documentSvc->method('getJobStatus')
            ->willReturn(null);

        $result = $this->controller->jobStatus(jobId: 'nonexistent-job');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(404, $result->getStatus());

    }//end testJobStatusReturns404WhenNotFound()

    /**
     * Test jobStatus returns 403 for another user's job.
     *
     * @return void
     */
    public function testJobStatusReturns403ForAnotherUsersJob(): void
    {
        $this->documentSvc->method('getJobStatus')
            ->willReturn([
                'jobId'   => 'job-1',
                'status'  => 'completed',
                'options' => ['userId' => 'other-user'],
            ]);

        $result = $this->controller->jobStatus(jobId: 'job-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(403, $result->getStatus());

    }//end testJobStatusReturns403ForAnotherUsersJob()

    /**
     * Test jobStatus returns 200 with status for own job.
     *
     * @return void
     */
    public function testJobStatusReturns200ForOwnJob(): void
    {
        $this->documentSvc->method('getJobStatus')
            ->willReturn([
                'jobId'     => 'job-1',
                'status'    => 'completed',
                'total'     => 5,
                'completed' => 5,
                'errors'    => 0,
                'options'   => ['userId' => 'test-user'],
            ]);

        $result = $this->controller->jobStatus(jobId: 'job-1');

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(200, $result->getStatus());
        $data = $result->getData();
        $this->assertEquals('completed', $data['status']);

    }//end testJobStatusReturns200ForOwnJob()

    /**
     * Test generate returns 401 when not authenticated.
     *
     * @return void
     */
    public function testGenerateReturns401WhenNotAuthenticated(): void
    {
        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn(null);

        $controller = new DocumentController(
            'docudesk',
            $this->request,
            $this->documentSvc,
            $this->userSession,
            $this->logger,
            $this->l10n
        );

        $result = $controller->generate();

        $this->assertInstanceOf(JSONResponse::class, $result);
        $this->assertEquals(401, $result->getStatus());

    }//end testGenerateReturns401WhenNotAuthenticated()
}//end class
