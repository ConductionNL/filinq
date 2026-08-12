<?php

/**
 * Unit tests for TemplatePreviewService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/advanced-template-management/tasks.md#task-5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service;

use Exception;
use OCA\DocuDesk\Service\TemplatePreviewService;
use OCA\DocuDesk\Service\TemplateRenderer;
use OCA\DocuDesk\Service\TemplateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TemplatePreviewService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplatePreviewServiceTest extends TestCase {

	/**
	 * The TemplatePreviewService under test
	 *
	 * @var TemplatePreviewService
	 */
	private TemplatePreviewService $previewService;

	/**
	 * Mock TemplateRenderer
	 *
	 * @var TemplateRenderer|MockObject
	 */
	private TemplateRenderer|MockObject $mockRenderer;

	/**
	 * Mock TemplateService
	 *
	 * @var TemplateService|MockObject
	 */
	private TemplateService|MockObject $mockTemplateService;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mockRenderer = $this->createMock(TemplateRenderer::class);
		$this->mockTemplateService = $this->createMock(TemplateService::class);

		$this->previewService = new TemplatePreviewService(
			$this->mockRenderer,
			$this->mockTemplateService
		);

	}//end setUp()

	/**
	 * Test preview converts conditional sections then renders
	 *
	 * @return void
	 */
	public function testPreviewConvertsConditionalSectionsThenRenders(): void {
		$inputContent = '<p data-condition-field="x" data-condition-op="is_not_empty">Hi</p>';
		$processedContent = '{% if x is not empty %}<p>Hi</p>{% endif %}';
		$renderedHtml = '<p>Hi</p>';

		$this->mockRenderer->expects($this->once())
			->method('convertConditionalSections')
			->with($inputContent)
			->willReturn($processedContent);

		$this->mockRenderer->expects($this->once())
			->method('renderTemplate')
			->with($processedContent, ['x' => 'hello'])
			->willReturn($renderedHtml);

		$result = $this->previewService->preview(
			content: $inputContent,
			data: ['x' => 'hello']
		);

		$this->assertEquals($renderedHtml, $result);

	}//end testPreviewConvertsConditionalSectionsThenRenders()

	/**
	 * Test preview with empty data still renders
	 *
	 * @return void
	 */
	public function testPreviewWithEmptyDataStillRenders(): void {
		$content = '<h1>Hello</h1>';

		$this->mockRenderer->method('convertConditionalSections')
			->willReturn($content);
		$this->mockRenderer->method('renderTemplate')
			->willReturn($content);

		$result = $this->previewService->preview(content: $content, data: []);

		$this->assertEquals($content, $result);

	}//end testPreviewWithEmptyDataStillRenders()

	/**
	 * Test preview propagates exception from renderer
	 *
	 * @return void
	 */
	public function testPreviewPropagatesRendererException(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Template rendering failed');

		$this->mockRenderer->method('convertConditionalSections')
			->willReturn('<p>{{ broken }}');
		$this->mockRenderer->method('renderTemplate')
			->willThrowException(new Exception('Template rendering failed', 400));

		$this->previewService->preview(content: '<p>{{ broken }}', data: []);

	}//end testPreviewPropagatesRendererException()

	/**
	 * Test previewTemplate fetches template by ID then renders
	 *
	 * @return void
	 */
	public function testPreviewTemplateFetchesAndRenders(): void {
		$template = [
			'id' => 'tmpl-1',
			'content' => '<h1>{{ title }}</h1>',
			'name' => 'Test Template',
		];

		$this->mockTemplateService->expects($this->once())
			->method('getTemplate')
			->with('tmpl-1')
			->willReturn($template);

		$this->mockRenderer->method('convertConditionalSections')
			->willReturn('<h1>{{ title }}</h1>');
		$this->mockRenderer->method('renderTemplate')
			->willReturn('<h1>My Title</h1>');

		$result = $this->previewService->previewTemplate(
			templateId: 'tmpl-1',
			data: ['title' => 'My Title']
		);

		$this->assertEquals('<h1>My Title</h1>', $result);

	}//end testPreviewTemplateFetchesAndRenders()

	/**
	 * Test previewTemplate throws when template not found
	 *
	 * @return void
	 */
	public function testPreviewTemplateThrowsWhenTemplateNotFound(): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Template not found');

		$this->mockTemplateService->method('getTemplate')
			->willThrowException(new Exception('Template not found', 404));

		$this->previewService->previewTemplate(
			templateId: 'nonexistent',
			data: []
		);

	}//end testPreviewTemplateThrowsWhenTemplateNotFound()

}//end class
