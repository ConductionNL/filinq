<?php

/**
 * Remediation guidance for a PDF/UA-1 verdict, from where the document came from.
 *
 * @category Service
 * @package  OCA\Filinq\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 * @spec     openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-remediation-guidance-is-honest-and-never-claims-certification-req-ddpum-003
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

/**
 * Pure: verdict and bytes in, guidance key out. The keys describe a
 * conformance verdict and what to do about it; none of them claims the
 * document is certified.
 */
class AccessibilityGuidance {

	/**
	 * Conformant: nothing to do.
	 */
	public const NONE = 'none';

	/**
	 * Filinq wrote the document through its tagged output path: generate it
	 * again through Filinq with the accessible output option.
	 */
	public const REGENERATE_ACCESSIBLE = 'regenerateAccessible';

	/**
	 * The pages came from elsewhere, or from Filinq's untagged output path:
	 * Filinq does not retag pages, so re-author from an accessible source.
	 */
	public const REAUTHOR_FROM_SOURCE = 'reauthorFromSource';

	/**
	 * A PDF carrying a structure tree, the mark of tagged output.
	 */
	private const STRUCTURE_TREE = '/\/StructTreeRoot\b/';

	/**
	 * Constructor.
	 *
	 * @param ConformanceGuidance $origins Tells documents Filinq rendered from imported ones.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ConformanceGuidance $origins,
	) {

	}//end __construct()

	/**
	 * The guidance for a PDF/UA verdict on these bytes.
	 *
	 * @param array<string, mixed> $verdict The verdict.
	 * @param string               $bytes   The PDF that was validated.
	 *
	 * @return string One of the guidance constants.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-remediation-guidance-is-honest-and-never-claims-certification-req-ddpum-003
	 */
	public function for(array $verdict, string $bytes): string {
		if (($verdict['compliant'] ?? false) === true) {
			return self::NONE;
		}

		$rendered = ($this->origins->originOf(bytes: $bytes, verdict: $verdict) === ConformanceGuidance::ORIGIN_RENDERED);
		if ($rendered === true && preg_match(self::STRUCTURE_TREE, $bytes) === 1) {
			return self::REGENERATE_ACCESSIBLE;
		}

		return self::REAUTHOR_FROM_SOURCE;

	}//end for()
}//end class
