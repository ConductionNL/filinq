<?php

/**
 * Unit tests for generateDocument() with options.engine
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/tasks.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentJobStore;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\DocumentOutput;
use OCA\Filinq\Service\Support\DocumentProduction;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Vendor\RenderEngine;
use OCA\Filinq\Service\Vendor\VendorRenderService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * generateDocument() renders with filinq unless options.engine names a vendor, and never falls back.
 */
class DocumentServiceVendorEngineTest extends TestCase {

	/**
	 * The entries written to the generatedDocument log.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * Filinq's own template renderer.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer&MockObject $renderer;

	/**
	 * A vendor render double that reads the engine as the real one does.
	 *
	 * @return VendorRenderService&MockObject The double.
	 */
	private function vendorMock(): VendorRenderService&MockObject {
		$vendor = $this->createMock(originalClassName: VendorRenderService::class);
		$vendor->method('vendorSlug')->willReturnCallback(
			static fn (mixed $engine): ?string => (new RenderEngine())->vendorSlug(engine: $engine)
		);

		return $vendor;

	}//end vendorMock()

	/**
	 * The service.
	 *
	 * @param VendorRenderService|null $vendor The vendor render, or none.
	 *
	 * @return DocumentService The service.
	 */
	private function service(?VendorRenderService $vendor): DocumentService {
		$log = $this->createMock(originalClassName: ObjectService::class);
		$log->method('saveObject')->willReturnCallback(function (array $object): array {
			$this->logged[] = $object;
			return $object;
		});
		$logResolver = $this->createMock(originalClassName: DocumentObjectServiceResolver::class);
		$logResolver->method('resolve')->willReturn($log);

		$templates = $this->createMock(originalClassName: TemplateService::class);
		$templates->method('getPublishedTemplate')->willReturn(
			['name' => 'Besluit', 'content' => '<p>{{ besluit }}</p>', 'version' => 2, 'vendorTemplateId' => 'besluit-standaard']
		);
		$resolver = $this->createMock(originalClassName: DataResolverService::class);
		$resolver->method('resolve')->willReturn(['data' => ['besluit' => 'toegewezen'], 'errors' => [], 'warnings' => ['one ref was empty']]);
		$this->renderer = $this->createMock(originalClassName: TemplateRenderer::class);
		$this->renderer->method('renderTemplate')->willReturn('<p>toegewezen</p>');
		$logger     = $this->createMock(originalClassName: LoggerInterface::class);
		$rasterizer = $this->createMock(originalClassName: SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(
			static fn (string $html, string $format): array => ['html' => $html, 'warnings' => []]
		);

		return new DocumentService(
			sources: new TemplateSources(templates: $templates, data: $resolver),
			production: new DocumentProduction(
				pipeline: new DocumentRenderPipeline(
					rendering: new PdfRendering(renderer: $this->renderer, pdf: $this->createMock(originalClassName: PdfService::class)),
					objectResolver: $logResolver,
					logger: $logger,
					svgRasterizer: $rasterizer
				),
				jobs: $this->createMock(originalClassName: DocumentJobStore::class),
				vendor: $vendor
			),
			output: new DocumentOutput(
				storage: $this->createMock(originalClassName: DocumentStorageService::class),
				log: new GeneratedDocumentLogger(objectResolver: $logResolver, logger: $logger)
			),
		);

	}//end service()

	/**
	 * No engine, and engine `filinq`, render exactly as before; the vendor is never asked.
	 *
	 * @return void
	 */
	public function testWithoutAnEngineFilinqRenders(): void {
		$vendor = $this->vendorMock();
		$vendor->expects($this->never())->method('render');
		$service = $this->service(vendor: $vendor);
		$this->renderer->expects($this->exactly(count: 2))->method('renderTemplate');

		foreach ([['format' => 'html', 'userId' => 'alice'], ['format' => 'html', 'userId' => 'alice', 'engine' => 'filinq']] as $options) {
			$result = $service->generateDocument(templateId: 'tpl-1', dataRefs: [], options: $options);
			$this->assertSame(expected: 'html', actual: $result['format']);
			$this->assertStringContainsString(needle: 'toegewezen', haystack: $result['content']);
		}

		$this->assertSame(expected: ['generated', 'generated'], actual: array_column($this->logged, 'status'));

	}//end testWithoutAnEngineFilinqRenders()

	/**
	 * An unknown engine is refused with 400 before anything renders or is logged.
	 *
	 * @return void
	 */
	public function testAnUnknownEngineIsRefusedAndNothingRenders(): void {
		$service = $this->service(vendor: $this->vendorMock());
		$this->renderer->expects($this->never())->method('renderTemplate');

		try {
			$service->generateDocument(templateId: 'tpl-1', dataRefs: [], options: ['format' => 'pdf', 'engine' => 'docmosis']);
			$this->fail(message: 'The unknown engine was accepted.');
		} catch (Exception $e) {
			$this->assertSame(expected: 400, actual: $e->getCode());
			$this->assertStringContainsString(needle: '"docmosis"', haystack: $e->getMessage());
		}

		$this->assertSame(expected: [], actual: $this->logged);

	}//end testAnUnknownEngineIsRefusedAndNothingRenders()

	/**
	 * A vendor engine hands the template and the resolved data to the vendor render; filinq renders nothing.
	 *
	 * @return void
	 */
	public function testAVendorEngineHandsOffAndFilinqRendersNothing(): void {
		$vendor = $this->vendorMock();
		$vendor->expects($this->once())->method('render')->willReturnCallback(
			function (string $slug, array $run, array $data): array {
				$this->assertSame(expected: 'smartdocuments', actual: $slug);
				$this->assertSame(expected: 'besluit-standaard', actual: $run['template']['vendorTemplateId']);
				$this->assertSame(expected: 'tpl-1', actual: $run['templateId']);
				$this->assertSame(expected: 'pdf', actual: $run['format']);
				$this->assertSame(expected: ['besluit' => 'toegewezen'], actual: $data);
				return ['status' => 'pending', 'vendorJobId' => 'job-1', 'warnings' => ['vendor note']];
			}
		);
		$service = $this->service(vendor: $vendor);
		$this->renderer->expects($this->never())->method('renderTemplate');

		$result = $service->generateDocument(
			templateId: 'tpl-1',
			dataRefs: [],
			options: ['format' => 'pdf', 'userId' => 'alice', 'engine' => 'vendor:smartdocuments']
		);

		$this->assertSame(expected: 'pending', actual: $result['status']);
		$this->assertSame(expected: ['one ref was empty', 'vendor note'], actual: $result['warnings']);
		$this->assertSame(expected: [], actual: $this->logged);

	}//end testAVendorEngineHandsOffAndFilinqRendersNothing()

	/**
	 * Without a vendor render on this instance every engine but filinq's own is refused with 400, never rendered by filinq.
	 *
	 * @return void
	 */
	public function testWithoutTheVendorRenderAVendorEngineIsRefused(): void {
		$service = $this->service(vendor: null);
		$this->renderer->expects($this->once())->method('renderTemplate');
		$filinq = $service->generateDocument(templateId: 'tpl-1', dataRefs: [], options: ['format' => 'html', 'engine' => 'filinq']);
		$this->assertSame(expected: 'html', actual: $filinq['format']);

		$this->expectExceptionCode(code: 400);
		$this->expectExceptionMessage(message: '"vendor:smartdocuments" cannot be rendered on this instance');
		$service->generateDocument(templateId: 'tpl-1', dataRefs: [], options: ['format' => 'pdf', 'engine' => 'vendor:smartdocuments']);

	}//end testWithoutTheVendorRenderAVendorEngineIsRefused()
}//end class
