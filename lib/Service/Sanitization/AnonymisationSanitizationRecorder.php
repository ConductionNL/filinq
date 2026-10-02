<?php

/**
 * Anonymisation sanitization recorder
 *
 * OpenRegister sanitises every office document it anonymises and keeps a
 * report of what it removed. This keeps that report as a sanitizationRecord
 * (trigger anonymisation) and puts it on the run result. A run that produced
 * no report (plain text, PDF) records nothing, and a PDF made from a cleaned
 * document is never recorded as sanitized.
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
	 * Keep the run's report.
	 *
	 * @param array<string, mixed> $resultInfo The run result, with anonymizedFileId and anonymizedFileName.
	 * @param array<string, mixed> $context    fileId, userId, sanitizationReport (OpenRegister's, or null).
	 *
	 * @return array<string, mixed> The result, with sanitizationReport and, when the record failed, sanitizationWarning.
	 *
	 * @spec openspec/changes/document-sanitization/tasks.md#3-3
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
		if (isset($resultInfo['sanitizationReport']) === true && $outputId > 0 && $pdfOutput === false) {
			$resultInfo = $this->keep(resultInfo: $resultInfo, context: $context, outputId: $outputId);
		}

		return $resultInfo;

	}//end record()

	/**
	 * Write the record of an office run against its anonymised file.
	 *
	 * @param array<string, mixed> $resultInfo The run result, carrying sanitizationReport.
	 * @param array<string, mixed> $context    fileId and userId.
	 * @param int                  $outputId   The anonymised file.
	 *
	 * @return array<string, mixed> The result, with a warning when the record could not be written.
	 */
	private function keep(array $resultInfo, array $context, int $outputId): array {
		$report = $resultInfo['sanitizationReport'];
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

		return $resultInfo;

	}//end keep()
}//end class
