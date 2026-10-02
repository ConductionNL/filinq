<?php

/**
 * Office template lifecycle tests
 *
 * The parts of the existing template machinery that office templates pass
 * through: the version snapshot and restore, the format matrix, the
 * generatedDocument entry, the render branch of DocumentRenderPipeline and
 * the text fragment CRUD. Payloads are checked against the real register
 * fragments (Opis).
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\OfficeTemplate;

require_once __DIR__ . '/OfficeTemplateDoubles.php';

use OCA\Filinq\Service\Conversion\LibreOfficeHeadlessBackend;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\FormatMatrixService;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRefused;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\OfficeTemplate\TemplateObjectRepository;
use OCA\Filinq\Service\OfficeTemplate\TextFragmentService;
use OCA\Filinq\Service\OpenRegisterResolver;
use OCA\Filinq\Service\PdfConversionService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateVersionService;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * @covers \OCA\Filinq\Service\TemplateVersionService
 * @covers \OCA\Filinq\Service\FormatMatrixService
 * @covers \OCA\Filinq\Service\DocumentRenderPipeline
 * @covers \OCA\Filinq\Service\OfficeTemplate\TextFragmentService
 */
class OfficeTemplateLifecycleTest extends TestCase {

	private const HASH_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

	private const HASH_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

	/**
	 * Saved version payloads.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $versionSaves = [];

	private function versionService(): TemplateVersionService {
		$objects = $this->createMock(ObjectService::class);
		$objects->method('buildSearchQuery')->willReturn([]);
		$objects->method('searchObjectsPaginated')->willReturn(['results' => [], 'total' => 0]);
		$objects->method('saveObject')->willReturnCallback(function (array $object): array {
			WizardObjectStore::assertValid(schema: 'templateVersion', payload: $object);
			$this->versionSaves[] = $object;

			return $object + ['id' => 'ver-' . count($this->versionSaves)];
		});
		$objects->method('find')->willReturn([
			'id' => 'ver-a', 'templateId' => 'tpl-1', 'version' => 1, 'content' => 'A', 'name' => 'Beschikking', 'editor' => 'alice',
			'templateType' => 'office', 'sourceFileId' => 9001, 'contentHash' => self::HASH_A,
		]);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$resolver = $this->createMock(OpenRegisterResolver::class);
		$resolver->method('getVersionRegisterAndSchema')->willReturn(['register' => 'filinq', 'schema' => 'templateVersion']);

		return new TemplateVersionService($container, $appManager, $resolver);
	}

	public function testNewSourceUploadCreatesAVersionAndRestoreBringsItBack(): void {
		$versions = $this->versionService();
		$versions->createVersion(
			templateId: 'tpl-1',
			templateState: ['name' => 'Beschikking', 'content' => 'A', 'templateType' => 'office', 'sourceFileId' => 9001, 'contentHash' => self::HASH_A],
			editor: 'alice'
		);
		$this->assertSame(9001, $this->versionSaves[0]['sourceFileId'], 'the snapshot of A points at A\'s file');
		$this->assertSame(self::HASH_A, $this->versionSaves[0]['contentHash']);

		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['name' => 'Beschikking', 'content' => 'B', 'templateType' => 'office', 'sourceFileId' => 9002, 'contentHash' => self::HASH_B]);
		$templates->expects($this->once())->method('updateTemplateWithoutVersion')->with(
			'tpl-1',
			$this->callback(static fn (array $data): bool => $data['sourceFileId'] === 9001 && $data['contentHash'] === self::HASH_A)
		)->willReturn([]);

		$versions->restoreVersion(templateId: 'tpl-1', versionId: 'ver-a', editor: 'alice', service: $templates);
		$this->assertSame(9002, $this->versionSaves[1]['sourceFileId'], 'B is snapshotted on restore');
	}

	public function testATwigVersionCarriesNoOfficeFields(): void {
		$this->versionService()->createVersion(templateId: 'tpl-2', templateState: ['name' => 'Brief', 'content' => '<p>x</p>'], editor: 'alice');

		$this->assertArrayNotHasKey('sourceFileId', $this->versionSaves[0]);
	}

	private function matrix(bool $libreOffice): FormatMatrixService {
		$conversion = $this->createMock(PdfConversionService::class);
		$conversion->method('getCapabilities')->willReturn([['name' => 'libreoffice_headless', 'available' => $libreOffice]]);

		return new FormatMatrixService($conversion);
	}

	public function testTemplateMatrixReflectsTheTemplateType(): void {
		$matrix = $this->matrix(libreOffice: true)->forTemplate(template: ['templateType' => 'office']);

		foreach (['pdf', 'docx', 'odf', 'html'] as $format) {
			$this->assertTrue($matrix[$format]['available'], $format);
		}
	}

	public function testOfficeHtmlGatedOnLibreOffice(): void {
		$office = $this->matrix(libreOffice: false)->forTemplate(template: ['templateType' => 'office']);
		$twig = $this->matrix(libreOffice: false)->forTemplate(template: ['content' => '<p>x</p>']);

		$this->assertTrue($office['pdf']['available']);
		foreach (['html', 'docx', 'odf'] as $format) {
			$this->assertFalse($office[$format]['available'], $format);
			$this->assertSame(LibreOfficeHeadlessBackend::UNAVAILABLE_REASON, $office[$format]['reason']);
		}

		$this->assertTrue($twig['html']['available'], 'a Twig template keeps the instance matrix');
	}

	public function testExistingTwigTemplatesAreUntouchedByTheSchemaBump(): void {
		WizardObjectStore::assertValid(schema: 'template', payload: ['name' => 'Oud', 'content' => '<p>{{ x }}</p>', 'namespace' => 'filinq']);

		$office = $this->createMock(OfficeTemplateRenderer::class);
		$office->method('isOffice')->willReturnCallback(static fn (array $template): bool => ($template['templateType'] ?? 'twig') === 'office');
		$office->expects($this->never())->method('render');
		$office->method('prepareTwig')->willReturnCallback(static fn (array $template): array => ['content' => $template['content'], 'tokens' => [], 'warnings' => []]);
		$twig = $this->createMock(TemplateRenderer::class);
		$twig->method('renderTemplate')->willReturn('<p>gerenderd</p>');
		$pdf = $this->createMock(PdfService::class);
		$pdf->method('renderPdf')->willReturn('%PDF');
		$pipeline = new DocumentRenderPipeline(
			$twig,
			$pdf,
			$this->createMock(DocumentObjectServiceResolver::class),
			new NullLogger(),
			$this->createMock(SvgRasterizer::class),
			null,
			null,
			$office
		);

		$body = $pipeline->renderBody(template: ['content' => '<p>{{ x }}</p>'], data: [], huisstijl: null, output: ['format' => 'pdf', 'pdfOptions' => [], 'options' => []]);

		$this->assertSame('twig', $body['templateType']);
		$this->assertSame('%PDF', $body['content']);
	}

	public function testAnOfficeTemplateTakesTheOfficeBranchAndRecordsItsType(): void {
		$office = $this->createMock(OfficeTemplateRenderer::class);
		$office->method('isOffice')->willReturn(true);
		$office->expects($this->once())->method('render')->willReturn(['content' => 'DOCX', 'html' => '', 'warnings' => ['w'], 'fragments' => []]);
		$pipeline = new DocumentRenderPipeline(
			$this->createMock(TemplateRenderer::class),
			$this->createMock(PdfService::class),
			$this->createMock(DocumentObjectServiceResolver::class),
			new NullLogger(),
			$this->createMock(SvgRasterizer::class),
			null,
			null,
			$office
		);

		$body = $pipeline->renderBody(template: ['templateType' => 'office'], data: [], huisstijl: null, output: ['format' => 'docx', 'pdfOptions' => [], 'options' => []]);

		$this->assertSame(['content' => 'DOCX', 'html' => '', 'warnings' => ['w'], 'templateType' => 'office'], $body);
		WizardObjectStore::assertValid(schema: 'generatedDocument', payload: ['templateId' => 't', 'generatedAt' => '2026-10-02T10:00:00+00:00', 'format' => 'docx', 'status' => 'generated', 'generatedBy' => 'alice', 'templateType' => 'office']);
	}

	private function fragments(OfficeObjectStore $objects): TextFragmentService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objects);

		return new TextFragmentService(objects: new TemplateObjectRepository(objectResolver: $resolver));
	}

	public function testFragmentCrudKeepsSlugsUniquePerNamespace(): void {
		$objects = new OfficeObjectStore();
		$service = $this->fragments(objects: $objects);
		$created = $service->create(data: ['name' => 'Slot', 'slug' => 'slot', 'content' => 'Met vriendelijke groet', 'namespace' => 'filinq', 'language' => 'nl']);
		$service->create(data: ['name' => 'Slot', 'slug' => 'slot', 'content' => 'Kind regards', 'namespace' => 'pipelinq']);

		try {
			$service->create(data: ['name' => 'Slot 2', 'slug' => 'slot', 'content' => 'x', 'namespace' => 'filinq']);
			$this->fail('duplicate slug accepted');
		} catch (OfficeTemplateRefused $e) {
			$this->assertSame(409, $e->getCode());
		}

		$updated = $service->update(id: $created['uuid'], data: ['content' => 'Hoogachtend', 'namespace' => 'pipelinq']);
		$this->assertSame('Hoogachtend', $updated['content']);
		$this->assertSame('filinq', $updated['namespace'], 'the namespace stays');
		$this->assertTrue($objects->rbac[0], 'fragment CRUD keeps the caller\'s RBAC');

		$service->delete(id: $created['uuid']);
		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(404);
		$service->get(id: $created['uuid']);
	}

	public function testABadSlugIsRefused(): void {
		$this->expectException(OfficeTemplateRefused::class);
		$this->expectExceptionCode(400);
		$this->fragments(objects: new OfficeObjectStore())->create(data: ['name' => 'X', 'slug' => 'Niet Goed', 'content' => 'x', 'namespace' => 'filinq']);
	}
}
