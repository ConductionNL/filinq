<?php
/**
 * FolderBatchService
 *
 * Creates anonymization batches from existing Nextcloud folders identified by
 * either ID or path.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
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
 */
class FolderBatchService
{


    /**
     * Constructor for FolderBatchService
     *
     * @param LoggerInterface   $logger       Logger for error reporting
     * @param IRootFolder       $rootFolder   Root folder for file operations
     * @param IUserSession      $userSession  User session for current user
     * @param BatchStateService $stateService Batch state management
     * @param IJobList          $jobList      Background job list
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly BatchStateService $stateService,
        private readonly IJobList $jobList
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

        // Queue the extraction job and fire it immediately (no cron wait).
        $this->jobList->add(FolderExtractionJob::class, ['batchId' => $batch['batchId']]);
        $this->fireJob(batchId: $batch['batchId']);

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
     * Find the queued job by batchId and execute it immediately via occ
     *
     * @param string $batchId The batch ID to match
     *
     * @return void
     */
    private function fireJob(string $batchId): void
    {
        $jobId = null;
        foreach ($this->jobList->getJobs(FolderExtractionJob::class, 100, 0) as $job) {
            $arg = $job->getArgument();
            if (is_array($arg) === true && ($arg['batchId'] ?? '') === $batchId) {
                $jobId = $job->getId();
                break;
            }
        }

        if ($jobId === null) {
            $this->logger->warning('Could not find job to fire immediately', ['batchId' => $batchId]);
            return;
        }

        $phpBinary  = PHP_BINARY;
        $serverRoot = \OC::$SERVERROOT;

        exec($phpBinary.' '.$serverRoot.'/occ background-job:execute '.$jobId.' > /dev/null 2>&1 &');

    }//end fireJob()


    /**
     * Enumerate direct file children of a folder (flat, no recursion)
     *
     * @param Folder $folder The folder to enumerate
     *
     * @return File[] Array of file nodes
     */
    private function enumerateFiles(Folder $folder): array
    {
        $files = [];
        foreach ($folder->getDirectoryListing() as $node) {
            if ($node instanceof File) {
                $files[] = $node;
            }
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
