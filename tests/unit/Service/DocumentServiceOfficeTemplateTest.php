<?php

/**
 * DocumentService renders an office template through the office renderer,
 * ignores a huisstijl for it with a warning, records its type and fragments
 * in the audit entry, and resolves fragments in a Twig template first.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\BackgroundJob\JobQueue;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentJobStore;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\OfficeTemplate\OfficeTemplateRenderer;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\DocumentOutput;
use OCA\Filinq\Service\Support\DocumentProduction;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\DocumentService
 */
class DocumentServiceOfficeTemplateTest extends TestCase {

	/**
	 * The templates.
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService&MockObject $templates;

	/**
	 * The office renderer.
	 *
	 * @var OfficeTemplateRenderer&MockObject
	 */
	private OfficeTemplateRenderer&MockObject $office;

	/**
	 * The Twig renderer.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer&MockObject $twig;

	/**
	 * OpenRegister's object service, where the audit entry is saved.
	 *
	 * @var ObjectService&MockObject
	 */
	private ObjectService&MockObject $objects;

	/**
	 * The audit entries saved.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * The service under test.
	 *
	 * @var DocumentService
	 */
	private DocumentService $service;

	/**
	 * Build the service.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->templates = $this->createMock(TemplateService::class);
		$this->office = $this->createMock(OfficeTemplateRenderer::class);
		$this->office->method('isOffice')->willReturnCallback(static fn (array $template): bool => ($template['templateType'] ?? 'twig') === 'office');
		$this->twig = $this->createMock(TemplateRenderer::class);
		$this->objects = $this->createMock(ObjectService::class);
		$this->objects->method('saveObject')->willReturnCallback(
			function (array $object=[]): array {
				$this->logged[] = $object;
				return $object + ['id' => 'log-1'];
			}
		);

		$data = $this->createMock(DataResolverService::class);
		$data->method('resolve')->willReturn(['data' => ['aanvrager' => ['naam' => 'Jan']], 'errors' => [], 'warnings' => []]);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($this->objects);
		$resolver = new DocumentObjectServiceResolver($container, $apps);
		$logger = $this->createMock(LoggerInterface::class);
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html, string $format): array => ['html' => $html, 'warnings' => []]);

		$this->service = new DocumentService(
			sources: new TemplateSources(templates: $this->templates, data: $data),
			production: new DocumentProduction(
				pipeline: new DocumentRenderPipeline(new PdfRendering(renderer: $this->twig, pdf: $this->createMock(PdfService::class)), $resolver, $logger, $rasterizer),
				jobs: new DocumentJobStore(appConfig: $this->createMock(IAppConfig::class), jobList: new JobQueue(jobList: $this->createMock(IJobList::class)), logger: $logger)
			),
			output: new DocumentOutput(storage: $this->createMock(DocumentStorageService::class), log: new GeneratedDocumentLogger($resolver, $logger)),
			officeRenderer: $this->office
		);
	}//end setUp()

	/**
	 * An office template renders through the office renderer; a huisstijl is
	 * not applied and the answer says so; the entry records type and
	 * fragments.
	 *
	 * @return void
	 */
	public function testAnOfficeTemplateRendersThroughTheOfficeRenderer(): void {
		$template = ['id' => 't-1', 'name' => 'Beschikking', 'templateType' => 'office', 'sourceFileId' => 5, 'version' => 3, 'content' => 'tekst'];
		$this->templates->method('getTemplate')->willReturn($template);
		$this->twig->expects($this->never())->method('renderTemplate');
		$this->office->expects($this->once())->method('render')
			->with($template, ['aanvrager' => ['naam' => 'Jan']], 'pdf', $this->arrayHasKey('huisstijlId'))
			->willReturn(['html' => '<p>Jan</p>', 'content' => '%PDF', 'warnings' => ['No value for merge tag "besluit.datum"; it is left empty.'], 'fragments' => ['groet']]);

		$result = $this->service->generateDocument(templateId: 't-1', dataRefs: [], options: ['format' => 'pdf', 'huisstijlId' => 'h-1', 'userId' => 'u']);

		$this->assertSame('%PDF', $result['content']);
		$this->assertSame('<p>Jan</p>', $result['html']);
		$this->assertSame(
			[
				'A huisstijl is not applied to an office template; the document keeps its own house style.',
				'No value for merge tag "besluit.datum"; it is left empty.',
			],
			$result['warnings']
		);
		$this->assertSame('office', $this->logged[0]['templateType']);
		$this->assertSame(['groet'], $this->logged[0]['fragments']);
		$this->assertSame('t-1', $this->logged[0]['templateId']);
		$this->assertSame(3, $this->logged[0]['templateVersion']);
	}//end testAnOfficeTemplateRendersThroughTheOfficeRenderer()

	/**
	 * A Twig template's fragments are resolved before it renders, and its
	 * entry names them.
	 *
	 * @return void
	 */
	public function testATwigTemplateHasItsFragmentsResolvedFirst(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-2', 'name' => 'Brief', 'namespace' => 'filinq', 'content' => '${fragment:groet}']);
		$this->office->expects($this->never())->method('render');
		$this->office->expects($this->once())->method('resolveTwigFragments')->with('${fragment:groet}', 'filinq')
			->willReturn(['content' => 'Groet {{ aanvrager.naam }}', 'warnings' => ['w'], 'fragments' => ['groet']]);
		$this->twig->expects($this->once())->method('renderTemplate')
			->with('Groet {{ aanvrager.naam }}', $this->anything())
			->willReturn('Groet Jan');

		$result = $this->service->generateDocument(templateId: 't-2', dataRefs: [], options: ['format' => 'html']);

		$this->assertStringContainsString('Groet Jan', $result['content']);
		$this->assertContains('w', $result['warnings']);
		$this->assertSame('twig', $this->logged[0]['templateType']);
		$this->assertSame(['groet'], $this->logged[0]['fragments']);
	}//end testATwigTemplateHasItsFragmentsResolvedFirst()

	/**
	 * A Twig template without fragments writes no type into its entry.
	 *
	 * @return void
	 */
	public function testATwigTemplateWithoutFragmentsRecordsNoType(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-3', 'name' => 'Brief', 'content' => '{{ x }}']);
		$this->office->method('resolveTwigFragments')->willReturn(['content' => '{{ x }}', 'warnings' => [], 'fragments' => []]);
		$this->twig->method('renderTemplate')->willReturn('x');

		$this->service->generateDocument(templateId: 't-3', dataRefs: [], options: ['format' => 'html']);

		$this->assertArrayNotHasKey('templateType', $this->logged[0]);
		$this->assertArrayNotHasKey('fragments', $this->logged[0]);
	}//end testATwigTemplateWithoutFragmentsRecordsNoType()

	/**
	 * An office preview shows the filled text and its warnings.
	 *
	 * @return void
	 */
	public function testAnOfficePreviewShowsTheFilledText(): void {
		$this->templates->method('getTemplate')->willReturn(['id' => 't-1', 'templateType' => 'office', 'sourceFileId' => 5]);
		$this->office->expects($this->once())->method('fill')
			->willReturn(['docx' => 'x', 'html' => '<p>Jan</p>', 'warnings' => ['gap'], 'fragments' => []]);
		$this->twig->expects($this->never())->method('renderTemplate');

		$this->assertSame(['html' => '<p>Jan</p>', 'warnings' => ['gap']], $this->service->generatePreview(templateId: 't-1', dataRefs: []));
	}//end testAnOfficePreviewShowsTheFilledText()

	/**
	 * Filling for a multi-format run resolves the data, fills once and
	 * stores and logs nothing.
	 *
	 * @return void
	 */
	public function testFillingAnOfficeTemplateStoresAndLogsNothing(): void {
		$template = ['id' => 't-1', 'templateType' => 'office', 'sourceFileId' => 5];
		$this->office->expects($this->once())->method('fill')->with($template, ['aanvrager' => ['naam' => 'Jan']])
			->willReturn(['docx' => 'FILLED', 'html' => '<p>Jan</p>', 'warnings' => ['gap'], 'fragments' => []]);

		$filled = $this->service->fillOfficeTemplate(template: $template, dataRefs: []);

		$this->assertSame(['docx' => 'FILLED', 'html' => '<p>Jan</p>', 'warnings' => ['gap'], 'fragments' => []], $filled);
		$this->assertSame([], $this->logged);
	}//end testFillingAnOfficeTemplateStoresAndLogsNothing()
}//end class
