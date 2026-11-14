<?php

/**
 * Tests for DocumentService::generateFromHtml(), the finish of a document draft.
 *
 * The real DocumentService, render pipeline, GeneratedDocumentLogger and wizard
 * gate; OpenRegister, the template lookup, the renderer and storage are doubles.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
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

namespace OCA\Filinq\Tests\Unit\Service;

require_once __DIR__ . '/Wizard/WizardDoubles.php';

use OCA\Filinq\Exception\WizardRefused;
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
use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardConditions;
use OCA\Filinq\Service\Wizard\WizardGenerationGate;
use OCA\Filinq\Service\Wizard\WizardRepository;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardFixtures;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Finishing produces, files and logs the stored HTML through the direct path,
 * and never renders it through Twig again.
 *
 * @covers \OCA\Filinq\Service\DocumentService
 * @uses   \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses   \OCA\Filinq\Service\GeneratedDocumentLogger
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 * @uses   \OCA\Filinq\Service\Support\DocumentOutput
 * @uses   \OCA\Filinq\Service\Support\DocumentProduction
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 * @uses   \OCA\Filinq\Service\Support\TemplateSources
 * @uses   \OCA\Filinq\Service\Wizard\WizardGenerationGate
 * @uses   \OCA\Filinq\Service\Wizard\WizardRepository
 * @uses   \OCA\Filinq\Service\Wizard\WizardAnswers
 * @uses   \OCA\Filinq\Service\Wizard\WizardConditions
 */
class DocumentServiceGenerateFromHtmlTest extends TestCase {

	/**
	 * The generatedDocument entries written.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * The renderer double, to assert Twig is never called.
	 *
	 * @var TemplateRenderer&MockObject
	 */
	private TemplateRenderer $renderer;

	/**
	 * The storage double.
	 *
	 * @var DocumentStorageService&MockObject
	 */
	private DocumentStorageService $storage;

	/**
	 * The service under test, wired with the real logger and wizard gate.
	 *
	 * @return DocumentService
	 */
	private function service(): DocumentService {
		$wizards = new WizardObjectStore();
		$wizards->saveObject(object: WizardFixtures::seed(), register: 'filinq', schema: 'wizardDefinition', uuid: 'wizard-1');

		$log = $this->createMock(ObjectService::class);
		$log->method('saveObject')->willReturnCallback(function (array $object): array {
			$this->logged[] = $object;
			return $object + ['@self' => ['id' => 'record-1']];
		});
		$logResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$logResolver->method('resolve')->willReturn($log);
		$wizardResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$wizardResolver->method('resolve')->willReturn($wizards);

		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['name' => 'Beschikking parkeervergunning', 'content' => '<p>{{ naam }}</p>', 'version' => 4, 'namespace' => 'beschikkingen']);
		$templates->method('getTemplateAtVersion')->willReturn(['name' => 'Beschikking parkeervergunning', 'content' => '<p>old</p>', 'version' => 2, 'namespace' => 'beschikkingen']);
		$this->renderer = $this->createMock(TemplateRenderer::class);
		$this->storage  = $this->createMock(DocumentStorageService::class);
		$logger         = $this->createMock(LoggerInterface::class);
		$rasterizer     = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);

		return new DocumentService(
			sources: new TemplateSources(templates: $templates, data: $this->createMock(DataResolverService::class)),
			production: new DocumentProduction(
				pipeline: new DocumentRenderPipeline(new PdfRendering(renderer: $this->renderer, pdf: $this->createMock(PdfService::class)), $logResolver, $logger, $rasterizer),
				jobs: $this->createMock(DocumentJobStore::class)
			),
			output: new DocumentOutput(storage: $this->storage, log: new GeneratedDocumentLogger($logResolver, $logger)),
			wizardGate: new WizardGenerationGate(
				repository: new WizardRepository(objectResolver: $wizardResolver),
				answers: new WizardAnswers(conditions: new WizardConditions())
			),
		);
	}//end service()

	/**
	 * Typed braces stay characters: the HTML is produced as it is, Twig is not called,
	 * and the record names the draft, the editor and the template version.
	 *
	 * @return void
	 */
	public function testTheHtmlIsProducedWithoutRenderingAndLoggedWithTheDraft(): void {
		$service = $this->service();
		$this->renderer->expects($this->never())->method('renderTemplate');
		$this->storage->expects($this->never())->method('store');
		$dataRefs = [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']];

		$result = $service->generateFromHtml(
			templateId: 'template-1',
			html: '<p>Beste {{ naam }}, uw aanvraag is toegewezen.</p>',
			dataRefs: $dataRefs,
			options: ['format' => 'html', 'userId' => 'alice'],
			recordFields: ['draftId' => 'draft-9', 'editedBy' => 'alice']
		);

		$this->assertStringContainsString('{{ naam }}', $result['content']);
		$this->assertSame('html', $result['format']);
		$this->assertSame('return', $result['output']['mode']);
		$this->assertSame(4, $result['templateVersion']);
		$this->assertCount(1, $this->logged);
		$this->assertSame('draft-9', $this->logged[0]['draftId']);
		$this->assertSame('alice', $this->logged[0]['editedBy']);
		$this->assertSame('alice', $this->logged[0]['generatedBy']);
		$this->assertSame('template-1', $this->logged[0]['templateId']);
		$this->assertSame($dataRefs, $this->logged[0]['dataRefs']);
		$this->assertSame('record-1', $result['metadata']['@self']['id']);
	}//end testTheHtmlIsProducedWithoutRenderingAndLoggedWithTheDraft()

	/**
	 * A caller that logs elsewhere gets the document and no generatedDocument record.
	 *
	 * @return void
	 */
	public function testWithoutLoggingNoRecordIsWritten(): void {
		$result = $this->service()->generateFromHtml(
			templateId: 'template-1',
			html: '<p>Brief</p>',
			options: ['format' => 'html', 'userId' => 'alice'],
			log: false
		);

		$this->assertSame([], $this->logged);
		$this->assertSame([], $result['metadata']);
		$this->assertStringContainsString('<p>Brief</p>', $result['content']);
	}//end testWithoutLoggingNoRecordIsWritten()

	/**
	 * Output mode `files` files the bytes once, for the user, under the template's namespace.
	 *
	 * @return void
	 */
	public function testFilesModeStoresTheDocumentOnce(): void {
		$service = $this->service();
		$this->storage->expects($this->once())->method('store')
			->with('alice', $this->stringContains('beschikkingen'), $this->stringContains('brief'), $this->stringContains('Beste'))
			->willReturn(['fileId' => 77, 'path' => '/Filinq/beschikkingen/brief.html', 'name' => 'brief.html', 'size' => 10]);

		$result = $service->generateFromHtml(
			templateId: 'template-1',
			html: '<p>Beste mevrouw</p>',
			options: ['format' => 'html', 'userId' => 'alice', 'filename' => 'brief', 'output' => ['mode' => 'files']],
			recordFields: ['draftId' => 'draft-9', 'editedBy' => 'alice']
		);

		$this->assertSame(77, $result['output']['fileId']);
		$this->assertSame(77, $this->logged[0]['fileId']);
	}//end testFilesModeStoresTheDocumentOnce()

	/**
	 * A stored template version is used when the options name one.
	 *
	 * @return void
	 */
	public function testTheNamedTemplateVersionIsRecorded(): void {
		$result = $this->service()->generateFromHtml(
			templateId: 'template-1',
			html: '<p>x</p>',
			options: ['format' => 'html', 'userId' => 'alice', 'templateVersion' => 2]
		);

		$this->assertSame(2, $result['templateVersion']);
		$this->assertSame(2, $this->logged[0]['templateVersion']);
	}//end testTheNamedTemplateVersionIsRecorded()

	/**
	 * A wizard draft runs the wizard check: a missing answer is refused with 422 and nothing is logged.
	 *
	 * @return void
	 */
	public function testAWizardDraftWithAMissingAnswerIsRefused(): void {
		try {
			$this->service()->generateFromHtml(
				templateId: WizardFixtures::TEMPLATE,
				html: '<p>x</p>',
				dataRefs: [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']],
				options: ['format' => 'html', 'userId' => 'alice', 'wizardContext' => ['wizardId' => 'wizard-1', 'answers' => ['dossier' => 'd-17', 'besluit' => 'afgewezen']]]
			);
			$this->fail('The run was not refused.');
		} catch (WizardRefused $e) {
			$this->assertSame(422, $e->getCode());
		}

		$this->assertSame([], $this->logged);
	}//end testAWizardDraftWithAMissingAnswerIsRefused()

	/**
	 * An unknown format is refused before anything is produced.
	 *
	 * @return void
	 */
	public function testAnUnknownFormatIsRefused(): void {
		$this->expectExceptionCode(400);
		$this->service()->generateFromHtml(templateId: 'template-1', html: '<p>x</p>', options: ['format' => 'exe']);
	}//end testAnUnknownFormatIsRefused()
}//end class
