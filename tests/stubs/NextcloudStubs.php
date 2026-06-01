<?php

/**
 * Stubs for Nextcloud OCP classes used in unit tests (no NC server required)
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

namespace OCP;

/**
 * Stub for OCP\IRequest
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IRequest
{

    /**
     * Get a parameter from the request
     *
     * @param string $key     Parameter key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function getParam(string $key, mixed $default=null): mixed;


    /**
     * Get all parameters
     *
     * @return array<string, mixed>
     */
    public function getParams(): array;


    /**
     * Get an uploaded file
     *
     * @param string $key File key
     *
     * @return array<string, mixed>|null
     */
    public function getUploadedFile(string $key): ?array;


}//end interface


/**
 * Stub for OCP\IL10N
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IL10N
{

    /**
     * Translate a string
     *
     * @param string       $text       The text to translate
     * @param array<mixed> $parameters Optional parameters
     *
     * @return string
     */
    public function t(string $text, array $parameters=[]): string;


}//end interface

namespace OCP\AppFramework\Utility;

/**
 * Stub for OCP\AppFramework\Utility\ITimeFactory
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ITimeFactory
{

    public function getTime(): int;
    public function getDateTime(string $time='now', ?\DateTimeZone $timezone=null): \DateTime;
    public function now(): \DateTimeImmutable;

}//end interface


namespace OCP\BackgroundJob;

/**
 * Stub for OCP\BackgroundJob\IJobList
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IJobList
{

    public function add(string $job, mixed $argument=null): void;
    public function remove(string $job, mixed $argument=null): void;
    public function has(string $job, mixed $argument): bool;

}//end interface


/**
 * Stub for OCP\BackgroundJob\QueuedJob
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
abstract class QueuedJob
{

    public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time)
    {

    }//end __construct()

    abstract protected function run(mixed $argument): void;

}//end class


namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Controller
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Controller
{


    /**
     * Constructor
     *
     * @param string          $appName App name
     * @param \OCP\IRequest   $request The request object
     *
     * @return void
     */
    public function __construct(
        protected string $appName,
        protected \OCP\IRequest $request
    ) {

    }//end __construct()


}//end class

namespace OCP\AppFramework\Http;

/**
 * Stub for OCP\AppFramework\Http\JSONResponse
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class JSONResponse
{

    /**
     * The response data
     *
     * @var mixed
     */
    private mixed $data;

    /**
     * HTTP status code
     *
     * @var int
     */
    private int $status;


    /**
     * Constructor
     *
     * @param mixed $data       Response data
     * @param int   $statusCode HTTP status code
     *
     * @return void
     */
    public function __construct(mixed $data=[], int $statusCode=200)
    {
        $this->data   = is_array($data) === true ? $data : [];
        $this->status = $statusCode;

    }//end __construct()


    /**
     * Get response data
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;

    }//end getData()


    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;

    }//end getStatus()


}//end class


/**
 * Stub for OCP\AppFramework\Http\DataDownloadResponse
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DataDownloadResponse
{


    /**
     * Constructor
     *
     * @param string $data        Response data
     * @param string $filename    Filename
     * @param string $contentType Content-Type header
     *
     * @return void
     */
    public function __construct(string $data, string $filename, string $contentType)
    {

    }//end __construct()


}//end class

namespace OCP\AppFramework\Http;

if (!\class_exists('OCP\\AppFramework\\Http\\TemplateResponse')) {
    /**
     * Stub for OCP\AppFramework\Http\TemplateResponse
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    class TemplateResponse
    {

        private string $templateName;

        public function __construct(
            string $appName,
            string $templateName,
            array $params=[],
            string $renderAs='user'
        ) {
            $this->templateName = $templateName;
        }//end __construct()

        public function getStatus(): int
        {
            return 200;
        }//end getStatus()

        public function getTemplateName(): string
        {
            return $this->templateName;
        }//end getTemplateName()

    }//end class
}//end if

namespace OCP;

/**
 * Stub for OCP\Constants
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Constants
{
    public const PERMISSION_READ = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_CREATE = 4;
    public const PERMISSION_DELETE = 8;
    public const PERMISSION_SHARE = 16;
    public const PERMISSION_ALL = 31;
}//end class


/**
 * Stub for OCP\IConfig
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IConfig
{
    public function getUserValue(string $userId, string $appName, string $key, mixed $default=null): mixed;
    public function setUserValue(string $userId, string $appName, string $key, mixed $value, mixed $preCondition=null): void;
    public function getSystemValue(string $key, mixed $default=''): mixed;
    public function getAppValue(string $appName, string $key, string $default=''): string;
    public function setAppValue(string $appName, string $key, string $value): void;
    public function deleteAppValue(string $appName, string $key): void;
    public function getSystemValueBool(string $key, bool $default=false): bool;
    public function getSystemValueInt(string $key, int $default=0): int;
    public function getSystemValueString(string $key, string $default=''): string;
}//end interface


/**
 * Stub for OCP\ICacheFactory
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ICacheFactory
{
    public function createDistributed(string $prefix=''): \OCP\ICache;
    public function createLocal(string $prefix=''): \OCP\ICache;
    public function createInMemory(int $capacity=512): \OCP\ICache;
    public function isLocalCacheAvailable(): bool;
    public function isAvailable(): bool;
}//end interface


/**
 * Stub for OCP\ICache
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ICache
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl=0): bool;
    public function hasKey(string $key): bool;
    public function remove(string $key): bool;
    public function clear(string $prefix=''): bool;
}//end interface

namespace OCP\Files;

/**
 * Stub for OCP\Files\IRootFolder
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IRootFolder
{
    public function get(string $path): \OCP\Files\Node;
    public function nodeExists(string $path): bool;
    public function newFolder(string $path): \OCP\Files\Folder;
    public function getUserFolder(string $userId): \OCP\Files\Folder;
    public function getById(int $id): array;
}//end interface


/**
 * Stub for OCP\Files\Node
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Node
{
    public function getId(): int;
    public function getName(): string;
    public function getPath(): string;
    public function getRelativePath(string $path): ?string;
    public function getMimetype(): string;
    public function getSize(bool $includeMounts=true): int|float;
    public function getPermissions(): int;
    public function isReadable(): bool;
    public function isCreatable(): bool;
    public function isDeletable(): bool;
    public function isUpdateable(): bool;
    public function isShareable(): bool;
    public function getParent(): Folder;
    public function delete(): void;
}//end interface


/**
 * Stub for OCP\Files\File
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface File extends Node
{
    public function getContent(): string;
    public function putContent(mixed $data): void;
    public function fopen(string $mode): mixed;
}//end interface


/**
 * Stub for OCP\Files\Folder
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface Folder extends Node
{
    public function getDirectoryListing(): array;
    public function get(string $path): Node;
    public function nodeExists(string $path): bool;
    public function newFolder(string $path): Folder;
    public function newFile(string $path, mixed $content=null): File;
    public function search(string $query): array;
    public function getById(int $id): array;
    public function getFreeSpace(): float;
    public function isSubNode(Node $node): bool;
}//end interface

namespace OCP\Share;

/**
 * Stub for OCP\Share\IManager
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IManager
{
    public function createShare(\OCP\Share\IShare $share): \OCP\Share\IShare;
    public function getSharedWith(string $userId, int $shareType, mixed $node=null, int $limit=-1, int $offset=0): array;
    public function newShare(): \OCP\Share\IShare;
}//end interface


/**
 * Stub for OCP\Share\IShare
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IShare
{
    public function getId(): int;
    public function getFullId(): string;
    public function setNode(\OCP\Files\Node $node): IShare;
    public function getNode(): \OCP\Files\Node;
    public function setSharedWith(string $sharedWith): IShare;
    public function getSharedWith(): string;
    public function setShareType(int $shareType): IShare;
    public function getShareType(): int;
    public function setPermissions(int $permissions): IShare;
    public function getPermissions(): int;
    public function setSharedBy(string $sharedBy): IShare;
    public function getSharedBy(): string;
}//end interface

namespace OCP\AppFramework\Http;

if (!\class_exists('OCP\\AppFramework\\Http\\TextPlainResponse')) {
    /**
     * Stub for OCP\AppFramework\Http\TextPlainResponse
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    class TextPlainResponse
    {

        private string $plainText;

        public function __construct(string $plainText='', int $statusCode=200)
        {
            $this->plainText = $plainText;
        }//end __construct()

        public function getStatus(): int
        {
            return 200;
        }//end getStatus()

        public function render(): string
        {
            return $this->plainText;
        }//end render()

        public function addHeader(string $name, string $value): self
        {
            return $this;
        }//end addHeader()

        public function getHeaders(): array
        {
            return [];
        }//end getHeaders()

    }//end class
}//end if

namespace OCP\AppFramework;

if (!\class_exists('OCP\\AppFramework\\App')) {
    /**
     * Stub for OCP\AppFramework\App
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    class App
    {

        public function __construct(string $appName, array $urlParams=[])
        {
        }//end __construct()

    }//end class
}//end if

namespace OCP\AppFramework\Bootstrap;

if (!\interface_exists('OCP\\AppFramework\\Bootstrap\\IBootstrap')) {
    /**
     * Stub for OCP\AppFramework\Bootstrap\IBootstrap
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    interface IBootstrap
    {
        public function register(\OCP\AppFramework\Bootstrap\IRegistrationContext $context): void;
        public function boot(\OCP\AppFramework\Bootstrap\IBootContext $context): void;
    }//end interface
}//end if

if (!\interface_exists('OCP\\AppFramework\\Bootstrap\\IRegistrationContext')) {
    /**
     * Stub for OCP\AppFramework\Bootstrap\IRegistrationContext
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    interface IRegistrationContext
    {
        public function registerDashboardWidget(string $widgetClass): void;
        public function registerEventListener(string $event, string $listener): void;
    }//end interface
}//end if

if (!\interface_exists('OCP\\AppFramework\\Bootstrap\\IBootContext')) {
    /**
     * Stub for OCP\AppFramework\Bootstrap\IBootContext
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    interface IBootContext
    {
        public function getServerContainer(): \Psr\Container\ContainerInterface;
        public function getAppContainer(): \Psr\Container\ContainerInterface;
    }//end interface
}//end if

namespace OCP\Files;

if (!\class_exists('OCP\\Files\\NotFoundException')) {
    /**
     * Stub for OCP\Files\NotFoundException
     *
     * @category Tests
     * @package  OCA\DocuDesk\Tests
     * @author   Conduction B.V. <info@conduction.nl>
     * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
     * @link     https://www.DocuDesk.app
     */
    class NotFoundException extends \Exception
    {

    }//end class
}//end if
