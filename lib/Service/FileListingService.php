<?php
/**
 * File Listing Service
 *
 * Service for listing processed files in the user's DocuDesk folder.
 * Retrieves entity counts, anonymization status, and risk levels
 * from OpenRegister services.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for listing processed files with entity and risk data
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileListingService
{


    /**
     * Constructor for FileListingService
     *
     * @param LoggerInterface    $logger      Logger for error reporting
     * @param ContainerInterface $container   Container for dependency injection
     * @param IAppManager        $appManager  App manager interface
     * @param IRootFolder        $rootFolder  Root folder for file operations
     * @param IUserSession       $userSession User session for getting current user
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession
    ) {

    }//end __construct()


    /**
     * Check if OpenRegister app is installed
     *
     * @return bool True if OpenRegister is installed
     */
    private function isOpenRegisterInstalled(): bool
    {
        return in_array('openregister', $this->appManager->getInstalledApps(), true) === true;

    }//end isOpenRegisterInstalled()


    /**
     * Get the EntityRelationMapper from OpenRegister
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper The mapper instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getEntityRelationMapper(): \OCA\OpenRegister\Db\EntityRelationMapper
    {
        if ($this->isOpenRegisterInstalled() === true) {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        }

        throw new RuntimeException('OpenRegister EntityRelationMapper is not available.');

    }//end getEntityRelationMapper()


    /**
     * Get the RiskLevelService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\RiskLevelService The RiskLevelService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getRiskLevelService(): \OCA\OpenRegister\Service\RiskLevelService
    {
        if ($this->isOpenRegisterInstalled() === true) {
            return $this->container->get('OCA\OpenRegister\Service\RiskLevelService');
        }

        throw new RuntimeException('OpenRegister RiskLevelService is not available.');

    }//end getRiskLevelService()


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


    /**
     * Try to get the EntityRelationMapper, returning null on failure
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper|null The mapper or null
     */
    private function tryGetEntityRelationMapper(): ?\OCA\OpenRegister\Db\EntityRelationMapper
    {
        try {
            return $this->getEntityRelationMapper();
        } catch (\RuntimeException $e) {
            $this->logger->warning('EntityRelationMapper not available: '.$e->getMessage());
            return null;
        }

    }//end tryGetEntityRelationMapper()


    /**
     * Try to get the RiskLevelService, returning null on failure
     *
     * @return \OCA\OpenRegister\Service\RiskLevelService|null The service or null
     */
    private function tryGetRiskLevelService(): ?\OCA\OpenRegister\Service\RiskLevelService
    {
        try {
            return $this->getRiskLevelService();
        } catch (\RuntimeException $e) {
            $this->logger->warning('RiskLevelService not available: '.$e->getMessage());
            return null;
        }

    }//end tryGetRiskLevelService()


    /**
     * Get entity statistics for a file
     *
     * @param int                                            $fileId               The file ID
     * @param \OCA\OpenRegister\Db\EntityRelationMapper|null $entityRelationMapper The mapper
     *
     * @return array{entityCount: int, anonymizedCount: int, status: string} Entity stats
     */
    private function getEntityStats(
        int $fileId,
        ?\OCA\OpenRegister\Db\EntityRelationMapper $entityRelationMapper
    ): array {
        $stats = [
            'entityCount'     => 0,
            'anonymizedCount' => 0,
            'status'          => 'uploaded',
        ];

        if ($entityRelationMapper === null) {
            return $stats;
        }

        try {
            $relations            = $entityRelationMapper->findByFileId($fileId);
            $stats['entityCount'] = count($relations);
            $anonymized           = 0;

            foreach ($relations as $relation) {
                if ($relation->getAnonymized() === true) {
                    $anonymized++;
                }
            }

            $stats['anonymizedCount'] = $anonymized;
            $stats['status']          = $this->determineFileStatus($stats['entityCount'], $anonymized);
        } catch (\Exception $e) {
            $this->logger->debug('Could not fetch entities for file '.$fileId.': '.$e->getMessage());
        }

        return $stats;

    }//end getEntityStats()


    /**
     * Determine file status based on entity counts
     *
     * @param int $entityCount     Total entity count
     * @param int $anonymizedCount Anonymized entity count
     *
     * @return string The status string
     */
    private function determineFileStatus(int $entityCount, int $anonymizedCount): string
    {
        if ($entityCount > 0 && $anonymizedCount === $entityCount) {
            return 'anonymized';
        }

        if ($entityCount > 0) {
            return 'extracted';
        }

        return 'uploaded';

    }//end determineFileStatus()


    /**
     * Get risk level for a file
     *
     * @param int                                             $fileId           The file ID
     * @param \OCA\OpenRegister\Service\RiskLevelService|null $riskLevelService The service
     *
     * @return string The risk level
     */
    private function getFileRiskLevel(
        int $fileId,
        ?\OCA\OpenRegister\Service\RiskLevelService $riskLevelService
    ): string {
        if ($riskLevelService === null) {
            return 'none';
        }

        try {
            return $riskLevelService->getRiskLevel($fileId);
        } catch (\Exception $e) {
            $this->logger->debug('Could not fetch risk level for file '.$fileId.': '.$e->getMessage());
            return 'none';
        }

    }//end getFileRiskLevel()


    /**
     * Build info array for a single file
     *
     * @param \OCP\Files\File                                 $file                 The file
     * @param \OCA\OpenRegister\Db\EntityRelationMapper|null  $entityRelationMapper The mapper
     * @param \OCA\OpenRegister\Service\RiskLevelService|null $riskLevelService     The service
     *
     * @return array<string, mixed> File info
     */
    private function buildFileInfo(
        \OCP\Files\File $file,
        ?\OCA\OpenRegister\Db\EntityRelationMapper $entityRelationMapper,
        ?\OCA\OpenRegister\Service\RiskLevelService $riskLevelService
    ): array {
        $fileId      = $file->getId();
        $entityStats = $this->getEntityStats($fileId, $entityRelationMapper);
        $riskLevel   = $this->getFileRiskLevel($fileId, $riskLevelService);

        return [
            'fileId'          => $fileId,
            'fileName'        => $file->getName(),
            'filePath'        => $file->getPath(),
            'fileSize'        => $file->getSize(),
            'mimeType'        => $file->getMimeType(),
            'entityCount'     => $entityStats['entityCount'],
            'anonymizedCount' => $entityStats['anonymizedCount'],
            'status'          => $entityStats['status'],
            'riskLevel'       => $riskLevel,
            'modified'        => $file->getMTime(),
        ];

    }//end buildFileInfo()


    /**
     * List all processed files in the user's DocuDesk folder
     *
     * @return array<int, array<string, mixed>> Array of file info
     */
    public function listProcessedFiles(): array
    {
        try {
            $userId     = $this->getCurrentUserId();
            $userFolder = $this->rootFolder->getUserFolder($userId);

            if ($userFolder->nodeExists('DocuDesk') === false) {
                return [];
            }

            $docuDeskNode = $userFolder->get('DocuDesk');
            if ($docuDeskNode instanceof \OCP\Files\Folder === false) {
                return [];
            }

            $files = $docuDeskNode->getDirectoryListing();
            $entityRelationMapper = $this->tryGetEntityRelationMapper();
            $riskLevelService     = $this->tryGetRiskLevelService();

            $result = [];
            foreach ($files as $file) {
                if ($file instanceof \OCP\Files\File === false) {
                    continue;
                }

                $result[] = $this->buildFileInfo($file, $entityRelationMapper, $riskLevelService);
            }

            usort(
                $result,
                function ($left, $right) {
                    return $right['modified'] - $left['modified'];
                }
            );

            return $result;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to list processed files: '.$e->getMessage(),
                ['exception' => $e]
            );
            throw new Exception('Failed to list processed files: '.$e->getMessage(), $e->getCode(), $e);
        }//end try

    }//end listProcessedFiles()


    /**
     * Get the DocuDesk folder for the current user, creating it if needed
     *
     * @return \OCP\Files\Folder The DocuDesk folder
     *
     * @throws Exception If folder creation fails
     */
    private function getDocuDeskFolder(): \OCP\Files\Folder
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
     */
    private function resolveUniqueFileName(\OCP\Files\Folder $folder, string $fileName): string
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
     */
    public function uploadFile(string $fileName, string $fileContent): array
    {
        try {
            $docuDeskFolder = $this->getDocuDeskFolder();
            $targetName     = $this->resolveUniqueFileName($docuDeskFolder, $fileName);
            $file           = $docuDeskFolder->newFile($targetName, $fileContent);

            $this->logger->info('File uploaded to DocuDesk folder', ['fileName' => $targetName, 'fileId' => $file->getId()]);

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
