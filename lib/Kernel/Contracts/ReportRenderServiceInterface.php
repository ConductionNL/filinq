<?php

/**
 * Contract of ReportRenderService
 *
 * The public surface of the ReportRenderService service, so callers in other
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
 * Contract of ReportRenderService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface ReportRenderServiceInterface {
	/**
	 * The namespace external render requests resolve templates under.
	 *
	 * @var string
	 */
	public const NAMESPACE = 'learniq';

	/**
	 * Render one document from ad-hoc data against a slug-resolved template.
	 *
	 * @param string $templateSlug The template's stable slug.
	 * @param string|null $tenantId Optional tenant (e.g. school) to prefer.
	 * @param array<string,mixed> $data Ad-hoc Twig data context.
	 * @param array<string,mixed> $options {huisstijlId?, outputQuality?
	 *                                     (screen|print), userId?}.
	 *
	 * @return array{documentRef: string, templateSlug: string, renderedAt: string}
	 *
	 * @throws Exception Code 404 if the slug cannot be resolved, 400 for a
	 *                   missing storage user, or whatever the render/store
	 *                   layers raise.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-single-document-render-by-template-slug-req-rra-01
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-print-quality-output-option-req-rra-02
	 */
	public function render(string $templateSlug, ?string $tenantId, array $data, array $options = []): array;

	/**
	 * Render a list of ad-hoc payloads against one shared template slug and
	 * package the results into a single ZIP archive.
	 *
	 * @param string $templateSlug The template's stable slug.
	 * @param string|null $tenantId Optional tenant (e.g. school) to prefer.
	 * @param array<int,array{data?: array<string,mixed>}> $items One entry per document to render.
	 * @param array<string,mixed> $options {huisstijlId?, outputQuality?, userId?}.
	 *
	 * @return array{documentRef: string, count: int, failures: array<int,array{index:int,error:string}>}
	 *
	 * @throws Exception Code 404 if the slug cannot be resolved, 400 for
	 *                   empty items or a missing storage user.
	 *
	 * @spec openspec/changes/filinq-configurable-report-templates/specs/report-render-api/spec.md#requirement-batch-render-and-zip-req-rra-03
	 */
	public function renderBatch(string $templateSlug, ?string $tenantId, array $items, array $options = []): array;
}//end interface
