<?php

/**
 * The seeded woo-besluit and woo-inventarislijst bodies, rendered through the
 * real generator from the fixture decision: the letter and the inventory agree.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Woo
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link https://www.filinq.app
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md#requirement-the-decision-letter-and-the-inventory-agree-req-ddwrw-007
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Woo;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\DocumentGenerationRecord;
use OCA\Filinq\Service\DocumentJobStore;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\DocumentStorageService;
use OCA\Filinq\Service\GeneratedDocumentLogger;
use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\DocumentOutput;
use OCA\Filinq\Service\Support\DocumentProduction;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\Woo\WooDecisionContextBuilder;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The two Woo documents from one decision.
 *
 * @covers \OCA\Filinq\Service\DocumentService
 * @uses   \OCA\Filinq\Service\Woo\WooDecisionContextBuilder
 * @uses   \OCA\Filinq\Service\Grounds\RefusalGroundsResolver
 * @uses   \OCA\Filinq\Service\TemplateRenderer
 * @uses   \OCA\Filinq\Service\Charts\ChartSvgRenderer
 * @uses   \OCA\Filinq\Service\Charts\TableHtmlRenderer
 * @uses   \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses   \OCA\Filinq\Service\DocumentGenerationRecord
 * @uses   \OCA\Filinq\Service\GeneratedDocumentLogger
 * @uses   \OCA\Filinq\Service\DocumentObjectServiceResolver
 * @uses   \OCA\Filinq\Service\Support\DocumentOutput
 * @uses   \OCA\Filinq\Service\Support\DocumentProduction
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 * @uses   \OCA\Filinq\Service\Support\TemplateSources
 */
final class WooDecisionTemplatesTest extends TestCase {

	/**
	 * The seeded template with this slug.
	 *
	 * @param string $slug The slug.
	 *
	 * @return array<string, mixed>
	 */
	private static function seeded(string $slug): array {
		$register = json_decode((string) file_get_contents(__DIR__.'/../../../../lib/Settings/filinq_register.json'), true);
		foreach ($register['components']['objects'] as $object) {
			if (($object['@self']['schema'] ?? '') === 'template' && ($object['@self']['slug'] ?? '') === $slug) {
				return $object;
			}
		}

		self::fail('No seeded template '.$slug);

	}//end seeded()

	/**
	 * Render a seeded template over the fixture decision, checked by the real contract builder.
	 *
	 * @param string $slug The template slug.
	 *
	 * @return string The rendered HTML, tags stripped to text with one space between.
	 */
	private function render(string $slug): string {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturn(false);
		$apps->method('getInstalledApps')->willReturn([]);
		$builder  = new WooDecisionContextBuilder(
			new RefusalGroundsResolver($apps, $this->createMock(ContainerInterface::class), new NullLogger())
		);
		$decision = $builder->build(json_decode((string) file_get_contents(__DIR__.'/../../../fixtures/woo-decision.json'), true));

		$templates = $this->getMockBuilder(TemplateService::class)->disableOriginalConstructor()->onlyMethods(['getTemplate'])->getMock();
		$templates->method('getTemplate')->willReturn(self::seeded($slug));
		$data = $this->getMockBuilder(DataResolverService::class)->disableOriginalConstructor()->onlyMethods(['resolve'])->getMock();
		$data->method('resolve')->willReturnCallback(static fn (array $dataRefs, array $listRefs=[], array $adHocData=[]): array => ['data' => $adHocData, 'warnings' => [], 'errors' => []]);

		$storage = $this->getMockBuilder(DocumentStorageService::class)->disableOriginalConstructor()->onlyMethods(['store'])->getMock();
		$storage->expects($this->never())->method('store');
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->method('rasterizeInlineSvg')->willReturnCallback(static fn (string $html): array => ['html' => $html, 'warnings' => []]);
		$objects = new DocumentObjectServiceResolver($this->createMock(ContainerInterface::class), $apps);

		$service = new DocumentService(
			new TemplateSources(templates: $templates, data: $data),
			new DocumentProduction(pipeline: new DocumentRenderPipeline(
				new PdfRendering(renderer: new TemplateRenderer(new NullLogger(), new ChartSvgRenderer(), new TableHtmlRenderer()), pdf: $this->createMock(PdfService::class)),
				$objects,
				new NullLogger(),
				$rasterizer
			), jobs: $this->createMock(DocumentJobStore::class)),
			new DocumentOutput(storage: $storage, log: new GeneratedDocumentLogger($objects, new NullLogger()))
		);

		$result = $service->generateDocument(templateId: $slug, dataRefs: [], options: ['format' => 'html', 'adHocData' => ['wooDecision' => $decision]]);

		return trim((string) preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', $result['content']))));

	}//end render()

	/**
	 * The text between two headings of the letter.
	 *
	 * @param string      $text  The rendered text.
	 * @param string      $from  The heading the section starts at.
	 * @param string|null $until The next heading, or null for the end.
	 *
	 * @return string The section.
	 */
	private static function section(string $text, string $from, ?string $until): string {
		$start = strpos($text, $from);
		self::assertNotFalse($start, 'No section '.$from);
		$end = strlen($text);
		if ($until !== null) {
			$end = (int) strpos($text, $until, $start);
		}

		return substr($text, $start, $end - $start);

	}//end section()

	/**
	 * The letter cites 2 and 3 with their grounds in words; the inventory lists 1 to 3 the same way.
	 *
	 * @return void
	 */
	public function testTheLetterAndTheInventoryAgree(): void {
		$letter = $this->render('woo-besluit');

		$this->assertStringContainsString('Kenmerk: WOO-2026-0042', $letter);
		$this->assertStringContainsString('ontvangen op 01-09-2026', $letter);
		$this->assertStringContainsString('Datum van dit besluit: 13-10-2026', $letter);
		$this->assertStringContainsString('Gemeente Voorbeeld besluit de gevraagde informatie gedeeltelijk openbaar te maken.', $letter);

		$disclosed = self::section($letter, 'Openbaar Document', 'Gedeeltelijk openbaar');
		$this->assertStringContainsString('Document 1: Raadsvoorstel herinrichting', $disclosed);
		$this->assertStringNotContainsString('Document 2', $disclosed);
		$this->assertStringNotContainsString('Document 3', $disclosed);

		$partly = self::section($letter, 'Gedeeltelijk openbaar', 'Niet openbaar');
		$this->assertStringContainsString('Document 2: Advies verkeerskundige. Weigeringsgrond: Eerbiediging van de persoonlijke levenssfeer (artikel 5.1 Woo, grond 5.1.2.e)', $partly);

		$withheld = self::section($letter, 'Niet openbaar', 'Bezwaar');
		$this->assertStringContainsString('Document 3: Intern memo wethouder. Weigeringsgrond: Eerbiediging van de persoonlijke levenssfeer (artikel 5.1 Woo, grond 5.1.2.e); Persoonlijke beleidsopvattingen in stukken voor intern beraad (artikel 5.2 Woo, grond 5.2.1)', $withheld);

		$inventory = $this->render('woo-inventarislijst');
		$this->assertStringContainsString('Bij het besluit op Woo-verzoek WOO-2026-0042', $inventory);
		$this->assertMatchesRegularExpression(
			'/1 Raadsvoorstel herinrichting 10-01-2026 Openbaar .*2 Advies verkeerskundige 11-02-2026 Gedeeltelijk openbaar 5\.1\.2\.e Eerbiediging van de persoonlijke levenssfeer Namen en telefoonnummers gelakt .*3 Intern memo wethouder 12-03-2026 Niet openbaar 5\.1\.2\.e Eerbiediging van de persoonlijke levenssfeer; 5\.2\.1 Persoonlijke beleidsopvattingen/',
			$inventory
		);
		$this->assertStringNotContainsString('2 Advies verkeerskundige 11-02-2026 Openbaar', $inventory);

	}//end testTheLetterAndTheInventoryAgree()
}//end class
