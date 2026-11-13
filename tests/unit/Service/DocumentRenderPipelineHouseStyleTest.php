<?php

/**
 * A document without a named house style uses the user's managed one; a named
 * one wins, and the house style's fonts and cover reach the PDF options.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/templates-house-style-from-thematiq/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\SvgRasterizer;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentRenderPipeline;
use OCA\Filinq\Service\HuisstijlProfileSync;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \OCA\Filinq\Service\DocumentRenderPipeline
 * @uses   \OCA\Filinq\Service\HuisstijlProfileMapper
 * @uses   \OCA\Filinq\Service\Support\PdfRendering
 */
class DocumentRenderPipelineHouseStyleTest extends TestCase {

	private HuisstijlProfileSync&MockObject $sync;

	private ObjectService&MockObject $objectService;

	private TemplateRenderer&MockObject $renderer;

	protected function setUp(): void {
		parent::setUp();

		$this->sync = $this->createMock(HuisstijlProfileSync::class);
		$this->objectService = $this->createMock(ObjectService::class);
		$this->renderer = $this->createMock(TemplateRenderer::class);
	}//end setUp()

	private function pipeline(?HuisstijlProfileSync $sync): DocumentRenderPipeline {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->objectService);

		return new DocumentRenderPipeline(
			new PdfRendering(renderer: $this->renderer, pdf: $this->createMock(PdfService::class)),
			$resolver,
			$this->createMock(LoggerInterface::class),
			$this->createMock(SvgRasterizer::class),
			null,
			null,
			$sync
		);
	}//end pipeline()

	public function testWithoutAnIdTheUsersManagedHouseStyleApplies(): void {
		$managed = ['managedBy' => 'thematiq', 'footerHtml' => '<p>Gemeente Bergen</p>'];
		$this->sync->expects($this->once())->method('syncFor')->with('clerk-bergen')->willReturn($managed);
		$this->objectService->expects($this->never())->method('find');

		$this->assertSame($managed, $this->pipeline($this->sync)->loadHuisstijl(null, 'clerk-bergen'));
	}//end testWithoutAnIdTheUsersManagedHouseStyleApplies()

	public function testAnEmptyIdAsksForTheSignedInUsersHouseStyle(): void {
		$this->sync->expects($this->once())->method('syncFor')->with(null)->willReturn(null);

		$this->assertNull($this->pipeline($this->sync)->loadHuisstijl(''));
	}//end testAnEmptyIdAsksForTheSignedInUsersHouseStyle()

	public function testANamedHouseStyleWins(): void {
		$this->sync->expects($this->never())->method('syncFor');
		$this->objectService->expects($this->once())->method('find')
			->with('hand-made', 'filinq', 'huisstijl')
			->willReturn(['name' => 'Eigen']);

		$this->assertSame(['name' => 'Eigen'], $this->pipeline($this->sync)->loadHuisstijl('hand-made', 'clerk'));
	}//end testANamedHouseStyleWins()

	public function testWithoutTheSyncNothingChanges(): void {
		$this->assertNull($this->pipeline(null)->loadHuisstijl(null));
	}//end testWithoutTheSyncNothingChanges()

	public function testTheManagedFooterLineIsRendered(): void {
		$this->renderer->method('renderTemplate')->willReturnCallback(
			static fn (string $templateContent, array $data = [], ?array $huisstijl = null): string => $templateContent
		);
		$this->renderer->method('getLastRenderWarnings')->willReturn([]);
		$this->sync->method('syncFor')->willReturn(['managedBy' => 'thematiq', 'footerHtml' => '<p>Postbus 175, 1860 AD Bergen</p>']);
		$pipeline = $this->pipeline($this->sync);

		$result = $pipeline->renderWithHuisstijl('<p>Brief</p>', [], $pipeline->loadHuisstijl(null, 'clerk'));

		$this->assertSame('<p>Brief</p><p>Postbus 175, 1860 AD Bergen</p>', $result['html']);
	}//end testTheManagedFooterLineIsRendered()

	public function testFontsAndCoverReachThePdfOptions(): void {
		$options = $this->pipeline(null)->buildPdfOptions(
			['coverPage' => true],
			['fonts' => ['heading' => ['family' => 'Fira Sans', 'url' => '/f/3'], 'body' => []], 'coverImage' => '/cover'],
			['pdfOptions' => ['title' => 'Brief']]
		);

		$this->assertSame(['heading' => ['family' => 'Fira Sans', 'url' => '/f/3'], 'body' => ['family' => 'DejaVu Sans', 'url' => null]], $options['fonts']);
		$this->assertSame('/cover', $options['coverImage']);
		$this->assertSame('Brief', $options['title']);
	}//end testFontsAndCoverReachThePdfOptions()

	public function testAHouseStyleWithoutFontsAddsNothing(): void {
		$options = $this->pipeline(null)->buildPdfOptions([], ['name' => 'Eigen'], []);

		$this->assertSame(['format' => 'A4', 'orientation' => 'P'], $options);
	}//end testAHouseStyleWithoutFontsAddsNothing()
}//end class
