<?php
/**
 * Folder Extraction Background Job
 *
 * Background job that processes all files queued in a folder-based batch:
 * extracts text, detects entities, anonymises the document, and applies the
 * configured output-folder layout (moving the redacted copy into the
 * `<source>/<subfolder>/<clean>.<ext>` location). Per-file failures are logged
 * and recorded on the file entry without aborting the rest of the batch.
 *
 * @category  BackgroundJob
 * @package   OCA\DocuDesk\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\BackgroundJob;

use Exception;
use OCA\DocuDesk\Service\AnonymizationService;
use OCA\DocuDesk\Service\BatchStateService;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;

/**
 * Background job for extracting, anonymising and laying-out all files in a
 * folder-based batch.
 *
 * Processes files sequentially, updating batch state after each file:
 *   - `_anonymized`-suffixed source files are skipped (status `skipped`).
 *   - Files not in the `uploaded` state are left untouched (idempotent retry).
 *   - Each uploaded file is extracted, anonymised, and the redacted output is
 *     moved into the configured output subfolder; on success the file entry
 *     records the new target path, on move failure it keeps the legacy path
 *     and records a `MOVE_FAILED` warning.
 *   - Extraction or anonymisation errors mark the single file as `error`
 *     without aborting the batch.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
 */
class FolderExtractionJob extends QueuedJob
{
    /**
     * Constructor for FolderExtractionJob.
     *
     * @param ITimeFactory         $time           Time factory.
     * @param AnonymizationService $anonService    Anonymization/extraction service.
     * @param BatchStateService    $stateService   Batch state management.
     * @param LoggerInterface      $logger         Logger for error reporting.
     * @param OutputLayoutResolver $layoutResolver Output-folder layout resolver.
     * @param IRootFolder          $rootFolder     Root folder for file lookups.
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
        private readonly LoggerInterface $logger,
        private readonly OutputLayoutResolver $layoutResolver,
        private readonly IRootFolder $rootFolder
    ) {
        parent::__construct(time: $time);

    }//end __construct()

    /**
     * Run the folder extraction + anonymisation job.
     *
     * Processes each uploaded file in the batch sequentially. Sets batch
     * status to `extracting` at the start and `completed` once every file has
     * been attempted.
     *
     * @param mixed $argument Job arguments containing batchId.
     *
     * @return void
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
     */
    protected function run(mixed $argument): void
    {
        $batchId = $argument['batchId'] ?? '';
        if (empty($batchId) === true) {
            $this->logger->error('FolderExtractionJob: missing batchId');
            return;
        }

        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            $this->logger->error('FolderExtractionJob: batch not found', ['batchId' => $batchId]);
            return;
        }

        $batch['status'] = 'extracting';
        $this->stateService->updateBatch($batchId, $batch);

        $userId = ($batch['userId'] ?? '');

        foreach ($batch['files'] as $index => $file) {
            // Skip files that are not freshly uploaded (idempotent retry:
            // already-anonymized / skipped / error entries are left as-is).
            if (($file['status'] ?? '') !== 'uploaded') {
                continue;
            }

            $batch['files'][$index] = $this->processFileEntry(
                fileEntry: $batch['files'][$index],
                batchId: $batchId,
                userId: $userId
            );

            $this->stateService->updateBatch($batchId, $batch);
        }//end foreach

        $batch['status'] = 'completed';
        $this->stateService->updateBatch($batchId, $batch);

        $summary = $this->summariseOutcome(files: $batch['files']);

        $this->logger->info(
            "FolderExtractionJob completed: {$summary['anonymized']} anonymized, {$summary['errors']} errors",
            ['batchId' => $batchId]
        );

    }//end run()

    /**
     * Extract, anonymise and re-file a single freshly-uploaded batch entry.
     *
     * Returns the updated entry; the caller persists the batch afterwards. A
     * prior `_anonymized` output is skipped, and either processing step failing
     * marks the entry as `error` without aborting the batch.
     *
     * @param array<string, mixed> $fileEntry The batch file entry to process.
     * @param string               $batchId   The batch id, for log context.
     * @param string               $userId    Owning user id.
     *
     * @return array<string, mixed> The updated file entry.
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
     */
    private function processFileEntry(array $fileEntry, string $batchId, string $userId): array
    {
        $fileName = ($fileEntry['fileName'] ?? '');

        // Source-discovery filter: a `_anonymized`-suffixed file is a prior
        // anonymisation output and must not be re-processed.
        if ($this->layoutResolver->hasAnonymizedSuffix($fileName) === true) {
            $fileEntry['status'] = 'skipped';
            return $fileEntry;
        }

        try {
            $extraction = $this->anonService->extractAndDetectEntities((int) $fileEntry['fileId']);
            $fileEntry['entityCount'] = ($extraction['entityCount'] ?? 0);
        } catch (Exception $e) {
            return $this->markEntryFailed(
                fileEntry: $fileEntry,
                batchId: $batchId,
                message: 'FolderExtractionJob: extraction failed for file',
                error: $e->getMessage()
            );
        }

        try {
            $anonResult = $this->anonService->anonymizeDocument(
                (int) $fileEntry['fileId'],
                ($extraction['entities'] ?? [])
            );
        } catch (Exception $e) {
            return $this->markEntryFailed(
                fileEntry: $fileEntry,
                batchId: $batchId,
                message: 'FolderExtractionJob: anonymization failed for file',
                error: $e->getMessage()
            );
        }

        $fileEntry['status'] = 'anonymized';
        $fileEntry['anonymizedFilePath'] = ($anonResult['anonymizedFilePath'] ?? null);

        // Apply the output-folder layout: move the redacted copy into the
        // configured subfolder, recording the new path or a move-failure
        // warning. Only attempted when OR produced a real file node.
        $anonymizedFileId = ($anonResult['anonymizedFileId'] ?? null);
        if ($anonymizedFileId !== null) {
            $fileEntry = $this->applyOutputLayout(
                fileEntry: $fileEntry,
                userId: $userId,
                anonymizedFileId: (int) $anonymizedFileId,
                legacyPath: ($anonResult['anonymizedFilePath'] ?? '')
            );
        }

        return $fileEntry;

    }//end processFileEntry()

    /**
     * Mark a batch entry as failed and log the underlying reason.
     *
     * @param array<string, mixed> $fileEntry The batch file entry to mark.
     * @param string               $batchId   The batch id, for log context.
     * @param string               $message   The log message describing the failed step.
     * @param string               $error     The underlying exception message.
     *
     * @return array<string, mixed> The updated file entry.
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
     */
    private function markEntryFailed(array $fileEntry, string $batchId, string $message, string $error): array
    {
        $this->logger->warning(
            $message,
            ['batchId' => $batchId, 'fileId' => $fileEntry['fileId'], 'error' => $error]
        );

        $fileEntry['status'] = 'error';
        $fileEntry['error']  = $error;

        return $fileEntry;

    }//end markEntryFailed()

    /**
     * Count anonymised and errored entries for the completion log line.
     *
     * @param array<int|string, array<string, mixed>> $files The batch's file entries.
     *
     * @return array{anonymized: int, errors: int} The outcome tally.
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-2
     */
    private function summariseOutcome(array $files): array
    {
        $anonymized = 0;
        $errors     = 0;

        foreach ($files as $entry) {
            if ($entry['status'] === 'anonymized') {
                $anonymized++;
            } else if ($entry['status'] === 'error') {
                $errors++;
            }
        }

        return [
            'anonymized' => $anonymized,
            'errors'     => $errors,
        ];

    }//end summariseOutcome()

    /**
     * Move the anonymized output into the configured output subfolder.
     *
     * Looks the anonymized node up via the user's root folder, resolves the
     * canonical destination via {@see OutputLayoutResolver}, and moves the
     * node there. On success the returned file entry records the new target
     * path; on any failure it keeps the legacy path and attaches a
     * `MOVE_FAILED` warning so the reviewer sees the truth.
     *
     * @param array<string,mixed> $fileEntry        The file entry to update.
     * @param string              $userId           Owning user id.
     * @param int                 $anonymizedFileId OR file id of the redacted output.
     * @param string              $legacyPath       Legacy `_anonymized` output path.
     *
     * @return array<string,mixed> The updated file entry.
     */
    private function applyOutputLayout(
        array $fileEntry,
        string $userId,
        int $anonymizedFileId,
        string $legacyPath
    ): array {
        try {
            $userFolder = $this->rootFolder->getUserFolder($userId);
            $nodes      = $userFolder->getById($anonymizedFileId);

            $anonFile = null;
            foreach ($nodes as $node) {
                if ($node instanceof File) {
                    $anonFile = $node;
                    break;
                }
            }

            if ($anonFile === null) {
                $fileEntry['anonymizedFilePath'] = $legacyPath;
                $fileEntry['warning']            = [
                    'code'    => 'MOVE_FAILED',
                    'message' => 'Anonymized output node could not be located; left at legacy path.',
                ];
                return $fileEntry;
            }

            $sourceFolder   = $anonFile->getParent();
            $sourceName     = $anonFile->getName();
            $sourceBaseName = pathinfo($sourceName, PATHINFO_FILENAME);
            $extension      = pathinfo($sourceName, PATHINFO_EXTENSION);
            $subfolderName  = $this->layoutResolver->readSubfolderName();
            $targetPath     = $this->layoutResolver->resolveBatchDestination(
                $sourceFolder->getPath(),
                $sourceBaseName,
                $extension
            );

            // Create the destination subfolder if missing.
            if ($sourceFolder->nodeExists($subfolderName) === false) {
                $sourceFolder->newFolder($subfolderName);
            }

            $anonFile->move($targetPath);

            $fileEntry['anonymizedFilePath'] = $targetPath;
            unset($fileEntry['warning']);
        } catch (Exception $e) {
            $this->logger->warning(
                'FolderExtractionJob: output-layout move failed; keeping legacy path',
                ['fileId' => ($fileEntry['fileId'] ?? null), 'error' => $e->getMessage()]
            );
            $fileEntry['anonymizedFilePath'] = $legacyPath;
            $fileEntry['warning']            = [
                'code'    => 'MOVE_FAILED',
                'message' => $e->getMessage(),
            ];
        }//end try

        return $fileEntry;

    }//end applyOutputLayout()
}//end class
