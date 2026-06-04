<?php
/**
 * FolderBatchService
 *
 * Creates anonymization batches from existing Nextcloud folders identified by
 * either ID or path.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-2
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-1
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCA\DocuDesk\Service\Conversion\OutputLayoutResolver;
use OCP\BackgroundJob\IJobList;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service for creating batches from existing Nextcloud folders.
 *
 * Accepts either a folder path or a folder ID, enumerates direct children of
 * the resolved folder (flat, no recursion), creates a batch via
 * BatchStateService, and fires a FolderExtractionJob immediately for
 * background extraction.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-1
 * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
 */
class FolderBatchService
{
    /**
     * Constructor for FolderBatchService
     *
     * @param LoggerInterface      $logger         Logger for error reporting
     * @param IRootFolder          $rootFolder     Root folder for file operations
     * @param IUserSession         $userSession    User session for current user
     * @param BatchStateService    $stateService   Batch state management
     * @param IJobList             $jobList        Background job list
     * @param AnonymizationService $anonService    Anonymization/extraction service
     * @param OutputLayoutResolver $layoutResolver Resolver that computes the output subfolder destination
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly BatchStateService $stateService,
        private readonly IJobList $jobList,
        private readonly AnonymizationService $anonService,
        private readonly OutputLayoutResolver $layoutResolver
    ) {

    }//end __construct()

    /**
     * Create a batch from an existing Nextcloud folder
     *
     * Resolves the folder via either ID or path, enumerates file children
     * (flat), creates the batch, and fires extraction immediately in the
     * background. Exactly one of $folderId or $folderPath MUST be provided.
     *
     * @param int|null    $folderId   Node ID of the folder, or null
     * @param string|null $folderPath Path relative to the user's root folder, or null
     *
     * @return array<string, mixed> Batch data with batchId, folderId, folderPath, fileCount, files
     *
     * @throws Exception If input is invalid, folder is not found, not a folder, empty, or too large
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
     */
    public function createFolderBatch(?int $folderId=null, ?string $folderPath=null): array
    {
        $userId     = $this->getCurrentUserId();
        $userFolder = $this->rootFolder->getUserFolder($userId);

        $node = $this->resolveFolderNode(folderId: $folderId, folderPath: $folderPath, userFolder: $userFolder);

        if (($node instanceof Folder) === false) {
            throw new Exception('Path is not a folder', 400);
        }

        $files = $this->enumerateFiles(folder: $node);

        if (empty($files) === true) {
            throw new Exception('No files found in folder', 400);
        }

        $maxFiles = $this->stateService->getMaxFiles();
        if (count($files) > $maxFiles) {
            throw new Exception(
                'Folder contains too many files (found: '.count($files).', maximum: '.$maxFiles.')',
                400
            );
        }

        // Canonical identifiers captured from the resolved node — stored and
        // returned regardless of which input method the caller used.
        $resolvedFolderId   = $node->getId();
        $resolvedFolderPath = $userFolder->getRelativePath($node->getPath()) ?? $node->getPath();
        $inputMethod        = 'path';
        if ($folderId !== null) {
            $inputMethod = 'id';
        }

        $batchFiles = [];
        foreach ($files as $file) {
            $batchFiles[] = [
                'fileId'           => $file->getId(),
                'fileName'         => $file->getName(),
                'status'           => 'uploaded',
                'entityCount'      => 0,
                'replacementCount' => 0,
                'error'            => null,
            ];
        }

        $batch = $this->stateService->createBatch($userId, $batchFiles);

        // Store folder metadata on the batch — both identifiers regardless of input.
        $batch['sourceType'] = 'folder';
        $batch['folderId']   = $resolvedFolderId;
        $batch['folderPath'] = $resolvedFolderPath;
        $this->stateService->updateBatch($batch['batchId'], $batch);

        // Run extraction asynchronously: the shutdown function fires after the
        // HTTP response has been flushed to the client (via fastcgi_finish_request).
        // The IJobList entry serves as a fallback if the shutdown handler is
        // interrupted (e.g. process kill, memory limit).
        $this->jobList->add(FolderExtractionJob::class, ['batchId' => $batch['batchId']]);
        $this->scheduleExtraction(batchId: $batch['batchId'], userId: $userId);

        $this->logger->info(
            'Folder batch created, extraction job fired',
            [
                'batchId'     => $batch['batchId'],
                'folderId'    => $resolvedFolderId,
                'folderPath'  => $resolvedFolderPath,
                'fileCount'   => count($batchFiles),
                'inputMethod' => $inputMethod,
            ]
        );

        return $batch;

    }//end createFolderBatch()

    /**
     * Resolve the folder node from either a folder ID or folder path
     *
     * Enforces XOR on the inputs (exactly one must be provided). When ID is
     * used, chooses a writable mount first, falling back to the first
     * readable node. When path is used, preserves the existing lookup via
     * Folder::get(). Maps the "not found" case to HTTP 404 for both inputs.
     *
     * @param int|null    $folderId   Node ID of the folder, or null
     * @param string|null $folderPath Relative path of the folder, or null
     * @param Folder      $userFolder The current user's root folder
     *
     * @return Node The resolved node (type is validated by caller)
     *
     * @throws Exception If neither/both inputs provided (400), or folder not found (404)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-4
     */
    private function resolveFolderNode(?int $folderId, ?string $folderPath, Folder $userFolder): Node
    {
        $hasId   = $folderId !== null;
        $hasPath = $folderPath !== null && $folderPath !== '';

        if ($hasId === false && $hasPath === false) {
            throw new Exception('Either folderId or folderPath must be provided', 400);
        }

        if ($hasId === true && $hasPath === true) {
            throw new Exception('Provide only one of folderId or folderPath', 400);
        }

        if ($hasId === true) {
            $nodes = $userFolder->getById($folderId);
            if (empty($nodes) === true) {
                throw new Exception('Folder not found', 404);
            }

            return $this->pickPreferredNode(nodes: $nodes);
        }

        try {
            return $userFolder->get($folderPath);
        } catch (NotFoundException $e) {
            throw new Exception('Folder not found', 404, $e);
        }

    }//end resolveFolderNode()

    /**
     * Pick the preferred node when getById returns multiple mounts
     *
     * The same file ID can surface through multiple mounts in one user's
     * tree (personal storage + share + group folder). Prefer a writable
     * mount because the batch anonymization flow writes output files back
     * into the source folder; a read-only mount would succeed at extraction
     * but fail at write-back time. Fall back to the first readable node
     * when no writable mount exists — extraction-only use remains valid.
     *
     * @param Node[] $nodes Non-empty array of nodes returned by getById
     *
     * @return Node The preferred node
     */
    private function pickPreferredNode(array $nodes): Node
    {
        foreach ($nodes as $candidate) {
            if (($candidate->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
                return $candidate;
            }
        }

        return $nodes[0];

    }//end pickPreferredNode()

    /**
     * Schedule extraction + anonymization to run after the HTTP response is flushed
     *
     * Registers a shutdown function that flushes the response to the client
     * (via fastcgi_finish_request on PHP-FPM) and then runs extraction,
     * anonymization, and the output-layout post-process inline per file.
     * If all files complete, the queued background job is removed.
     *
     * @param string $batchId The batch ID to process
     * @param string $userId  The user ID owning the batch
     *
     * @return void
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-6
     * @spec openspec/changes/folder-batch-accept-folder-id/tasks.md#task-1
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-1
     */
    private function scheduleExtraction(string $batchId, string $userId): void
    {
        $stateService   = $this->stateService;
        $anonService    = $this->anonService;
        $jobList        = $this->jobList;
        $logger         = $this->logger;
        $rootFolder     = $this->rootFolder;
        $layoutResolver = $this->layoutResolver;

        register_shutdown_function(
            static function () use ($batchId, $userId, $stateService, $anonService, $jobList, $logger, $rootFolder, $layoutResolver): void {
                // Flush the response to the client before doing heavy work.
                if (function_exists('fastcgi_finish_request') === true) {
                    fastcgi_finish_request();
                }

                $batch = $stateService->getBatch($batchId);
                if ($batch === null) {
                    return;
                }

                $batch['status'] = 'extracting';
                $stateService->updateBatch($batchId, $batch);

                foreach ($batch['files'] as $i => $file) {
                    if ($file['status'] !== 'uploaded') {
                        continue;
                    }

                    $fileId = (int) $file['fileId'];

                    try {
                        $extractResult = $anonService->extractAndDetectEntities($fileId);
                        $batch['files'][$i]['entityCount'] = $extractResult['entityCount'];
                    } catch (\Exception $e) {
                        $logger->warning(
                            'Shutdown extraction failed for file',
                            ['batchId' => $batchId, 'fileId' => $fileId, 'error' => $e->getMessage()]
                        );
                        $batch['files'][$i]['status'] = 'error';
                        $batch['files'][$i]['error']  = $e->getMessage();
                        $stateService->updateBatch($batchId, $batch);
                        continue;
                    }

                    try {
                        $anonResult = $anonService->anonymizeDocument(
                            fileId: $fileId,
                            entities: $extractResult['entities']
                        );
                        $batch['files'][$i]['replacementCount'] = $anonResult['replacementCount'] ?? 0;
                        $batch['files'][$i]['anonymizedFileId'] = $anonResult['anonymizedFileId'] ?? null;

                        $moveResult = self::applyOutputLayout(
                            sourceFileId: $fileId,
                            anonymizationResult: $anonResult,
                            userId: $userId,
                            rootFolder: $rootFolder,
                            layoutResolver: $layoutResolver,
                            logger: $logger
                        );
                        $batch['files'][$i]['anonymizedFilePath'] = $moveResult['anonymizedFilePath'];
                        if (isset($moveResult['warning']) === true) {
                            $batch['files'][$i]['warning'] = $moveResult['warning'];
                        }

                        $batch['files'][$i]['status'] = 'anonymized';
                    } catch (\Exception $e) {
                        $logger->warning(
                            'Shutdown anonymization failed for file',
                            ['batchId' => $batchId, 'fileId' => $fileId, 'error' => $e->getMessage()]
                        );
                        $batch['files'][$i]['status'] = 'error';
                        $batch['files'][$i]['error']  = $e->getMessage();
                    }//end try

                    $stateService->updateBatch($batchId, $batch);
                }//end foreach

                $batch['status'] = 'completed';
                $stateService->updateBatch($batchId, $batch);

                // Processing completed — remove the fallback background job.
                $jobList->remove(FolderExtractionJob::class, ['batchId' => $batchId]);
            }
        );

    }//end scheduleExtraction()

    /**
     * Apply the output layout: move the anonymised file from OR's legacy path
     * into the configured subfolder under the source folder.
     *
     * On success returns an array with `anonymizedFilePath` set to the new
     * location. On failure returns the legacy path and a `warning` field; the
     * file is preserved at its original location.
     *
     * Public static so it can be called from static closures (the shutdown
     * handler) and from FolderExtractionJob without capturing $this.
     *
     * @param int                  $sourceFileId        NC file ID of the original source file.
     * @param array<string, mixed> $anonymizationResult Result array from anonymizeDocument.
     * @param string               $userId              User ID for rootFolder lookup.
     * @param IRootFolder          $rootFolder          Root folder service.
     * @param OutputLayoutResolver $layoutResolver      Resolver for subfolder name + path.
     * @param LoggerInterface      $logger              Logger for warnings.
     *
     * @return array{anonymizedFilePath: string|null, warning?: array<string, string>}
     *
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-1
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-4
     */
    public static function applyOutputLayout(
        int $sourceFileId,
        array $anonymizationResult,
        string $userId,
        IRootFolder $rootFolder,
        OutputLayoutResolver $layoutResolver,
        LoggerInterface $logger
    ): array {
        $anonymizedFileId = $anonymizationResult['anonymizedFileId'] ?? null;
        $legacyPath       = $anonymizationResult['anonymizedFilePath'] ?? null;

        if ($anonymizedFileId === null) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $userFolder = $rootFolder->getUserFolder($userId);
        $anonNodes  = $userFolder->getById((int) $anonymizedFileId);
        if (empty($anonNodes) === true) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $anonNode = $anonNodes[0];
        if (($anonNode instanceof File) === false) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $sourceFolder = self::resolveSourceFolder(
            sourceFileId: $sourceFileId,
            anonNode: $anonNode,
            userId: $userId,
            rootFolder: $rootFolder
        );
        if ($sourceFolder === null) {
            return ['anonymizedFilePath' => $legacyPath];
        }

        $anonName   = $anonNode->getName();
        $extension  = '.'.pathinfo($anonName, PATHINFO_EXTENSION);
        $baseName   = pathinfo($anonName, PATHINFO_FILENAME);
        $targetPath = $layoutResolver->resolveBatchDestination(
            sourceFolder: $sourceFolder,
            sourceBaseName: $baseName,
            extension: $extension
        );

        try {
            $subfolderName = $layoutResolver->readSubfolderName();
            if ($sourceFolder->nodeExists($subfolderName) === true) {
                $subfolder = $sourceFolder->get($subfolderName);
                if (($subfolder instanceof Folder) === false) {
                    $subfolder = $sourceFolder->newFolder($subfolderName);
                }
            } else {
                $subfolder = $sourceFolder->newFolder($subfolderName);
            }

            $targetName = pathinfo($targetPath, PATHINFO_BASENAME);
            if ($subfolder->nodeExists($targetName) === true) {
                $existing = $subfolder->get($targetName);
                $existing->delete();
            }

            $anonNode->move(targetPath: $targetPath);
            return ['anonymizedFilePath' => $targetPath];
        } catch (\Throwable $e) {
            $logger->warning(
                'FolderBatchService: post-process move failed; file preserved at legacy path.',
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

    }//end applyOutputLayout()

    /**
     * Determine the source folder for the output-layout move.
     *
     * Prefers the parent of the anonymised file node (as written by OR).
     * Falls back to the parent of the source file when the anonymised node
     * is not accessible.
     *
     * @param int         $sourceFileId NC file ID of the original source file.
     * @param File        $anonNode     The anonymised file node returned by OR.
     * @param string      $userId       User ID for rootFolder lookup.
     * @param IRootFolder $rootFolder   Root folder service.
     *
     * @return Folder|null The source folder, or null when it cannot be determined.
     */
    private static function resolveSourceFolder(
        int $sourceFileId,
        File $anonNode,
        string $userId,
        IRootFolder $rootFolder
    ): ?Folder {
        try {
            $parent = $anonNode->getParent();
            if ($parent instanceof Folder === true) {
                return $parent;
            }
        } catch (\Throwable) {
            // Fall through to source-file lookup.
        }

        $userFolder  = $rootFolder->getUserFolder($userId);
        $sourceNodes = $userFolder->getById($sourceFileId);
        if (empty($sourceNodes) === true) {
            return null;
        }

        $sourceNode = $sourceNodes[0];
        if (($sourceNode instanceof File) === false) {
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

    }//end resolveSourceFolder()

    /**
     * Enumerate direct file children of a folder (flat, no recursion).
     *
     * Excludes files whose base name ends with `_anonymized` — these are
     * legacy redacted outputs from pre-layout runs and must not be
     * re-anonymised by automated folder analysis. The filter is provided
     * by OutputLayoutResolver so both FolderBatchService and
     * FolderExtractionJob use the same rule.
     *
     * @param Folder $folder The folder to enumerate
     *
     * @return File[] Array of file nodes (legacy _anonymized files excluded)
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-5
     * @spec openspec/changes/anonymisation-folder-output-folder-layout/tasks.md#task-3
     */
    private function enumerateFiles(Folder $folder): array
    {
        $files = [];
        foreach ($folder->getDirectoryListing() as $node) {
            if (($node instanceof File) === false) {
                continue;
            }

            if ($this->layoutResolver->hasAnonymizedSuffix(fileName: $node->getName()) === true) {
                $this->logger->debug(
                    'FolderBatchService: skipping legacy _anonymized file in source discovery.',
                    ['fileName' => $node->getName()]
                );
                continue;
            }

            $files[] = $node;
        }

        return $files;

    }//end enumerateFiles()

    /**
     * Get the current user ID
     *
     * @return string The current user ID
     *
     * @throws Exception If no user is logged in
     */
    private function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new Exception('No user is currently logged in.', 401);
        }

        return $user->getUID();

    }//end getCurrentUserId()
}//end class
