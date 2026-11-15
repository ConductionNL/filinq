<?php

/**
 * Generation handing its stored document to the case system, end to end through DocumentService.
 *
 * Whether a delivery is recorded depends on WHERE DocumentService asks for
 * it: after the store, after the audit entry exists, and never in mode
 * `return`. CaseSystemDeliveryService's own tests cannot see that ordering,
 * so these run the real generation path over one object store double.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/changes/generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-stored-document-gets-a-delivery-for-its-destination-req-gsc-002
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\CaseSystemDeliveryService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentGenerationRecord;
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
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(DocumentService::class)]
#[UsesClass(CaseSystemDeliveryService::class)]
#[UsesClass(DocumentGenerationRecord::class)]
#[UsesClass(DocumentRenderPipeline::class)]
#[UsesClass(GeneratedDocumentLogger::class)]
#[UsesClass(DocumentOutput::class)]
#[UsesClass(DocumentProduction::class)]
#[UsesClass(PdfRendering::class)]
#[UsesClass(TemplateSources::class)]
class DocumentServiceCaseSystemTest extends TestCase {

	/**
	 * What was saved to the object store, in order.
	 *
	 * @var array<int, array{schema: string, object: array<string, mixed>, uuid: ?string}>
	 */
	private array $saved = [];

	/**
	 * How often the storage was asked to file a document.
	 *
	 * @var int
	 */
	private int $stores = 0;

	/**
	 * Build a service over an object store holding one active `hrmq` destination.
	 *
	 * @param bool        $storageFails Whether filing the document fails as a storage failure.
	 * @param string|null $failing      A schema whose save throws.
	 *
	 * @return DocumentService The service.
	 */
	private function service(bool $storageFails=false, ?string $failing=null): DocumentService {
		$this->saved  = [];
		$this->stores = 0;

		$templates = $this->getMockBuilder(TemplateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['getTemplate'])
			->getMock();
		$templates->method('getTemplate')->willReturn(['name' => 'Arbeidsovereenkomst', 'content' => 'Contract', 'namespace' => 'hrmq', 'version' => 2]);

		$data = $this->getMockBuilder(DataResolverService::class)
			->disableOriginalConstructor()
			->onlyMethods(['resolve'])
			->getMock();
		$data->method('resolve')->willReturn(['data' => [], 'warnings' => [], 'errors' => []]);

		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturnCallback(static fn (string $content): string => '<p>' . $content . '</p>');
		$renderer->method('getLastRenderWarnings')->willReturn([]);

		$storage = $this->getMockBuilder(DocumentStorageService::class)
			->disableOriginalConstructor()
			->onlyMethods(['store'])
			->getMock();
		$storage->method('store')->willReturnCallback(function (string $userId, string $targetPath, string $filename) use ($storageFails): array {
			$this->stores++;
			if ($storageFails === true) {
				throw new Exception('disk full', DocumentStorageService::ERROR_CODE_STORAGE_FAILURE);
			}

			return ['fileId' => 4711, 'path' => $targetPath . '/' . $filename, 'name' => $filename, 'size' => 10];
		});

		$objects = $this->getMockBuilder(ObjectService::class)->onlyMethods(['findAll', 'saveObject'])->getMock();
		$objects->method('findAll')->willReturnCallback(static function (array $config=[]): array {
			if ($config['filters']['schema'] !== 'caseSystemDestination') {
				return [];
			}

			return [[
				'id' => 'dest-hrmq',
				'templateNamespace' => 'hrmq',
				'sourceId' => 'zgw-delft',
				'informatieobjecttype' => 'https://zaken.example.nl/catalogi/api/v1/informatieobjecttypen/arbeidsovereenkomst',
				'vertrouwelijkheidaanduiding' => 'vertrouwelijk',
				'bronorganisatie' => '002220647',
				'active' => true,
			]];
		});
		$objects->method('saveObject')->willReturnCallback(function (array $object=[], ?\OCA\OpenRegister\Kernel\Contracts\Value\ObjectSaveOptions $options = null) use ($failing): array {
			$register = (string) ($options?->target->register ?? '');
			$schema = (string) ($options?->target->schema ?? '');
			$uuid = $options?->uuid;
			if ($schema === $failing) {
				throw new Exception('object store unavailable');
			}

			$this->saved[] = ['schema' => $schema, 'object' => $object, 'uuid' => $uuid];
			$ids = ['generatedDocument' => 'doc-1', 'caseSystemDelivery' => 'delivery-1'];

			return $object + ['id' => ($uuid ?? $ids[$schema] ?? 'other')];
		});

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new DocumentService(
			new TemplateSources(templates: $templates, data: $data),
			new DocumentProduction(pipeline: new DocumentRenderPipeline(
				new PdfRendering(renderer: $renderer, pdf: $this->createMock(PdfService::class)),
				$resolver,
				new NullLogger(),
				$this->passThroughRasterizer()
			), jobs: $this->createMock(DocumentJobStore::class)),
			new DocumentOutput(storage: $storage, log: new GeneratedDocumentLogger($resolver, new NullLogger())),
			caseSystem: new CaseSystemDeliveryService($resolver)
		);

	}//end service()

	/**
	 * Generation options for mode `mode`.
	 *
	 * @param string               $mode   The output mode.
	 * @param array<string, mixed> $output Extra `output` fields.
	 *
	 * @return array<string, mixed> The options.
	 */
	private static function options(string $mode, array $output=[]): array {
		return ['format' => 'html', 'userId' => 'anna', 'filename' => 'contract', 'output' => ['mode' => $mode] + $output];

	}//end options()

	/**
	 * The saves to one schema.
	 *
	 * @param string $schema The schema slug.
	 *
	 * @return array<int, array{schema: string, object: array<string, mixed>, uuid: ?string}>
	 */
	private function savesTo(string $schema): array {
		return array_values(array_filter($this->saved, static fn (array $save): bool => $save['schema'] === $schema));

	}//end savesTo()

	/**
	 * A filed document from a namespace with a destination is queued and linked.
	 *
	 * @return void
	 */
	public function testAFiledDocumentIsQueuedForTheCaseSystemAndLinked(): void {
		$result = $this->service()->generateDocument(templateId: 'template-1', dataRefs: [], options: self::options('both'));

		$deliveries = $this->savesTo('caseSystemDelivery');
		self::assertCount(1, $deliveries);
		self::assertSame('doc-1', $deliveries[0]['object']['generatedDocumentId']);
		self::assertSame('dest-hrmq', $deliveries[0]['object']['destinationId']);
		self::assertSame(4711, $deliveries[0]['object']['fileId']);
		self::assertSame('ready_for_writeback', $deliveries[0]['object']['deliveryStatus']);
		self::assertSame('vertrouwelijk', $deliveries[0]['object']['metadata']['vertrouwelijkheidaanduiding']);
		self::assertSame('text/html', $deliveries[0]['object']['metadata']['formaat']);

		$documents = $this->savesTo('generatedDocument');
		self::assertCount(2, $documents);
		self::assertSame('doc-1', $documents[1]['uuid']);
		self::assertSame('delivery-1', $documents[1]['object']['caseSystemDeliveryId']);
		self::assertSame('delivery-1', $result['metadata']['caseSystemDeliveryId']);
		self::assertSame([], $result['warnings']);

	}//end testAFiledDocumentIsQueuedForTheCaseSystemAndLinked()

	/**
	 * Mode `return` files nothing and so queues nothing, without even asking for a destination.
	 *
	 * @return void
	 */
	public function testModeReturnQueuesNothing(): void {
		$result = $this->service()->generateDocument(templateId: 'template-1', dataRefs: [], options: self::options('return'));

		self::assertSame([], $this->savesTo('caseSystemDelivery'));
		self::assertCount(1, $this->savesTo('generatedDocument'));
		self::assertArrayNotHasKey('caseSystemDeliveryId', $result['metadata']);

	}//end testModeReturnQueuesNothing()

	/**
	 * A case-system request in mode `return` is refused before anything is rendered or logged.
	 *
	 * @return void
	 */
	public function testACaseSystemRequestInModeReturnAnswers400(): void {
		$service = $this->service();

		try {
			$service->generateDocument(templateId: 'template-1', dataRefs: [], options: self::options('return', ['caseSystem' => ['sourceId' => 'zgw-delft']]));
			self::fail('The request was not refused.');
		} catch (Exception $e) {
			self::assertSame(400, $e->getCode());
			self::assertSame(CaseSystemDeliveryService::MUST_STORE, $e->getMessage());
		}

		self::assertSame([], $this->saved);

	}//end testACaseSystemRequestInModeReturnAnswers400()

	/**
	 * A tolerated storage failure in mode `both` queues nothing and says why.
	 *
	 * @return void
	 */
	public function testAFailedStoreInModeBothQueuesNothingAndWarns(): void {
		$result = $this->service(storageFails: true)->generateDocument(templateId: 'template-1', dataRefs: [], options: self::options('both'));

		self::assertSame(1, $this->stores);
		self::assertSame([], $this->savesTo('caseSystemDelivery'));
		self::assertContains(CaseSystemDeliveryService::NOT_STORED, $result['warnings']);

	}//end testAFailedStoreInModeBothQueuesNothingAndWarns()

	/**
	 * A delivery that cannot be written is a warning; the generation still answers.
	 *
	 * @return void
	 */
	public function testAFailingDeliveryWriteDoesNotFailTheGeneration(): void {
		$result = $this->service(failing: 'caseSystemDelivery')->generateDocument(templateId: 'template-1', dataRefs: [], options: self::options('files'));

		self::assertSame(4711, $result['output']['fileId']);
		self::assertSame(['Not sent to the case system: object store unavailable'], $result['warnings']);
		self::assertCount(1, $this->savesTo('generatedDocument'));
		self::assertArrayNotHasKey('caseSystemDeliveryId', $result['metadata']);

	}//end testAFailingDeliveryWriteDoesNotFailTheGeneration()

	/**
	 * A rasterizer that hands the HTML back unchanged.
	 *
	 * @return \OCA\Filinq\Service\Charts\SvgRasterizer The double.
	 */
	private function passThroughRasterizer(): \OCA\Filinq\Service\Charts\SvgRasterizer {
		$rasterizer = $this->createMock(\OCA\Filinq\Service\Charts\SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(
			static fn (string $html, string $format): array => ['html' => $html, 'warnings' => []]
		);
		return $rasterizer;

	}//end passThroughRasterizer()
}//end class
