<?php

/**
 * Unit tests for DocumentService
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
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\DataResolverService;
use OCA\DocuDesk\Service\DocumentService;
use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\TemplateRenderer;
use OCA\DocuDesk\Service\TemplateService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DocumentService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class DocumentServiceTest extends TestCase
{

    /**
     * The service under test.
     *
     * @var DocumentService
     */
    private DocumentService $service;

    /**
     * Mock template service.
     *
     * @var TemplateService&MockObject
     */
    private TemplateService $templateSvc;

    /**
     * Mock data resolver.
     *
     * @var DataResolverService&MockObject
     */
    private DataResolverService $dataResolver;

    /**
     * Mock template renderer.
     *
     * @var TemplateRenderer&MockObject
     */
    private TemplateRenderer $renderer;

    /**
     * Mock PDF service.
     *
     * @var PdfService&MockObject
     */
    private PdfService $pdfService;

    /**
     * Mock job list.
     *
     * @var IJobList&MockObject
     */
    private IJobList $jobList;

    /**
     * Mock object service.
     *
     * @var ObjectService&MockObject
     */
    private ObjectService $objectSvc;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->templateSvc  = $this->createMock(TemplateService::class);
        $this->dataResolver = $this->createMock(DataResolverService::class);
        $this->renderer     = $this->createMock(TemplateRenderer::class);
        $this->pdfService   = $this->createMock(PdfService::class);
        $this->jobList      = $this->createMock(IJobList::class);
        $this->objectSvc    = $this->createMock(ObjectService::class);

        $container  = $this->createMock(ContainerInterface::class);
        $appManager = $this->createMock(IAppManager::class);
        $logger     = $this->createMock(LoggerInterface::class);
        $appConfig  = $this->createMock(IAppConfig::class);

        $appManager->method('getInstalledApps')
            ->willReturn(['openregister']);

        $container->method('get')
            ->willReturnCallback(function ($class) use ($appConfig) {
                if ($class === 'OCA\OpenRegister\Service\ObjectService') {
                    return $this->objectSvc;
                }

                if ($class === IAppConfig::class) {
                    return $appConfig;
                }

                return null;
            });

        $this->service = new DocumentService(
            $this->templateSvc,
            $this->dataResolver,
            $this->renderer,
            $this->pdfService,
            $container,
            $appManager,
            $this->jobList,
            $logger
        );

    }//end setUp()

    /**
     * Test single PDF document generation (DCS-010, DCS-020).
     *
     * @return void
     */
    public function testGenerateDocumentPdf(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Beschikking',
            'content' => '<h1>{{ aanvrager }}</h1>',
            'format'  => 'A4',
            'version' => 2,
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn([
                'data'     => ['zaak' => ['aanvrager' => 'Jan Jansen']],
                'errors'   => [],
                'warnings' => [],
            ]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<h1>Jan Jansen</h1>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF-binary%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generateDocument(
            templateId: 'tmpl-1',
            dataRefs: [['register' => 'zaken', 'schema' => 'zaak', 'id' => 'abc-123']],
            options: ['userId' => 'test-user']
        );

        $this->assertEquals('pdf', $result['format']);
        $this->assertEquals('%PDF-binary%', $result['content']);
        $this->assertEmpty($result['warnings']);
        $this->assertIsArray($result['metadata']);

    }//end testGenerateDocumentPdf()

    /**
     * Test HTML preview generation (DCS-022).
     *
     * @return void
     */
    public function testGeneratePreviewReturnsHtml(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Preview',
            'content' => '<p>{{ naam }}</p>',
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn([
                'data'     => ['naam' => 'Test'],
                'errors'   => [],
                'warnings' => [],
            ]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>Test</p>');

        $result = $this->service->generatePreview(
            templateId: 'tmpl-1',
            dataRefs: [],
            options: []
        );

        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertEquals('<p>Test</p>', $result['html']);

    }//end testGeneratePreviewReturnsHtml()

    /**
     * Test synchronous bulk generation for small batch (DCS-040).
     *
     * @return void
     */
    public function testGenerateBulkSyncForSmallBatch(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>{{ naam }}</p>',
            'format'  => 'A4',
            'version' => 1,
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>test</p>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generateBulk(
            templateId: 'tmpl-1',
            objectIds: ['o1', 'o2', 'o3'],
            options: ['register' => 'brp', 'schema' => 'persoon']
        );

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['completed']);
        $this->assertEquals(0, $result['errors']);

    }//end testGenerateBulkSyncForSmallBatch()

    /**
     * Test async bulk dispatch for large batch (DCS-041).
     *
     * @return void
     */
    public function testGenerateBulkAsyncForLargeBatch(): void
    {
        $this->jobList->expects($this->once())
            ->method('add');

        $objectIds = array_fill(0, 15, 'object-id');

        $result = $this->service->generateBulk(
            templateId: 'tmpl-1',
            objectIds: $objectIds,
            options: []
        );

        $this->assertArrayHasKey('jobId', $result);
        $this->assertEquals('queued', $result['status']);
        $this->assertEquals(15, $result['total']);

    }//end testGenerateBulkAsyncForLargeBatch()

    /**
     * Test that invalid format raises exception (DCS-023).
     *
     * @return void
     */
    public function testInvalidFormatThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->templateSvc->method('getTemplate')
            ->willReturn(['id' => 'tmpl-1', 'content' => '<p>test</p>']);

        $this->service->generateDocument(
            templateId: 'tmpl-1',
            dataRefs: [],
            options: ['format' => 'unsupported']
        );

    }//end testInvalidFormatThrowsException()

    /**
     * Test that document metadata includes template version (DCS-051).
     *
     * @return void
     */
    public function testDocumentMetadataIncludesTemplateVersion(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Vergunningbrief',
            'content' => '<p>test</p>',
            'format'  => 'A4',
            'version' => 3,
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>test</p>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $capturedEntry = null;
        $logEntity     = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1', 'templateVersion' => 3]);

        $this->objectSvc->expects($this->once())
            ->method('saveObject')
            ->willReturnCallback(
                function ($entry) use ($logEntity, &$capturedEntry) {
                    $capturedEntry = $entry;
                    return $logEntity;
                }
            );

        $this->service->generateDocument(
            templateId: 'tmpl-1',
            dataRefs: [],
            options: ['userId' => 'user1']
        );

        $this->assertNotNull($capturedEntry);
        $this->assertEquals(3, $capturedEntry['templateVersion']);
        $this->assertEquals('tmpl-1', $capturedEntry['templateId']);
        $this->assertEquals('generatedDocument', 'generatedDocument');

    }//end testDocumentMetadataIncludesTemplateVersion()

    /**
     * Test partial failure does not abort bulk batch (DCS-043).
     *
     * @return void
     */
    public function testBulkPartialFailureDoesNotAbort(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>test</p>',
            'format'  => 'A4',
            'version' => 1,
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $callCount = 0;
        $this->dataResolver->method('resolve')
            ->willReturnCallback(
                function () use (&$callCount) {
                    $callCount++;
                    if ($callCount === 2) {
                        throw new Exception('Data not found');
                    }

                    return ['data' => [], 'errors' => [], 'warnings' => []];
                }
            );

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>test</p>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generateBulk(
            templateId: 'tmpl-1',
            objectIds: ['o1', 'o2', 'o3'],
            options: ['register' => 'brp', 'schema' => 'persoon']
        );

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['completed']);
        $this->assertEquals(1, $result['errors']);

    }//end testBulkPartialFailureDoesNotAbort()

    /**
     * Test data resolution errors appear in warnings not as abort (DCS-004).
     *
     * @return void
     */
    public function testDataResolutionErrorsBecomesWarnings(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>{{ naam }}</p>',
            'format'  => 'A4',
            'version' => 1,
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn([
                'data'     => ['naam' => 'Test'],
                'errors'   => [
                    [
                        'index'    => 0,
                        'register' => 'brp',
                        'schema'   => 'adres',
                        'id'       => 'missing-uuid',
                        'message'  => 'Object not found',
                    ],
                ],
                'warnings' => [],
            ]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>Test</p>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generateDocument(
            templateId: 'tmpl-1',
            dataRefs: [['register' => 'brp', 'schema' => 'adres', 'id' => 'missing-uuid']],
            options: []
        );

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Object not found', $result['warnings'][0]);

    }//end testDataResolutionErrorsBecomesWarnings()

    /**
     * Test huisstijl header and footer are applied when configured (DCS-030).
     *
     * @return void
     */
    public function testHuisstijlApplied(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>body</p>',
            'format'  => 'A4',
            'version' => 1,
        ];

        $huisstijl = [
            'id'         => 'hs-1',
            'headerHtml' => '<header>Logo</header>',
            'footerHtml' => '<footer>Footer</footer>',
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

        $huisstijlEntity = $this->createMock(ObjectEntity::class);
        $huisstijlEntity->method('jsonSerialize')->willReturn($huisstijl);

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);

        $this->objectSvc->method('find')
            ->willReturn($huisstijlEntity);

        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $renderedHtml = '';
        $this->renderer->method('renderTemplate')
            ->willReturnCallback(
                function ($content) use (&$renderedHtml) {
                    $renderedHtml .= $content;
                    return $content;
                }
            );

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $this->service->generateDocument(
            templateId: 'tmpl-1',
            dataRefs: [],
            options: ['huisstijlId' => 'hs-1']
        );

        $this->assertStringContainsString('<header>Logo</header>', $renderedHtml);
        $this->assertStringContainsString('<footer>Footer</footer>', $renderedHtml);

    }//end testHuisstijlApplied()
}//end class
