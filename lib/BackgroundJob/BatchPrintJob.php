<?php

/**
 * Batch Print Job
 *
 * Background job for processing large print batches asynchronously.
 * Dispatched by PrintJobService when the batch size exceeds the synchronous
 * limit (>10 items). Generates PDFs for each item, updates progress, and
 * stores the manifest.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\BackgroundJob;

use Exception;
use OCA\DocuDesk\Service\PrintJobService;
use OCA\DocuDesk\Service\PdfService;
use OCA\DocuDesk\Service\TemplateService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Queued background job for batch PDF print generation
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/print-functionality/tasks.md#task-4
 */
class BatchPrintJob extends QueuedJob
{
    /**
     * Constructor for BatchPrintJob
     *
     * @param ITimeFactory    $time        Time factory
     * @param PrintJobService $printJobSvc Print job service for status/PDF storage
     * @param PdfService      $pdfService  Service for PDF generation
     * @param TemplateService $templateSvc Service for template retrieval
     * @param LoggerInterface $logger      Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly PrintJobService $printJobSvc,
        private readonly PdfService $pdfService,
        private readonly TemplateService $templateSvc,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(time: $time);

    }//end __construct()

    /**
     * Run the batch print generation
     *
     * Processes each item individually. Updates job status after each item
     * so progress can be tracked. Individual failures do not abort the batch.
     *
     * @param array $argument Job arguments containing jobId, templateId, items, options, userId
     *
     * @return void
     *
     * @spec openspec/changes/print-functionality/tasks.md#task-4
     */
    protected function run(mixed $argument): void
    {
        $jobId      = $argument['jobId'] ?? '';
        $templateId = $argument['templateId'] ?? '';
        $items      = $argument['items'] ?? [];
        $options    = $argument['options'] ?? [];
        $userId     = (string) ($argument['userId'] ?? '');

        if (empty($jobId) === true || empty($templateId) === true) {
            $this->logger->error(
                message: 'BatchPrintJob: missing required arguments',
                context: ['argument' => $argument]
            );
            return;
        }

        $this->processItems(
            jobId: $jobId,
            templateId: $templateId,
            items: $items,
            options: $options,
            userId: $userId
        );

    }//end run()

    /**
     * Process all items in the batch
     *
     * @param string $jobId      Job UUID
     * @param string $templateId Template UUID
     * @param array  $items      Array of items to process
     * @param array  $options    Print generation options
     * @param string $userId     Requesting user UID
     *
     * @return void
     *
     * @spec openspec/changes/print-functionality/tasks.md#task-4
     */
    private function processItems(
        string $jobId,
        string $templateId,
        array $items,
        array $options,
        string $userId
    ): void {
        $total         = count($items);
        $completed     = 0;
        $errors        = 0;
        $manifestItems = [];
        $printConfig   = $this->printJobSvc->buildPrintConfig(options: $options);

        $this->printJobSvc->storeJobStatus(
            jobId: $jobId,
            data: [
                'status'      => 'processing',
                'total'       => $total,
                'completed'   => 0,
                'errors'      => 0,
                'ownerUserId' => $userId,
                'printConfig' => $printConfig,
                'manifest'    => [],
            ]
        );

        try {
            $template = $this->templateSvc->getTemplate(id: $templateId);
        } catch (Exception $e) {
            $this->logger->error(
                message: 'BatchPrintJob: failed to load template: '.$e->getMessage(),
                context: ['jobId' => $jobId, 'templateId' => $templateId]
            );
            $this->printJobSvc->storeJobStatus(
                jobId: $jobId,
                data: [
                    'status'      => 'failed',
                    'total'       => $total,
                    'completed'   => 0,
                    'errors'      => $total,
                    'ownerUserId' => $userId,
                    'printConfig' => $printConfig,
                    'manifest'    => [],
                    'error'       => 'Template not found',
                ]
            );
            return;
        }//end try

        $pdfOptions          = array_merge(
            [
                'format'      => $template['format'] ?? 'A4',
                'orientation' => $template['orientation'] ?? 'P',
                'duplex'      => $template['duplex'] ?? false,
                'color'       => $template['color'] ?? true,
                'paperTray'   => $template['paperTray'] ?? 'default',
                'stapling'    => $template['stapling'] ?? false,
            ],
            $options
        );
        $pdfOptions['title'] = $template['name'] ?? 'document';
        $pdfOptions['pdfa']  = ($options['pdfa'] ?? false) === true;

        foreach ($items as $index => $item) {
            $itemData     = $item['data'] ?? [];
            $itemFilename = $item['filename'] ?? ('document-'.$index.'.pdf');

            try {
                $pdfContent = $this->pdfService->renderPdf(
                    templateContent: $template['content'] ?? '',
                    data: $itemData,
                    options: $pdfOptions
                );
                $this->printJobSvc->storeJobPdf(
                    jobId: $jobId.'-'.$index,
                    content: $pdfContent
                );
                $manifestItems[] = ['filename' => $itemFilename, 'status' => 'success'];
                $completed++;
            } catch (Exception $e) {
                $manifestItems[] = [
                    'filename' => $itemFilename,
                    'status'   => 'error',
                    'error'    => $e->getMessage(),
                ];
                $errors++;

                $this->logger->warning(
                    message: 'BatchPrintJob: item failed: '.$e->getMessage(),
                    context: ['jobId' => $jobId, 'index' => $index]
                );
            }//end try

            $this->printJobSvc->storeJobStatus(
                jobId: $jobId,
                data: [
                    'status'      => 'processing',
                    'total'       => $total,
                    'completed'   => $completed,
                    'errors'      => $errors,
                    'ownerUserId' => $userId,
                    'printConfig' => $printConfig,
                    'manifest'    => $this->printJobSvc->buildManifest(
                        items: $manifestItems,
                        printConfig: $printConfig
                    ),
                ]
            );
        }//end foreach

        $this->printJobSvc->storeJobStatus(
            jobId: $jobId,
            data: [
                'status'      => 'completed',
                'total'       => $total,
                'completed'   => $completed,
                'errors'      => $errors,
                'ownerUserId' => $userId,
                'printConfig' => $printConfig,
                'manifest'    => $this->printJobSvc->buildManifest(
                    items: $manifestItems,
                    printConfig: $printConfig
                ),
            ]
        );

        $this->logger->info(
            message: "BatchPrintJob completed: {$completed}/{$total} successful, {$errors} errors",
            context: ['jobId' => $jobId]
        );

    }//end processItems()
}//end class
