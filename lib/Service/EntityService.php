<?php
/**
 * Service for managing entity objects
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Service;

use Exception;
use OCP\IAppConfig;
use OCA\OpenRegister\Service\ObjectService;
use Psr\Log\LoggerInterface;

/**
 * Service for managing entity objects
 *
 * This service provides functionality for creating, retrieving, and managing
 * entity objects that store information about detected entities across documents.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 */
class EntityService
{
    /**
     * Logger instance for error reporting
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Object service for storing entity objects
     *
     * @var ObjectService
     */
    private ObjectService $objectService;

    /**
     * App configuration service
     *
     * @var IAppConfig
     */
    private IAppConfig $appConfig;

    /**
     * Entity register type
     *
     * @var string
     */
    private string $entityRegisterType;

    /**
     * Entity schema type
     *
     * @var string
     */
    private string $entitySchemaType;

    /**
     * Constructor for EntityService
     *
     * @param LoggerInterface $logger        Logger for error reporting
     * @param ObjectService   $objectService Service for storing objects
     * @param IAppConfig      $appConfig     App configuration service
     *
     * @return void
     */
    public function __construct(
        LoggerInterface $logger,
        ObjectService $objectService,
        IAppConfig $appConfig
    ) {
        $this->logger        = $logger;
        $this->objectService = $objectService;
        $this->appConfig     = $appConfig;

        // Get entity configuration from app config
        $this->entityRegisterType = $this->appConfig->getValueString('docudesk', 'entity_register', 'document');
        $this->entitySchemaType   = $this->appConfig->getValueString('docudesk', 'entity_schema', 'entity');

        // Set the object service to use the entity configuration
        $this->objectService->setRegister($this->entityRegisterType);
        $this->objectService->setSchema($this->entitySchemaType);

    }//end __construct()

    /**
     * Find or create an entity object based on text and entityType
     *
     * This method searches for an existing entity object with the same text and entityType.
     * If not found, it creates a new entity object.
     *
     * @param string $text       The entity text content
     * @param string $entityType The type of entity (PERSON, ORGANIZATION, etc.)
     *
     * @return array{
     *     id: string,
     *     text: string,
     *     entityType: string,
     *     occurrenceCount: int,
     *     firstDetected: string,
     *     lastDetected: string,
     *     averageScore: float,
     *     maxScore: float,
     *     minScore: float
     * } The entity object data
     *
     * @throws Exception If entity creation or retrieval fails
     *
     * @psalm-return   array
     * @phpstan-return array
     */
    public function findOrCreateEntity(string $text, string $entityType): array
    {
        try {
            // Search for existing entity with same text and entityType
            $config['filters'] = [
                'text'       => $text,
                'entityType' => $entityType,
                'register'   => $this->entityRegisterType,
                'schema'     => $this->entitySchemaType,
            ];

            $existingEntities = $this->objectService->findAll($config);

            if (empty($existingEntities) === false) {
                // Return the first matching entity
                $entity = $existingEntities[0]->jsonSerialize();
                $this->logger->debug('Found existing entity: '.$entity['id'].' for text: '.$text);
                return $entity;
            }

            // Create new entity object
            $entityData = [
                'text'            => $text,
                'entityType'      => $entityType,
                'occurrenceCount' => 0,
                'firstDetected'   => date('c'),
                'lastDetected'    => date('c'),
                'averageScore'    => 0.0,
                'maxScore'        => 0.0,
                'minScore'        => 1.0,
            ];

            $entityObject = $this->objectService->saveObject(
                object: $entityData,
                register: $this->entityRegisterType,
                schema: $this->entitySchemaType
            );

            $entity = $entityObject->jsonSerialize();
            
            // Verify the entity was saved successfully
            if (empty($entity['id']) === true) {
                throw new Exception('Failed to create entity: no ID returned');
            }
            
            $this->logger->debug('Created new entity: '.$entity['id'].' for text: '.$text);
            return $entity;

        } catch (Exception $e) {
            $this->logger->error(
                'Failed to find or create entity: '.$e->getMessage(),
                [
                    'text'                => $text,
                    'entityType'          => $entityType,
                    'entityRegisterType'  => $this->entityRegisterType,
                    'entitySchemaType'    => $this->entitySchemaType,
                    'exception'           => $e,
                ]
            );
            throw new Exception('Failed to find or create entity: '.$e->getMessage(), 0, $e);
        }//end try

    }//end findOrCreateEntity()

    /**
     * Update entity statistics when it's detected in a document
     *
     * This method updates the occurrence count, detection timestamps, and score statistics
     * for an entity when it's detected in a document.
     *
     * @param string $entityId The ID of the entity object
     * @param float  $score    The confidence score for this detection
     *
     * @return array The updated entity object data
     *
     * @throws Exception If entity update fails
     *
     * @psalm-return   array
     * @phpstan-return array
     */
    public function updateEntityStatistics(string $entityId, float $score): array
    {
        try {
            // Get the current entity object
            $entity = $this->objectService->getObject($this->entitySchemaType, $entityId);

            if ($entity === null) {
                // Log additional debug information
                $this->logger->error(
                    'Entity not found in updateEntityStatistics',
                    [
                        'entityId'             => $entityId,
                        'entitySchemaType'     => $this->entitySchemaType,
                        'entityRegisterType'   => $this->entityRegisterType,
                    ]
                );
                throw new Exception('Entity not found: '.$entityId);
            }

            // Update statistics
            $occurrenceCount = ($entity['occurrenceCount'] ?? 0) + 1;
            $currentAverage  = $entity['averageScore'] ?? 0.0;
            $maxScore        = max($entity['maxScore'] ?? 0.0, $score);
            $minScore        = min($entity['minScore'] ?? 1.0, $score);

            // Calculate new average score
            $newAverage = (($currentAverage * ($occurrenceCount - 1)) + $score) / $occurrenceCount;

            // Update entity data
            $entity['occurrenceCount'] = $occurrenceCount;
            $entity['lastDetected']    = date('c');
            $entity['averageScore']    = $newAverage;
            $entity['maxScore']        = $maxScore;
            $entity['minScore']        = $minScore;

            // Save updated entity
            $entityObject = $this->objectService->saveObject(
                object: $entity,
                uuid: $entityId
            );

            $updatedEntity = $entityObject->jsonSerialize();
            $this->logger->debug('Updated entity statistics for: '.$entityId);
            return $updatedEntity;

        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update entity statistics: '.$e->getMessage(),
                [
                    'entityId'  => $entityId,
                    'score'     => $score,
                    'exception' => $e,
                ]
            );
            throw new Exception('Failed to update entity statistics: '.$e->getMessage(), 0, $e);
        }//end try

    }//end updateEntityStatistics()

    /**
     * Get entity by ID
     *
     * @param string $entityId The ID of the entity to retrieve
     *
     * @return array|null The entity data or null if not found
     *
     * @psalm-return   array|null
     * @phpstan-return array|null
     */
    public function getEntity(string $entityId): ?array
    {
        try {
            return $this->objectService->getObject($this->entitySchemaType, $entityId);
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to retrieve entity: '.$e->getMessage(),
                [
                    'entityId'  => $entityId,
                    'exception' => $e,
                ]
            );
            return null;
        }

    }//end getEntity()

    /**
     * Get all entities with optional filters
     *
     * @param array $filters Optional filters for entity search
     * @param int   $limit   Maximum number of entities to return
     * @param int   $offset  Offset for pagination
     *
     * @return array List of entity objects
     *
     * @psalm-return   array
     * @phpstan-return array
     */
    public function getEntities(array $filters=[], int $limit=50, int $offset=0): array
    {
        try {
            $config = [
                'filters' => array_merge($filters, [
                    'register' => $this->entityRegisterType,
                    'schema'   => $this->entitySchemaType,
                ]),
                'limit'   => $limit,
                'offset'  => $offset,
            ];

            $entities = $this->objectService->findAll($config);

            return array_map(function ($entity) {
                return $entity->jsonSerialize();
            }, $entities);

        } catch (Exception $e) {
            $this->logger->error(
                'Failed to retrieve entities: '.$e->getMessage(),
                [
                    'filters'   => $filters,
                    'exception' => $e,
                ]
            );
            return [];
        }

    }//end getEntities()

    /**
     * Delete an entity by ID
     *
     * @param string $entityId ID of the entity to delete
     *
     * @return bool True if deletion was successful, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    public function deleteEntity(string $entityId): bool
    {
        try {
            return $this->objectService->deleteObject($this->entitySchemaType, $entityId);
        } catch (Exception $e) {
            $this->logger->error('Failed to delete entity: '.$e->getMessage(), ['exception' => $e]);
            return false;
        }

    }//end deleteEntity()

}//end class 