<?php

/**
 * Filinq PdfUaChecks
 *
 * The validator-backed accessibility checks of document validation: veraPDF's
 * PDF/UA-1 (`ua1`) verdict as a finding in the `accessibility` category,
 * beside the heuristic checks, which stay as they are. Off unless a profile
 * switches `pdfua-conformance-failed` on; a check that is on but gets no
 * verdict says so as a warning, it is never skipped in silence and never
 * turned into a verdict.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/document-validation-checks/spec.md#requirement-the-check-catalogue-must-include-validator-backed-pdfua-accessibility-checks-req-ddpum-005
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Validation;

use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCP\Files\File;
use Throwable;

/**
 * PDF/UA-1 findings for one file.
 */
class PdfUaChecks {

	/**
	 * The source label of a finding the validator gave.
	 */
	public const SOURCE_VALIDATOR = 'validator';

	/**
	 * How many checkpoint references a finding names.
	 */
	private const CHECKS_NAMED = 3;

	/**
	 * Constructor.
	 *
	 * @param AccessibilityConformanceService|null $conformance The PDF/UA check, null when it cannot be built.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ?AccessibilityConformanceService $conformance,
	) {

	}//end __construct()

	/**
	 * The validator-backed accessibility findings for a file under a profile.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string               $mime    The file's MIME type.
	 * @param File                 $file    The file.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/document-validation-checks/spec.md#requirement-the-check-catalogue-must-include-validator-backed-pdfua-accessibility-checks-req-ddpum-005
	 */
	public function findings(array $profile, string $mime, File $file): array {
		$severity = (string) ($profile['severities'][DocumentValidationService::CHECK_PDFUA_CONFORMANCE] ?? DocumentValidationService::SEVERITY_OFF);
		if ($mime !== 'application/pdf' || $severity === DocumentValidationService::SEVERITY_OFF) {
			return [];
		}

		$report = $this->validatorReport(file: $file);
		if ($report === null) {
			return [$this->unavailable()];
		}

		if (($report['compliant'] ?? false) === true) {
			return [];
		}

		return [$this->nonConformant(report: $report, severity: $severity)];

	}//end findings()

	/**
	 * The validator's report on a PDF, or null when the validator is not wired, not installed or gives no verdict.
	 *
	 * @param File $file The PDF.
	 *
	 * @return array<string, mixed>|null The stored report, or null.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
	 */
	private function validatorReport(File $file): ?array {
		if ($this->conformance === null || $this->conformance->isAvailable() === false) {
			return null;
		}

		try {
			return $this->conformance->checkFile(file: $file, trigger: AccessibilityConformanceService::TRIGGER_VALIDATION);
		} catch (Throwable) {
			return null;
		}

	}//end validatorReport()

	/**
	 * The finding for a PDF the validator found not conformant, naming its first failing checks.
	 *
	 * @param array<string, mixed> $report   The stored accessibility conformance report.
	 * @param string               $severity The profile's severity for the check.
	 *
	 * @return array<string, mixed> The finding, with the report's guidance when it has any.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-validator-verdict-is-the-authoritative-conformance-heuristics-remain-the-floor-req-ddpum-004
	 */
	private function nonConformant(array $report, string $severity): array {
		$checkpoints = array_column(array_slice((array) ($report['failedChecks'] ?? []), 0, self::CHECKS_NAMED), 'checkpoint');
		$finding = $this->finding(
			checkId: DocumentValidationService::CHECK_PDFUA_CONFORMANCE,
			severity: $severity,
			message: 'The PDF does not conform to PDF/UA-{flavour}: {failedCheckCount} Matterhorn checks fail, such as {checks}.',
			params: [
				'flavour' => ltrim((string) ($report['flavour'] ?? ''), 'ua'),
				'failedCheckCount' => (int) ($report['failedCheckCount'] ?? 0),
				'checks' => implode(', ', $checkpoints),
			]
		);
		$guidance = (string) ($report['guidance'] ?? '');
		if ($guidance !== '') {
			$finding['guidance'] = $guidance;
		}

		return $finding;

	}//end nonConformant()

	/**
	 * The "not validated" finding: always a warning, never escalated.
	 *
	 * @return array<string, mixed> The finding.
	 */
	private function unavailable(): array {
		return $this->finding(
			checkId: DocumentValidationService::CHECK_ACCESSIBILITY_UNAVAILABLE,
			severity: DocumentValidationService::SEVERITY_WARNING,
			message: 'The PDF/UA validator is not available, so this PDF was not checked for PDF/UA conformance.',
			params: []
		);

	}//end unavailable()

	/**
	 * One validator-backed accessibility finding.
	 *
	 * @param string               $checkId  The check id.
	 * @param string               $severity The severity.
	 * @param string               $message  The English message the UI translates.
	 * @param array<string, mixed> $params   Its placeholders: references and counts only.
	 *
	 * @return array<string, mixed> The finding.
	 */
	private function finding(string $checkId, string $severity, string $message, array $params): array {
		return [
			'checkId' => $checkId,
			'category' => AccessibilityChecks::CATEGORY,
			'source' => self::SOURCE_VALIDATOR,
			'severity' => $severity,
			'message' => $message,
			'params' => $params,
		];

	}//end finding()
}//end class
