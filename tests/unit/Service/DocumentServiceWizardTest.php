<?php

/**
 * Tests for the wizard check inside DocumentService::generateDocument()
 *
 * The real DocumentService, GeneratedDocumentLogger, WizardGenerationGate,
 * WizardAnswers and WizardConditions; OpenRegister, the template lookup,
 * the data resolver and the renderer are doubles. The logged entry is
 * validated with Opis against the real generatedDocument fragment.
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

use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardConditions;
use OCA\Filinq\Service\Wizard\WizardGenerationGate;
use OCA\Filinq\Service\Wizard\WizardRefused;
use OCA\Filinq\Service\Wizard\WizardRepository;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardFixtures;
use OCA\Filinq\Tests\Unit\Service\Wizard\WizardObjectStore;
use OCA\OpenRegister\Service\ObjectService;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * generateDocument() with options.wizardContext.
 */
class DocumentServiceWizardTest extends TestCase {

	/**
	 * The entries written to the generatedDocument log.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $logged = [];

	/**
	 * The adHocData the resolver was given.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $adHoc = [];

	/**
	 * The wizard store.
	 *
	 * @var WizardObjectStore
	 */
	private WizardObjectStore $wizards;

	/**
	 * Build the service with a stored seed wizard.
	 *
	 * @return DocumentService The service.
	 */
	private function service(): DocumentService {
		$this->wizards = new WizardObjectStore();
		$this->wizards->saveObject(object: WizardFixtures::seed(), register: 'filinq', schema: 'wizardDefinition', uuid: 'wizard-1');

		$log = $this->createMock(ObjectService::class);
		$log->method('saveObject')->willReturnCallback(function (array $object): array {
			$this->logged[] = $object;
			return $object;
		});
		$logResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$logResolver->method('resolve')->willReturn($log);
		$wizardResolver = $this->createMock(DocumentObjectServiceResolver::class);
		$wizardResolver->method('resolve')->willReturn($this->wizards);

		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['name' => 'Beschikking parkeervergunning', 'content' => '<p>{{ besluit.uitkomst }}</p>', 'version' => 4]);
		$resolver = $this->createMock(DataResolverService::class);
		$resolver->method('resolve')->willReturnCallback(function (array $dataRefs, array $listRefs = [], array $adHocData = []): array {
			$this->adHoc[] = $adHocData;
			return ['data' => $adHocData, 'errors' => [], 'warnings' => []];
		});
		$renderer = $this->createMock(TemplateRenderer::class);
		$renderer->method('renderTemplate')->willReturn('<p>toegewezen</p>');
		$logger = $this->createMock(LoggerInterface::class);

		return new DocumentService(
			templateService: $templates,
			dataResolver: $resolver,
			renderPipeline: new DocumentRenderPipeline($renderer, $this->createMock(PdfService::class), $logResolver, $logger, $this->passThroughRasterizer()),
			storageService: $this->createMock(DocumentStorageService::class),
			documentLogger: new GeneratedDocumentLogger($logResolver, $logger),
			container: $this->createMock(ContainerInterface::class),
			jobList: $this->createMock(IJobList::class),
			logger: $logger,
			wizardGate: new WizardGenerationGate(
				repository: new WizardRepository(objectResolver: $wizardResolver),
				answers: new WizardAnswers(conditions: new WizardConditions())
			),
		);

	}//end service()

	/**
	 * A rasterizer that leaves the HTML alone.
	 *
	 * @return SvgRasterizer The double.
	 */
	private function passThroughRasterizer(): SvgRasterizer {
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(
			static fn (string $html, string $format): array => ['html' => $html, 'warnings' => []]
		);
		return $rasterizer;

	}//end passThroughRasterizer()

	/**
	 * A good wizard run is logged with the wizard id, its version and the visible answers.
	 *
	 * @return void
	 */
	public function testGeneratedDocumentCarriesTheWizardContext(): void {
		$dataRefs = [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']];

		$this->service()->generateDocument(
			templateId: WizardFixtures::TEMPLATE,
			dataRefs: $dataRefs,
			options: [
				'format' => 'html',
				'userId' => 'alice',
				'adHocData' => ['besluit' => ['uitkomst' => 'toegewezen', 'ingangsdatum' => '2026-11-01']],
				'wizardContext' => [
					'wizardId' => 'wizard-1',
					'wizardVersion' => 'what the browser thought',
					'answers' => ['dossier' => 'd-17', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-11-01', 'afwijzingsreden' => 'hidden'],
				],
			]
		);

		$this->assertCount(1, $this->logged);
		$entry = $this->logged[0];
		$this->assertSame('wizard-1', $entry['wizardContext']['wizardId']);
		$this->assertSame('1.0.1', $entry['wizardContext']['wizardVersion']);
		$this->assertSame(['dossier' => 'd-17', 'besluit' => 'toegewezen', 'ingangsdatum' => '2026-11-01'], $entry['wizardContext']['answers']);
		$this->assertSame(WizardFixtures::TEMPLATE, $entry['templateId']);
		$this->assertSame(4, $entry['templateVersion']);
		$this->assertSame($dataRefs, $entry['dataRefs']);
		// The logger writes null for an absent caseId, errorMessage, fileId and filePath, which the
		// fragment types as string; hardValidation is off, so OpenRegister keeps them. Inherited: the
		// wizard fields are what this test validates, so the nulls are left out.
		WizardObjectStore::assertValid(schema: 'generatedDocument', payload: array_filter($entry, static fn (mixed $value): bool => $value !== null));
		// The request's adHocData is what renders.
		$this->assertSame('toegewezen', $this->adHoc[0]['besluit']['uitkomst']);

	}//end testGeneratedDocumentCarriesTheWizardContext()

	/**
	 * A missing required visible answer is a 422 naming the question; nothing renders or is logged.
	 *
	 * @return void
	 */
	public function testMissingRequiredAnswerFails422AndLogsNothing(): void {
		try {
			$this->service()->generateDocument(
				templateId: WizardFixtures::TEMPLATE,
				dataRefs: [['register' => 'filinq', 'schema' => 'dossier', 'id' => 'd-17']],
				options: ['format' => 'html', 'userId' => 'alice', 'wizardContext' => ['wizardId' => 'wizard-1', 'answers' => ['dossier' => 'd-17', 'besluit' => 'afgewezen']]]
			);
			$this->fail('The run was not refused.');
		} catch (WizardRefused $e) {
			$this->assertSame(422, $e->getCode());
			$this->assertSame(['afwijzingsreden'], array_keys($e->getErrors()));
		}

		$this->assertSame([], $this->logged);
		$this->assertSame([], $this->adHoc);

	}//end testMissingRequiredAnswerFails422AndLogsNothing()

	/**
	 * A wizard of another template, or an unknown one, is refused.
	 *
	 * @return void
	 */
	public function testAWizardOfAnotherTemplateIsRefused(): void {
		$service = $this->service();
		foreach (['wizard-1' => 'another-template', 'nope' => WizardFixtures::TEMPLATE] as $wizardId => $templateId) {
			try {
				$service->generateDocument(templateId: $templateId, dataRefs: [], options: ['format' => 'html', 'wizardContext' => ['wizardId' => $wizardId, 'answers' => []]]);
				$this->fail('The run was not refused.');
			} catch (WizardRefused $e) {
				$this->assertSame(422, $e->getCode());
				$this->assertArrayHasKey('wizardId', $e->getErrors());
			}
		}

		$this->assertSame([], $this->logged);

	}//end testAWizardOfAnotherTemplateIsRefused()
}//end class
