<?php

/**
 * Print Job Service
 *
 * Manages the print job lifecycle: creation, rendering, batch dispatch, the
 * status a print service reports back, and the download. A job is a
 * `printJob` object in OpenRegister and its PDFs are files in the app data
 * folder; neither lives in app configuration any more.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use OCA\Filinq\BackgroundJob\BatchPrintJob;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;
use ZipArchive;

/**
 * Service for managing print jobs and batch print generation.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-jobs-in-the-app/specs/print-preview/spec.md
 */
class PrintJobService {

	/**
	 * Maximum items rendered during the request; larger batches go to a background job.
	 *
	 * @var int
	 */
	private const SYNC_BATCH_LIMIT = 10;

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
	 * Constructor for PrintJobService
	 *
	 * @param PdfService          $pdfService   Service for PDF generation
	 * @param TemplateService     $templateSvc  Service for template retrieval
	 * @param DataResolverService $dataResolver Resolves an item's dataRefs into template data
	 * @param PrintJobRepository  $jobs         The printJob rows
	 * @param PrintJobFileStore   $files        The PDFs in app data
	 * @param IJobList            $jobList      Nextcloud job list for async dispatch
	 * @param LoggerInterface     $logger       Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly TemplateService $templateSvc,
		private readonly DataResolverService $dataResolver,
		private readonly PrintJobRepository $jobs,
		private readonly PrintJobFileStore $files,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Create a single-document print job.
	 *
	 * @param string $templateId Template UUID to render
	 * @param array  $data       Data context for template rendering
	 * @param array  $options    Print options: format, orientation, pdfa, duplex, color, paperTray, stapling
	 * @param string $userId     UID of the requesting user
	 * @param string $filename   Desired download filename
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @throws Exception If template retrieval or storing the job fails
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function createJob(
		string $templateId,
		array $data = [],
		array $options = [],
		string $userId = '',
		string $filename = 'document.pdf',
	): array {
		return $this->createBatchJob(
			templateId: $templateId,
			items: [['data' => $data, 'filename' => $filename]],
			options: $options,
			userId: $userId,
			filename: $filename
		);

	}//end createJob()

	/**
	 * Create a print job for one or more letters.
	 *
	 * Each item carries `data`, `dataRefs` (resolved like a generation) or
	 * both, and an optional `filename`. Up to SYNC_BATCH_LIMIT items are
	 * rendered now; a larger batch is rendered by BatchPrintJob.
	 *
	 * @param string $templateId Template UUID to render
	 * @param array  $items      The letters
	 * @param array  $options    Print options (same as createJob)
	 * @param string $userId     UID of the requesting user
	 * @param string $filename   Name of the download
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @throws Exception If storing the job fails
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function createBatchJob(
		string $templateId,
		array $items = [],
		array $options = [],
		string $userId = '',
		string $filename = 'print-job.pdf',
	): array {
		$items = array_values($items);
		$now = $this->now();
		$job = $this->jobs->save(
			job: [
				'status' => 'rendering',
				'requestedBy' => $userId,
				'requestedAt' => $now,
				'templateId' => $templateId,
				'filename' => $filename,
				'total' => count($items),
				'rendered' => 0,
				'errors' => 0,
				'files' => [],
				'printConfig' => $this->buildPrintConfig(options: $options),
				'manifest' => [],
				'statusChangedAt' => $now,
			]
		);

		if (count($items) > self::SYNC_BATCH_LIMIT) {
			$this->jobList->add(
				BatchPrintJob::class,
				['jobId' => $job['uuid'], 'templateId' => $templateId, 'items' => $items, 'options' => $options]
			);
		} else {
			$job = $this->renderJob(jobId: $job['uuid'], templateId: $templateId, items: $items, options: $options);
		}

		return [
			'jobId' => (string) $job['uuid'],
			'status' => (string) $job['status'],
			'total' => count($items),
			'printConfig' => $job['printConfig'],
		];

	}//end createBatchJob()

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
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function renderJob(string $jobId, string $templateId, array $items, array $options): array {
		$job = $this->jobs->find(uuid: $jobId);
		if ($job === null) {
			throw new InvalidArgumentException('Print job not found: ' . $jobId);
		}

		try {
			$template = $this->templateSvc->getTemplate(id: $templateId);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Print job template could not be loaded: ' . $e->getMessage(),
				context: ['jobId' => $jobId, 'templateId' => $templateId]
			);
			return $this->changeStatus(job: $job, status: 'failed', details: 'Template not found');
		}

		$outcome = $this->renderItems(jobId: $jobId, template: $template, items: array_values($items), options: $options);

		$job['files'] = $outcome['files'];
		$job['rendered'] = count($outcome['files']);
		$job['errors'] = $outcome['errors'];
		$job['manifest'] = $this->buildManifest(items: $outcome['manifest'], printConfig: (array) ($job['printConfig'] ?? []));

		$status = 'queued';
		if ($outcome['files'] === []) {
			$status = 'failed';
		}

		return $this->changeStatus(job: $job, status: $status, details: null);

	}//end renderJob()

	/**
	 * Retrieve one job.
	 *
	 * @param string $jobId Job uuid
	 *
	 * @return array|null Job data or null if not found
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function getJob(string $jobId): ?array {
		return $this->jobs->find(uuid: $jobId);

	}//end getJob()

	/**
	 * Every job one user sent, newest first.
	 *
	 * @param string $userId The user
	 *
	 * @return array<int, array<string, mixed>> The jobs.
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.3
	 */
	public function listJobs(string $userId): array {
		return $this->jobs->findForUser(userId: $userId);

	}//end listJobs()

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
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function recordExternalStatus(array $job, string $externalStatus, ?string $details): array {
		if (isset(self::EXTERNAL_STATUSES[$externalStatus]) === false) {
			throw new InvalidArgumentException('Invalid status. Valid values: ' . implode(', ', array_keys(self::EXTERNAL_STATUSES)));
		}

		return $this->changeStatus(job: $job, status: self::EXTERNAL_STATUSES[$externalStatus], details: $details);

	}//end recordExternalStatus()

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
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function download(array $job, ?int $item = null): ?array {
		$names = array_values((array) ($job['files'] ?? []));
		$filename = (string) ($job['filename'] ?? 'print-job.pdf');

		if ($item !== null) {
			$names = array_slice($names, $item, 1);
		}

		if (count($names) === 1) {
			$content = $this->files->get(name: (string) $names[0]);
			if ($content === null) {
				return null;
			}

			return ['content' => $content, 'filename' => $filename, 'contentType' => 'application/pdf'];
		}

		if ($names === []) {
			return null;
		}

		return [
			'content' => $this->zip(names: $names, manifest: (array) ($job['manifest'] ?? [])),
			'filename' => preg_replace('/\.pdf$/i', '', $filename) . '.zip',
			'contentType' => 'application/zip',
		];

	}//end download()

	/**
	 * Build a manifest listing all documents with metadata.
	 *
	 * @param array $items       Array of items with 'filename' and 'status' keys
	 * @param array $printConfig Print configuration for all items in the batch
	 *
	 * @return array Manifest array
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function buildManifest(array $items, array $printConfig = []): array {
		$manifest = [];
		foreach ($items as $index => $item) {
			$manifest[] = [
				'index' => $index,
				'filename' => $item['filename'] ?? ('document-' . $index . '.pdf'),
				'status' => $item['status'] ?? 'pending',
				'printConfig' => $printConfig,
				'error' => $item['error'] ?? null,
			];
		}

		return $manifest;

	}//end buildManifest()

	/**
	 * Extract print configuration keys from options array.
	 *
	 * @param array $options Full options array
	 *
	 * @return array{duplex: bool, color: bool, paperTray: string, stapling: bool}
	 *
	 * @spec openspec/changes/print-jobs-in-the-app/tasks.md#task-1.2
	 */
	public function buildPrintConfig(array $options): array {
		return [
			'duplex' => (bool) ($options['duplex'] ?? false),
			'color' => (bool) ($options['color'] ?? true),
			'paperTray' => (string) ($options['paperTray'] ?? 'default'),
			'stapling' => (bool) ($options['stapling'] ?? false),
		];

	}//end buildPrintConfig()

	/**
	 * Render the letters of a job, storing each PDF.
	 *
	 * @param string $jobId    The job uuid
	 * @param array  $template The template
	 * @param array  $items    The letters
	 * @param array  $options  Print options
	 *
	 * @return array{files: array<int, string>, errors: int, manifest: array<int, array<string, mixed>>}
	 */
	private function renderItems(string $jobId, array $template, array $items, array $options): array {
		$pdfOptions = $this->buildPdfOptions(template: $template, options: $options);
		$files = [];
		$manifest = [];
		$errors = 0;

		foreach ($items as $index => $item) {
			$itemFilename = (string) ($item['filename'] ?? ('document-' . $index . '.pdf'));
			try {
				$content = $this->pdfService->renderPdf(
					templateContent: $template['content'] ?? '',
					data: $this->itemData(item: $item),
					options: $pdfOptions
				);
				$files[] = $this->files->put(jobId: $jobId, index: $index, content: $content);
				$manifest[] = ['filename' => $itemFilename, 'status' => 'success'];
			} catch (Exception $e) {
				$errors++;
				$manifest[] = ['filename' => $itemFilename, 'status' => 'error', 'error' => $e->getMessage()];
				$this->logger->warning(
					message: 'Print job letter failed: ' . $e->getMessage(),
					context: ['jobId' => $jobId, 'index' => $index]
				);
			}
		}//end foreach

		return ['files' => $files, 'errors' => $errors, 'manifest' => $manifest];

	}//end renderItems()

	/**
	 * The template data of one letter: its resolved dataRefs with its data on top.
	 *
	 * @param array $item The letter
	 *
	 * @return array<string, mixed> The data.
	 */
	private function itemData(array $item): array {
		$data = (array) ($item['data'] ?? []);
		$refs = (array) ($item['dataRefs'] ?? []);
		if ($refs === []) {
			return $data;
		}

		$resolution = $this->dataResolver->resolve(dataRefs: $refs, adHocData: $data);
		if ($resolution['errors'] !== []) {
			throw new InvalidArgumentException('Data could not be resolved: ' . (string) ($resolution['errors'][0]['message'] ?? ''));
		}

		return $resolution['data'];

	}//end itemData()

	/**
	 * The PDF options from the template and the request.
	 *
	 * @param array $template The template
	 * @param array $options  The request options
	 *
	 * @return array<string, mixed> The options for PdfService.
	 */
	private function buildPdfOptions(array $template, array $options): array {
		$pdfOptions = array_merge(
			[
				'format' => $template['format'] ?? 'A4',
				'orientation' => $template['orientation'] ?? 'P',
				'duplex' => $template['duplex'] ?? false,
				'color' => $template['color'] ?? true,
				'paperTray' => $template['paperTray'] ?? 'default',
				'stapling' => $template['stapling'] ?? false,
			],
			$options
		);
		$pdfOptions['title'] = $template['name'] ?? 'document';
		$pdfOptions['pdfa'] = ($options['pdfa'] ?? false) === true;

		return $pdfOptions;

	}//end buildPdfOptions()

	/**
	 * Set a job's status and when it changed, and store it.
	 *
	 * @param array       $job     The job
	 * @param string      $status  The new status
	 * @param string|null $details What the print service said, or why the job failed
	 *
	 * @return array<string, mixed> The stored job.
	 */
	private function changeStatus(array $job, string $status, ?string $details): array {
		$uuid = (string) ($job['uuid'] ?? '');
		$job['status'] = $status;
		$job['statusChangedAt'] = $this->now();
		if ($details !== null) {
			$job['statusDetails'] = mb_substr($details, 0, 4096);
		}

		return $this->jobs->save(job: $job, uuid: $uuid);

	}//end changeStatus()

	/**
	 * Pack the PDFs of a job and its manifest into one ZIP.
	 *
	 * @param array<int, string> $names    The stored file names
	 * @param array              $manifest The job manifest
	 *
	 * @return string The ZIP bytes.
	 */
	private function zip(array $names, array $manifest): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-print-');
		$zip = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		foreach ($names as $name) {
			$zip->addFromString($name, (string) $this->files->get(name: $name));
		}

		$zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT));
		$zip->close();
		$content = (string) file_get_contents($path);
		unlink($path);

		return $content;

	}//end zip()

	/**
	 * The current time as the register stores it.
	 *
	 * @return string An ISO 8601 date-time.
	 */
	private function now(): string {
		return (new DateTimeImmutable())->format(DateTimeInterface::ATOM);

	}//end now()
}//end class
