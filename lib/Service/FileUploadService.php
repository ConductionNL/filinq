<?php
/**
 * File Upload Service
 *
 * Service for uploading files to the user's DocuDesk folder.
 * Handles folder creation and unique file name resolution.
 * Extracted from FileListingService to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service for uploading files to the DocuDesk folder
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileUploadService
{
    /**
     * Constructor for FileUploadService
     *
     * @param LoggerInterface $logger      Logger for error reporting
     * @param IRootFolder     $rootFolder  Root folder for file operations
     * @param IUserSession    $userSession User session for getting current user
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession
    ) {

    }//end __construct()

    /**
     * Get the current user ID
     *
     * @return string The current user ID
     *
     * @throws Exception If no user is logged in
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
     */
    public function getCurrentUserId(): string
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new Exception('No user is currently logged in.', 401);
        }

        return $user->getUID();

    }//end getCurrentUserId()

    /**
     * Get the DocuDesk folder for the current user, creating it if needed
     *
     * @return \OCP\Files\Folder The DocuDesk folder
     *
     * @throws Exception If folder creation fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
     */
    public function getDocuDeskFolder(): \OCP\Files\Folder
    {
        $userId     = $this->getCurrentUserId();
        $userFolder = $this->rootFolder->getUserFolder($userId);

        if ($userFolder->nodeExists('DocuDesk') === false) {
            $userFolder->newFolder('DocuDesk');
        }

        $docuDeskNode = $userFolder->get('DocuDesk');
        if ($docuDeskNode instanceof \OCP\Files\Folder === false) {
            throw new Exception('DocuDesk path exists but is not a folder.');
        }

        return $docuDeskNode;

    }//end getDocuDeskFolder()

    /**
     * Resolve a unique file name within a folder by appending a counter
     *
     * @param \OCP\Files\Folder $folder   The folder to check for duplicates
     * @param string            $fileName The desired file name
     *
     * @return string A unique file name
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
     */
    public function resolveUniqueFileName(\OCP\Files\Folder $folder, string $fileName): string
    {
        $targetName = $fileName;
        $counter    = 1;
        while ($folder->nodeExists($targetName) === true) {
            $pathInfo  = pathinfo($fileName);
            $baseName  = $pathInfo['filename'];
            $extension = '';
            if (isset($pathInfo['extension']) === true) {
                $extension = '.'.$pathInfo['extension'];
            }

            $targetName = $baseName.'_'.$counter.$extension;
            $counter++;
        }

        return $targetName;

    }//end resolveUniqueFileName()

    /**
     * Upload a file to the user's DocuDesk folder
     *
     * @param string $fileName    The name of the file to upload
     * @param string $fileContent The raw file content
     *
     * @return array<string, mixed> Upload result with fileId, filePath, fileName, fileSize
     *
     * @throws Exception If the upload fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
     */
    public function uploadFile(string $fileName, string $fileContent): array
    {
        try {
            $docuDeskFolder = $this->getDocuDeskFolder();
            $targetName     = $this->resolveUniqueFileName(folder: $docuDeskFolder, fileName: $fileName);
            $file           = $docuDeskFolder->newFile($targetName, $fileContent);

            $this->logger->info(
                'File uploaded to DocuDesk folder',
                ['fileName' => $targetName, 'fileId' => $file->getId()]
            );

            return [
                'fileId'   => $file->getId(),
                'filePath' => $file->getPath(),
                'fileName' => $targetName,
                'fileSize' => $file->getSize(),
            ];
        } catch (Exception $e) {
            $this->logger->error('Failed to upload file: '.$e->getMessage(), ['exception' => $e]);
            throw new Exception('Failed to upload file: '.$e->getMessage(), $e->getCode(), $e);
        }//end try

    }//end uploadFile()
}//end class
