<?php

/**
 * Filinq Office Fill Run
 *
 * The state of filling one DOCX: the document, its data, the fragment texts,
 * the field mapping and what the fill found along the way.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * One fill of one DOCX, passed between the filler's steps.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
 */
final class OfficeFillRun {

	/**
	 * The warnings so far.
	 *
	 * @var array<int, string>
	 */
	public array $warnings = [];

	/**
	 * The fragment slugs used so far.
	 *
	 * @var array<int, string>
	 */
	public array $fragmentsUsed = [];

	/**
	 * The document's tags before any block was cloned.
	 *
	 * @var array<int, string>
	 */
	public array $documentTags = [];

	/**
	 * Constructor.
	 *
	 * @param TemplateProcessor     $processor The document.
	 * @param array<string, mixed>  $data      The data context.
	 * @param array<string, string> $fragments The fragment texts by slug.
	 * @param array<string, mixed>  $fieldMap  Tag => data path aliases.
	 */
	public function __construct(
		public readonly TemplateProcessor $processor,
		public readonly array $data,
		public readonly array $fragments,
		public readonly array $fieldMap,
	) {
	}//end __construct()

	/**
	 * The data path a tag reads, after the template's field mapping.
	 *
	 * @param string $tag The tag.
	 *
	 * @return string
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function aliased(string $tag): string {
		$alias = $this->fieldMap[$tag] ?? null;
		if (is_string($alias) === true && trim($alias) !== '') {
			return trim($alias);
		}

		return $tag;
	}//end aliased()
}//end class
