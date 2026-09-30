<?php

/**
 * Anonymisation sanitization recorder
 *
 * OpenRegister sanitises every office document it anonymises and keeps a
 * report of what it removed. This keeps that report as a sanitizationRecord
 * (trigger anonymisation) and puts it on the run result. A run that produced
 * no report (plain text, PDF) records nothing. With `sanitize` asked for, a
 * PDF result is flagged: OpenRegister cannot clean PDFs yet, so the file is
 * kept and the response says it was not sanitized.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Sanitization
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/document-sanitization/tasks.md#3-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Sanitization;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Records the sanitization side of an anonymisation run.
 */
class AnonymisationSanitizationRecorder {

	/**
	 * Constructor.
	 *
	 * @param SanitizationRecordRepository $records The run records.
	 * @param LoggerInterface              $logger  The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SanitizationRecordRepository $records,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Keep the run's report and flag a sanitize request that could not be met.
	 *
	 * @param array<string, mixed> $resultInfo The run result, with anonymizedFileId and anonymizedFileName.
	 * @param array<string, mixed> $context    fileId, userId, sanitizationReport (OpenRegister's, or null), sanitize.
	 *
	 * @return array<string, mixed> The result, with sanitizationReport and, when needed, sanitizationWarning.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-3
	 * @spec openspec/changes/document-sanitization/tasks.md#3-4
	 */
	public function record(array $resultInfo, array $context): array {
		$report = $context['sanitizationReport'] ?? null;
		$outputId = (int) ($resultInfo['anonymizedFileId'] ?? 0);
		$pdfOutput = str_ends_with(strtolower((string) ($resultInfo['anonymizedFileName'] ?? '')), '.pdf');
		if (is_array($report) === true && $report !== []) {
			$resultInfo['sanitizationReport'] = $report;
		}

		// A PDF made from the cleaned document carries metadata the conversion
		// wrote, so no record may call that PDF sanitized.
		if (is_array($report) === true && $report !== [] && $outputId > 0 && $pdfOutput === false) {
			try {
				$this->records->save(
					record: [
						'fileId'          => (int) ($context['fileId'] ?? 0),
						'sanitizedFileId' => $outputId,
						'trigger'         => 'anonymisation',
						'engine'          => 'OfficeDocumentSanitizer',
						'report'          => $report,
						'sanitizedAt'     => gmdate(format: 'Y-m-d\TH:i:s\Z'),
						'sanitizedBy'     => (string) ($context['userId'] ?? ''),
					]
				);
			} catch (Throwable $e) {
				// The anonymised file stands; only the evidence row is missing.
				$this->logger->warning('Sanitization record not written', ['fileId' => $context['fileId'] ?? null, 'exception' => $e->getMessage()]);
				$resultInfo['sanitizationWarning'] = ['reason' => 'record_not_written'];
			}
		}

		if (($context['sanitize'] ?? false) === true && ($pdfOutput === true || isset($resultInfo['sanitizationReport']) === false)) {
			// The final artifact was asked to be clean and is not: keep it, say so.
			$resultInfo['sanitizationWarning'] = ['reason' => $this->unmetReason(pdfOutput: $pdfOutput)];
		}

		return $resultInfo;

	}//end record()

	/**
	 * Why a requested sanitization did not happen.
	 *
	 * @param bool $pdfOutput Whether the delivered file is a PDF.
	 *
	 * @return string The reason.
	 */
	private function unmetReason(bool $pdfOutput): string {
		if ($pdfOutput === true) {
			return 'pdf_sanitizer_unavailable';
		}

		return 'nothing_to_sanitize';

	}//end unmetReason()
}//end class
