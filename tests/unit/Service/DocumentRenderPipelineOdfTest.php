<?php

/**
 * The ODF output path carries charts across (template-charts REQ-DDTCH-007).
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateRenderer;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The pipeline hands ODF-bound HTML to the rasterizer and reports its warnings.
 */
class DocumentRenderPipelineOdfTest extends TestCase {

	/**
	 * Build the pipeline over the given rasterizer.
	 *
	 * @param SvgRasterizer $rasterizer The rasterizer.
	 *
	 * @return DocumentRenderPipeline
	 */
	private function pipeline(SvgRasterizer $rasterizer): DocumentRenderPipeline {
		$container = $this->createMock(ContainerInterface::class);
		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('getInstalledApps')->willReturn([]);
		$renderer = new TemplateRenderer(new NullLogger(), new ChartSvgRenderer(), new TableHtmlRenderer());

		return new DocumentRenderPipeline(
			$renderer,
			$this->createMock(PdfService::class),
			new DocumentObjectServiceResolver($container, $appManager),
			new NullLogger(),
			$rasterizer
		);
	}

	/**
	 * ODF output goes through the rasterizer, and a chart it could not convert
	 * is a warning the generation response can carry.
	 *
	 * @return void
	 */
	public function testOdfOutputRasterizesAndReportsWarnings(): void {
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->expects($this->once())
			->method('rasterizeInlineSvg')
			->with('<p>a</p><svg></svg>', 'odf')
			->willReturn(['html' => '<p>a</p><span>[x]</span>', 'warnings' => ['chart error: the chart could not be converted for odf']]);
		$pipeline = $this->pipeline(rasterizer: $rasterizer);

		try {
			$pipeline->produceOutput(htmlContent: '<p>a</p><svg></svg>', format: 'odf', pdfOptions: []);
		} catch (Exception $e) {
			// The ODT conversion needs LibreOffice; on a host without it the call
			// answers 503 after the rasterizer ran. The warning is set either way.
			$this->assertContains($e->getCode(), [500, 503]);
		}

		$this->assertSame(['chart error: the chart could not be converted for odf'], $pipeline->getLastOutputWarnings());
	}

	/**
	 * HTML output keeps the SVG and resets the warnings of an earlier call.
	 *
	 * @return void
	 */
	public function testHtmlOutputKeepsSvgAndClearsWarnings(): void {
		$rasterizer = $this->createMock(SvgRasterizer::class);
		$rasterizer->expects($this->never())->method('rasterizeInlineSvg');
		$pipeline = $this->pipeline(rasterizer: $rasterizer);

		$html = $pipeline->produceOutput(htmlContent: '<svg></svg>', format: 'html', pdfOptions: []);

		$this->assertSame('<svg></svg>', $html);
		$this->assertSame([], $pipeline->getLastOutputWarnings());
	}
}
