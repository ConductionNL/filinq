<?php

/**
 * Print Job Service
 *
 * Manages print job lifecycle: creation, queuing, batch dispatch, and manifest
 * generation. Uses IAppConfig for transient job status persistence, following
 * the same pattern as CorrespondenceService.
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
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\BackgroundJob\IJobList;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing print jobs and batch print generation
 *
 * Handles job creation, status tracking via IAppConfig, and dispatching
 * large batches to the BatchPrintJob background job.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-3
 */
class PrintJobService {

	/**
	 * Maximum items for synchronous batch processing.
	 *
	 * @var int
	 */
	private const SYNC_BATCH_LIMIT = 10;

	/**
	 * App config key prefix for print jobs.
	 *
	 * @var string
	 */
	private const JOB_KEY_PREFIX = 'print_job_';

	/**
	 * Constructor for PrintJobService
	 *
	 * @param PdfService $pdfService Service for PDF generation
	 * @param TemplateService $templateSvc Service for template retrieval
	 * @param ContainerInterface $container DI container for IAppConfig access
	 * @param IJobList $jobList Nextcloud job list for async dispatch
	 * @param LoggerInterface $logger Logger for error reporting
	 *
	 * @return void
	 */
	public function __construct(
		private readonly PdfService $pdfService,
		private readonly TemplateService $templateSvc,
		private readonly ContainerInterface $container,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Generate a unique job ID
	 *
	 * @return string UUID v4 format job identifier
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function generateJobId(): string {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);

	}//end generateJobId()

	/**
	 * Create a single-document print job
	 *
	 * Generates the PDF immediately (synchronous), stores the result and
	 * returns job info including the print configuration.
	 *
	 * @param string $templateId Template UUID to render
	 * @param array $data Data context for template rendering
	 * @param array $options Print options: format, orientation, pdfa, cropMarks,
	 *                       duplex, color, paperTray, stapling, author, caseReference
	 * @param string $userId UID of the requesting user (for ownership check)
	 * @param string $filename Desired download filename
	 *
	 * @return array{jobId: string, status: string, printConfig: array}
	 *
	 * @throws Exception If template retrieval or PDF generation fails
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function createJob(
		string $templateId,
		array $data = [],
		array $options = [],
		string $userId = '',
		string $filename = 'document.pdf',
	): array {
		$jobId = $this->generateJobId();
		$template = $this->templateSvc->getTemplate(id: $templateId);

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

		$pdfContent = $this->pdfService->renderPdf(
			templateContent: $template['content'] ?? '',
			data: $data,
			options: $pdfOptions
		);
		$printConfig = $this->buildPrintConfig(options: $pdfOptions);

		$jobData = [
			'status' => 'completed',
			'total' => 1,
			'completed' => 1,
			'errors' => 0,
			'filename' => $filename,
			'ownerUserId' => $userId,
			'printConfig' => $printConfig,
			'manifest' => $this->buildManifest(
				items: [['filename' => $filename, 'status' => 'success']],
				printConfig: $printConfig
			),
		];

		$this->storeJobStatus(jobId: $jobId, data: $jobData);
		$this->storeJobPdf(jobId: $jobId, content: $pdfContent);

		return [
			'jobId' => $jobId,
			'status' => 'completed',
			'printConfig' => $printConfig,
		];

	}//end createJob()

	/**
	 * Create and dispatch a batch print job
	 *
	 * For batches of SYNC_BATCH_LIMIT or fewer items, generates PDFs synchronously.
	 * For larger batches, dispatches a BatchPrintJob background job.
	 *
	 * @param string $templateId Template UUID to render
	 * @param array $items Array of items, each with optional 'data' and 'filename' keys
	 * @param array $options Print options (same as createJob)
	 * @param string $userId UID of the requesting user
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @throws Exception If template retrieval fails
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function createBatchJob(
		string $templateId,
		array $items = [],
		array $options = [],
		string $userId = '',
	): array {
		$count = count($items);

		if ($count <= self::SYNC_BATCH_LIMIT) {
			return $this->processBatchSync(
				templateId: $templateId,
				items: $items,
				options: $options,
				userId: $userId
			);
		}

		return $this->dispatchBatchJob(
			templateId: $templateId,
			items: $items,
			options: $options,
			userId: $userId
		);

	}//end createBatchJob()

	/**
	 * Process a batch synchronously
	 *
	 * @param string $templateId Template UUID
	 * @param array $items Array of items to process
	 * @param array $options Print options
	 * @param string $userId Requesting user UID
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	private function processBatchSync(
		string $templateId,
		array $items,
		array $options,
		string $userId,
	): array {
		$jobId = $this->generateJobId();
		$template = $this->templateSvc->getTemplate(id: $templateId);

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

		$printConfig = $this->buildPrintConfig(options: $pdfOptions);
		$manifestItems = [];
		$completed = 0;
		$errors = 0;

		foreach ($items as $item) {
			$itemData = $item['data'] ?? [];
			$itemFilename = $item['filename'] ?? ('document-' . $completed . '.pdf');

			try {
				$pdfContent = $this->pdfService->renderPdf(
					templateContent: $template['content'] ?? '',
					data: $itemData,
					options: $pdfOptions
				);
				$manifestItems[] = [
					'filename' => $itemFilename,
					'status' => 'success',
				];
				$this->storeJobPdf(jobId: $jobId . '-' . $completed, content: $pdfContent);
				$completed++;
			} catch (Exception $e) {
				$manifestItems[] = [
					'filename' => $itemFilename,
					'status' => 'error',
					'error' => $e->getMessage(),
				];
				$errors++;
				$this->logger->warning(
					message: 'Batch print failed for item: ' . $e->getMessage(),
					context: ['jobId' => $jobId, 'filename' => $itemFilename]
				);
			}//end try
		}//end foreach

		$manifest = $this->buildManifest(items: $manifestItems, printConfig: $printConfig);

		$this->storeJobStatus(
			jobId: $jobId,
			data: [
				'status' => 'completed',
				'total' => count($items),
				'completed' => $completed,
				'errors' => $errors,
				'ownerUserId' => $userId,
				'printConfig' => $printConfig,
				'manifest' => $manifest,
			]
		);

		return [
			'jobId' => $jobId,
			'status' => 'completed',
			'total' => count($items),
			'printConfig' => $printConfig,
		];

	}//end processBatchSync()

	/**
	 * Dispatch a large batch to a background job
	 *
	 * @param string $templateId Template UUID
	 * @param array $items Array of items
	 * @param array $options Print options
	 * @param string $userId Requesting user UID
	 *
	 * @return array{jobId: string, status: string, total: int, printConfig: array}
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	private function dispatchBatchJob(
		string $templateId,
		array $items,
		array $options,
		string $userId,
	): array {
		$jobId = $this->generateJobId();
		$printConfig = $this->buildPrintConfig(options: $options);

		$this->storeJobStatus(
			jobId: $jobId,
			data: [
				'status' => 'queued',
				'total' => count($items),
				'completed' => 0,
				'errors' => 0,
				'ownerUserId' => $userId,
				'printConfig' => $printConfig,
				'manifest' => [],
			]
		);

		$this->jobList->add(
			\OCA\Filinq\BackgroundJob\BatchPrintJob::class,
			[
				'jobId' => $jobId,
				'templateId' => $templateId,
				'items' => $items,
				'options' => $options,
				'userId' => $userId,
			]
		);

		return [
			'jobId' => $jobId,
			'status' => 'queued',
			'total' => count($items),
			'printConfig' => $printConfig,
		];

	}//end dispatchBatchJob()

	/**
	 * Retrieve job info including manifest and print config
	 *
	 * @param string $jobId Job UUID
	 *
	 * @return array|null Job data or null if not found
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function getJob(string $jobId): ?array {
		return $this->loadJobStatus(jobId: $jobId);
	}//end getJob()

	/**
	 * Build a manifest listing all documents with metadata
	 *
	 * Per acceptance criterion 2: each entry includes filename, status, and
	 * print configuration metadata.
	 *
	 * @param array $items Array of items with 'filename' and 'status' keys
	 * @param array $printConfig Print configuration for all items in the batch
	 *
	 * @return array Manifest array
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
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
	 * Extract print configuration keys from options array
	 *
	 * @param array $options Full options array
	 *
	 * @return array{duplex: bool, color: bool, paperTray: string, stapling: bool}
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function buildPrintConfig(array $options): array {
		return [
			'duplex' => (bool)($options['duplex'] ?? false),
			'color' => (bool)($options['color'] ?? true),
			'paperTray' => (string)($options['paperTray'] ?? 'default'),
			'stapling' => (bool)($options['stapling'] ?? false),
		];

	}//end buildPrintConfig()

	/**
	 * Store job status in IAppConfig
	 *
	 * @param string $jobId Job UUID
	 * @param array $data Status data to persist
	 *
	 * @return void
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function storeJobStatus(string $jobId, array $data): void {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$config->setValueString(
				'filinq',
				self::JOB_KEY_PREFIX . $jobId,
				json_encode($data)
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to store print job status: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
		}

	}//end storeJobStatus()

	/**
	 * Store generated PDF binary for a job
	 *
	 * PDF content is stored as base64 in IAppConfig to avoid filesystem
	 * dependencies. For large batches the BatchPrintJob stores per-item
	 * PDFs with suffixed keys (jobId-0, jobId-1, …).
	 *
	 * @param string $jobId Job UUID (may include item index suffix)
	 * @param string $content PDF binary content
	 *
	 * @return void
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function storeJobPdf(string $jobId, string $content): void {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$config->setValueString(
				'filinq',
				self::JOB_KEY_PREFIX . 'pdf_' . $jobId,
				base64_encode($content)
			);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to store print job PDF: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
		}

	}//end storeJobPdf()

	/**
	 * Load a stored job PDF from IAppConfig
	 *
	 * @param string $jobId Job UUID
	 *
	 * @return string|null Base64-decoded PDF binary or null if not found
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	public function loadJobPdf(string $jobId): ?string {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$encoded = $config->getValueString(
				'filinq',
				self::JOB_KEY_PREFIX . 'pdf_' . $jobId,
				''
			);

			if (empty($encoded) === true) {
				return null;
			}

			return base64_decode($encoded);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to load print job PDF: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
			return null;
		}//end try

	}//end loadJobPdf()

	/**
	 * Load job status from IAppConfig
	 *
	 * @param string $jobId Job UUID
	 *
	 * @return array|null Decoded job data or null if not found
	 *
	 * @spec openspec/changes/print-functionality/tasks.md#task-3
	 */
	private function loadJobStatus(string $jobId): ?array {
		try {
			$config = $this->container->get(\OCP\IAppConfig::class);
			$value = $config->getValueString(
				'filinq',
				self::JOB_KEY_PREFIX . $jobId,
				''
			);

			if (empty($value) === true) {
				return null;
			}

			return json_decode($value, true);
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to load print job status: ' . $e->getMessage(),
				context: ['jobId' => $jobId]
			);
			return null;
		}//end try

	}//end loadJobStatus()
}//end class
