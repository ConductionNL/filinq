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

    /**
     * Get the remote (client) IP address for the request.
     *
     * @return string
     */
    public function getRemoteAddress(): string;

    /**
     * Get a request header value.
     *
     * @param string $name Header name
     *
     * @return string
     */
    public function getHeader(string $name): string;

    /**
     * Get the HTTP method of the request.
     *
     * @return string
     */
    public function getMethod(): string;
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
     * @param string        $appName App name
     * @param \OCP\IRequest $request The request object
     *
     * @return void
     */
    public function __construct(
        protected string $appName,
        protected \OCP\IRequest $request
    ) {

    }//end __construct()
}//end class

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Http
 *
 * Provides the HTTP status code constants referenced throughout the
 * DocuDesk controllers (e.g. Http::STATUS_OK, Http::STATUS_NOT_FOUND).
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

    public const STATUS_METHOD_NOT_ALLOWED = 405;

    public const STATUS_CONFLICT = 409;

    public const STATUS_GONE = 410;

    public const STATUS_UNPROCESSABLE_ENTITY = 422;

    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    public const STATUS_NOT_IMPLEMENTED = 501;

    public const STATUS_SERVICE_UNAVAILABLE = 503;
}//end class

namespace OCP\AppFramework\OCS;

/**
 * Stub for OCP\AppFramework\OCS\OCSForbiddenException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class OCSForbiddenException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $message Exception message
     *
     * @return void
     */
    public function __construct(string $message='')
    {
        parent::__construct(message: $message, code: 403);
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
/**
 * Stub for the OCP\AppFramework\Http\Response base class.
 *
 * Tracks headers so tests can assert addHeader() calls without a full NC
 * stack. Minimal — only the surface our middleware + tests use.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class Response
{
    /**
     * Headers keyed by name.
     *
     * @var array<string,string>
     */
    private array $headers = [];

    /**
     * Add a header.
     *
     * @param string $name  Header name.
     * @param string $value Header value.
     *
     * @return self
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Get response headers.
     *
     * @return array<string,string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}//end class

class JSONResponse extends Response
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
     * @var integer
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
     * Construct the application.
     *
     * @param string  $appName   Application name
     * @param mixed[] $urlParams URL parameters
     *
     * @return void
     */
    public function __construct(string $appName, array $urlParams=[])
    {
    }//end __construct()

    /**
     * Get the application container.
     *
     * @return \Psr\Container\ContainerInterface|null
     */
    public function getContainer(): ?\Psr\Container\ContainerInterface
    {
        return null;
    }//end getContainer()
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
    /**
     * Register services.
     *
     * @param \OCP\AppFramework\Bootstrap\IRegistrationContext $context Registration context
     *
     * @return void
     */
    public function register(\OCP\AppFramework\Bootstrap\IRegistrationContext $context): void;

    /**
     * Boot the application.
     *
     * @param \OCP\AppFramework\Bootstrap\IBootContext $context Boot context
     *
     * @return void
     */
    public function boot(\OCP\AppFramework\Bootstrap\IBootContext $context): void;
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
    /**
     * Register a service.
     *
     * @param string   $name    Service name
     * @param callable $factory Factory callable
     * @param bool     $shared  Whether shared
     *
     * @return void
     */
    public function registerService(string $name, callable $factory, bool $shared=true): void;

    /**
     * Register an alias.
     *
     * @param string $alias  Alias name
     * @param string $target Target class
     *
     * @return void
     */
    public function registerAlias(string $alias, string $target): void;

    /**
     * Register a service alias.
     *
     * @param string $alias  Alias name
     * @param string $target Target class
     *
     * @return void
     */
    public function registerServiceAlias(string $alias, string $target): void;

    /**
     * Register a parameter.
     *
     * @param string $name  Parameter name
     * @param mixed  $value Parameter value
     *
     * @return void
     */
    public function registerParameter(string $name, mixed $value): void;
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
    /**
     * Get the application container.
     *
     * @return \Psr\Container\ContainerInterface
     */
    public function getAppContainer(): \Psr\Container\ContainerInterface;
}//end interface

namespace OCP\AppFramework\Http;

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

    /**
     * Response text content.
     *
     * @var string
     */
    private string $data;

    /**
     * HTTP status code.
     *
     * @var integer
     */
    private int $status;

    /**
     * Construct a plain text response.
     *
     * @param string $text       Response text
     * @param int    $statusCode HTTP status code
     *
     * @return void
     */
    public function __construct(string $text='', int $statusCode=200)
    {
        $this->data   = $text;
        $this->status = $statusCode;
    }//end __construct()

    /**
     * Get response data.
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }//end getData()

    /**
     * Get HTTP status code.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }//end getStatus()

    /**
     * Add a response header.
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return self
     */
    public function addHeader(string $name, string $value): self
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

    /**
     * Application name.
     *
     * @var string
     */
    private string $appName;

    /**
     * Template name.
     *
     * @var string
     */
    private string $templateName;

    /**
     * Template parameters.
     *
     * @var mixed[]
     */
    private array $params;

    /**
     * Render mode.
     *
     * @var string
     */
    private string $renderAs;

    /**
     * Construct a template response.
     *
     * @param string  $appName      Application name
     * @param string  $templateName Template name
     * @param mixed[] $params       Template parameters
     * @param string  $renderAs     Render mode
     *
     * @return void
     */
    public function __construct(
        string $appName,
        string $templateName,
        array $params=[],
        string $renderAs='user'
    ) {
        $this->appName      = $appName;
        $this->templateName = $templateName;
        $this->params       = $params;
        $this->renderAs     = $renderAs;
    }//end __construct()

    /**
     * Get HTTP status code.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return 200;
    }//end getStatus()

    /**
     * Get template name.
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }//end getTemplateName()

    /**
     * Get render mode.
     *
     * @return string
     */
    public function getRenderAs(): string
    {
        return $this->renderAs;
    }//end getRenderAs()

    /**
     * Get template parameters.
     *
     * @return mixed[]
     */
    public function getParams(): array
    {
        return $this->params;
    }//end getParams()
}//end class

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

    public const PERMISSION_READ   = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_CREATE = 4;
    public const PERMISSION_DELETE = 8;
    public const PERMISSION_SHARE  = 16;
    public const PERMISSION_ALL    = 31;

}//end class

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
    /**
     * Get a cached value.
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Set a cached value.
     *
     * @param string $key   Cache key
     * @param mixed  $value Value to cache
     * @param int    $ttl   TTL in seconds
     *
     * @return mixed
     */
    public function set(string $key, mixed $value, int $ttl=0): mixed;

    /**
     * Check if a key exists.
     *
     * @param string $key Cache key
     *
     * @return bool
     */
    public function hasKey(string $key): bool;

    /**
     * Remove a cached value.
     *
     * @param string $key Cache key
     *
     * @return mixed
     */
    public function remove(string $key): mixed;

    /**
     * Clear the cache.
     *
     * @param string $prefix Key prefix
     *
     * @return mixed
     */
    public function clear(string $prefix=''): mixed;
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
    /**
     * Create a distributed cache.
     *
     * @param string $prefix Key prefix
     *
     * @return ICache
     */
    public function createDistributed(string $prefix=''): ICache;

    /**
     * Create a local cache.
     *
     * @param string $prefix Key prefix
     *
     * @return ICache
     */
    public function createLocal(string $prefix=''): ICache;

    /**
     * Create an in-memory cache.
     *
     * @param int $capacity Max entries
     *
     * @return ICache
     */
    public function createInMemory(int $capacity=512): ICache;

    /**
     * Check if distributed cache is available.
     *
     * @return bool
     */
    public function isAvailable(): bool;
}//end interface

namespace OCP\Files;

/**
 * Stub for OCP\Files\NotFoundException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class NotFoundException extends \RuntimeException
{
}//end class

/**
 * Stub for OCP\Files\NotPermittedException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class NotPermittedException extends \RuntimeException
{
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
    /**
     * Get the current Unix timestamp.
     *
     * @return int
     */
    public function getTime(): int;

    /**
     * Get a DateTime object.
     *
     * @param string             $time     Time string
     * @param \DateTimeZone|null $timezone Timezone
     *
     * @return \DateTime
     */
    public function getDateTime(string $time='', ?\DateTimeZone $timezone=null): \DateTime;

    /**
     * Get a DateTimeImmutable for now.
     *
     * @return \DateTimeImmutable
     */
    public function now(): \DateTimeImmutable;
}//end interface

namespace OCP\BackgroundJob;

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
     * Job argument data.
     *
     * @var mixed
     */
    protected mixed $argument;

    /**
     * Construct the queued job.
     *
     * @param \OCP\AppFramework\Utility\ITimeFactory $time Time factory
     *
     * @return void
     */
    public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time)
    {
    }//end __construct()

    /**
     * Run the job.
     *
     * @param mixed $argument Job argument
     *
     * @return void
     */
    abstract protected function run(mixed $argument): void;

    /**
     * Execute the job.
     *
     * @param \OCP\BackgroundJob\IJobList   $jobList Job list
     * @param \Psr\Log\LoggerInterface|null $logger  Logger
     *
     * @return void
     */
    public function execute(\OCP\BackgroundJob\IJobList $jobList, ?\Psr\Log\LoggerInterface $logger=null): void
    {
    }//end execute()

    /**
     * Set the argument
     *
     * @param mixed $argument Job argument
     *
     * @return void
     */
    public function setArgument(mixed $argument): void
    {
        $this->argument = $argument;
    }//end setArgument()

    /**
     * Get the argument
     *
     * @return mixed
     */
    public function getArgument(): mixed
    {
        return $this->argument;
    }//end getArgument()
}//end class

/**
 * Stub for OCP\BackgroundJob\TimedJob
 *
 * Mirrors QueuedJob but adds the interval/last-run scheduling helpers used
 * by DocuDesk's TimedJob subclasses (e.g. SigningExpirationJob).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
abstract class TimedJob
{

    /**
     * Job argument data.
     *
     * @var mixed
     */
    protected mixed $argument;

    /**
     * Configured run interval in seconds.
     *
     * @var integer
     */
    protected int $interval = 0;

    /**
     * Time-sensitivity flag.
     *
     * @var integer
     */
    protected int $timeSensitivity = 0;

    /**
     * Construct the timed job.
     *
     * @param \OCP\AppFramework\Utility\ITimeFactory $time Time factory
     *
     * @return void
     */
    public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time)
    {
    }//end __construct()

    /**
     * Run the job.
     *
     * @param mixed $argument Job argument
     *
     * @return void
     */
    abstract protected function run(mixed $argument): void;

    /**
     * Set the run interval.
     *
     * @param int $seconds Interval in seconds
     *
     * @return void
     */
    public function setInterval(int $seconds): void
    {
        $this->interval = $seconds;
    }//end setInterval()

    /**
     * Set the time sensitivity.
     *
     * @param int $sensitivity Sensitivity flag
     *
     * @return void
     */
    public function setTimeSensitivity(int $sensitivity): void
    {
        $this->timeSensitivity = $sensitivity;
    }//end setTimeSensitivity()

    /**
     * Execute the job.
     *
     * @param \OCP\BackgroundJob\IJobList   $jobList Job list
     * @param \Psr\Log\LoggerInterface|null $logger  Logger
     *
     * @return void
     */
    public function execute(\OCP\BackgroundJob\IJobList $jobList, ?\Psr\Log\LoggerInterface $logger=null): void
    {
    }//end execute()

    /**
     * Set the argument
     *
     * @param mixed $argument Job argument
     *
     * @return void
     */
    public function setArgument(mixed $argument): void
    {
        $this->argument = $argument;
    }//end setArgument()

    /**
     * Get the argument
     *
     * @return mixed
     */
    public function getArgument(): mixed
    {
        return $this->argument;
    }//end getArgument()
}//end class

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
    /**
     * Add a job to the list.
     *
     * @param string $job      Job class name
     * @param mixed  $argument Job argument
     *
     * @return void
     */
    public function add(string $job, mixed $argument=null): void;

    /**
     * Remove a job from the list.
     *
     * @param string $job      Job class name
     * @param mixed  $argument Job argument
     *
     * @return void
     */
    public function remove(string $job, mixed $argument=null): void;

    /**
     * Check if a job exists.
     *
     * @param string $job      Job class name
     * @param mixed  $argument Job argument
     *
     * @return bool
     */
    public function has(string $job, mixed $argument): bool;
}//end interface

namespace OCP;

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
    /**
     * Set a system-level config value.
     *
     * @param string $key   Config key
     * @param mixed  $value Config value
     *
     * @return void
     */
    public function setSystemValue(string $key, mixed $value): void;

    /**
     * Get a system-level config value.
     *
     * @param string $key     Config key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function getSystemValue(string $key, mixed $default=''): mixed;

    /**
     * Get a system-level config value as boolean.
     *
     * @param string $key     Config key
     * @param bool   $default Default value
     *
     * @return bool
     */
    public function getSystemValueBool(string $key, bool $default=false): bool;

    /**
     * Get a system-level config value as integer.
     *
     * @param string $key     Config key
     * @param int    $default Default value
     *
     * @return int
     */
    public function getSystemValueInt(string $key, int $default=0): int;

    /**
     * Get a system-level config value as string.
     *
     * @param string $key     Config key
     * @param string $default Default value
     *
     * @return string
     */
    public function getSystemValueString(string $key, string $default=''): string;

    /**
     * Set an app-level config value.
     *
     * @param string $appName App identifier
     * @param string $key     Config key
     * @param string $value   Config value
     *
     * @return void
     */
    public function setAppValue(string $appName, string $key, string $value): void;

    /**
     * Get an app-level config value.
     *
     * @param string $appName App identifier
     * @param string $key     Config key
     * @param string $default Default value
     *
     * @return string
     */
    public function getAppValue(string $appName, string $key, string $default=''): string;

    /**
     * Delete an app-level config value.
     *
     * @param string $appName App identifier
     * @param string $key     Config key
     *
     * @return void
     */
    public function deleteAppValue(string $appName, string $key): void;

    /**
     * Set a user-level config value.
     *
     * @param string      $userId       User identifier
     * @param string      $appName      App identifier
     * @param string      $key          Config key
     * @param string      $value        Config value
     * @param string|null $preCondition Pre-condition
     *
     * @return void
     */
    public function setUserValue(string $userId, string $appName, string $key, string $value, ?string $preCondition=null): void;

    /**
     * Get a user-level config value.
     *
     * @param string $userId  User identifier
     * @param string $appName App identifier
     * @param string $key     Config key
     * @param string $default Default value
     *
     * @return string
     */
    public function getUserValue(string $userId, string $appName, string $key, string $default=''): string;

    /**
     * Delete a user-level config value.
     *
     * @param string $userId  User identifier
     * @param string $appName App identifier
     * @param string $key     Config key
     *
     * @return void
     */
    public function deleteUserValue(string $userId, string $appName, string $key): void;
}//end interface

/**
 * Stub for OCP\IAppConfig
 *
 * The lazy/typed app-configuration API used by DocuDesk services and
 * background jobs (getValueString/Int/Float, setValueString, etc.).
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IAppConfig
{
    /**
     * Get a string app-config value.
     *
     * @param string $app     App identifier
     * @param string $key     Config key
     * @param string $default Default value
     * @param bool   $lazy    Whether the value is lazy-loaded
     *
     * @return string
     */
    public function getValueString(string $app, string $key, string $default='', bool $lazy=false): string;

    /**
     * Get an integer app-config value.
     *
     * @param string $app     App identifier
     * @param string $key     Config key
     * @param int    $default Default value
     * @param bool   $lazy    Whether the value is lazy-loaded
     *
     * @return int
     */
    public function getValueInt(string $app, string $key, int $default=0, bool $lazy=false): int;

    /**
     * Get a float app-config value.
     *
     * @param string $app     App identifier
     * @param string $key     Config key
     * @param float  $default Default value
     * @param bool   $lazy    Whether the value is lazy-loaded
     *
     * @return float
     */
    public function getValueFloat(string $app, string $key, float $default=0.0, bool $lazy=false): float;

    /**
     * Get a boolean app-config value.
     *
     * @param string $app     App identifier
     * @param string $key     Config key
     * @param bool   $default Default value
     * @param bool   $lazy    Whether the value is lazy-loaded
     *
     * @return bool
     */
    public function getValueBool(string $app, string $key, bool $default=false, bool $lazy=false): bool;

    /**
     * Set a string app-config value.
     *
     * @param string $app       App identifier
     * @param string $key       Config key
     * @param string $value     Config value
     * @param bool   $lazy      Whether the value is lazy-loaded
     * @param bool   $sensitive Whether the value is sensitive
     *
     * @return bool
     */
    public function setValueString(string $app, string $key, string $value, bool $lazy=false, bool $sensitive=false): bool;

    /**
     * Set an integer app-config value.
     *
     * @param string $app       App identifier
     * @param string $key       Config key
     * @param int    $value     Config value
     * @param bool   $lazy      Whether the value is lazy-loaded
     * @param bool   $sensitive Whether the value is sensitive
     *
     * @return bool
     */
    public function setValueInt(string $app, string $key, int $value, bool $lazy=false, bool $sensitive=false): bool;

    /**
     * Set a boolean app-config value.
     *
     * @param string $app   App identifier
     * @param string $key   Config key
     * @param bool   $value Config value
     * @param bool   $lazy  Whether the value is lazy-loaded
     *
     * @return bool
     */
    public function setValueBool(string $app, string $key, bool $value, bool $lazy=false): bool;

    /**
     * Determine whether an app-config key exists.
     *
     * @param string $app  App identifier
     * @param string $key  Config key
     * @param bool   $lazy Whether the value is lazy-loaded
     *
     * @return bool
     */
    public function hasKey(string $app, string $key, ?bool $lazy=false): bool;

    /**
     * Delete an app-config key.
     *
     * @param string $app App identifier
     * @param string $key Config key
     *
     * @return void
     */
    public function deleteKey(string $app, string $key): void;

    /**
     * Get the config keys defined for an app.
     *
     * @param string $app App identifier
     *
     * @return array<int, string>
     */
    public function getKeys(string $app): array;
}//end interface

namespace OCP\App;

/**
 * Stub for OCP\App\IAppManager
 *
 * Used by DataResolverService and SettingsInitializer to query installed
 * apps and resolve app versions.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IAppManager
{
    /**
     * Determine whether an app is installed.
     *
     * @param string $appId App identifier
     *
     * @return bool
     */
    public function isInstalled(string $appId): bool;

    /**
     * Determine whether an app is enabled for a user.
     *
     * @param string          $appId App identifier
     * @param \OCP\IUser|null $user  The user, or null for the current user
     *
     * @return bool
     */
    public function isEnabledForUser(string $appId, $user=null): bool;

    /**
     * Get the version of an installed app.
     *
     * @param string $appId App identifier
     *
     * @return string
     */
    public function getAppVersion(string $appId): string;

    /**
     * Get the list of installed apps.
     *
     * @return array<int, string>
     */
    public function getInstalledApps(): array;
}//end interface

namespace OCP\Notification;

/**
 * Stub for OCP\Notification\INotification
 *
 * A minimal fluent builder mirror of the Nextcloud notification model.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface INotification
{
    /**
     * Set the app the notification belongs to.
     *
     * @param string $app App identifier
     *
     * @return INotification
     */
    public function setApp(string $app): INotification;

    /**
     * Set the target user.
     *
     * @param string $user User identifier
     *
     * @return INotification
     */
    public function setUser(string $user): INotification;

    /**
     * Set the notification object.
     *
     * @param string $type Object type
     * @param string $id   Object id
     *
     * @return INotification
     */
    public function setObject(string $type, string $id): INotification;

    /**
     * Set the notification subject.
     *
     * @param string       $subject    Subject key
     * @param array<mixed> $parameters Subject parameters
     *
     * @return INotification
     */
    public function setSubject(string $subject, array $parameters=[]): INotification;
}//end interface

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
    /**
     * Create a fresh notification builder.
     *
     * @return INotification
     */
    public function createNotification(): INotification;

    /**
     * Dispatch a notification.
     *
     * @param INotification $notification The notification to send
     *
     * @return void
     */
    public function notify(INotification $notification): void;

    /**
     * Mark matching notifications as processed.
     *
     * @param INotification $notification The notification matcher
     *
     * @return void
     */
    public function markProcessed(INotification $notification): void;
}//end interface

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Middleware
 *
 * Minimal base class so docudesk's LanguageNegotiationMiddleware compiles
 * in the standalone unit-test runner.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.DocuDesk.app
 */
class Middleware
{
    /**
     * Hook fired before the controller method.
     *
     * @param mixed  $controller The controller instance.
     * @param string $methodName The method name being called.
     *
     * @return void
     */
    public function beforeController($controller, $methodName): void
    {
    }

    /**
     * Hook fired after the controller method returns.
     *
     * @param mixed                                  $controller The controller instance.
     * @param string                                 $methodName The method name that was called.
     * @param \OCP\AppFramework\Http\Response        $response   The response object.
     *
     * @return \OCP\AppFramework\Http\Response
     */
    public function afterController($controller, $methodName, \OCP\AppFramework\Http\Response $response): \OCP\AppFramework\Http\Response
    {
        return $response;
    }

    /**
     * Hook fired when an exception is thrown.
     *
     * @param mixed      $controller The controller instance.
     * @param string     $methodName The method name being called.
     * @param \Throwable $exception  The exception thrown.
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function afterException($controller, $methodName, \Throwable $exception)
    {
        throw $exception;
    }
}//end class
