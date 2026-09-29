<?php

/**
 * Template Preview Service
 *
 * Service for rendering template previews with sample data.
 * Uses the existing TemplateRenderer for Twig sandbox rendering
 * and converts conditional sections before rendering.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-management/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Service\Validation\TemplateAccessibilityLint;
use OCP\IConfig;

/**
 * Service for rendering template previews with sample data
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TemplatePreviewService {
	/**
	 * Constructor for TemplatePreviewService
	 *
	 * @param TemplateRenderer $templateRenderer Twig template renderer
	 * @param TemplateService $templateService Template CRUD service
	 * @param IConfig|null $config System config, for the instance language the lint falls back to
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateRenderer $templateRenderer,
		private readonly TemplateService $templateService,
		private readonly ?IConfig $config = null,
	) {

	}//end __construct()

	/**
	 * Preview template content with sample data
	 *
	 * Processes conditional sections and renders Twig template
	 * with the provided data context.
	 *
	 * @param string $content Template HTML/Twig content
	 * @param array $data Sample data context for rendering
	 *
	 * @return string Rendered HTML output
	 *
	 * @throws Exception If rendering fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function preview(string $content, array $data): string {
		// Convert conditional sections to Twig before rendering.
		$processedContent = $this->templateRenderer->convertConditionalSections(
			html: $content
		);

		return $this->templateRenderer->renderTemplate(
			templateContent: $processedContent,
			data: $data
		);

	}//end preview()

	/**
	 * Preview template content and lint the result for accessibility.
	 *
	 * The lint is advice beside the preview; it never stops one.
	 *
	 * @param string $content Template HTML/Twig content
	 * @param array $data Sample data context for rendering
	 *
	 * @return array{html: string, lint: array<int, array<string, mixed>>} The preview and its lint
	 *
	 * @throws Exception If rendering fails
	 *
	 * @spec openspec/changes/pdfua-accessible-output/tasks.md#task-3.3
	 */
	public function previewWithLint(string $content, array $data): array {
		$html = $this->preview(content: $content, data: $data);
		$language = '';
		if ($this->config !== null) {
			$language = $this->config->getSystemValueString('default_language', '');
		}

		return [
			'html' => $html,
			'lint' => (new TemplateAccessibilityLint())->lint(html: $html, instanceLanguage: $language),
		];

	}//end previewWithLint()

	/**
	 * Preview an existing template with sample data
	 *
	 * Fetches the template by ID and renders it with the provided data.
	 *
	 * @param string $templateId The template UUID
	 * @param array $data Sample data context for rendering
	 *
	 * @return string Rendered HTML output
	 *
	 * @throws Exception If the template is not found or rendering fails
	 *
	 * @spec openspec/specs/template-management/spec.md
	 */
	public function previewTemplate(string $templateId, array $data): string {
		$template = $this->templateService->getTemplate(id: $templateId);

		return $this->preview(
			content: $template['content'],
			data: $data
		);

	}//end previewTemplate()
}//end class
