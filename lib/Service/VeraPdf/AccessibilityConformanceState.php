<?php

/**
 * Filinq AccessibilityConformanceState
 *
 * Which accessibility signal a document shows. A stored PDF/UA-1 report is
 * the validator's verdict and is authoritative: a heuristic pass beside a
 * failing report is a fail. Without a report the heuristic findings are the
 * signal, labelled as heuristic ("looks tagged"), never as a conformance
 * verdict. No state claims certification.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

/**
 * Pure: report and heuristic findings in, surfaced state out.
 */
class AccessibilityConformanceState {

	public const SOURCE_VALIDATOR = 'validator';

	public const SOURCE_HEURISTIC = 'heuristic';

	/**
	 * The validator found the PDF conformant with PDF/UA-1.
	 */
	public const STATE_CONFORMANT = 'conformant';

	/**
	 * The validator found the PDF not conformant with PDF/UA-1.
	 */
	public const STATE_NON_CONFORMANT = 'non-conformant';

	/**
	 * Not validated; the heuristics found nothing missing.
	 */
	public const STATE_LOOKS_TAGGED = 'looks-tagged';

	/**
	 * Not validated; the heuristics found something missing.
	 */
	public const STATE_HEURISTIC_ISSUES = 'heuristic-issues';

	/**
	 * The surfaced accessibility state of one document.
	 *
	 * @param array<string, mixed>|null        $report             The stored PDF/UA-1 report, null when none.
	 * @param array<int, array<string, mixed>> $heuristicFindings  The heuristic accessibility findings.
	 * @param bool                             $validatorAvailable Whether veraPDF can answer now.
	 *
	 * @return array{source: string, state: string, validated: bool, validatorAvailable: bool} The state.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
	 */
	public function resolve(?array $report, array $heuristicFindings, bool $validatorAvailable): array {
		if ($report !== null && is_bool($report['compliant'] ?? null) === true) {
			$state = self::STATE_NON_CONFORMANT;
			if ($report['compliant'] === true) {
				$state = self::STATE_CONFORMANT;
			}

			return [
				'source' => self::SOURCE_VALIDATOR,
				'state' => $state,
				'validated' => true,
				'validatorAvailable' => $validatorAvailable,
			];
		}

		$state = self::STATE_LOOKS_TAGGED;
		if ($heuristicFindings !== []) {
			$state = self::STATE_HEURISTIC_ISSUES;
		}

		return [
			'source' => self::SOURCE_HEURISTIC,
			'state' => $state,
			'validated' => false,
			'validatorAvailable' => $validatorAvailable,
		];

	}//end resolve()
}//end class
