<?php

/**
 * Contract of TemplatePreviewService
 *
 * The public surface of the TemplatePreviewService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
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

namespace OCA\Filinq\Kernel\Contracts;

use Exception;

/**
 * Contract of TemplatePreviewService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface TemplatePreviewServiceInterface {
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
	public function preview(string $content, array $data): string;

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
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
	 */
	public function previewWithLint(string $content, array $data): array;

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
	public function previewTemplate(string $templateId, array $data): string;
}//end interface
