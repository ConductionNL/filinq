<?php

/**
 * Filinq PdfUaReadinessCheck
 *
 * The PDF/UA part of publication readiness: every PDF of a record whose
 * latest PDF/UA-1 report is not compliant, or that has no report, is a
 * readiness reason naming the file by id. The reason warns while the
 * profile severity of `pdfua-conformance-failed` is below `blocking`; at
 * `blocking` it stops the hand-off. A missing report counts as not passed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-publication-readiness-counts-a-pdfua-verdict-that-did-not-pass-req-ddpum-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Service\VeraPdf\AccessibilityConformanceService;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Throwable;

/**
 * Which PDFs of a record did not pass PDF/UA, and whether that blocks.
 */
class PdfUaReadinessCheck {

	/**
	 * Every PDF passed (or the record has no PDF).
	 */
	public const GATE_CLEAR = 'clear';

	/**
	 * A PDF did not pass; the hand-off may go on.
	 */
	public const GATE_WARNING = 'warning';

	/**
	 * A PDF did not pass and the profile makes that block the hand-off.
	 */
	public const GATE_BLOCKING = 'blocking';

	/**
	 * Constructor.
	 *
	 * @param AccessibilityConformanceService    $conformance The stored PDF/UA reports.
	 * @param IRootFolder                        $rootFolder  Tells PDFs from other files.
	 * @param DocumentValidationServiceInterface $validation  The validation profile, for the severity.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly AccessibilityConformanceService $conformance,
		private readonly IRootFolder $rootFolder,
		private readonly DocumentValidationServiceInterface $validation,
	) {

	}//end __construct()

	/**
	 * The PDF/UA gate of a record's files.
	 *
	 * @param array<int, string> $fileIds      The record's files; empty and repeated ids are skipped.
	 * @param string             $documentType The record's document type, '' for the default profile.
	 *
	 * @return array{gate: string, reasons: list<string>} The gate and one reason per PDF that did not pass.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-publication-readiness-counts-a-pdfua-verdict-that-did-not-pass-req-ddpum-007
	 */
	public function evaluate(array $fileIds, string $documentType): array {
		$reasons = [];
		foreach (array_unique(array_filter($fileIds, static fn (string $id): bool => (int) $id > 0)) as $fileId) {
			if ($this->isPdf(fileId: (int) $fileId) === false) {
				continue;
			}

			$compliant = $this->compliance(fileId: (int) $fileId);
			if ($compliant === null) {
				$reasons[] = 'PDF ' . $fileId . ' has not been checked against PDF/UA';
			} else if ($compliant === false) {
				$reasons[] = 'PDF ' . $fileId . ' does not conform to PDF/UA';
			}
		}

		if ($reasons === []) {
			return ['gate' => self::GATE_CLEAR, 'reasons' => []];
		}

		return ['gate' => $this->gateFor(documentType: $documentType), 'reasons' => $reasons];

	}//end evaluate()

	/**
	 * Whether a stored gate lets the record through.
	 *
	 * @param string $gate A GATE_* value; anything unknown is treated as clear.
	 *
	 * @return bool False only for GATE_BLOCKING.
	 *
	 * @spec openspec/changes/pdfua-verapdf-matterhorn/specs/pdfua-verapdf-matterhorn/spec.md#requirement-publication-readiness-counts-a-pdfua-verdict-that-did-not-pass-req-ddpum-007
	 */
	public static function lets(string $gate): bool {
		return $gate !== self::GATE_BLOCKING;

	}//end lets()

	/**
	 * Warning or blocking, by the profile severity of the conformance check.
	 *
	 * @param string $documentType The document type.
	 *
	 * @return string GATE_WARNING or GATE_BLOCKING.
	 */
	private function gateFor(string $documentType): string {
		try {
			$profile = $this->validation->resolveProfile(documentType: $documentType);
		} catch (Throwable) {
			return self::GATE_WARNING;
		}

		$severity = (string) ($profile['severities'][DocumentValidationServiceInterface::CHECK_PDFUA_CONFORMANCE] ?? '');
		if ($severity === DocumentValidationServiceInterface::SEVERITY_BLOCKING) {
			return self::GATE_BLOCKING;
		}

		return self::GATE_WARNING;

	}//end gateFor()

	/**
	 * The stored verdict of a file: true, false, or null when there is none.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool|null The verdict; a report that cannot be read counts as none.
	 */
	private function compliance(int $fileId): ?bool {
		try {
			$report = $this->conformance->reportFor(fileId: $fileId);
		} catch (Throwable) {
			return null;
		}

		if ($report === null || is_bool($report['compliant'] ?? null) === false) {
			return null;
		}

		return $report['compliant'];

	}//end compliance()

	/**
	 * Whether a file is a PDF.
	 *
	 * @param int $fileId The file id.
	 *
	 * @return bool True for a PDF that can be found.
	 */
	private function isPdf(int $fileId): bool {
		try {
			$node = $this->rootFolder->getFirstNodeById($fileId);
		} catch (Throwable) {
			return false;
		}

		return $node instanceof File && $node->getMimetype() === 'application/pdf';

	}//end isPdf()
}//end class
