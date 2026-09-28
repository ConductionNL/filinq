<?php

/**
 * Migrate print jobs out of app configuration
 *
 * Print jobs used to be `print_job_<id>` JSON strings in app configuration,
 * with every PDF base64-encoded under `print_job_pdf_<id>` (and
 * `print_job_pdf_<id>-<n>` per letter of a batch). `oc_appconfig` is loaded on
 * every request, so each printed letter made every page slower. This step
 * moves each job to a `printJob` object under the same id, its PDFs to the
 * app data folder, and then deletes the entries.
 *
 * Safe to run again: an entry is deleted only after its object was stored, so
 * a run before the register import (no `printJob` schema yet) moves nothing,
 * says so, and the next repair pass does the move.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version GIT: <git-id>
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Repair;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Filinq\Service\PrintJobFileStore;
use OCA\Filinq\Service\PrintJobRepository;
use OCA\Filinq\Service\PrintJobService;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Moves legacy print jobs from app configuration to OpenRegister and app data.
 *
 * @category Repair
 * @package  OCA\Filinq\Repair
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 */
class MigratePrintJobsOutOfAppConfig implements IRepairStep {

	/**
	 * Key prefix of a legacy job.
	 */
	private const JOB_PREFIX = 'print_job_';

	/**
	 * Key prefix of a legacy PDF.
	 */
	private const PDF_PREFIX = 'print_job_pdf_';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig         $appConfig The app configuration holding the legacy entries
	 * @param PrintJobRepository $jobs      The printJob rows
	 * @param PrintJobFileStore  $files     The PDFs in app data
	 * @param LoggerInterface    $logger    Logger
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly PrintJobRepository $jobs,
		private readonly PrintJobFileStore $files,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The name shown during the repair run.
	 *
	 * @return string The name.
	 */
	public function getName(): string {
		return 'Move Filinq print jobs out of app configuration';

	}//end getName()

	/**
	 * Move every legacy job.
	 *
	 * @param IOutput $output The repair output
	 *
	 * @return void
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function run(IOutput $output): void {
		$keys = $this->appConfig->getKeys('filinq');
		$moved = 0;
		$left = 0;

		foreach ($keys as $key) {
			if (str_starts_with($key, self::JOB_PREFIX) === false || str_starts_with($key, self::PDF_PREFIX) === true) {
				continue;
			}

			if ($this->moveJob(jobId: substr($key, strlen(self::JOB_PREFIX)), keys: $keys) === true) {
				$moved++;
			} else {
				$left++;
			}
		}

		$output->info('Print jobs moved out of app configuration: ' . $moved . ', left for a later run: ' . $left);

	}//end run()

	/**
	 * Move one job and its PDFs, then delete its entries.
	 *
	 * @param string             $jobId The legacy job id, kept as the object uuid
	 * @param array<int, string> $keys  Every app config key of this app
	 *
	 * @return bool True when the job was moved and its entries deleted.
	 */
	private function moveJob(string $jobId, array $keys): bool {
		$legacy = json_decode($this->appConfig->getValueString('filinq', self::JOB_PREFIX . $jobId, ''), true);
		if (is_array($legacy) === false) {
			$legacy = [];
		}

		$pdfKeys = $this->pdfKeys(jobId: $jobId, keys: $keys);

		try {
			$files = [];
			foreach (array_values($pdfKeys) as $index => $pdfKey) {
				$content = base64_decode($this->appConfig->getValueString('filinq', $pdfKey, ''), true);
				if ($content !== false && $content !== '') {
					$files[] = $this->files->put(jobId: $jobId, index: $index, content: $content);
				}
			}

			$this->jobs->save(job: $this->toPrintJob(legacy: $legacy, files: $files), uuid: $jobId);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: 'Print job ' . $jobId . ' stays in app configuration for now: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
			return false;
		}

		$this->appConfig->deleteKey('filinq', self::JOB_PREFIX . $jobId);
		foreach ($pdfKeys as $pdfKey) {
			$this->appConfig->deleteKey('filinq', $pdfKey);
		}

		return true;

	}//end moveJob()

	/**
	 * The PDF keys of one job, in letter order.
	 *
	 * @param string             $jobId The legacy job id
	 * @param array<int, string> $keys  Every app config key of this app
	 *
	 * @return array<int, string> The keys.
	 */
	private function pdfKeys(string $jobId, array $keys): array {
		$single = self::PDF_PREFIX . $jobId;
		$found = [];
		foreach ($keys as $key) {
			if ($key === $single) {
				$found[-1] = $key;
			} else if (preg_match('/^' . preg_quote($single . '-', '/') . '(\d+)$/', $key, $match) === 1) {
				$found[(int) $match[1]] = $key;
			}
		}

		ksort($found);

		return array_values($found);

	}//end pdfKeys()

	/**
	 * The printJob object for a legacy entry.
	 *
	 * @param array<string, mixed> $legacy The decoded legacy JSON
	 * @param array<int, string>   $files  The PDFs stored for it
	 *
	 * @return array<string, mixed> The object.
	 */
	public function toPrintJob(array $legacy, array $files): array {
		$now = (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
		$job = [
			'status' => $this->status(legacy: $legacy, hasFiles: $files !== []),
			'requestedBy' => (string) ($legacy['ownerUserId'] ?? ''),
			'requestedAt' => $now,
			'filename' => (string) ($legacy['filename'] ?? 'print-job.pdf'),
			'total' => max(0, (int) ($legacy['total'] ?? count($files))),
			'rendered' => count($files),
			'errors' => max(0, (int) ($legacy['errors'] ?? 0)),
			'files' => $files,
			'printConfig' => (array) ($legacy['printConfig'] ?? []),
			'manifest' => array_values((array) ($legacy['manifest'] ?? [])),
			'statusChangedAt' => $now,
		];

		$details = $legacy['externalDetails'] ?? ($legacy['error'] ?? null);
		if ($details !== null) {
			$job['statusDetails'] = mb_substr(is_string($details) === true ? $details : (string) json_encode($details), 0, 4096);
		}

		return $job;

	}//end toPrintJob()

	/**
	 * The new status of a legacy job.
	 *
	 * @param array<string, mixed> $legacy   The decoded legacy JSON
	 * @param bool                 $hasFiles Whether any PDF was found
	 *
	 * @return string The status.
	 */
	private function status(array $legacy, bool $hasFiles): string {
		$external = (string) ($legacy['externalStatus'] ?? '');
		if (isset(PrintJobService::EXTERNAL_STATUSES[$external]) === true) {
			return PrintJobService::EXTERNAL_STATUSES[$external];
		}

		$legacyStatus = (string) ($legacy['status'] ?? '');
		if ($legacyStatus === 'failed') {
			return 'failed';
		}

		if ($legacyStatus === 'completed' && $hasFiles === true) {
			return 'queued';
		}

		if ($legacyStatus === 'completed') {
			return 'failed';
		}

		// queued or processing: the background job still holds the letters
		// and renders them into this object under the same id.
		return 'rendering';

	}//end status()
}//end class
