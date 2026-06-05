<?php

/**
 * Unit tests for PrintJobService
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\PrintJobService;
use OCA\DocuDesk\Service\TemplateService;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PrintJobService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PrintJobServiceTest extends TestCase
{

    /**
     * The service under test.
     *
     * @var PrintJobService
     */
    private PrintJobService $service;

    /**
     * Mock PDF service.
     *
     * @var PdfService&MockObject
     */
    private PdfService $pdfService;

    /**
     * Mock template service.
     *
     * @var TemplateService&MockObject
     */
    private TemplateService $templateSvc;

    /**
     * Mock job list.
     *
     * @var IJobList&MockObject
     */
    private IJobList $jobList;

    /**
     * Mock app config.
     *
     * @var IAppConfig&MockObject
     */
    private IAppConfig $appConfig;

    /**
     * Mock container.
     *
     * @var ContainerInterface&MockObject
     */
    private ContainerInterface $container;

    /**
     * Mock logger.
     *
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdfService  = $this->createMock(originalClassName: PdfService::class);
        $this->templateSvc = $this->createMock(originalClassName: TemplateService::class);
        $this->jobList     = $this->createMock(originalClassName: IJobList::class);
        $this->appConfig   = $this->createMock(originalClassName: IAppConfig::class);
        $this->logger      = $this->createMock(originalClassName: LoggerInterface::class);

        $this->container = $this->createMock(originalClassName: ContainerInterface::class);
        $this->container->method('get')
            ->willReturnCallback(
                function ($class) {
                    if ($class === IAppConfig::class) {
                        return $this->appConfig;
                    }

                    return null;
                }
            );

        $this->service = new PrintJobService(
            pdfService: $this->pdfService,
            templateSvc: $this->templateSvc,
            container: $this->container,
            jobList: $this->jobList,
            logger: $this->logger
        );

    }//end setUp()


    /**
     * Test generateJobId returns a non-empty string in UUID format
     *
     * @return void
     */
    public function testGenerateJobIdReturnsUuid(): void
    {
        $jobId = $this->service->generateJobId();

        $this->assertNotEmpty($jobId);
        $this->assertMatchesRegularExpression(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            string: $jobId
        );

    }//end testGenerateJobIdReturnsUuid()


    /**
     * Test generateJobId returns different IDs on each call
     *
     * @return void
     */
    public function testGenerateJobIdIsUnique(): void
    {
        $id1 = $this->service->generateJobId();
        $id2 = $this->service->generateJobId();

        $this->assertNotEquals($id1, $id2);

    }//end testGenerateJobIdIsUnique()


    /**
     * Test buildManifest returns correct structure for items
     *
     * @return void
     */
    public function testBuildManifestReturnsCorrectStructure(): void
    {
        $items = [
            ['filename' => 'doc1.pdf', 'status' => 'success'],
            ['filename' => 'doc2.pdf', 'status' => 'error', 'error' => 'Failed'],
        ];

        $printConfig = ['duplex' => true, 'color' => false, 'paperTray' => 'tray1', 'stapling' => false];

        $manifest = $this->service->buildManifest(items: $items, printConfig: $printConfig);

        $this->assertCount(2, $manifest);
        $this->assertEquals('doc1.pdf', $manifest[0]['filename']);
        $this->assertEquals('success', $manifest[0]['status']);
        $this->assertEquals($printConfig, $manifest[0]['printConfig']);
        $this->assertEquals('error', $manifest[1]['status']);
        $this->assertEquals('Failed', $manifest[1]['error']);

    }//end testBuildManifestReturnsCorrectStructure()


    /**
     * Test buildManifest uses fallback filename when not provided
     *
     * @return void
     */
    public function testBuildManifestUsesIndexAsFilenameWhenMissing(): void
    {
        $items    = [['status' => 'success']];
        $manifest = $this->service->buildManifest(items: $items);

        $this->assertEquals('document-0.pdf', $manifest[0]['filename']);

    }//end testBuildManifestUsesIndexAsFilenameWhenMissing()


    /**
     * Test buildPrintConfig extracts correct values from options
     *
     * @return void
     */
    public function testBuildPrintConfigExtractsValues(): void
    {
        $options = [
            'duplex'    => true,
            'color'     => false,
            'paperTray' => 'tray-2',
            'stapling'  => true,
        ];

        $config = $this->service->buildPrintConfig(options: $options);

        $this->assertTrue($config['duplex']);
        $this->assertFalse($config['color']);
        $this->assertEquals('tray-2', $config['paperTray']);
        $this->assertTrue($config['stapling']);

    }//end testBuildPrintConfigExtractsValues()


    /**
     * Test buildPrintConfig uses defaults when options are empty
     *
     * @return void
     */
    public function testBuildPrintConfigUsesDefaults(): void
    {
        $config = $this->service->buildPrintConfig(options: []);

        $this->assertFalse($config['duplex']);
        $this->assertTrue($config['color']);
        $this->assertEquals('default', $config['paperTray']);
        $this->assertFalse($config['stapling']);

    }//end testBuildPrintConfigUsesDefaults()


    /**
     * Test storeJobStatus persists data via IAppConfig
     *
     * @return void
     */
    public function testStoreJobStatusPersistsData(): void
    {
        $jobId = 'test-job-123';
        $data  = ['status' => 'queued', 'total' => 5];

        $this->appConfig->expects($this->once())
            ->method('setValueString')
            ->with(
                'docudesk',
                'print_job_'.$jobId,
                json_encode($data)
            );

        $this->service->storeJobStatus(jobId: $jobId, data: $data);

    }//end testStoreJobStatusPersistsData()


    /**
     * Test getJob returns null when not found
     *
     * @return void
     */
    public function testGetJobReturnsNullWhenNotFound(): void
    {
        $this->appConfig->method('getValueString')
            ->willReturn('');

        $result = $this->service->getJob(jobId: 'nonexistent');

        $this->assertNull($result);

    }//end testGetJobReturnsNullWhenNotFound()


    /**
     * Test getJob returns decoded data when found
     *
     * @return void
     */
    public function testGetJobReturnsDecodedData(): void
    {
        $data = ['status' => 'completed', 'total' => 3];

        $this->appConfig->method('getValueString')
            ->willReturn(json_encode($data));

        $result = $this->service->getJob(jobId: 'existing-job');

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(3, $result['total']);

    }//end testGetJobReturnsDecodedData()


    /**
     * Test createBatchJob dispatches background job for large batches
     *
     * @return void
     */
    public function testCreateBatchJobDispatchesJobForLargeBatch(): void
    {
        $items = array_fill(0, 15, ['data' => [], 'filename' => 'doc.pdf']);

        $this->templateSvc->method('getTemplate')
            ->willReturn(
                [
                    'content'     => '<h1>Test</h1>',
                    'name'        => 'Test Template',
                    'format'      => 'A4',
                    'orientation' => 'P',
                ]
            );

        $this->jobList->expects($this->once())
            ->method('add');

        $this->appConfig->method('setValueString')->willReturn(true);

        $result = $this->service->createBatchJob(
            templateId: 'template-uuid',
            items: $items,
            options: [],
            userId: 'user1'
        );

        $this->assertEquals('queued', $result['status']);
        $this->assertEquals(15, $result['total']);
        $this->assertArrayHasKey('jobId', $result);

    }//end testCreateBatchJobDispatchesJobForLargeBatch()
}//end class
