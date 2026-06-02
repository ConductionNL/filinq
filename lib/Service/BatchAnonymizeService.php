<?php
/**
 * Batch Anonymize Service
 *
 * Iterates over the files in a reviewed batch and applies the user-approved
 * entity list to each document via AnonymizationService. After each file is
 * anonymized, the output is post-processed: moved from OR's legacy output
 * location (`<source>/<base>_anonymized.<ext>`) into a configurable subfolder
 * (`<source>/<subfolder>/<cleanBase>.<ext>`). Failures are recorded on the
 * file entry and reported back to the caller rather than aborting the batch.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-9
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-4
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Applies a user-reviewed entity list across every file in a batch.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
 * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
 */
class BatchAnonymizeService
{
    /**
     * Constructor for BatchAnonymizeService
     *
     * @param LoggerInterface      $logger         Logger for error reporting.
     * @param AnonymizationService $anonService    Service that performs single-document anonymization.
     * @param BatchStateService    $stateService   Service that persists per-batch state between calls.
     * @param IRootFolder          $rootFolder     Root folder for file operations.
     * @param IUserSession         $userSession    User session for current user.
     * @param OutputLayoutResolver $layoutResolver Resolver that computes the output subfolder destination.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AnonymizationService $anonService,
        private readonly BatchStateService $stateService,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly OutputLayoutResolver $layoutResolver,
    ) {

    }//end __construct()

    /**
     * Anonymize every extracted file in a batch using the approved entity list.
     *
     * When appendBasisSummary is true the flag is forwarded to each per-file
     * anonymization call. Summary failures are collected as per-file `warning`
     * entries and do not abort the batch; the batch always completes as
     * HTTP 200.  Files with a non-extracted status are skipped (previous
     * errors are recorded in the skipped list; other states are ignored).
     *
     * After each successful anonymization, the output file is moved from OR's
     * legacy path (`<source>/<base>_anonymized.<ext>`) into the configured
     * output subfolder. Move failures preserve the file at the legacy path and
     * attach a `warning` to the per-file result; the anonymisation is still
     * counted as successful.
     *
     * When unredactedEntities is non-empty, the prohibition gate is checked
     * per file. Files with prohibition violations are recorded with a
     * `prohibitionViolation` status and counted in prohibitionSkippedFiles.
     *
     * @param string                           $batchId            Identifier of the batch to anonymize.
     * @param array<int, array<string, mixed>> $entities           User-approved entities to anonymize.
     * @param bool                             $appendBasisSummary Whether to append a grondslagen summary per file.
     * @param array<int, array<string, mixed>> $unredactedEntities Entities to publish unredacted with consent creation.
     *
     * @return array Summary of the run, with shape:
     *   {
     *     batchId: string,
     *     batchStatus: string,
     *     processedFiles: int,
     *     skippedFiles: array<int, array{fileId: mixed, reason: string}>,
     *     prohibitionSkippedFiles: int,
     *     totalFiles: int,
     *   }
     *
     * @throws Exception When the batch cannot be found.
     *
     * @spec openspec/changes/anonymisation-append-basis-summary-flag/tasks.md#task-3
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-9
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-5
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
     */
    public function anonymizeBatch(
        string $batchId,
        array $entities,
        bool $appendBasisSummary=false,
        array $unredactedEntities=[]
    ): array {
        $batch = $this->stateService->getBatch($batchId);
        if ($batch === null) {
            throw new Exception('Batch not found or expired', 404);
        }

        $batch['status'] = 'anonymizing';
        $this->stateService->updateBatch($batchId, $batch);
        $skipped            = [];
        $processed          = 0;
        $prohibitionSkipped = 0;

        foreach ($batch['files'] as $i => $file) {
            if ($file['status'] === 'error') {
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $file['error'] ?? 'Previous error'];
                continue;
            }

            if ($file['status'] !== 'extracted') {
                continue;
            }

            if (empty($unredactedEntities) === false) {
                $violations = $this->anonService->checkUnredactedProhibitions(
                    unredactedEntities: $unredactedEntities
                );
                if (empty($violations) === false) {
                    $batch['files'][$i]['status']            = 'prohibitionViolation';
                    $batch['files'][$i]['prohibitedEntries'] = $violations;
                    $skipped[] = [
                        'fileId'     => $file['fileId'],
                        'reason'     => 'Prohibition violation on unredacted entities',
                        'httpStatus' => 422,
                    ];
                    $prohibitionSkipped++;
                    continue;
                }
            }

            try {
                $result = $this->anonService->anonymizeDocument(
                    fileId: (int) $file['fileId'],
                    entities: $entities,
                    appendBasisSummary: $appendBasisSummary,
                    unredactedEntities: $unredactedEntities
                );
                $batch['files'][$i]['status']           = 'anonymized';
                $batch['files'][$i]['replacementCount'] = $result['replacementCount'] ?? 0;
                $batch['files'][$i]['anonymizedFileId'] = $result['anonymizedFileId'] ?? null;

                if (isset($result['warning']) === true) {
                    $batch['files'][$i]['warning'] = $result['warning'];
                }

                if (isset($result['summaryFileId']) === true) {
                    $batch['files'][$i]['summaryFileId']   = $result['summaryFileId'];
                    $batch['files'][$i]['summaryFilePath'] = $result['summaryFilePath'] ?? null;
                }

                // Post-process: move the anonymised file into the output subfolder.
                $moveResult = $this->postProcessMove(
                    sourceFileId: (int) $file['fileId'],
                    result: $result,
                );
                $batch['files'][$i]['anonymizedFilePath'] = $moveResult['anonymizedFilePath'];
                if (isset($moveResult['warning']) === true) {
                    $batch['files'][$i]['warning'] = $moveResult['warning'];
                }

                if (isset($result['createdConsents']) === true) {
                    $batch['files'][$i]['createdConsents'] = $result['createdConsents'];
                }

                $processed++;
            } catch (Exception $e) {
                $batch['files'][$i]['status'] = 'error';
                $batch['files'][$i]['error']  = $e->getMessage();
                $skipped[] = ['fileId' => $file['fileId'], 'reason' => $e->getMessage()];
            }//end try
        }//end foreach

        $batch['status'] = 'completed';
        $this->stateService->updateBatch($batchId, $batch);
        return [
            'batchId'                 => $batchId,
            'batchStatus'             => 'completed',
            'processedFiles'          => $processed,
            'skippedFiles'            => $skipped,
            'prohibitionSkippedFiles' => $prohibitionSkipped,
            'totalFiles'              => count($batch['files']),
        ];

    }//end anonymizeBatch()

    /**
     * Move the anonymised file from OR's legacy path into the output subfolder.
     *
     * On success returns an array with `anonymizedFilePath` set to the new
     * location. On failure returns the legacy path and a `warning` field; the
     * file is preserved at its original location (NOT discarded).
     *
     * @param int                  $sourceFileId The NC file ID of the original source file.
     * @param array<string, mixed> $result       The result array from anonymizeDocument.
     *
     * @return array{anonymizedFilePath: string|null, warning?: array<string, string>}
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-4
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-5
     */
    private function postProcessMove(int $sourceFileId, array $result): array
    {
        $anonymizedFileId = $result['anonymizedFileId'] ?? null;
        $legacyPath       = $result['anonymizedFilePath'] ?? null;

        if ($anonymizedFileId === null) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $anonNode = $this->getFileNodeById(fileId: (int) $anonymizedFileId);
        if ($anonNode === null) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $sourceFolder = $this->getSourceFolder(sourceFileId: $sourceFileId, anonNode: $anonNode);
        if ($sourceFolder === null) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $anonName  = $anonNode->getName();
        $extension = '.'.pathinfo($anonName, PATHINFO_EXTENSION);
        $baseName  = pathinfo($anonName, PATHINFO_FILENAME);

        $targetPath = $this->layoutResolver->resolveBatchDestination(
            sourceFolder: $sourceFolder,
            sourceBaseName: $baseName,
            extension: $extension
        );

        try {
            $subfolder = $this->ensureSubfolder(sourceFolder: $sourceFolder);
            // Move to target path; overwrite any existing file at destination.
            $targetName = pathinfo($targetPath, PATHINFO_BASENAME);
            if ($subfolder->nodeExists($targetName) === true) {
                $existing = $subfolder->get($targetName);
                $existing->delete();
            }

            $anonNode->move(targetPath: $targetPath);
            return ['anonymizedFilePath' => $targetPath];
        } catch (\Throwable $e) {
            $this->logger->warning(
                'BatchAnonymizeService: post-process move failed; file preserved at legacy path.',
                [
                    'sourcePath' => $legacyPath,
                    'targetPath' => $targetPath,
                    'error'      => $e->getMessage(),
                ]
            );
            return [
                'anonymizedFilePath' => $legacyPath,
                'warning'            => [
                    'code'    => 'MOVE_FAILED',
                    'message' => 'Output file could not be moved to the subfolder; '
                        .'file is preserved at the legacy path.',
                ],
            ];
        }//end try

    }//end postProcessMove()

    /**
     * Ensure the output subfolder exists under $sourceFolder, creating it if needed.
     *
     * @param Folder $sourceFolder The source folder.
     *
     * @return Folder The (existing or newly created) subfolder.
     *
     * @throws \OCP\Files\NotPermittedException When the subfolder cannot be created.
     *
     * @spec openspec/changes/anonymisation-batch-output-folder-layout/tasks.md#task-3
     */
    private function ensureSubfolder(Folder $sourceFolder): Folder
    {
        $subfolderName = $this->layoutResolver->readSubfolderName();

        if ($sourceFolder->nodeExists($subfolderName) === true) {
            $node = $sourceFolder->get($subfolderName);
            if ($node instanceof Folder === true) {
                return $node;
            }
        }

        return $sourceFolder->newFolder($subfolderName);

    }//end ensureSubfolder()

    /**
     * Get a file node by its NC file ID in the current user's tree.
     *
     * @param int $fileId The NC file ID.
     *
     * @return File|null The file node, or null when not found.
     */
    private function getFileNodeById(int $fileId): ?File
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return null;
        }

        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $nodes      = $userFolder->getById($fileId);

        if (empty($nodes) === true) {
            return null;
        }

        $node = $nodes[0];
        if ($node instanceof File === false) {
            return null;
        }

        return $node;

    }//end getFileNodeById()

    /**
     * Determine the source folder for the post-process move.
     *
     * Prefers the parent of the anonymised file node (which is the source folder
     * as written by OR). Falls back to the parent of the source file when the
     * anonymised node is not accessible.
     *
     * @param int  $sourceFileId The NC file ID of the original source file.
     * @param File $anonNode     The anonymised file node returned by OR.
     *
     * @return Folder|null The source folder, or null when it cannot be determined.
     */
    private function getSourceFolder(int $sourceFileId, File $anonNode): ?Folder
    {
        try {
            $parent = $anonNode->getParent();
            if ($parent instanceof Folder === true) {
                return $parent;
            }
        } catch (\Throwable) {
            // Fall through to source-file lookup.
        }

        $sourceNode = $this->getFileNodeById(fileId: $sourceFileId);
        if ($sourceNode === null) {
            return null;
        }

        try {
            $parent = $sourceNode->getParent();
            if ($parent instanceof Folder === true) {
                return $parent;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;

    }//end getSourceFolder()
}//end class
