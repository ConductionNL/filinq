<?php

/**
 * Unit tests for MergeCoverRenderer
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
 * @spec openspec/specs/document-merge/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\MergeCoverRenderer;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\TemplateService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The cover renderer returns the rendered bytes and fails loudly on every
 * way a cover can go missing.
 */
#[CoversClass(MergeCoverRenderer::class)]
class MergeCoverRendererTest extends TestCase {

	/**
	 * The template store.
	 *
	 * @var TemplateService&MockObject
	 */
	private TemplateService $templates;

	/**
	 * The PDF renderer.
	 *
	 * @var PdfService&MockObject
	 */
	private PdfService $pdf;

	/**
	 * The subject.
	 *
	 * @var MergeCoverRenderer
	 */
	private MergeCoverRenderer $renderer;

	/**
	 * Build the subject over mocked collaborators.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->templates = $this->createMock(TemplateService::class);
		$this->pdf = $this->createMock(PdfService::class);
		$this->renderer = new MergeCoverRenderer(templates: $this->templates, pdf: $this->pdf);
	}//end setUp()

	/**
	 * The template content and the merge data reach the PDF renderer, and its bytes come back.
	 *
	 * @return void
	 */
	public function testRendersTheTemplateContentOverTheData(): void {
		$data = ['inputs' => ['a.pdf', 'b.pdf'], 'requestedBy' => 'alice'];
		$this->templates->expects($this->once())
			->method('getTemplate')
			->with('cover-1')
			->willReturn(['content' => '<h1>{{ requestedBy }}</h1>']);
		$this->pdf->expects($this->once())
			->method('renderPdf')
			->with('<h1>{{ requestedBy }}</h1>', $data)
			->willReturn('%PDF-1.7 cover');

		$this->assertSame('%PDF-1.7 cover', $this->renderer->render(templateRef: 'cover-1', data: $data));
	}//end testRendersTheTemplateContentOverTheData()

	/**
	 * An empty template reference is refused before the store is asked.
	 *
	 * @return void
	 */
	public function testAnEmptyTemplateReferenceIsRefused(): void {
		$this->templates->expects($this->never())->method('getTemplate');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('A cover page names the template it renders.');
		$this->renderer->render(templateRef: '');
	}//end testAnEmptyTemplateReferenceIsRefused()

	/**
	 * A template that cannot be read is reported with its reference and the cause chained.
	 *
	 * @return void
	 */
	public function testAnUnreadableTemplateIsReportedWithItsCause(): void {
		$cause = new \InvalidArgumentException('not found');
		$this->templates->method('getTemplate')->willThrowException($cause);
		$this->pdf->expects($this->never())->method('renderPdf');

		try {
			$this->renderer->render(templateRef: 'gone');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('The cover template "gone" could not be read: not found', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testAnUnreadableTemplateIsReportedWithItsCause()

	/**
	 * A template with no content is refused.
	 *
	 * @return void
	 */
	public function testATemplateWithoutContentIsRefused(): void {
		$this->templates->method('getTemplate')->willReturn(['name' => 'Cover']);
		$this->pdf->expects($this->never())->method('renderPdf');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The cover template "cover-2" holds no content.');
		$this->renderer->render(templateRef: 'cover-2');
	}//end testATemplateWithoutContentIsRefused()

	/**
	 * A renderer failure is wrapped with the cause chained.
	 *
	 * @return void
	 */
	public function testARenderFailureIsWrapped(): void {
		$cause = new \RuntimeException('mPDF exploded');
		$this->templates->method('getTemplate')->willReturn(['content' => 'x']);
		$this->pdf->method('renderPdf')->willThrowException($cause);

		try {
			$this->renderer->render(templateRef: 'cover-3');
			$this->fail('Expected a RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertSame('The cover page did not render: mPDF exploded', $e->getMessage());
			$this->assertSame($cause, $e->getPrevious());
		}
	}//end testARenderFailureIsWrapped()

	/**
	 * A render that yields no bytes is refused.
	 *
	 * @return void
	 */
	public function testAnEmptyRenderIsRefused(): void {
		$this->templates->method('getTemplate')->willReturn(['content' => 'x']);
		$this->pdf->method('renderPdf')->willReturn('');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The cover page rendered to nothing.');
		$this->renderer->render(templateRef: 'cover-4');
	}//end testAnEmptyRenderIsRefused()
}//end class
