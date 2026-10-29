<?php

/**
 * Contract of FormatMatrixService
 *
 * The public surface of the FormatMatrixService service, so callers in other
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

/**
 * Contract of FormatMatrixService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface FormatMatrixServiceInterface {
	/**
	 * The formats document generation offers, in display order.
	 *
	 * @var string[]
	 */
	public const DOCUMENT_FORMATS = ['pdf', 'docx', 'odf', 'html'];

	/**
	 * The formats a letter can be made in, in display order.
	 *
	 * @var string[]
	 */
	public const CORRESPONDENCE_FORMATS = ['pdf', 'docx', 'html', 'email'];

	/**
	 * The matrix for a flow on this server.
	 *
	 * @param string $flow `documents` (generation) or `correspondence` (letters).
	 *
	 * @return array<string, array{available: bool, reason?: string}> Keyed by format, in display order.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
	 */
	public function forInstance(string $flow = 'documents'): array;

	/**
	 * The matrix for one template.
	 *
	 * Every template today is a Twig template, rendered to HTML, from which
	 * all four formats are made, so its matrix is the instance's. Office
	 * templates (office-template-authoring) will narrow it here.
	 *
	 * @param array $template The template, as TemplateService returns it.
	 *
	 * @return array<string, array{available: bool, reason?: string}>
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) The template decides nothing until office templates exist.
	 *
	 * @spec openspec/changes/archive/2026-09-29-multi-format-output/tasks.md#task-2.3
	 */
	public function forTemplate(array $template): array;
}//end interface
