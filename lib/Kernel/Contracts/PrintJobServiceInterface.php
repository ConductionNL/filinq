<?php

/**
 * Contract of PrintJobService
 *
 * The public surface of the PrintJobService service, so callers in other
 * components depend on this contract instead of the implementation.
 *
 * @category  Kernel
 * @package   OCA\Filinq\Kernel\Contracts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Kernel\Contracts;

use Exception;
use InvalidArgumentException;
use OCA\Filinq\Kernel\PrintJobRequest;

/**
 * Contract of PrintJobService.
 *
 * @category Kernel
 * @package  OCA\Filinq\Kernel\Contracts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude Structural contract, no functional requirement.
 */
interface PrintJobServiceInterface {
	/**
	 * What a print service may report, and the job status each one means.
	 *
	 * @var array<string, string>
	 */
	public const EXTERNAL_STATUSES = [
		'printing' => 'sent',
		'sent' => 'sent',
		'printed' => 'printed',
		'failed' => 'failed',
	];

	/**
	 * Create a single-document print job.
	 *
	 * Without a download name the job and its letter are document.pdf.
	 *
	 * @param PrintJobRequest      $request The template, options and download name
	 * @param array<string, mixed> $data    Data context for template rendering
	 * @param string               $userId  UID of the requesting user
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @throws Exception If template retrieval or storing the job fails
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function createJob(PrintJobRequest $request, array $data, string $userId): array;

	/**
	 * Create a print job for one or more letters.
	 *
	 * Each item carries `data`, `dataRefs` (resolved like a generation) or
	 * both, and an optional `filename`. Up to SYNC_BATCH_LIMIT items are
	 * rendered now; a larger batch is rendered by BatchPrintJob. Without a
	 * download name the job is print-job.pdf.
	 *
	 * @param PrintJobRequest $request The template, options and download name
	 * @param array           $items   The letters
	 * @param string          $userId  UID of the requesting user
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @throws Exception If storing the job fails
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function createBatchJob(PrintJobRequest $request, array $items, string $userId): array;

	/**
	 * Render every letter of a job into app data and move it to queued.
	 *
	 * A job whose template cannot be loaded, or whose letters all fail, is
	 * failed; a job with some failed letters is queued with the rest.
	 *
	 * @param string $jobId      The job uuid
	 * @param string $templateId Template UUID
	 * @param array  $items      The letters
	 * @param array  $options    Print options
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws Exception If the job cannot be read or stored
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function renderJob(string $jobId, string $templateId, array $items, array $options): array;

	/**
	 * Retrieve one job.
	 *
	 * @param string $jobId Job uuid
	 *
	 * @return array|null Job data or null if not found
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function getJob(string $jobId): ?array;

	/**
	 * Every job one user sent, newest first.
	 *
	 * @param string $userId The user
	 *
	 * @return array<int, array<string, mixed>> The jobs.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.3
	 */
	public function listJobs(string $userId): array;

	/**
	 * Record what the print service reported.
	 *
	 * @param array       $job            The job as read
	 * @param string      $externalStatus One of the keys of EXTERNAL_STATUSES
	 * @param string|null $details        What the service said, if anything
	 *
	 * @return array<string, mixed> The stored job.
	 *
	 * @throws InvalidArgumentException When the status is not one a print service may report
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function recordExternalStatus(array $job, string $externalStatus, ?string $details): array;

	/**
	 * The download of a job: the PDF of a one-letter job, a ZIP of all PDFs
	 * and the manifest otherwise, or one letter when an index is given.
	 *
	 * @param array    $job  The job
	 * @param int|null $item The letter to download, or null for the whole job
	 *
	 * @return array{content: string, filename: string, contentType: string}|null
	 *         The download, or null when there is nothing to download.
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function download(array $job, ?int $item = null): ?array;

	/**
	 * Build a manifest listing all documents with metadata.
	 *
	 * @param array $items       Array of items with 'filename' and 'status' keys
	 * @param array $printConfig Print configuration for all items in the batch
	 *
	 * @return array Manifest array
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function buildManifest(array $items, array $printConfig = []): array;

	/**
	 * Extract print configuration keys from options array.
	 *
	 * @param array $options Full options array
	 *
	 * @return array{duplex: bool, color: bool, paperTray: string, stapling: bool}
	 *
	 * @spec openspec/changes/archive/2026-09-29-print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function buildPrintConfig(array $options): array;
}//end interface
