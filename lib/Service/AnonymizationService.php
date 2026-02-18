<?php
/**
 * Anonymization Service
 *
 * Service for orchestrating the document anonymization pipeline:
 * upload, text extraction with entity detection, and anonymization.
 * Uses OpenRegister services for text extraction and entity recognition.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version  GIT: <git_id>
 * @link     https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for orchestrating the document anonymization pipeline
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationService
{
    /**
     * Constructor for AnonymizationService
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
     * Get the TextExtractionService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\TextExtractionService The TextExtractionService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getTextExtractionService(): \OCA\OpenRegister\Service\TextExtractionService
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\TextExtractionService');
        }

        throw new \RuntimeException('OpenRegister TextExtractionService is not available.');

    }//end getTextExtractionService()

    /**
     * Get the FileService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\FileService The FileService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getFileService(): \OCA\OpenRegister\Service\FileService
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\FileService');
        }

        throw new \RuntimeException('OpenRegister FileService is not available.');

    }//end getFileService()

    /**
     * Get the EntityRelationMapper from OpenRegister
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper The mapper instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getEntityRelationMapper(): \OCA\OpenRegister\Db\EntityRelationMapper
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        }

        throw new \RuntimeException('OpenRegister EntityRelationMapper is not available.');

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
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\RiskLevelService');
        }

        throw new \RuntimeException('OpenRegister RiskLevelService is not available.');

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
            throw new Exception('No user is currently logged in.');
        }

        return $user->getUID();

    }//end getCurrentUserId()

    /**
     * Upload a file to the user's DocuDesk folder
     *
     * Creates the DocuDesk subfolder in the user's files if it doesn't exist,
     * then writes the file content to it.
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
            $userId = $this->getCurrentUserId();
            $userFolder = $this->rootFolder->getUserFolder($userId);

            // Create DocuDesk subfolder if it doesn't exist.
            if ($userFolder->nodeExists('DocuDesk') === false) {
                $userFolder->newFolder('DocuDesk');
            }

            $docuDeskFolder = $userFolder->get('DocuDesk');

            // Handle duplicate file names by appending a number.
            $targetName = $fileName;
            $counter = 1;
            while ($docuDeskFolder->nodeExists($targetName) === true) {
                $pathInfo = pathinfo($fileName);
                $baseName = $pathInfo['filename'];
                $extension = isset($pathInfo['extension']) === true ? '.'.$pathInfo['extension'] : '';
                $targetName = $baseName.'_'.$counter.$extension;
                $counter++;
            }

            $file = $docuDeskFolder->newFile($targetName, $fileContent);

            $this->logger->info(
                'File uploaded to DocuDesk folder',
                [
                    'userId'   => $userId,
                    'fileName' => $targetName,
                    'fileId'   => $file->getId(),
                ]
            );

            return [
                'fileId'   => $file->getId(),
                'filePath' => $file->getPath(),
                'fileName' => $targetName,
                'fileSize' => $file->getSize(),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to upload file: '.$e->getMessage(),
                ['exception' => $e]
            );
            throw new Exception('Failed to upload file: '.$e->getMessage(), 0, $e);
        }

    }//end uploadFile()

    /**
     * Extract text from a file and detect entities
     *
     * Uses OpenRegister's TextExtractionService to extract text,
     * EntityRecognitionHandler to detect entities, and EntityRelationMapper
     * to retrieve the full entity details.
     *
     * @param int $fileId The Nextcloud file ID
     *
     * @return array<string, mixed> Extraction result with entities, entityCount, chunksProcessed
     *
     * @throws Exception If extraction or detection fails
     */
    public function extractAndDetectEntities(int $fileId): array
    {
        try {
            // Step 1: Extract text from the file.
            $textExtractionService = $this->getTextExtractionService();
            $extractionResult = $textExtractionService->extractFile($fileId, true);

            $this->logger->debug(
                'Text extracted from file',
                [
                    'fileId' => $fileId,
                    'result' => is_array($extractionResult) === true ? array_keys($extractionResult) : 'non-array',
                ]
            );

            // Step 2: Retrieve full entity details.
            // Entity recognition already ran inside extractFile() using the method
            // configured in OpenRegister file settings (e.g. presidio, openanonymiser, hybrid).
            $entityRelationMapper = $this->getEntityRelationMapper();
            $entities = $entityRelationMapper->findEntitiesForFile($fileId);

            // Normalize entity data to a consistent format.
            $normalizedEntities = [];
            foreach ($entities as $entity) {
                $entityData = is_object($entity) === true && method_exists($entity, 'jsonSerialize') === true
                    ? $entity->jsonSerialize()
                    : (array) $entity;

                $normalizedEntities[] = [
                    'type'       => $entityData['entity_type'] ?? $entityData['entityType'] ?? 'UNKNOWN',
                    'value'      => $entityData['entity_value'] ?? $entityData['entityValue'] ?? '',
                    'confidence' => $entityData['confidence'] ?? 0.0,
                ];
            }

            return [
                'entities'        => $normalizedEntities,
                'entityCount'     => count($normalizedEntities),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to extract and detect entities: '.$e->getMessage(),
                [
                    'fileId'    => $fileId,
                    'exception' => $e,
                ]
            );
            throw new Exception('Failed to extract and detect entities: '.$e->getMessage(), 0, $e);
        }

    }//end extractAndDetectEntities()

    /**
     * Anonymize entities in a document
     *
     * Maps entities to the format expected by OpenRegister's FileService
     * and calls anonymizeDocument to create an anonymized copy.
     *
     * @param int                    $fileId   The Nextcloud file ID
     * @param array<array<string, mixed>> $entities The entities to anonymize
     *
     * @return array<string, mixed> Anonymization result with anonymizedFileId, anonymizedFileName, etc.
     *
     * @throws Exception If anonymization fails
     */
    public function anonymizeDocument(int $fileId, array $entities): array
    {
        try {
            $fileService = $this->getFileService();

            // Get the file node.
            $node = $fileService->getFileById($fileId);

            // Map entities to the format expected by OpenRegister's anonymizeDocument.
            // All text values must be strings to avoid str_ireplace() type errors
            // in OpenRegister's DocumentProcessingHandler (PHP casts numeric string
            // array keys to integers, causing TypeError).
            $mappedEntities = [];
            $seen = [];
            foreach ($entities as $entity) {
                $text = (string) ($entity['value'] ?? $entity['text'] ?? '');

                // Skip empty, very short, or purely numeric values.
                // Numeric strings like "2026" or "0" cause PHP array key type coercion.
                if ($text === '' || strlen($text) < 3 || is_numeric($text) === true) {
                    continue;
                }

                // Skip duplicate entity values.
                if (isset($seen[$text]) === true) {
                    continue;
                }
                $seen[$text] = true;

                $mappedEntities[] = [
                    'text'       => $text,
                    'entityType' => (string) ($entity['type'] ?? $entity['entityType'] ?? 'UNKNOWN'),
                    'key'        => $this->generateUuid(),
                ];
            }

            // Call the anonymization.
            $result = $fileService->anonymizeDocument($node, $mappedEntities);

            $this->logger->info(
                'Document anonymized',
                [
                    'fileId'       => $fileId,
                    'entityCount'  => count($mappedEntities),
                ]
            );

            // Extract result information.
            $anonymizedFileId   = null;
            $anonymizedFileName = null;
            $anonymizedFilePath = null;

            if (is_object($result) === true && method_exists($result, 'getId') === true) {
                $anonymizedFileId   = $result->getId();
                $anonymizedFileName = $result->getName();
                $anonymizedFilePath = $result->getPath();
            } elseif (is_array($result) === true) {
                $anonymizedFileId   = $result['fileId'] ?? $result['id'] ?? null;
                $anonymizedFileName = $result['fileName'] ?? $result['name'] ?? null;
                $anonymizedFilePath = $result['filePath'] ?? $result['path'] ?? null;
            }

            return [
                'anonymizedFileId'   => $anonymizedFileId,
                'anonymizedFileName' => $anonymizedFileName,
                'anonymizedFilePath' => $anonymizedFilePath,
                'replacementCount'   => count($mappedEntities),
            ];
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to anonymize document: '.$e->getMessage(),
                [
                    'fileId'    => $fileId,
                    'exception' => $e,
                ]
            );
            throw new Exception('Failed to anonymize document: '.$e->getMessage(), 0, $e);
        }

    }//end anonymizeDocument()

    /**
     * List all processed files in the user's DocuDesk folder with entity counts and status
     *
     * Scans the DocuDesk folder and joins with entity relation data
     * from OpenRegister to provide entity counts and anonymization status.
     *
     * @return array<int, array<string, mixed>> Array of file info with entityCount, status
     */
    public function listProcessedFiles(): array
    {
        try {
            $userId = $this->getCurrentUserId();
            $userFolder = $this->rootFolder->getUserFolder($userId);

            if ($userFolder->nodeExists('DocuDesk') === false) {
                return [];
            }

            $docuDeskFolder = $userFolder->get('DocuDesk');
            $files = $docuDeskFolder->getDirectoryListing();

            $entityRelationMapper = null;
            try {
                $entityRelationMapper = $this->getEntityRelationMapper();
            } catch (\RuntimeException $e) {
                $this->logger->warning('EntityRelationMapper not available: '.$e->getMessage());
            }

            $riskLevelService = null;
            try {
                $riskLevelService = $this->getRiskLevelService();
            } catch (\RuntimeException $e) {
                $this->logger->warning('RiskLevelService not available: '.$e->getMessage());
            }

            $result = [];
            foreach ($files as $file) {
                if ($file instanceof \OCP\Files\File === false) {
                    continue;
                }

                $fileId = $file->getId();
                $entityCount = 0;
                $anonymizedCount = 0;
                $status = 'uploaded';

                if ($entityRelationMapper !== null) {
                    try {
                        $relations = $entityRelationMapper->findByFileId($fileId);
                        $entityCount = count($relations);

                        foreach ($relations as $relation) {
                            if ($relation->getAnonymized() === true) {
                                $anonymizedCount++;
                            }
                        }

                        if ($entityCount > 0 && $anonymizedCount === $entityCount) {
                            $status = 'anonymized';
                        } elseif ($entityCount > 0) {
                            $status = 'extracted';
                        }
                    } catch (\Exception $e) {
                        $this->logger->debug('Could not fetch entities for file '.$fileId.': '.$e->getMessage());
                    }
                }

                $riskLevel = 'none';
                if ($riskLevelService !== null) {
                    try {
                        $riskLevel = $riskLevelService->getRiskLevel($fileId);
                    } catch (\Exception $e) {
                        $this->logger->debug('Could not fetch risk level for file '.$fileId.': '.$e->getMessage());
                    }
                }

                $result[] = [
                    'fileId'         => $fileId,
                    'fileName'       => $file->getName(),
                    'filePath'       => $file->getPath(),
                    'fileSize'       => $file->getSize(),
                    'mimeType'       => $file->getMimeType(),
                    'entityCount'    => $entityCount,
                    'anonymizedCount' => $anonymizedCount,
                    'status'         => $status,
                    'riskLevel'      => $riskLevel,
                    'modified'       => $file->getMTime(),
                ];
            }

            // Sort by modification time descending (newest first).
            usort($result, function ($a, $b) {
                return $b['modified'] - $a['modified'];
            });

            return $result;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to list processed files: '.$e->getMessage(),
                ['exception' => $e]
            );
            throw new Exception('Failed to list processed files: '.$e->getMessage(), 0, $e);
        }

    }//end listProcessedFiles()

    /**
     * Generate a UUID v4 string
     *
     * @return string A UUID v4 string
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    }//end generateUuid()

}//end class
