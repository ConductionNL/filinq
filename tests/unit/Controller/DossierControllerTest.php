<?php

/**
 * Unit tests for DossierController
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
 * @spec openspec/changes/anonymisation-grondslagen-summary-rendering/tasks.md#task-10
 */

namespace OCA\DocuDesk\Tests\Unit\Controller;

use OCA\DocuDesk\Controller\DossierController;
use OCA\DocuDesk\Service\GrondslagenSummaryService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DossierController
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress                               PropertyNotSetInConstructor
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DossierControllerTest extends TestCase
{

    /**
     * @var DossierController
     */
    private DossierController $controller;

    /**
     * @var GrondslagenSummaryService|MockObject
     */
    private GrondslagenSummaryService|MockObject $mockSummaryService;

    /**
     * @var IRequest|MockObject
     */
    private IRequest|MockObject $mockRequest;

    /**
     * @var IUserSession|MockObject
     */
    private IUserSession|MockObject $mockUserSession;

    /**
     * @var IL10N|MockObject
     */
    private IL10N|MockObject $mockL10n;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockSummaryService = $this->createMock(GrondslagenSummaryService::class);
        $this->mockRequest        = $this->createMock(IRequest::class);
        $this->mockUserSession    = $this->createMock(IUserSession::class);
        $this->mockLogger         = $this->createMock(LoggerInterface::class);

        $this->mockL10n = $this->createMock(IL10N::class);
        $this->mockL10n->method('t')
            ->willReturnCallback(
                    static function (string $text, array $params=[]) {
                        return vsprintf($text, $params);
                    }
                    );

        $this->controller = new DossierController(
            appName: 'docudesk',
            request: $this->mockRequest,
            logger: $this->mockLogger,
            grondslagenSummaryService: $this->mockSummaryService,
            l10n: $this->mockL10n,
            userSession: $this->mockUserSession
        );

    }//end setUp()

    /**
     * Test generateGrondslagenPdf returns 401 when not authenticated
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfReturns401WhenNotAuthenticated(): void
    {
        $this->mockUserSession->method('getUser')->willReturn(null);

        $response = $this->controller->generateGrondslagenPdf(dossierId: 'uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());

    }//end testGenerateGrondslagenPdfReturns401WhenNotAuthenticated()

    /**
     * Test generateGrondslagenPdf returns 404 when dossier not found
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfReturns404WhenDossierNotFound(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->mockSummaryService->method('renderDossierSummary')
            ->willReturn(null);

        $response = $this->controller->generateGrondslagenPdf(dossierId: 'uuid-missing');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());

    }//end testGenerateGrondslagenPdfReturns404WhenDossierNotFound()

    /**
     * Test generateGrondslagenPdf returns 400 for empty dossier ID
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfReturns400ForEmptyDossierId(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $response = $this->controller->generateGrondslagenPdf(dossierId: '   ');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());

    }//end testGenerateGrondslagenPdfReturns400ForEmptyDossierId()

    /**
     * Test generateGrondslagenPdf returns 200 with dossier data on success
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfReturns200OnSuccess(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $mockDossier = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getObject'])
            ->getMock();
        $mockDossier->method('getObject')->willReturn(
                [
                    'id'            => 'uuid-dossier',
                    'name'          => 'Test Dossier',
                    'configuration' => [
                        'grondslagen' => [
                            'fileId'          => 42,
                            'lastGeneratedAt' => '2026-06-01T00:00:00Z',
                        ],
                    ],
                ]
                );

        $this->mockSummaryService->method('renderDossierSummary')
            ->willReturn($mockDossier);

        $response = $this->controller->generateGrondslagenPdf(dossierId: 'uuid-dossier');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());

        $data = $response->getData();
        $this->assertArrayHasKey('configuration', $data);
        $this->assertSame(42, $data['configuration']['grondslagen']['fileId']);

    }//end testGenerateGrondslagenPdfReturns200OnSuccess()

    /**
     * Test generateGrondslagenPdf returns 500 when service throws
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfReturns500WhenServiceThrows(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $this->mockSummaryService->method('renderDossierSummary')
            ->willThrowException(new \RuntimeException('mPDF failed'));

        $response = $this->controller->generateGrondslagenPdf(dossierId: 'uuid-1');

        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertSame(500, $response->getStatus());

    }//end testGenerateGrondslagenPdfReturns500WhenServiceThrows()

    /**
     * Test generateGrondslagenPdf configuration grondslagen fields are updated
     *
     * @return void
     */
    public function testGenerateGrondslagenPdfConfigurationFieldsUpdated(): void
    {
        $mockUser = $this->createMock(IUser::class);
        $this->mockUserSession->method('getUser')->willReturn($mockUser);

        $mockDossier = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getObject'])
            ->getMock();
        $mockDossier->method('getObject')->willReturn(
                [
                    'id'            => 'uuid-2',
                    'configuration' => [
                        'grondslagen' => [
                            'fileId'            => 55,
                            'lastGeneratedAt'   => '2026-06-01T10:00:00Z',
                            'autoRegenOnReview' => true,
                        ],
                    ],
                ]
                );

        $this->mockSummaryService->method('renderDossierSummary')
            ->willReturn($mockDossier);

        $response = $this->controller->generateGrondslagenPdf(dossierId: 'uuid-2');
        $data     = $response->getData();

        $this->assertArrayHasKey('configuration', $data);
        $this->assertArrayHasKey('grondslagen', $data['configuration']);
        $this->assertArrayHasKey('fileId', $data['configuration']['grondslagen']);
        $this->assertArrayHasKey('lastGeneratedAt', $data['configuration']['grondslagen']);

    }//end testGenerateGrondslagenPdfConfigurationFieldsUpdated()
}//end class
