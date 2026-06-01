<?php
/**
 * File Listing Service
 *
 * Service for listing processed files in the user's DocuDesk folder.
 * Delegates entity stats to FileEntityStatsService and upload logic
 * to FileUploadService.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-1
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-2
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-3.5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service for listing processed files with entity and risk data
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/ocr-document-scanning/tasks.md#task-3.5
 */
class FileListingService
{
    /**
     * Constructor for FileListingService
     *
     * @param LoggerInterface        $logger             Logger for error reporting
     * @param FileUploadService      $fileUploadService  Upload and folder management
     * @param FileEntityStatsService $entityStatsService Entity counts and risk levels
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly FileUploadService $fileUploadService,
        private readonly FileEntityStatsService $entityStatsService
    ) {

    }//end __construct()

    /**
     * Build info array for a single file
     *
     * @param \OCP\Files\File                                 $file                 The file
     * @param \OCA\OpenRegister\Db\EntityRelationMapper|null  $entityRelationMapper The mapper
     * @param \OCA\OpenRegister\Service\RiskLevelService|null $riskLevelService     The service
     *
     * @return array<string, mixed> File info
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-1
     */
    private function buildFileInfo(
        \OCP\Files\File $file,
        ?\OCA\OpenRegister\Db\EntityRelationMapper $entityRelationMapper,
        ?\OCA\OpenRegister\Service\RiskLevelService $riskLevelService
    ): array {
        $fileId      = $file->getId();
        $entityStats = $this->entityStatsService->getEntityStats($fileId, $entityRelationMapper);
        $riskLevel   = $this->entityStatsService->getFileRiskLevel($fileId, $riskLevelService);
        $mimeType    = $file->getMimeType();

        // Determine if file is an OCR candidate based on MIME type.
        $ocrMimeTypes   = [
            'image/png',
            'image/jpeg',
            'image/tiff',
            'image/bmp',
            'image/gif',
        ];
        $isOcrCandidate = in_array($mimeType, $ocrMimeTypes, true) === true
            || $mimeType === 'application/pdf';

        return [
            'fileId'          => $fileId,
            'fileName'        => $file->getName(),
            'filePath'        => $file->getPath(),
            'fileSize'        => $file->getSize(),
            'mimeType'        => $mimeType,
            'entityCount'     => $entityStats['entityCount'],
            'anonymizedCount' => $entityStats['anonymizedCount'],
            'status'          => $entityStats['status'],
            'riskLevel'       => $riskLevel,
            'modified'        => $file->getMTime(),
            'ocrProcessed'    => $isOcrCandidate
                && $entityStats['status'] !== 'uploaded',
            'ocrConfidence'   => null,
        ];

    }//end buildFileInfo()

    /**
     * List all processed files in the user's DocuDesk folder
     *
     * @return array<int, array<string, mixed>> Array of file info
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-1
     */
    public function listProcessedFiles(): array
    {
        try {
            // Ensures the user is authenticated (throws otherwise).
            $this->fileUploadService->getCurrentUserId();
            $docuDeskFolder = $this->fileUploadService->getDocuDeskFolder();

            $files = $docuDeskFolder->getDirectoryListing();
            $entityRelationMapper = $this->entityStatsService->tryGetEntityRelationMapper();
            $riskLevelService     = $this->entityStatsService->tryGetRiskLevelService();

            $result = [];
            foreach ($files as $file) {
                if ($file instanceof \OCP\Files\File === false) {
                    continue;
                }

                $result[] = $this->buildFileInfo(
                    file: $file,
                    entityRelationMapper: $entityRelationMapper,
                    riskLevelService: $riskLevelService
                );
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
            throw new Exception(
                'Failed to list processed files: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }//end try

    }//end listProcessedFiles()

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
        return $this->fileUploadService->uploadFile($fileName, $fileContent);

    }//end uploadFile()
}//end class
