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
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
     * Find all objects matching a set of filters
     *
     * @param array<string, mixed> $config Config with 'filters' key
     *
     * @return array<mixed>
     */
    public function findAll(array $config=[]): array
    {
        return [];

    }//end findAll()


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
     * Search objects
     *
     * @param array<string, mixed> $query Search query with optional @self scope and filters
     *
     * @return array
     */
    public function searchObjects(array $query=[])
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

    public function extractFile(int $fileId, bool $force = false): void
    {
    }

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
}

namespace OCP;

/**
 * Stub for OCP\IUserSession
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IUserSession
{

    /**
     * Get the currently logged in user
     *
     * @return \OCP\IUser|null
     */
    public function getUser(): ?\OCP\IUser;


}//end interface


/**
 * Stub for OCP\IAppConfig
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IAppConfig
{

    public function getValueFloat(string $app, string $key, float $default = 0, bool $lazy = false): float;
    public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string;
    public function getValueInt(string $app, string $key, int $default = 0, bool $lazy = false): int;
    public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool;
    public function getValueArray(string $app, string $key, array $default = [], bool $lazy = false): array;
    public function setValueFloat(string $app, string $key, float $value, bool $lazy = false, bool $sensitive = false): bool;
    public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;
    public function setValueInt(string $app, string $key, int $value, bool $lazy = false, bool $sensitive = false): bool;
    public function setValueBool(string $app, string $key, bool $value, bool $lazy = false): bool;
    public function setValueArray(string $app, string $key, array $value, bool $lazy = false, bool $sensitive = false): bool;
    public function getApps(): array;
    public function getKeys(string $app): array;
    public function hasKey(string $app, string $key, ?bool $lazy = null): bool;
    public function deleteKey(string $app, string $key): bool;
    public function deleteApp(string $app): bool;
    public function clearCache(bool $reload = false): void;
    public function getAllValues(string $app, string $prefix = '', bool $filtered = false): array;
    public function getValueType(string $app, string $key, ?bool $lazy = null): int;
    public function getValues($app, $key);
    public function getFilteredValues($app);
    public function getAppInstalledVersions(bool $onlyEnabled = false): array;

}

namespace OCP\App;

/**
 * Stub for OCP\App\IAppManager
 *
 * @category Tests
 * @package  OCP\App
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IAppManager
{

    public function getInstalledApps(): array;
    public function isEnabledForUser(string $appId, ?\OCP\IUser $user = null): bool;
    public function isInstalled(string $appId): bool;
    public function enableApp(string $appId, bool $forceEnable = false): void;
    public function disableApp(string $appId, bool $automaticDisabled = false): void;
    public function getAppVersion(string $appId, bool $useCache = true): string;

}

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Http constants
 *
 * @category Tests
 * @package  OCP\AppFramework
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Http
{
    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_MULTI_STATUS = 207;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;
}//end class


namespace OCP;

/**
 * Stub for OCP\IUser
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IUser
{

    public function getUID(): string;
    public function getDisplayName(): string;
    public function getEMailAddress(): ?string;
    public function isEnabled(): bool;

}


/**
 * Stub for OCP\IGroupManager
 *
 * @category Tests
 * @package  OCP
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IGroupManager
{

    public function isAdmin(string $userId): bool;


}//end interface


namespace OCP\Files;

/**
 * Stub for OCP\Files\Folder
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Folder
{

    /**
     * Get nodes by file ID
     *
     * @param int $id The file ID
     *
     * @return array<\OCP\Files\Node>
     */
    public function getById(int $id): array;


}//end interface


/**
 * Stub for OCP\Files\Node
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Node
{

    public function getName(): string;
    public function getPath(): string;

}//end interface


/**
 * Stub for OCP\Files\File
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface File extends Node
{

    public function getContent(): string;
    public function getMimeType(): string;

}//end interface


/**
 * Stub for OCP\Files\IRootFolder
 *
 * @category Tests
 * @package  OCP\Files
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IRootFolder
{

    /**
     * Get a user's home folder
     *
     * @param string $userId The user ID
     *
     * @return \OCP\Files\Folder
     */
    public function getUserFolder(string $userId): \OCP\Files\Folder;


}//end interface
