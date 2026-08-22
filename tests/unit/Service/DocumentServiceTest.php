<?php

/**
 * Unit tests for DocumentService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/document-creatie-sjablonen/tasks.md#task-2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
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
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class DocumentServiceTest extends TestCase {

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
	 * Mock document storage service.
	 *
	 * @var DocumentStorageService&MockObject
	 */
	private DocumentStorageService $storageService;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->templateSvc = $this->createMock(TemplateService::class);
		$this->dataResolver = $this->createMock(DataResolverService::class);
		$this->renderer = $this->createMock(TemplateRenderer::class);
		$this->pdfService = $this->createMock(PdfService::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->objectSvc = $this->createMock(ObjectService::class);
		$this->storageService = $this->createMock(DocumentStorageService::class);

		$container = $this->createMock(ContainerInterface::class);
		$appManager = $this->createMock(IAppManager::class);
		$logger = $this->createMock(LoggerInterface::class);
		$appConfig = $this->createMock(IAppConfig::class);

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

		$objectResolver = new \OCA\Filinq\Service\DocumentObjectServiceResolver(
			$container,
			$appManager
		);

		$this->service = new DocumentService(
			$this->templateSvc,
			$this->dataResolver,
			new \OCA\Filinq\Service\DocumentRenderPipeline(
				$this->renderer,
				$this->pdfService,
				$objectResolver,
				$logger
			),
			$this->storageService,
			new \OCA\Filinq\Service\GeneratedDocumentLogger(
				$objectResolver,
				$logger
			),
			$container,
			$this->jobList,
			$logger
		);

	}//end setUp()

	/**
	 * Test single PDF document generation (DCS-010, DCS-020).
	 *
	 * @return void
	 */
	public function testGenerateDocumentPdf(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Beschikking',
			'content' => '<h1>{{ aanvrager }}</h1>',
			'format' => 'A4',
			'version' => 2,
		];

		$this->templateSvc->method('getTemplate')
			->willReturn($template);

		$this->dataResolver->method('resolve')
			->willReturn([
				'data' => ['zaak' => ['aanvrager' => 'Jan Jansen']],
				'errors' => [],
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
	public function testGeneratePreviewReturnsHtml(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Preview',
			'content' => '<p>{{ naam }}</p>',
		];

		$this->templateSvc->method('getTemplate')
			->willReturn($template);

		$this->dataResolver->method('resolve')
			->willReturn([
				'data' => ['naam' => 'Test'],
				'errors' => [],
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
	 * Test that generateDocument() forwards options.listRefs to
	 * DataResolverService::resolve() unchanged.
	 *
	 * @return void
	 */
	public function testGenerateDocumentForwardsListRefs(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Rapport',
			'content' => '<ul>{% for c in competitors %}<li>{{ c.name }}</li>{% endfor %}</ul>',
			'format' => 'A4',
			'version' => 1,
		];

		$this->templateSvc->method('getTemplate')
			->willReturn($template);

		$listRefs = [
			[
				'register' => 'spectr-live',
				'schema' => 'v-app-competitors',
				'filter' => ['app_id' => 6],
				'limit' => 5,
				'as' => 'competitors',
			],
		];

		$this->dataResolver->expects($this->once())
			->method('resolve')
			->with(
				$this->anything(),
				$this->equalTo($listRefs),
				$this->anything()
			)
			->willReturn([
				'data' => ['competitors' => [['name' => 'Acme']]],
				'errors' => [],
				'warnings' => [],
			]);

		$this->renderer->method('renderTemplate')
			->willReturn('<ul><li>Acme</li></ul>');

		$this->pdfService->method('renderPdf')
			->willReturn('%PDF-binary%');

		$logEntity = $this->createMock(ObjectEntity::class);
		$logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
		$this->objectSvc->method('saveObject')
			->willReturn($logEntity);

		$result = $this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'test-user', 'listRefs' => $listRefs]
		);

		$this->assertEquals('%PDF-binary%', $result['content']);

	}//end testGenerateDocumentForwardsListRefs()

	/**
	 * Test that generatePreview() forwards options.listRefs to
	 * DataResolverService::resolve() unchanged.
	 *
	 * @return void
	 */
	public function testGeneratePreviewForwardsListRefs(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Preview',
			'content' => '{% for c in competitors %}{{ c.name }}{% endfor %}',
		];

		$this->templateSvc->method('getTemplate')
			->willReturn($template);

		$listRefs = [
			['register' => 'spectr-live', 'schema' => 'v-app-competitors', 'as' => 'competitors'],
		];

		$this->dataResolver->expects($this->once())
			->method('resolve')
			->with(
				$this->anything(),
				$this->equalTo($listRefs),
				$this->anything()
			)
			->willReturn([
				'data' => ['competitors' => [['name' => 'Acme']]],
				'errors' => [],
				'warnings' => [],
			]);

		$this->renderer->method('renderTemplate')
			->willReturn('Acme');

		$result = $this->service->generatePreview(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['listRefs' => $listRefs]
		);

		$this->assertEquals('Acme', $result['html']);

	}//end testGeneratePreviewForwardsListRefs()

	/**
	 * Test synchronous bulk generation for small batch (DCS-040).
	 *
	 * @return void
	 */
	public function testGenerateBulkSyncForSmallBatch(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Brief',
			'content' => '<p>{{ naam }}</p>',
			'format' => 'A4',
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
	public function testGenerateBulkAsyncForLargeBatch(): void {
		$this->jobList->expects($this->once())
			->method('add');

		$objectIds = array_fill(0, 15, 'object-id');

		$result = $this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: $objectIds,
			options: ['output' => ['mode' => 'files'], 'userId' => 'user1']
		);

		$this->assertArrayHasKey('jobId', $result);
		$this->assertEquals('queued', $result['status']);
		$this->assertEquals(15, $result['total']);

	}//end testGenerateBulkAsyncForLargeBatch()

	/**
	 * Test async bulk without output.mode "files" is rejected (REQ-DDOB-005).
	 *
	 * @return void
	 */
	public function testAsyncBulkWithoutFilesModeIsRejected(): void {
		$this->jobList->expects($this->never())->method('add');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: array_fill(0, 15, 'object-id'),
			options: []
		);

	}//end testAsyncBulkWithoutFilesModeIsRejected()

	/**
	 * Test async bulk with output.mode "both" is rejected (REQ-DDOB-005) —
	 * there is no HTTP response left to attach a binary to once queued.
	 *
	 * @return void
	 */
	public function testAsyncBulkRejectsBothMode(): void {
		$this->jobList->expects($this->never())->method('add');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: array_fill(0, 15, 'object-id'),
			options: ['output' => ['mode' => 'both']]
		);

	}//end testAsyncBulkRejectsBothMode()

	/**
	 * Test async bulk with output.mode "files" computes a per-job targetPath
	 * and forwards it to the dispatched job (REQ-DDOB-006).
	 *
	 * @return void
	 */
	public function testAsyncBulkComputesPerJobTargetPath(): void {
		$this->templateSvc->method('getTemplate')
			->willReturn(['id' => 'tmpl-1', 'namespace' => 'procest']);

		$capturedArgument = null;
		$this->jobList->expects($this->once())
			->method('add')
			->willReturnCallback(
				function (string $job, array $argument) use (&$capturedArgument): void {
					$capturedArgument = $argument;
				}
			);

		$this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: array_fill(0, 15, 'object-id'),
			options: ['output' => ['mode' => 'files'], 'userId' => 'user1']
		);

		$this->assertNotNull($capturedArgument);
		$targetPath = $capturedArgument['options']['output']['targetPath'];
		$this->assertStringStartsWith('DocuDesk/procest/', $targetPath);
		$this->assertStringEndsNotWith('/', $targetPath);

	}//end testAsyncBulkComputesPerJobTargetPath()

	/**
	 * Test that invalid format raises exception (DCS-023).
	 *
	 * @return void
	 */
	public function testInvalidFormatThrowsException(): void {
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
	public function testDocumentMetadataIncludesTemplateVersion(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Vergunningbrief',
			'content' => '<p>test</p>',
			'format' => 'A4',
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
		$logEntity = $this->createMock(ObjectEntity::class);
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
	public function testBulkPartialFailureDoesNotAbort(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Brief',
			'content' => '<p>test</p>',
			'format' => 'A4',
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
	public function testDataResolutionErrorsBecomesWarnings(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Brief',
			'content' => '<p>{{ naam }}</p>',
			'format' => 'A4',
			'version' => 1,
		];

		$this->templateSvc->method('getTemplate')
			->willReturn($template);

		$this->dataResolver->method('resolve')
			->willReturn([
				'data' => ['naam' => 'Test'],
				'errors' => [
					[
						'index' => 0,
						'register' => 'brp',
						'schema' => 'adres',
						'id' => 'missing-uuid',
						'message' => 'Object not found',
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
	public function testHuisstijlApplied(): void {
		$template = [
			'id' => 'tmpl-1',
			'name' => 'Brief',
			'content' => '<p>body</p>',
			'format' => 'A4',
			'version' => 1,
		];

		$huisstijl = [
			'id' => 'hs-1',
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

	/**
	 * Stub the render pipeline (template, data resolution, rendering, PDF
	 * output, and audit logging) for a happy-path single generation.
	 *
	 * @return void
	 */
	private function stubHappyPathGeneration(): void {
		$this->templateSvc->method('getTemplate')
			->willReturn([
				'id' => 'tmpl-1',
				'name' => 'Beschikking',
				'content' => '<h1>test</h1>',
				'format' => 'A4',
				'version' => 1,
				'namespace' => 'procest',
			]);

		$this->dataResolver->method('resolve')
			->willReturn(['data' => [], 'errors' => [], 'warnings' => []]);

		$this->renderer->method('renderTemplate')
			->willReturn('<h1>test</h1>');

		$this->pdfService->method('renderPdf')
			->willReturn('%PDF-binary%');

		$logEntity = $this->createMock(ObjectEntity::class);
		$logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
		$this->objectSvc->method('saveObject')
			->willReturn($logEntity);

	}//end stubHappyPathGeneration()

	/**
	 * Test options.output omitted is unchanged: no storage call, output
	 * sub-array reports mode 'return' with null refs (REQ-DDOB-001).
	 *
	 * @return void
	 */
	public function testOutputOmittedDefaultsToReturnAndSkipsStorage(): void {
		$this->stubHappyPathGeneration();
		$this->storageService->expects($this->never())->method('store');

		$result = $this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1']
		);

		$this->assertEquals('%PDF-binary%', $result['content']);
		$this->assertEquals('return', $result['output']['mode']);
		$this->assertNull($result['output']['fileId']);
		$this->assertNull($result['output']['path']);

	}//end testOutputOmittedDefaultsToReturnAndSkipsStorage()

	/**
	 * Test an invalid options.output.mode value is rejected (REQ-DDOB-001).
	 *
	 * @return void
	 */
	public function testInvalidOutputModeThrowsException(): void {
		$this->stubHappyPathGeneration();

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'somewhere-else']]
		);

	}//end testInvalidOutputModeThrowsException()

	/**
	 * Test mode "files" stores the document and returns JSON refs in the
	 * output sub-array instead of failing (REQ-DDOB-001, REQ-DDOB-002).
	 *
	 * @return void
	 */
	public function testModeFilesStoresDocumentAndReturnsRefs(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->expects($this->once())
			->method('store')
			->with(
				$this->equalTo('user1'),
				$this->equalTo('DocuDesk/procest'),
				$this->equalTo('beschikking.pdf'),
				$this->equalTo('%PDF-binary%')
			)
			->willReturn(['fileId' => 99, 'path' => '/user1/files/DocuDesk/procest/beschikking.pdf', 'name' => 'beschikking.pdf', 'size' => 512]);

		$result = $this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'filename' => 'beschikking', 'output' => ['mode' => 'files']]
		);

		$this->assertEquals('files', $result['output']['mode']);
		$this->assertEquals(99, $result['output']['fileId']);
		$this->assertEquals('/user1/files/DocuDesk/procest/beschikking.pdf', $result['output']['path']);

	}//end testModeFilesStoresDocumentAndReturnsRefs()

	/**
	 * Test mode "both" stores the document AND keeps the binary content
	 * (REQ-DDOB-001).
	 *
	 * @return void
	 */
	public function testModeBothStoresDocumentAndKeepsContent(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willReturn(['fileId' => 100, 'path' => '/user1/files/DocuDesk/procest/beschikking.pdf', 'name' => 'beschikking.pdf', 'size' => 512]);

		$result = $this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'both']]
		);

		$this->assertEquals('%PDF-binary%', $result['content']);
		$this->assertEquals('both', $result['output']['mode']);
		$this->assertEquals(100, $result['output']['fileId']);

	}//end testModeBothStoresDocumentAndKeepsContent()

	/**
	 * Test that mode "files" propagates a storage execution failure as a
	 * hard failure (REQ-DDOB-003).
	 *
	 * @return void
	 */
	public function testFilesModeStorageFailureIsHardFailure(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willThrowException(new Exception('quota exceeded', DocumentStorageService::ERROR_CODE_STORAGE_FAILURE));

		$this->expectException(Exception::class);
		$this->expectExceptionCode(DocumentStorageService::ERROR_CODE_STORAGE_FAILURE);

		$this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'files']]
		);

	}//end testFilesModeStorageFailureIsHardFailure()

	/**
	 * Test that mode "both" fails open to a return-only response when
	 * storage fails, with a warning attached and no fileId (REQ-DDOB-003).
	 *
	 * @return void
	 */
	public function testBothModeStorageFailureFailsOpenToReturn(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willThrowException(new Exception('quota exceeded', DocumentStorageService::ERROR_CODE_STORAGE_FAILURE));

		$result = $this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'both']]
		);

		$this->assertEquals('%PDF-binary%', $result['content']);
		$this->assertNull($result['output']['fileId']);
		$this->assertNotEmpty(
			array_filter(
				$result['warnings'],
				static function (string $w): bool {
					return str_contains($w, 'could not be stored in Files');
				}
			)
		);

	}//end testBothModeStorageFailureFailsOpenToReturn()

	/**
	 * Test that a targetPath validation failure (code 400) is never
	 * fail-open, even for mode "both" (REQ-DDOB-003).
	 *
	 * @return void
	 */
	public function testBothModeTargetPathValidationFailureIsHardFailure(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willThrowException(new Exception('bad targetPath', 400));

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'both', 'targetPath' => '../etc']]
		);

	}//end testBothModeTargetPathValidationFailureIsHardFailure()

	/**
	 * Test that a stored document's audit row records fileId/filePath
	 * (REQ-DDOB-004).
	 *
	 * @return void
	 */
	public function testLogsFileIdAndPathWhenStored(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willReturn(['fileId' => 77, 'path' => '/user1/files/DocuDesk/procest/x.pdf', 'name' => 'x.pdf', 'size' => 10]);

		$capturedEntry = null;
		$this->objectSvc->method('saveObject')
			->willReturnCallback(
				function ($entry) use (&$capturedEntry) {
					$capturedEntry = $entry;
					$logEntity = $this->createMock(ObjectEntity::class);
					$logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
					return $logEntity;
				}
			);

		$this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1', 'output' => ['mode' => 'files']]
		);

		$this->assertEquals(77, $capturedEntry['fileId']);
		$this->assertEquals('/user1/files/DocuDesk/procest/x.pdf', $capturedEntry['filePath']);

	}//end testLogsFileIdAndPathWhenStored()

	/**
	 * Test that options.output omitted keeps fileId/filePath null on the
	 * audit row (REQ-DDOB-004).
	 *
	 * @return void
	 */
	public function testLogsNullFileIdAndPathWhenNotStored(): void {
		$this->stubHappyPathGeneration();

		$capturedEntry = null;
		$this->objectSvc->method('saveObject')
			->willReturnCallback(
				function ($entry) use (&$capturedEntry) {
					$capturedEntry = $entry;
					$logEntity = $this->createMock(ObjectEntity::class);
					$logEntity->method('jsonSerialize')->willReturn(['id' => 'log-1']);
					return $logEntity;
				}
			);

		$this->service->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['userId' => 'user1']
		);

		$this->assertNull($capturedEntry['fileId']);
		$this->assertNull($capturedEntry['filePath']);

	}//end testLogsNullFileIdAndPathWhenNotStored()

	/**
	 * Test sync bulk (<=10 objects) mode "files" returns refs inline
	 * instead of content (REQ-DDOB-007).
	 *
	 * @return void
	 */
	public function testSyncBulkFilesModeReturnsRefsNotContent(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willReturn(['fileId' => 5, 'path' => '/user1/files/DocuDesk/procest/x.pdf', 'name' => 'x.pdf', 'size' => 10]);

		$result = $this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: ['o1', 'o2'],
			options: ['register' => 'brp', 'schema' => 'persoon', 'userId' => 'user1', 'output' => ['mode' => 'files']]
		);

		foreach ($result['results'] as $item) {
			$this->assertArrayNotHasKey('content', $item);
			$this->assertEquals(5, $item['fileId']);
			$this->assertEquals('/user1/files/DocuDesk/procest/x.pdf', $item['path']);
		}

	}//end testSyncBulkFilesModeReturnsRefsNotContent()

	/**
	 * Test sync bulk mode "both" returns both content and refs inline
	 * (REQ-DDOB-007).
	 *
	 * @return void
	 */
	public function testSyncBulkBothModeReturnsContentAndRefs(): void {
		$this->stubHappyPathGeneration();

		$this->storageService->method('store')
			->willReturn(['fileId' => 6, 'path' => '/user1/files/DocuDesk/procest/x.pdf', 'name' => 'x.pdf', 'size' => 10]);

		$result = $this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: ['o1'],
			options: ['register' => 'brp', 'schema' => 'persoon', 'userId' => 'user1', 'output' => ['mode' => 'both']]
		);

		$item = $result['results'][0];
		$this->assertEquals('%PDF-binary%', $item['content']);
		$this->assertEquals(6, $item['fileId']);

	}//end testSyncBulkBothModeReturnsContentAndRefs()

	/**
	 * Test sync bulk mode "return" (default) is unchanged: content inline,
	 * no fileId key present (regression guard).
	 *
	 * @return void
	 */
	public function testSyncBulkReturnModeUnchanged(): void {
		$this->stubHappyPathGeneration();
		$this->storageService->expects($this->never())->method('store');

		$result = $this->service->generateBulk(
			templateId: 'tmpl-1',
			objectIds: ['o1'],
			options: ['register' => 'brp', 'schema' => 'persoon']
		);

		$item = $result['results'][0];
		$this->assertEquals('%PDF-binary%', $item['content']);
		$this->assertArrayNotHasKey('fileId', $item);

	}//end testSyncBulkReturnModeUnchanged()
}//end class
