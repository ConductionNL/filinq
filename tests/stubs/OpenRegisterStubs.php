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
    public function extractFile(int $fileId, bool $force=false): void
    {
    }//end extractFile()
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
 * Stub for Register entity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Register
{
    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug(): string
    {
        return '';

    }//end getSlug()

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return '';

    }//end getTitle()

    /**
     * Get ID
     *
     * @return int
     */
    public function getId(): int
    {
        return 0;

    }//end getId()

    /**
     * Serialize to array
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [];

    }//end jsonSerialize()
}//end class

/**
 * Stub for Schema entity
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Schema
{
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return '';

    }//end getTitle()

    /**
     * Get ID
     *
     * @return int
     */
    public function getId(): int
    {
        return 0;

    }//end getId()

    /**
     * Serialize to array
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [];

    }//end jsonSerialize()
}//end class

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

    /** @var string|null */
    protected ?string $uuid = null;

    /** @var string|null */
    protected ?string $register = null;

    /** @var string|null */
    protected ?string $schema = null;

    /**
     * Set UUID.
     *
     * @param string|null $uuid UUID.
     *
     * @return void
     */
    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;

    }//end setUuid()

    /**
     * Get UUID.
     *
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;

    }//end getUuid()

    /**
     * Get register.
     *
     * @return string|null
     */
    public function getRegister(): ?string
    {
        return $this->register;

    }//end getRegister()

    /**
     * Get schema.
     *
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return $this->schema;

    }//end getSchema()

    /**
     * Get integer ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return null;

    }//end getId()

    /**
     * Serialize to array.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [];

    }//end jsonSerialize()
}//end class


/**
 * Stub for AuditTrail entity.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AuditTrail
{

    /** @var string|null */
    protected ?string $objectUuid = null;

    /** @var string|null */
    protected ?string $action = null;

    /** @var array<string, mixed> */
    protected array $changed = [];

    /** @var \DateTime|null */
    protected ?\DateTime $created = null;

    /**
     * Set objectUuid.
     *
     * @param string|null $objectUuid UUID.
     *
     * @return void
     */
    public function setObjectUuid(?string $objectUuid): void
    {
        $this->objectUuid = $objectUuid;

    }//end setObjectUuid()

    /**
     * Get objectUuid.
     *
     * @return string|null
     */
    public function getObjectUuid(): ?string
    {
        return $this->objectUuid;

    }//end getObjectUuid()

    /**
     * Set action.
     *
     * @param string|null $action Action type.
     *
     * @return void
     */
    public function setAction(?string $action): void
    {
        $this->action = $action;

    }//end setAction()

    /**
     * Get action.
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;

    }//end getAction()

    /**
     * Set changed.
     *
     * @param array<string, mixed> $changed Changed data.
     *
     * @return void
     */
    public function setChanged(array $changed): void
    {
        $this->changed = $changed;

    }//end setChanged()

    /**
     * Get changed.
     *
     * @return array<string, mixed>
     */
    public function getChanged(): array
    {
        return $this->changed;

    }//end getChanged()

    /**
     * Set created timestamp.
     *
     * @param \DateTime|null $created Created at.
     *
     * @return void
     */
    public function setCreated(?\DateTime $created): void
    {
        $this->created = $created;

    }//end setCreated()

    /**
     * Get created timestamp.
     *
     * @return \DateTime|null
     */
    public function getCreated(): ?\DateTime
    {
        return $this->created;

    }//end getCreated()

    /**
     * Serialize to array.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'objectUuid' => $this->objectUuid,
            'action'     => $this->action,
            'changed'    => $this->changed,
            'created'    => $this->created !== null ? $this->created->format(\DateTimeInterface::ATOM) : null,
        ];

    }//end jsonSerialize()
}//end class


/**
 * Stub for AuditTrailMapper.
 *
 * @category Tests
 * @package  OCA\OpenRegister\Db
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AuditTrailMapper
{

    /**
     * Find all audit trail entries with optional filters.
     *
     * @param int|null   $limit   Limit.
     * @param int|null   $offset  Offset.
     * @param array|null $filters Filters.
     * @param array|null $sort    Sort order.
     * @param string|null $search Search term.
     *
     * @return AuditTrail[]
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[],
        ?array $sort=['created' => 'DESC'],
        ?string $search=null
    ): array {
        return [];

    }//end findAll()

    /**
     * Create an audit trail entry for a custom action.
     *
     * @param ObjectEntity $object  The object the entry relates to.
     * @param string       $action  The action type.
     * @param array        $context Additional context data.
     *
     * @return AuditTrail
     */
    public function createAuditTrailEntry(
        ObjectEntity $object,
        string $action,
        array $context=[]
    ): AuditTrail {
        $trail = new AuditTrail();
        $trail->setObjectUuid($object->getUuid());
        $trail->setAction($action);
        $trail->setChanged($context);
        $trail->setCreated(new \DateTime());
        return $trail;

    }//end createAuditTrailEntry()
}//end class

// OCP\IRequest and OCP\IL10N are defined in NextcloudStubs.php (loaded first).

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

// OCP\AppFramework\Http\JSONResponse, DataDownloadResponse, and OCP\AppFramework\Controller
// are defined in NextcloudStubs.php (loaded first).

namespace Psr\Log;

/**
 * Stub for Psr\Log\LoggerInterface
 *
 * @category Tests
 * @package  Psr\Log
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface LoggerInterface
{
    public function emergency(string|\Stringable $message, array $context=[]): void;

    public function alert(string|\Stringable $message, array $context=[]): void;

    public function critical(string|\Stringable $message, array $context=[]): void;

    public function error(string|\Stringable $message, array $context=[]): void;

    public function warning(string|\Stringable $message, array $context=[]): void;

    public function notice(string|\Stringable $message, array $context=[]): void;

    public function info(string|\Stringable $message, array $context=[]): void;

    public function debug(string|\Stringable $message, array $context=[]): void;

    public function log(mixed $level, string|\Stringable $message, array $context=[]): void;
}//end interface

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
}//end interface


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
interface Folder extends Node
{
    /**
     * Get nodes by file ID
     *
     * @param int $id The file ID
     *
     * @return array<\OCP\Files\Node>
     */
    public function getById(int $id): array;

    public function get(string $path): \OCP\Files\Node;

    public function getDirectoryListing(): array;

    public function getRelativePath(string $path): ?string;

    public function nodeExists(string $path): bool;

    public function newFolder(string $path): \OCP\Files\Folder;

    public function newFile(string $path, mixed $content=null): \OCP\Files\File;

    public function search(string $query): array;

    public function searchByMime(string $mimetype): array;
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

    public function getId(): int;

    public function getPermissions(): int;

    public function getMimeType(): string;
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

// ICache and ICacheFactory are defined in NextcloudStubs.php — no duplicate here.

