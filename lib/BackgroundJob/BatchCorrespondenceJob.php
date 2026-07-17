<?php
/**
 * Batch Correspondence Job
 *
 * Background job for processing large correspondence batches asynchronously.
 * Dispatched by CorrespondenceService when the batch size exceeds the
 * synchronous limit (>10 recipients).
 *
 * @category  BackgroundJob
 * @package   OCA\DocuDesk\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\BackgroundJob;

use Exception;
use OCA\DocuDesk\Service\CorrespondenceService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Queued job for batch correspondence generation
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/letter-correspondence-generation/tasks.md#task-2
 */
class BatchCorrespondenceJob extends QueuedJob
{
    /**
     * Constructor for BatchCorrespondenceJob
     *
     * @param ITimeFactory          $time    Time factory
     * @param CorrespondenceService $corrSvc Correspondence generation service
     * @param LoggerInterface       $logger  Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly CorrespondenceService $corrSvc,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(time: $time);

    }//end __construct()

    /**
     * Run the batch correspondence generation
     *
     * Processes each recipient individually. Updates job status after each
     * recipient so progress can be tracked. Individual failures do not
     * abort the batch.
     *
     * @param array $argument Job arguments containing jobId, templateId,
     *                        recipientIds, and options
     *
     * @return void
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    protected function run(mixed $argument): void
    {
        $jobId        = $argument['jobId'] ?? '';
        $templateId   = $argument['templateId'] ?? '';
        $recipientIds = $argument['recipientIds'] ?? [];
        $options      = $argument['options'] ?? [];
        // SB1 fix: carry the ownerUserId stored at dispatch time so every
        // mid-job progress update retains it (otherwise ownership check reads null).
        $ownerUserId = (string) ($options['userId'] ?? '');

        if (empty($jobId) === true || empty($templateId) === true) {
            $this->logger->error(
                message: 'BatchCorrespondenceJob: missing required arguments',
                context: ['argument' => $argument]
            );
            return;
        }

        $this->initializeJobStatus(jobId: $jobId, total: count($recipientIds), ownerUserId: $ownerUserId);
        $this->processRecipients(
            jobId: $jobId,
            templateId: $templateId,
            recipientIds: $recipientIds,
            options: $options,
            ownerUserId: $ownerUserId
        );

    }//end run()

    /**
     * Initialize job status to processing
     *
     * @param string $jobId       The job UUID
     * @param int    $total       Total number of recipients
     * @param string $ownerUserId UID of the user who dispatched the job (SB1 fix)
     *
     * @return void
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    private function initializeJobStatus(string $jobId, int $total, string $ownerUserId=''): void
    {
        $this->corrSvc->storeJobStatus(
            jobId: $jobId,
            data: [
                'status'      => 'processing',
                'total'       => $total,
                'completed'   => 0,
                'errors'      => 0,
                'results'     => [],
                'ownerUserId' => $ownerUserId,
            ]
        );

    }//end initializeJobStatus()

    /**
     * Process all recipients in the batch
     *
     * @param string $jobId        The job UUID
     * @param string $templateId   The template UUID
     * @param array  $recipientIds Array of recipient UUIDs
     * @param array  $options      Generation options
     * @param string $ownerUserId  UID of the user who dispatched the job (SB1 fix)
     *
     * @return void
     *
     * @spec openspec/specs/letter-correspondence-generation/spec.md#requirement-batch-correspondence-generation
     */
    private function processRecipients(
        string $jobId,
        string $templateId,
        array $recipientIds,
        array $options,
        string $ownerUserId=''
    ): void {
        $total     = count($recipientIds);
        $completed = 0;
        $errors    = 0;
        $results   = [];
        $register  = $options['register'] ?? '';
        $schema    = $options['schema'] ?? '';

        foreach ($recipientIds as $recipientId) {
            $dataRefs = [
                [
                    'register' => $register,
                    'schema'   => $schema,
                    'id'       => $recipientId,
                ],
            ];

            try {
                $this->corrSvc->generate(
                    templateId: $templateId,
                    dataRefs: $dataRefs,
                    options: $options
                );
                $results[] = [
                    'recipientId' => $recipientId,
                    'status'      => 'success',
                ];
                $completed++;
            } catch (Exception $e) {
                $results[] = [
                    'recipientId' => $recipientId,
                    'status'      => 'error',
                    'error'       => $e->getMessage(),
                ];
                $errors++;

                $this->logger->warning(
                    message: 'Batch correspondence failed for recipient: '.$e->getMessage(),
                    context: [
                        'jobId'       => $jobId,
                        'recipientId' => $recipientId,
                    ]
                );
            }//end try

            // Update progress after each recipient (retain ownerUserId so ownership check holds).
            $this->corrSvc->storeJobStatus(
                jobId: $jobId,
                data: [
                    'status'      => 'processing',
                    'total'       => $total,
                    'completed'   => $completed,
                    'errors'      => $errors,
                    'results'     => $results,
                    'ownerUserId' => $ownerUserId,
                ]
            );
        }//end foreach

        // Mark job as complete (retain ownerUserId so ownership check holds).
        $this->corrSvc->storeJobStatus(
            jobId: $jobId,
            data: [
                'status'      => 'completed',
                'total'       => $total,
                'completed'   => $completed,
                'errors'      => $errors,
                'results'     => $results,
                'ownerUserId' => $ownerUserId,
            ]
        );

        $this->logger->info(
            message: "BatchCorrespondenceJob completed: {$completed}/{$total} successful, {$errors} errors",
            context: ['jobId' => $jobId]
        );

    }//end processRecipients()
}//end class
