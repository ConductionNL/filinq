<?php

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\BackgroundJob\FolderExtractionJob;
use OCP\BackgroundJob\IJobList;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service for creating batches from existing Nextcloud folders.
 *
 * Enumerates direct children of a folder (flat, no recursion),
 * creates a batch via BatchStateService, and fires a
 * FolderExtractionJob immediately for background extraction.
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
     * Resolves the folder path, enumerates file children (flat),
     * creates the batch, and fires extraction immediately in the background.
     *
     * @param string $folderPath Path relative to the user's root folder
     *
     * @return array<string, mixed> Batch data with batchId, fileCount, files
     *
     * @throws Exception If folder is not found, not a folder, empty, or too large
     */
    public function createFolderBatch(string $folderPath): array
    {
        $userId     = $this->getCurrentUserId();
        $userFolder = $this->rootFolder->getUserFolder($userId);

        try {
            $node = $userFolder->get($folderPath);
        } catch (NotFoundException $e) {
            throw new Exception('Folder not found', 404, $e);
        }

        if (($node instanceof Folder) === false) {
            throw new Exception('Path is not a folder', 400);
        }

        $files = $this->enumerateFiles($node);

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

        // Store folder metadata on the batch.
        $batch['sourceType'] = 'folder';
        $batch['folderPath'] = $folderPath;
        $this->stateService->updateBatch($batch['batchId'], $batch);

        // Queue the extraction job and fire it immediately (no cron wait).
        $this->jobList->add(FolderExtractionJob::class, ['batchId' => $batch['batchId']]);
        $this->fireJob($batch['batchId']);

        $this->logger->info(
            'Folder batch created, extraction job fired',
            ['batchId' => $batch['batchId'], 'folderPath' => $folderPath, 'fileCount' => count($batchFiles)]
        );

        return $batch;

    }//end createFolderBatch()


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

        $phpBinary  = PHP_BINARY ?: 'php';
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
