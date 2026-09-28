<?php

/**
 * Decision letter context tests for DocumentService
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
 * @spec openspec/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\ObjectionTermCalculator;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCP\App\IAppManager;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * A decision letter carries its legal basis and objection deadline (REQ-DLB-001).
 *
 * The resolver, the Twig renderer and the calculator are the real classes, so
 * the date in the letter is the one a user would read.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DocumentServiceDecisionLetterTest extends TestCase {

	/**
	 * Build the service with real resolver, renderer and calculator.
	 *
	 * @param string $content The template source
	 *
	 * @return DocumentService
	 */
	private function service(string $content): DocumentService {
		$logger = $this->createMock(LoggerInterface::class);
		$container = $this->createMock(ContainerInterface::class);
		$appManager = $this->createMock(IAppManager::class);
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueInt')->willReturnArgument(2);

		$templates = $this->createMock(TemplateService::class);
		$templates->method('getTemplate')->willReturn(['id' => 'tmpl-1', 'name' => 'Besluit', 'content' => $content]);

		$renderer = new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer());
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(
			static fn (string $html, string $format): array => ['html' => $html, 'warnings' => []]
		);
		$objectResolver = new DocumentObjectServiceResolver($container, $appManager);

		return new DocumentService(
			$templates,
			new DataResolverService($container, $appManager, $logger, $appConfig),
			new DocumentRenderPipeline($renderer, new PdfService($logger, $renderer), $objectResolver, $logger, $rasterizer),
			$this->createMock(DocumentStorageService::class),
			new GeneratedDocumentLogger($objectResolver, $logger),
			$container,
			$this->createMock(IJobList::class),
			$logger,
			null,
			new ObjectionTermCalculator($appConfig)
		);

	}//end service()

	/**
	 * The letter states the last day to object.
	 *
	 * @return void
	 */
	public function testTheLetterStatesTheLastDayToObject(): void {
		$result = $this->service('<p>Bezwaar tot en met {{ bezwaar.uiterlijk }} ({{ bezwaar.termijnWeken }} weken)</p>')->generatePreview(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['adHocData' => ['besluitDatum' => '2026-09-01']]
		);

		$this->assertStringContainsString('Bezwaar tot en met 13-10-2026 (6 weken)', $result['html']);
		$this->assertSame([], $result['warnings']);

	}//end testTheLetterStatesTheLastDayToObject()

	/**
	 * The legal basis of a resolved base object is offered as grondslag.
	 *
	 * @return void
	 */
	public function testTheLegalBasisIsOfferedAsGrondslag(): void {
		$result = $this->service('<p>{{ grondslag.name }}</p>')->generatePreview(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['adHocData' => ['base' => ['name' => 'Art. 5.1 lid 2 sub e Woo']]]
		);

		$this->assertStringContainsString('Art. 5.1 lid 2 sub e Woo', $result['html']);

	}//end testTheLegalBasisIsOfferedAsGrondslag()

	/**
	 * No decision date is a warning, not a wrong date.
	 *
	 * @return void
	 */
	public function testNoDecisionDateIsAWarning(): void {
		$result = $this->service('<p>{% if bezwaar %}Tot {{ bezwaar.uiterlijk }}{% else %}geen termijn{% endif %}</p>')->generateDocument(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['format' => 'html', 'adHocData' => ['naam' => 'Jan']]
		);

		$this->assertStringContainsString('geen termijn', $result['content']);
		$this->assertContains(
			'No decision date found (besluitDatum or decisionDate), so the objection deadline is left out.',
			$result['warnings']
		);

	}//end testNoDecisionDateIsAWarning()

	/**
	 * A template that never mentions bezwaar gets no warning for a missing date.
	 *
	 * @return void
	 */
	public function testALetterWithoutBezwaarGetsNoWarning(): void {
		$result = $this->service('<p>{{ naam }}</p>')->generatePreview(
			templateId: 'tmpl-1',
			dataRefs: [],
			options: ['adHocData' => ['naam' => 'Jan']]
		);

		$this->assertSame([], $result['warnings']);

	}//end testALetterWithoutBezwaarGetsNoWarning()
}//end class
