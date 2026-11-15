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
	 *
	 * @return void
	 */
	public function __construct(
		private readonly BatchStateService $stateService,
		private readonly ReportOrganisationLabel $organisationLabel,
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
				'organisationName',
				'organisationUuid',
			]
		);
		foreach ($batch['files'] as $f) {
			fputcsv(
				$output,
				[
					$f['fileName'] ?? '',
					$f['fileId'] ?? '',
					$f['anonymizedFileId'] ?? '',
					$f['entityCount'] ?? 0,
					$f['replacementCount'] ?? 0,
					$f['status'] ?? '',
					date('c', $batch['createdAt'] ?? time()),
					$organisation['name'],
					$organisation['uuid'],
				]
			);
		}

		rewind($output);
		$csv = stream_get_contents($output);
		fclose($output);
		return $csv;
	}//end generateReport()
}//end class
