<?php

/**
 * The archival checks of document validation: PDF/A conformance and
 * embedded fonts, answered by veraPDF.
 *
 * They run only on a PDF and only when the profile switches one on, since
 * each run starts a Java validator. When one is on and veraPDF cannot
 * answer, the finding says "not checked" instead of skipping in silence,
 * and no conformance finding is made up.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Validation
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Validation;

use OCA\Filinq\Service\DocumentValidationService;
use OCA\Filinq\Service\VeraPdf\ConformanceService;
use OCP\Files\File;
use Throwable;

/**
 * Archival findings for one file.
 */
class ArchivalChecks {

	public const CATEGORY = 'archival';

	/**
	 * How many rule references a finding names.
	 */
	private const RULES_NAMED = 3;

	/**
	 * Constructor.
	 *
	 * @param ConformanceService|null $conformance The conformance check, null when it cannot be built.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly ?ConformanceService $conformance,
	) {

	}//end __construct()

	/**
	 * The archival findings for a file under a profile.
	 *
	 * @param array<string, mixed> $profile The resolved profile.
	 * @param string               $mime    The file's MIME type.
	 * @param File                 $file    The file.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.4
	 */
	public function findings(array $profile, string $mime, File $file): array {
		$conformance = $this->severity(profile: $profile, check: DocumentValidationService::CHECK_PDFA_CONFORMANCE);
		$fonts = $this->severity(profile: $profile, check: DocumentValidationService::CHECK_PDFA_FONTS);
		$bothOff = ($conformance === DocumentValidationService::SEVERITY_OFF && $fonts === DocumentValidationService::SEVERITY_OFF);
		if ($mime !== 'application/pdf' || $bothOff === true) {
			return [];
		}

		if ($this->conformance === null || $this->conformance->isAvailable() === false) {
			return [$this->unavailable()];
		}

		try {
			$report = $this->conformance->checkFile(file: $file, trigger: ConformanceService::TRIGGER_VALIDATION);
		} catch (Throwable) {
			return [$this->unavailable()];
		}

		return $this->reportFindings(report: $report, conformance: $conformance, fonts: $fonts);

	}//end findings()

	/**
	 * The findings a conformance report gives under the two severities.
	 *
	 * @param array<string, mixed> $report      The conformance report.
	 * @param string               $conformance The severity of the conformance check.
	 * @param string               $fonts       The severity of the font check.
	 *
	 * @return array<int, array<string, mixed>> The findings.
	 */
	private function reportFindings(array $report, string $conformance, string $fonts): array {
		$findings = [];
		if ($report['compliant'] === false && $conformance !== DocumentValidationService::SEVERITY_OFF) {
			$findings[] = $this->finding(
				checkId: DocumentValidationService::CHECK_PDFA_CONFORMANCE,
				severity: $conformance,
				message: 'The PDF does not meet PDF/A-{flavour}: {failedRuleCount} rules fail, such as {rules}.',
				params: [
					'flavour' => (string) $report['flavour'],
					'failedRuleCount' => (int) $report['failedRuleCount'],
					'rules' => implode(', ', array_column(array_slice((array) $report['failedRules'], 0, self::RULES_NAMED), 'ruleId')),
				],
				guidance: (string) $report['guidance']
			);
		}

		if ($report['fontsNotEmbedded'] !== [] && $fonts !== DocumentValidationService::SEVERITY_OFF) {
			$findings[] = $this->finding(
				checkId: DocumentValidationService::CHECK_PDFA_FONTS,
				severity: $fonts,
				message: 'Fonts used but not embedded: {fonts}. An e-depot rejects this PDF.',
				params: ['fonts' => implode(', ', (array) $report['fontsNotEmbedded'])],
				guidance: (string) $report['guidance']
			);
		}

		return $findings;

	}//end reportFindings()

	/**
	 * The "not checked" finding.
	 *
	 * @return array<string, mixed> The finding, always a warning.
	 */
	private function unavailable(): array {
		return $this->finding(
			checkId: DocumentValidationService::CHECK_ARCHIVAL_UNAVAILABLE,
			severity: DocumentValidationService::SEVERITY_WARNING,
			message: 'The PDF/A validator is not available, so this PDF was not checked against PDF/A.',
			params: [],
			guidance: ''
		);

	}//end unavailable()

	/**
	 * One archival finding.
	 *
	 * @param string               $checkId  The check id.
	 * @param string               $severity The severity.
	 * @param string               $message  The English message the UI translates.
	 * @param array<string, mixed> $params   Its placeholders: references and font names only.
	 * @param string               $guidance The guidance key, '' for none.
	 *
	 * @return array<string, mixed> The finding.
	 */
	private function finding(string $checkId, string $severity, string $message, array $params, string $guidance): array {
		$finding = [
			'checkId' => $checkId,
			'category' => self::CATEGORY,
			'severity' => $severity,
			'message' => $message,
			'params' => $params,
		];
		if ($guidance !== '') {
			$finding['guidance'] = $guidance;
		}

		return $finding;

	}//end finding()

	/**
	 * A check's severity in the profile; archival checks are off unless set.
	 *
	 * @param array<string, mixed> $profile The profile.
	 * @param string               $check   The check id.
	 *
	 * @return string The severity.
	 */
	private function severity(array $profile, string $check): string {
		return (string) ($profile['severities'][$check] ?? DocumentValidationService::SEVERITY_OFF);

	}//end severity()
}//end class
