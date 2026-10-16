<?php

/**
 * Unit tests for ReportRenderService
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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\Pdfa3ConversionService;
use OCA\Filinq\Service\ReportRenderService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ReportRenderService::render() and ::renderBatch(), added
 * for openspec/changes/filinq-configurable-report-templates.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ReportRenderServiceTest extends TestCase {

	/**
	 * Mocked template service.
	 *
	 * @var TemplateSlugResolver&MockObject
	 */
	private TemplateSlugResolver $slugResolver;

	/**
	 * Mocked render pipeline.
	 *
	 * @var DocumentRenderPipeline&MockObject
	 */
	private DocumentRenderPipeline $renderPipeline;

	/**
	 * Mocked PDF/A-3 conversion service.
	 *
	 * @var Pdfa3ConversionService&MockObject
	 */
	private Pdfa3ConversionService $pdfa3Service;

	/**
	 * Mocked storage service.
	 *
	 * @var DocumentStorageService&MockObject
	 */
	private DocumentStorageService $storageService;

	/**
	 * Mocked app config.
	 *
	 * @var IAppConfig&MockObject
	 */
	private IAppConfig $appConfig;

	/**
	 * The service under test.
	 *
	 * @var ReportRenderService
	 */
	private ReportRenderService $service;

	/**
	 * Fixture template used by most tests.
	 *
	 * @var array<string, mixed>
	 */
	private const TEMPLATE = [
		'id' => 'tpl-1',
		'name' => 'Report Card',
		'content' => '<h1>{{ leerling.naam }}</h1>',
		'format' => 'A4',
		'orientation' => 'P',
	];

	/**
	 * Set up mocks and the service under test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->slugResolver = $this->createMock(TemplateSlugResolver::class);
		$this->renderPipeline = $this->createMock(DocumentRenderPipeline::class);
		$this->pdfa3Service = $this->createMock(Pdfa3ConversionService::class);
		$this->storageService = $this->createMock(DocumentStorageService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->renderPipeline->method('loadHuisstijl')->willReturn(null);
		$this->renderPipeline->method('renderWithHuisstijl')
			->willReturn(['html' => '<h1>Rendered</h1>', 'warnings' => []]);
		$this->renderPipeline->method('buildPdfOptions')->willReturn(['format' => 'A4', 'orientation' => 'P']);
		$this->renderPipeline->method('produceOutput')->willReturn('%PDF-screen-bytes%');

		$this->service = new ReportRenderService(
			$this->slugResolver,
			$this->renderPipeline,
			$this->pdfa3Service,
			$this->storageService,
			$this->appConfig,
			$this->createMock(LoggerInterface::class)
		);

	}//end setUp()

	/**
	 * A successful render resolves the template by slug, renders at screen
	 * quality by default, stores it, and returns a documentRef.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-successful-render-returns-a-document-reference
	 */
	public function testRenderReturnsDocumentRef(): void {
		$this->slugResolver->expects($this->once())
			->method('resolve')
			->with('learniq', 'report-card', 'school-a')
			->willReturn(self::TEMPLATE);

		$this->storageService->expects($this->once())
			->method('store')
			->with(
				$this->equalTo('admin'),
				$this->anything(),
				$this->anything(),
				$this->equalTo('%PDF-screen-bytes%')
			)
			->willReturn(['fileId' => 42, 'path' => '/x', 'name' => 'x.pdf', 'size' => 10]);

		$this->pdfa3Service->expects($this->never())->method('convertHtml');

		$result = $this->service->render(
			'report-card',
			'school-a',
			['leerling' => ['naam' => 'Test']],
			['userId' => 'admin']
		);

		$this->assertEquals('42', $result['documentRef']);
		$this->assertEquals('report-card', $result['templateSlug']);

	}//end testRenderReturnsDocumentRef()

	/**
	 * outputQuality "print" routes through Pdfa3ConversionService instead
	 * of the default mPDF path.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-print-quality-routes-through-pdfa-3-conversion
	 */
	public function testRenderWithPrintQualityUsesPdfa3Conversion(): void {
		$this->slugResolver->method('resolve')->willReturn(self::TEMPLATE);

		$this->pdfa3Service->expects($this->once())
			->method('convertHtml')
			->willReturn(['content' => '%PDFA3-bytes%', 'checksumSha256' => 'x', 'pages' => 1, 'conformance' => 'PDF/A-3b']);

		$this->renderPipeline->expects($this->never())->method('produceOutput');

		$this->storageService->expects($this->once())
			->method('store')
			->with(
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo('%PDFA3-bytes%')
			)
			->willReturn(['fileId' => 43, 'path' => '/x', 'name' => 'x.pdf', 'size' => 10]);

		$result = $this->service->render(
			'report-card',
			null,
			[],
			['userId' => 'admin', 'outputQuality' => 'print']
		);

		$this->assertEquals('43', $result['documentRef']);

	}//end testRenderWithPrintQualityUsesPdfa3Conversion()

	/**
	 * No userId in the request and no configured default is a 400.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-no-storage-user-available-is-rejected
	 */
	public function testRenderThrowsWhenNoStorageUserAvailable(): void {
		$this->appConfig->method('getValueString')->willReturn('');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->render('report-card', null, [], []);

	}//end testRenderThrowsWhenNoStorageUserAvailable()

	/**
	 * The app-config storage-user default is used when the request omits
	 * userId.
	 *
	 * @return void
	 */
	public function testRenderFallsBackToConfiguredStorageUser(): void {
		$this->appConfig->method('getValueString')->willReturn('report-service-account');
		$this->slugResolver->method('resolve')->willReturn(self::TEMPLATE);

		$this->storageService->expects($this->once())
			->method('store')
			->with($this->equalTo('report-service-account'), $this->anything(), $this->anything(), $this->anything())
			->willReturn(['fileId' => 1, 'path' => '/x', 'name' => 'x.pdf', 'size' => 1]);

		$this->service->render('report-card', null, [], []);

	}//end testRenderFallsBackToConfiguredStorageUser()

	/**
	 * A 404 from slug resolution propagates unchanged.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-unknown-slug-returns-404
	 */
	public function testRenderPropagates404FromSlugResolution(): void {
		$this->slugResolver->method('resolve')
			->willThrowException(new Exception('No template found for namespace "learniq" and slug "x".', 404));

		$this->expectException(Exception::class);
		$this->expectExceptionCode(404);

		$this->service->render('x', null, [], ['userId' => 'admin']);

	}//end testRenderPropagates404FromSlugResolution()

	/**
	 * An empty items array is rejected before any template resolution.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-empty-items-array-is-rejected
	 */
	public function testRenderBatchRejectsEmptyItems(): void {
		$this->slugResolver->expects($this->never())->method('resolve');

		$this->expectException(Exception::class);
		$this->expectExceptionCode(400);

		$this->service->renderBatch('report-card', null, [], ['userId' => 'admin']);

	}//end testRenderBatchRejectsEmptyItems()

	/**
	 * All-success batch: every item renders, one ZIP is stored, count
	 * matches, no failures.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-batch-render-produces-one-zip
	 */
	public function testRenderBatchAllSuccess(): void {
		$this->slugResolver->method('resolve')->willReturn(self::TEMPLATE);

		$this->storageService->expects($this->once())
			->method('store')
			->willReturn(['fileId' => 99, 'path' => '/x', 'name' => 'x.zip', 'size' => 100]);

		$result = $this->service->renderBatch(
			'report-card',
			'school-a',
			[['data' => ['a' => 1]], ['data' => ['a' => 2]]],
			['userId' => 'admin']
		);

		$this->assertEquals('99', $result['documentRef']);
		$this->assertEquals(2, $result['count']);
		$this->assertEmpty($result['failures']);

	}//end testRenderBatchAllSuccess()

	/**
	 * One item's render failure does not abort the batch: the remaining
	 * items are still rendered and the failure is reported alongside the
	 * ZIP, not thrown.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#scenario-one-items-render-failure-does-not-abort-the-batch
	 */
	public function testRenderBatchContinuesAfterOneItemFails(): void {
		$this->slugResolver->method('resolve')->willReturn(self::TEMPLATE);

		// First call succeeds, second throws, third succeeds.
		$this->renderPipeline->method('renderWithHuisstijl')
			->willReturnCallback(function (string $templateContent, array $data, ?array $huisstijl) {
				if (($data['fail'] ?? false) === true) {
					throw new Exception('bad data for this item');
				}

				return ['html' => '<h1>OK</h1>', 'warnings' => []];
			});

		$this->storageService->expects($this->once())
			->method('store')
			->willReturn(['fileId' => 7, 'path' => '/x', 'name' => 'x.zip', 'size' => 20]);

		$result = $this->service->renderBatch(
			'report-card',
			null,
			[
				['data' => ['a' => 1]],
				['data' => ['fail' => true]],
				['data' => ['a' => 3]],
			],
			['userId' => 'admin']
		);

		$this->assertEquals(2, $result['count']);
		$this->assertCount(1, $result['failures']);
		$this->assertEquals(1, $result['failures'][0]['index']);

	}//end testRenderBatchContinuesAfterOneItemFails()
}//end class
