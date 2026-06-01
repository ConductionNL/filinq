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

    public function getParam(string $key, mixed $default=null): mixed;
    public function getParams(): array;
    public function getMethod(): string;
    public function getUploadedFile(string $key): ?array;
    public function getHeader(string $name): string;
    public function isUserAgent(array $agent): bool;
    public function getServerProtocol(): string;
    public function getRawPathInfo(): string;
    public function getPathInfo(): string|false;
    public function getRequestUri(): string;
    public function getId(): string;
    public function getRemoteAddress(): string;
    public function getServerHost(): string;

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

    public function t(string $text, array $parameters=[]): string;
    public function n(string $text_singular, string $text_plural, int $count, array $parameters=[]): string;
    public function l(string $type, mixed $data, array $options=[]): mixed;
    public function getLanguageCode(): string;
    public function getLocaleCode(): string;

}//end interface


/**
 * Stub for OCP\IDBConnection
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IDBConnection
{

    public function getQueryBuilder(): mixed;
    public function executeQuery(string $sql, array $params=[], array $types=[]): mixed;
    public function executeStatement(string $sql, array $params=[], array $types=[]): int;

}//end interface


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

    public function getUserValue(string $userId, string $appName, string $key, string $default=''): string;
    public function setUserValue(string $userId, string $appName, string $key, string $value): void;
    public function deleteUserValue(string $userId, string $appName, string $key): void;
    public function getSystemValue(string $key, mixed $default=''): mixed;
    public function getSystemValueString(string $key, string $default=''): string;
    public function getSystemValueInt(string $key, int $default=0): int;
    public function getSystemValueBool(string $key, bool $default=false): bool;
    public function getAppValue(string $appName, string $key, string $default=''): string;
    public function setAppValue(string $appName, string $key, string $value): void;

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

    public function createDistributed(string $prefix=''): ICache;
    public function createLocal(string $prefix=''): ICache;
    public function createInMemory(int $capacity=512): ICache;
    public function isAvailable(): bool;

}//end interface


namespace OCP\AppFramework;

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

    /**
     * Constructor
     *
     * @param string               $appName     App name
     * @param array<string, mixed> $urlParams   URL parameters
     *
     * @return void
     */
    public function __construct(string $appName, array $urlParams=[])
    {

    }//end __construct()


}//end class


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


/**
 * Stub for OCP\AppFramework\Http constants
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class Http
{

    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_ACCEPTED = 202;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_CONFLICT = 409;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

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

    /** @var mixed */
    private mixed $data;

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
        $this->data   = $data;
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


    /**
     * Set status code
     *
     * @param int $code HTTP status code
     *
     * @return static
     */
    public function setStatus(int $code): static
    {
        $this->status = $code;
        return $this;

    }//end setStatus()


    /**
     * Set data
     *
     * @param mixed $data Response data
     *
     * @return static
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;

    }//end setData()


    /**
     * Add a header
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return static
     */
    public function addHeader(string $name, string $value): static
    {
        return $this;

    }//end addHeader()


    /**
     * Get headers
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];

    }//end getHeaders()


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
class DataDownloadResponse extends JSONResponse
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
        parent::__construct(data: $data);

    }//end __construct()


}//end class


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

    private string $body;
    private int $status;


    /**
     * Constructor
     *
     * @param string $body       Plain text body
     * @param int    $statusCode HTTP status code
     *
     * @return void
     */
    public function __construct(string $body='', int $statusCode=200)
    {
        $this->body   = $body;
        $this->status = $statusCode;

    }//end __construct()


    /**
     * Get body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;

    }//end getBody()


    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;

    }//end getStatus()


    /**
     * Add a response header
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return static
     */
    public function addHeader(string $name, string $value): static
    {
        return $this;

    }//end addHeader()


}//end class


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
    private array $params;
    private string $renderAs;


    /**
     * Constructor
     *
     * @param string $appName      App name
     * @param string $templateName Template name
     * @param array  $params       Template parameters
     * @param string $renderAs     Render type
     *
     * @return void
     */
    public function __construct(
        string $appName,
        string $templateName,
        array $params=[],
        string $renderAs='user'
    ) {
        $this->templateName = $templateName;
        $this->params       = $params;
        $this->renderAs     = $renderAs;

    }//end __construct()


    /**
     * Get template name
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;

    }//end getTemplateName()


    /**
     * Get params
     *
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;

    }//end getParams()


}//end class


namespace OCP\AppFramework\Bootstrap;

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

    public function register(IRegistrationContext $context): void;
    public function boot(IBootContext $context): void;

}//end interface


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

    public function registerService(string $name, callable $factory, bool $shared=true): void;
    public function registerServiceAlias(string $alias, string $target): void;
    public function registerEventListener(string $event, string $listener, int $priority=0): void;
    public function registerMiddleware(string $middleware, bool $global=false): void;

}//end interface


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

    public function getAppContainer(): mixed;
    public function getServerContainer(): mixed;

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
    public function getJobs(string $job, ?int $limit, int $offset): array;

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

    /**
     * Constructor
     *
     * @param \OCP\AppFramework\Utility\ITimeFactory $time Time factory
     *
     * @return void
     */
    public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time)
    {

    }//end __construct()


    /**
     * Execute the job
     *
     * @param mixed $argument Job argument
     *
     * @return void
     */
    abstract protected function run(mixed $argument): void;


}//end class


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
    public function getDateTime(string $time='', ?\DateTimeZone $timezone=null): \DateTime;

}//end interface


namespace OCP\Notification;

/**
 * Stub for OCP\Notification\IManager
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IManager
{

    public function createNotification(): INotification;
    public function notify(INotification $notification): void;
    public function markProcessed(INotification $notification): void;
    public function getCount(INotification $notification): int;

}//end interface


/**
 * Stub for OCP\Notification\INotification
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface INotification
{

    public function setApp(string $app): INotification;
    public function setUser(string $user): INotification;
    public function setObject(string $type, string $id): INotification;
    public function setSubject(string $subject, array $parameters=[]): INotification;
    public function setMessage(string $message, array $parameters=[]): INotification;
    public function setIcon(string $icon): INotification;
    public function getApp(): string;
    public function getUser(): string;

}//end interface


namespace Psr\Container;

/**
 * Stub for Psr\Container\ContainerInterface
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ContainerInterface
{

    public function get(string $id): mixed;
    public function has(string $id): bool;

}//end interface
