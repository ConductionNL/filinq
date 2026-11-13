<?php

/**
 * Validate a file against PDF/UA-1 and store the accessibility conformance report.
 *
 * @category Service
 * @package  OCA\Filinq\Service\VeraPdf
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 * @spec     openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use DateTimeInterface;
use OCA\Filinq\Exception\VeraPdfException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use RuntimeException;
use Throwable;

/**
 * Validate, advise, store. The report holds Matterhorn clause and test
 * references only, never text from the document.
 */
class AccessibilityConformanceService {

	public const TRIGGER_MANUAL = 'manual';

	public const TRIGGER_VALIDATION = 'validation';

	public const TRIGGER_GENERATION = 'generation';

	/**
	 * The most failed checks kept on a report.
	 */
	public const MAX_FAILED_CHECKS = VeraPdfReportParser::MAX_RULES;

	/**
	 * Constructor.
	 *
	 * @param VeraPdfService                           $veraPdf  The validator.
	 * @param AccessibilityGuidance                    $guidance The advice.
	 * @param AccessibilityConformanceReportRepository $reports  The stored reports.
	 * @param ITimeFactory                             $time     The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly VeraPdfService $veraPdf,
		private readonly AccessibilityGuidance $guidance,
		private readonly AccessibilityConformanceReportRepository $reports,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Whether a check can run now.
	 *
	 * @return bool True when veraPDF is switched on and answers.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-pdfua-1-conformance-is-validated-through-the-shared-verapdf-backend-req-ddpum-001
	 */
	public function isAvailable(): bool {
		return $this->veraPdf->isAvailable();

	}//end isAvailable()

	/**
	 * Check a stored file against PDF/UA-1 and store the report.
	 *
	 * @param File   $file    The PDF.
	 * @param string $trigger One of the TRIGGER_* constants.
	 *
	 * @return array<string, mixed> The stored report.
	 *
	 * @throws VeraPdfException When veraPDF gives no verdict; nothing is stored then.
	 * @throws RuntimeException When the report cannot be stored.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	public function checkFile(File $file, string $trigger): array {
		try {
			$bytes = (string) $file->getContent();
		} catch (Throwable $e) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'The document could not be read.', previous: $e);
		}

		$verdict = $this->veraPdf->validateUaBytes(bytes: $bytes);

		return $this->reports->save(
			report: [
				'fileId' => (int) $file->getId(),
				'flavour' => VeraPdfService::UA_FLAVOUR,
				'compliant' => (bool) $verdict['compliant'],
				'failedCheckCount' => (int) $verdict['failedRuleCount'],
				'failedChecks' => $this->failedChecks(rules: (array) $verdict['failedRules']),
				'guidance' => $this->guidance->for(verdict: $verdict, bytes: $bytes),
				'validatorVersion' => (string) $verdict['validatorVersion'],
				'validatedAt' => $this->time->getDateTime()->format(DateTimeInterface::ATOM),
				'trigger' => $trigger,
			]
		);

	}//end checkFile()

	/**
	 * The report stored for a file.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, mixed>|null The report, or null when none is stored.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-a-persisted-accessibility-conformance-report-is-stored-per-document-and-re-runnable-req-ddpum-002
	 */
	public function reportFor(int $fileId): ?array {
		return $this->reports->findForFile(fileId: $fileId);

	}//end reportFor()

	/**
	 * The failed rules as Matterhorn references: clause, test number and the
	 * checkpoint id veraPDF names them by.
	 *
	 * @param array<int, mixed> $rules The failed rules of a verdict.
	 *
	 * @return array<int, array{clause: string, testNumber: int, checkpoint: string}> The references, at most MAX_FAILED_CHECKS.
	 */
	private function failedChecks(array $rules): array {
		$checks = [];
		foreach (array_slice($rules, 0, self::MAX_FAILED_CHECKS) as $rule) {
			$checks[] = [
				'clause' => (string) ($rule['clause'] ?? ''),
				'testNumber' => (int) ($rule['testNumber'] ?? 0),
				'checkpoint' => (string) ($rule['ruleId'] ?? ''),
			];
		}

		return $checks;

	}//end failedChecks()
}//end class
