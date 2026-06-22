<?php

/**
 * Stubs for OpenRegister classes used in tests
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\OpenRegister\Service;

/**
 * Stub for ObjectService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectService
{


    /**
     * Find an object
     *
     * @return mixed
     */
    public function find()
    {
        return null;
    }


    /**
     * Save an object
     *
     * @return mixed
     */
    public function saveObject()
    {
        return null;
    }


    /**
     * Search objects
     *
     * @return array
     */
    public function searchObjects()
    {
        return [];
    }


    /**
     * Search objects by register/schema slug
     *
     * @return array
     */
    public function searchObjectsBySlug()
    {
        return [];
    }


}

/**
 * Stub for RegisterService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RegisterService
{


    /**
     * Find all registers
     *
     * @return array
     */
    public function findAll($limit = null, $offset = null, $filters = [], $searchConditions = [], $searchParams = [], $_extend = [])
    {
        return [];
    }


}

/**
 * Stub for ConfigurationService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConfigurationService
{


    /**
     * Import from app
     *
     * @return void
     */
    public function importFromApp()
    {
    }


}

/**
 * Stub for TextExtractionService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TextExtractionService
{
}

/**
 * Stub for FileService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileService
{
}

/**
 * Stub for RiskLevelService
 *
 * @category Tests
 * @package  OCA\OpenRegister\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class RiskLevelService
{


    /**
     * Get risk level
     *
     * @param int $fileId File ID
     *
     * @return string
     */
    public function getRiskLevel(int $fileId)
    {
        return 'none';
    }


}

namespace OCA\OpenRegister\Db;

/**
 * Stub for EntityRelationMapper
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityRelationMapper
{


    /**
     * Find by file ID
     *
     * @param int $fileId File ID
     *
     * @return array
     */
    public function findByFileId(int $fileId)
    {
        return [];
    }


    /**
     * Find entities for file
     *
     * @param int $fileId File ID
     *
     * @return array
     */
    public function findEntitiesForFile(int $fileId)
    {
        return [];
    }


    /**
     * Find a single relation by id
     *
     * @param int $id Relation ID
     *
     * @return mixed
     */
    public function find(int $id)
    {
        return new EntityRelation();
    }


    /**
     * Update decision metadata (bases / skipAnonymization) on a relation
     *
     * @param mixed $relation   Relation row
     * @param array $fields     Whitelisted fields to update
     * @param mixed $actingUser Optional acting user
     *
     * @return mixed
     */
    public function updateDecisionMetadata($relation, array $fields, $actingUser = null)
    {
        return $relation;
    }


}

/**
 * Stub for EntityRelation entity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class EntityRelation
{

    /**
     * Relation row id.
     *
     * @var int|null
     */
    private $id = null;

    /**
     * Legal bases (grondslagen) assigned to the relation.
     *
     * @var array|null
     */
    private $bases = null;


    /**
     * Get the relation id.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set the relation id.
     *
     * @param int|null $id Relation id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * Get the assigned bases
     *
     * @return array|null
     */
    public function getBases()
    {
        return $this->bases;
    }


    /**
     * Set the assigned bases
     *
     * @param array|null $bases Bases to assign
     *
     * @return void
     */
    public function setBases($bases)
    {
        $this->bases = $bases;
    }


}

/**
 * Stub for ObjectEntity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectEntity implements \JsonSerializable
{

    /**
     * Flat payload (incl. synthetic @self block).
     *
     * @var array
     */
    private $payload = [];


    /**
     * Set the flat payload returned by jsonSerialize().
     *
     * @param array $payload Payload to return
     *
     * @return void
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }


    /**
     * Return the flat payload.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->payload;
    }


}

namespace OC\Hooks;

/**
 * Stub for OC\Hooks\Emitter interface
 *
 * @category Tests
 * @package  OC\Hooks
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Emitter
{
}
