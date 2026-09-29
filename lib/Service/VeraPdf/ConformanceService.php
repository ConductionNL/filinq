<?php

/**
 * Checks a PDF with veraPDF and keeps the verdict as a conformanceReport.
 *
 * The one place a verdict becomes a stored report, whoever asked for it: a
 * person on the document, document validation, or a PDF/A-3 conversion.
 * A validator that cannot answer raises; nothing here ever stores a
 * verdict that veraPDF did not give.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
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
 * Validate, advise, store.
 */
class ConformanceService {

	public const TRIGGER_MANUAL = 'manual';

	public const TRIGGER_VALIDATION = 'validation';

	public const TRIGGER_CONVERSION = 'conversion';

	/**
	 * Constructor.
	 *
	 * @param VeraPdfService              $veraPdf  The validator.
	 * @param ConformanceGuidance         $guidance The advice.
	 * @param ConformanceReportRepository $reports  The stored reports.
	 * @param ITimeFactory                $time     The clock.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly VeraPdfService $veraPdf,
		private readonly ConformanceGuidance $guidance,
		private readonly ConformanceReportRepository $reports,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Whether a check can run now.
	 *
	 * @return bool True when veraPDF is switched on and answers.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function isAvailable(): bool {
		return $this->veraPdf->isAvailable();

	}//end isAvailable()

	/**
	 * Check a stored file and store the report.
	 *
	 * @param File   $file    The PDF.
	 * @param string $trigger TRIGGER_MANUAL or TRIGGER_VALIDATION.
	 *
	 * @return array<string, mixed> The stored report.
	 *
	 * @throws VeraPdfException When veraPDF gives no verdict.
	 * @throws RuntimeException When the report cannot be stored.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function checkFile(File $file, string $trigger): array {
		try {
			$bytes = (string) $file->getContent();
		} catch (Throwable $e) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'The document could not be read.', previous: $e);
		}

		$verdict = $this->veraPdf->validateBytes(bytes: $bytes);

		return $this->reports->save(
			report: $this->report(
				fileId: (int) $file->getId(),
				subject: ConformanceReportRepository::SUBJECT_FILE,
				trigger: $trigger,
				verdict: $verdict,
				guidance: $this->guidance->for(verdict: $verdict, origin: $this->guidance->originOf(bytes: $bytes, verdict: $verdict)),
				bytes: $bytes
			)
		);

	}//end checkFile()

	/**
	 * Check the PDF/A-3 bytes a conversion made, before they are returned.
	 *
	 * @param string   $bytes        The converted PDF.
	 * @param string   $origin       ConformanceGuidance::ORIGIN_RENDERED for HTML, ORIGIN_IMPORTED for a wrapped PDF.
	 * @param int|null $sourceFileId The file converted, when there is one; the report is stored on it.
	 *
	 * @return array<string, mixed> The report, with `stored` saying whether it was kept on the source file.
	 *
	 * @throws VeraPdfException When veraPDF gives no verdict.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.5
	 */
	public function checkConversion(string $bytes, string $origin, ?int $sourceFileId): array {
		$verdict = $this->veraPdf->validateBytes(bytes: $bytes, flavour: VeraPdfService::DEFAULT_FLAVOUR);
		$report = $this->report(
			fileId: (int) $sourceFileId,
			subject: ConformanceReportRepository::SUBJECT_CONVERSION,
			trigger: self::TRIGGER_CONVERSION,
			verdict: $verdict,
			guidance: $this->guidance->for(verdict: $verdict, origin: $origin),
			bytes: $bytes
		);
		if ($sourceFileId === null || $sourceFileId <= 0) {
			return $report + ['stored' => false];
		}

		try {
			return $this->reports->save(report: $report) + ['stored' => true];
		} catch (RuntimeException) {
			// The verdict stands without its row; the caller logs it.
			return $report + ['stored' => false];
		}

	}//end checkConversion()

	/**
	 * The reports stored for a file, keyed by subject.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return array<string, array<string, mixed>> The reports.
	 *
	 * @throws RuntimeException When the register cannot be read.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.3
	 */
	public function reportsFor(int $fileId): array {
		return $this->reports->findForFile(fileId: $fileId);

	}//end reportsFor()

	/**
	 * A verdict as the conformanceReport the register takes.
	 *
	 * @param int                  $fileId   The file id.
	 * @param string               $subject  The subject.
	 * @param string               $trigger  The trigger.
	 * @param array<string, mixed> $verdict  The verdict.
	 * @param string               $guidance The guidance key.
	 * @param string               $bytes    The bytes checked.
	 *
	 * @return array<string, mixed> The report.
	 */
	private function report(int $fileId, string $subject, string $trigger, array $verdict, string $guidance, string $bytes): array {
		return [
			'fileId' => $fileId,
			'subject' => $subject,
			'flavour' => (string) $verdict['flavour'],
			'compliant' => (bool) $verdict['compliant'],
			'failedRuleCount' => (int) $verdict['failedRuleCount'],
			'failedRules' => array_values((array) $verdict['failedRules']),
			'fontsNotEmbedded' => array_values((array) $verdict['fontsNotEmbedded']),
			'guidance' => $guidance,
			'validatorVersion' => (string) $verdict['validatorVersion'],
			'validatedAt' => $this->time->getDateTime()->format(DateTimeInterface::ATOM),
			'trigger' => $trigger,
			'checksumSha256' => hash('sha256', $bytes),
			'elapsedMs' => (int) ($verdict['elapsedMs'] ?? 0),
		];

	}//end report()
}//end class
