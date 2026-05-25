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
     * Find an object by id
     *
     * @param string $id       Object UUID
     * @param string $register Register slug
     * @param string $schema   Schema slug
     *
     * @return mixed
     */
    public function find(string $id='', string $register='', string $schema='')
    {
        return null;

    }//end find()


    /**
     * Save an object
     *
     * @param array  $object   Object data
     * @param string $register Register slug
     * @param string $schema   Schema slug
     *
     * @return mixed
     */
    public function saveObject(array $object=[], string $register='', string $schema='')
    {
        return null;

    }//end saveObject()


    /**
     * Delete an object
     *
     * @param string $uuid Object UUID
     *
     * @return void
     */
    public function deleteObject(string $uuid='')
    {

    }//end deleteObject()


    /**
     * Build a search query
     *
     * @param array  $requestParams Search params
     * @param string $register      Register slug
     * @param string $schema        Schema slug
     *
     * @return array
     */
    public function buildSearchQuery(array $requestParams=[], string $register='', string $schema='')
    {
        return [];

    }//end buildSearchQuery()


    /**
     * Search objects (paginated)
     *
     * @param array $query Search query
     *
     * @return array{results: array, total: int}
     */
    public function searchObjectsPaginated(array $query=[])
    {
        return ['results' => [], 'total' => 0];

    }//end searchObjectsPaginated()


    /**
     * Search objects (legacy)
     *
     * @return array
     */
    public function searchObjects()
    {
        return [];

    }//end searchObjects()


}//end class

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
    public function findAll($limit=null, $offset=null, $filters=[], $searchConditions=[], $searchParams=[], $_extend=[])
    {
        return [];

    }//end findAll()


}//end class

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

    }//end importFromApp()


}//end class

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
}//end class

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
}//end class

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

    }//end getRiskLevel()


}//end class

namespace OCA\OpenRegister\Db;

/**
 * Stub for ObjectEntity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectEntity
{


    /**
     * Serialize to array
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [];

    }//end jsonSerialize()


}//end class

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

    }//end findByFileId()


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

    }//end findEntitiesForFile()


}//end class

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
}//end interface
