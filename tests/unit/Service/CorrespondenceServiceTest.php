<?php

/**
 * Unit tests for CorrespondenceService
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

use Exception;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCA\DocuDesk\Service\DataResolverService;
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
 * Unit tests for CorrespondenceService
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
class CorrespondenceServiceTest extends TestCase
{

    /**
     * The service under test
     *
     * @var CorrespondenceService
     */
    private CorrespondenceService $service;

    /**
     * Mock template service
     *
     * @var TemplateService&MockObject
     */
    private TemplateService $templateSvc;

    /**
     * Mock data resolver
     *
     * @var DataResolverService&MockObject
     */
    private DataResolverService $dataResolver;

    /**
     * Mock template renderer
     *
     * @var TemplateRenderer&MockObject
     */
    private TemplateRenderer $renderer;

    /**
     * Mock PDF service
     *
     * @var PdfService&MockObject
     */
    private PdfService $pdfService;

    /**
     * Mock job list
     *
     * @var IJobList&MockObject
     */
    private IJobList $jobList;

    /**
     * Mock object service
     *
     * @var ObjectService&MockObject
     */
    private ObjectService $objectSvc;


    /**
     * Set up test fixtures
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

        $this->service = new CorrespondenceService(
            $this->templateSvc,
            $this->dataResolver,
            $this->renderer,
            $this->pdfService,
            $container,
            $appManager,
            $this->jobList,
            $logger,
            $appConfig
        );

    }//end setUp()


    /**
     * Test single PDF generation
     *
     * @return void
     */
    public function testGeneratePdf(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Test Brief',
            'content' => '<h1>{{ naam }}</h1>',
            'format'  => 'A4',
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn([
                'data'     => ['persoon' => ['naam' => 'Jan']],
                'errors'   => [],
                'warnings' => [],
            ]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<h1>Jan</h1>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF-binary-content%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generate(
            templateId: 'tmpl-1',
            dataRefs: [['register' => 'brp', 'schema' => 'persoon', 'id' => 'abc']],
            options: []
        );

        $this->assertEquals('pdf', $result['format']);
        $this->assertEquals('%PDF-binary-content%', $result['content']);
        $this->assertEmpty($result['warnings']);

    }//end testGeneratePdf()


    /**
     * Test HTML output format
     *
     * @return void
     */
    public function testGenerateHtml(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Test',
            'content' => '<p>Hello</p>',
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>Hello</p>');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generate(
            templateId: 'tmpl-1',
            dataRefs: [['register' => 'brp', 'schema' => 'x', 'id' => 'y']],
            options: ['format' => 'html']
        );

        $this->assertEquals('html', $result['format']);
        $this->assertEquals('<p>Hello</p>', $result['content']);

    }//end testGenerateHtml()


    /**
     * Test invalid format throws exception
     *
     * @return void
     */
    public function testInvalidFormatThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(400);

        $this->service->generate(
            templateId: 'tmpl-1',
            dataRefs: [],
            options: ['format' => 'invalid']
        );

    }//end testInvalidFormatThrowsException()


    /**
     * Test synchronous batch for small recipient list
     *
     * @return void
     */
    public function testBatchSyncForSmallList(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>{{ naam }}</p>',
        ];

        $this->templateSvc->method('getTemplate')
            ->willReturn($template);

        $this->dataResolver->method('resolve')
            ->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

        $this->renderer->method('renderTemplate')
            ->willReturn('<p>Test</p>');

        $this->pdfService->method('renderPdf')
            ->willReturn('%PDF%');

        $logEntity = $this->createMock(ObjectEntity::class);
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log']);
        $this->objectSvc->method('saveObject')
            ->willReturn($logEntity);

        $result = $this->service->generateBatch(
            templateId: 'tmpl-1',
            recipientIds: ['r1', 'r2', 'r3'],
            options: ['register' => 'brp', 'schema' => 'persoon']
        );

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['completed']);
        $this->assertEquals(0, $result['errors']);

    }//end testBatchSyncForSmallList()


    /**
     * Test async batch for large recipient list
     *
     * @return void
     */
    public function testBatchAsyncForLargeList(): void
    {
        $this->jobList->expects($this->once())
            ->method('add');

        $recipientIds = array_fill(0, 15, 'recipient-id');

        $result = $this->service->generateBatch(
            templateId: 'tmpl-1',
            recipientIds: $recipientIds,
            options: []
        );

        $this->assertArrayHasKey('jobId', $result);
        $this->assertEquals('queued', $result['status']);
        $this->assertEquals(15, $result['totalRecipients']);

    }//end testBatchAsyncForLargeList()


    /**
     * Test register logging on generation
     *
     * @return void
     */
    public function testCorrespondenceLogging(): void
    {
        $template = [
            'id'      => 'tmpl-1',
            'name'    => 'Brief',
            'content' => '<p>test</p>',
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
        $logEntity->method('jsonSerialize')->willReturn(['id' => 'log-entry']);
        $this->objectSvc->expects($this->once())
            ->method('saveObject')
            ->with(
                $this->callback(function ($entry) {
                    return $entry['templateId'] === 'tmpl-1'
                        && $entry['status'] === 'generated'
                        && $entry['format'] === 'pdf';
                }),
                $this->equalTo('document'),
                $this->equalTo('correspondence')
            )
            ->willReturn($logEntity);

        $result = $this->service->generate(
            templateId: 'tmpl-1',
            dataRefs: [['register' => 'brp', 'schema' => 'p', 'id' => 'abc']],
            options: []
        );

        $this->assertArrayHasKey('registerEntry', $result);

    }//end testCorrespondenceLogging()


}//end class
