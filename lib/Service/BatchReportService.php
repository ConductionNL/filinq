<?php

/**
 * Batch Report Service
 *
 * Produces a CSV summary of a completed anonymization batch, listing each
 * file's identifiers, entity/replacement counts, final status, and
 * creation timestamp.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCA\Filinq\Kernel\Contracts\BatchReportServiceInterface;

/**
 * Generates CSV reports summarizing the outcome of a batch.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class BatchReportService implements BatchReportServiceInterface {
	/**
	 * Constructor for BatchReportService
	 *
	 * @param BatchStateService       $stateService      Service that exposes stored batch state.
	 * @param ReportOrganisationLabel $organisationLabel Names the organisation the batch belongs to.
	 * @param BatchReportAggregator   $aggregator        The read-time summary of a batch.
	 * @param BatchQaOutcomes|null    $qaOutcomes        Reads each QA sample's outcome from its review;
	 *                                                   null reports the stored outcome.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateService $stateService,
		private readonly ReportOrganisationLabel $organisationLabel,
		private readonly BatchReportAggregator $aggregator = new BatchReportAggregator(),
		private readonly ?BatchQaOutcomes $qaOutcomes = null,
	) {

	}//end __construct()

	/**
	 * Generate a CSV report for a completed batch.
	 *
	 * @param string $batchId Identifier of the batch to report on.
	 *
	 * @return string CSV document (header row + one row per file). Every row
	 *                names the organisation the batch belongs to, by name and
	 *                UUID, so the report states its scope (REQ-DDMTH-005).
	 *
	 * @throws Exception When the batch cannot be found or is not yet completed.
	 *
	 * @spec openspec/specs/batch-anonymization/spec.md#requirement-batch-completion-report
	 * @spec openspec/changes/multi-tenant-hardening/tasks.md#3-backend-per-organisation-behaviour
	 */
	public function generateReport(string $batchId): string {
		$batch = $this->stateService->getBatch($batchId);
		if ($batch === null) {
			throw new Exception('Batch not found or expired', 404);
		}

		if ($batch['status'] !== 'completed') {
			throw new Exception('Batch is not yet completed', 409);
		}

		$organisation = $this->organisationLabel->describe(uuid: $batch['organisation'] ?? null);
		if ($this->qaOutcomes !== null) {
			$batch = $this->qaOutcomes->refresh(batch: $batch);
		}

		$output = fopen('php://temp', 'r+');
		fputcsv(
			$output,
			[
				'fileName',
				'originalFileId',
				'anonymizedFileId',
				'entityCount',
				'replacementCount',
				'status',
				'timestamp',
				'reason',
				'qaOutcome',
				'organisationName',
				'organisationUuid',
			]
		);
		$timestamp = date('c', $batch['createdAt'] ?? time());
		foreach ($batch['files'] as $f) {
			fputcsv($output, $this->fileRow(file: $f, timestamp: $timestamp, organisation: $organisation));
		}

		$summary = $this->aggregator->summarise(batch: $batch);
		$summary['qaSampling'] = 'enabled';
		if (isset($batch['qaSampleRate']) === true && (int)$batch['qaSampleRate'] === 0) {
			$summary['qaSampling'] = 'disabled';
		}

		$this->writeSummary(output: $output, summary: $summary);

		rewind($output);
		$csv = stream_get_contents($output);
		fclose($output);
		return (string)$csv;
	}//end generateReport()

	/**
	 * One file's row of the report, in the header's column order.
	 *
	 * @param array<string, mixed>                $file         The per-file entry.
	 * @param string                              $timestamp    The batch's creation time, ISO 8601.
	 * @param array{name: string, uuid: string} $organisation The organisation the batch belongs to.
	 *
	 * @return array<int, mixed>
	 */
	private function fileRow(array $file, string $timestamp, array $organisation): array {
		return [
			$file['fileName'] ?? '',
			$file['fileId'] ?? '',
			$file['anonymizedFileId'] ?? '',
			$file['entityCount'] ?? 0,
			$file['replacementCount'] ?? 0,
			$file['status'] ?? '',
			$timestamp,
			$this->reasonColumn(file: $file),
			$file['qaOutcome'] ?? '',
			$organisation['name'],
			$organisation['uuid'],
		];
	}//end fileRow()

	/**
	 * The failure group of a failed file, empty for a file that did not fail.
	 *
	 * @param array<string, mixed> $file The per-file entry.
	 *
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) BatchReportAggregator::reasonOf() is a pure function of
	 * the file entry, shared with the summary.
	 */
	private function reasonColumn(array $file): string {
		if (($file['status'] ?? '') !== 'error') {
			return '';
		}

		return BatchReportAggregator::reasonOf(file: $file);
	}//end reasonColumn()

	/**
	 * Write the read-time summary below the per-file rows (redaction-at-scale
	 * REQ-DDRAS-006): totals, documents per hour, phase durations, failures
	 * per reason and the QA sample outcomes. Entity values are not in it.
	 *
	 * @param resource             $output  The CSV stream.
	 * @param array<string, mixed> $summary BatchReportAggregator::summarise().
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-at-scale/specs/redaction-at-scale/spec.md#requirement-throughput-and-failure-reporting-req-ddras-006
	 */
	private function writeSummary($output, array $summary): void {
		fputcsv($output, []);
		fputcsv($output, ['summary', 'files', $summary['files']]);
		fputcsv($output, ['summary', 'succeeded', $summary['succeeded']]);
		fputcsv($output, ['summary', 'failed', $summary['failed']]);
		fputcsv($output, ['summary', 'documentsPerHour', $summary['documentsPerHour'] ?? '']);
		fputcsv($output, ['summary', 'extractMs', $summary['phaseMs']['extract'] ?? '']);
		fputcsv($output, ['summary', 'anonymizeMs', $summary['phaseMs']['anonymize'] ?? '']);
		foreach ($summary['failures'] as $reason => $count) {
			fputcsv($output, ['failures', (string)$reason, $count]);
		}

		if (($summary['qaSampling'] ?? '') === 'disabled') {
			fputcsv($output, ['summary', 'qaSampling', 'disabled']);
		}

		foreach ($summary['qaSamples'] as $fileId => $outcome) {
			fputcsv($output, ['qaSample', (string)$fileId, $outcome]);
		}
	}//end writeSummary()
}//end class
