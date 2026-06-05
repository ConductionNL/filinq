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

namespace OCP\AppFramework\Http;

/**
 * Stub for OCP\AppFramework\Http\OCSForbiddenException
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

namespace OCP;

/**
 * Stub for OCP\ITempManager
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ITempManager
{
    /**
     * Create a temporary file with optional suffix.
     *
     * @param string $postFix File suffix including dot (e.g. ".docx").
     *
     * @return string|false Absolute path to the temporary file.
     */
    public function getTemporaryFile(string $postFix='');

    /**
     * Create a temporary directory.
     *
     * @param string $postFix Directory name suffix.
     *
     * @return string|false Absolute path to the temporary directory.
     */
    public function getTemporaryFolder(string $postFix='');
}//end interface

namespace OCP\Lock;

/**
 * Stub for OCP\Lock\LockedException
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class LockedException extends \RuntimeException
{
    /**
     * Constructor.
     *
     * @param string          $path     The path that is locked.
     * @param \Throwable|null $previous Previous exception if any.
     * @param string          $lockType Lock type identifier.
     */
    public function __construct(string $path, ?\Throwable $previous=null, string $lockType='')
    {
        parent::__construct(
            message: 'The path "'.$path.'" is currently locked, please try again later.',
            code: 0,
            previous: $previous
        );

    }//end __construct()
}//end class

/**
 * Stub for OCP\Lock\ILockingProvider
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ILockingProvider
{

    public const LOCK_SHARED    = 1;
    public const LOCK_EXCLUSIVE = 2;

    /**
     * Acquire a lock on the given path.
     *
     * @param string $path         Path to lock.
     * @param int    $type         Lock type (LOCK_SHARED or LOCK_EXCLUSIVE).
     * @param string $readablePath Human-readable path (optional).
     *
     * @return void
     *
     * @throws LockedException When the lock cannot be acquired.
     */
    public function acquireLock(string $path, int $type, string $readablePath='');

    /**
     * Release a lock on the given path.
     *
     * @param string $path Path to unlock.
     * @param int    $type Lock type.
     *
     * @return void
     */
    public function releaseLock(string $path, int $type): void;

    /**
     * Change the lock type on the given path.
     *
     * @param string $path Path to change lock on.
     * @param int    $type New lock type.
     *
     * @return void
     */
    public function changeLock(string $path, int $type): void;
}//end interface

namespace OCP\Files\Conversion;

/**
 * Stub for OCP\Files\Conversion\ConversionMimeProvider
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface ConversionMimeProvider
{
    /**
     * Return the source MIME type.
     *
     * @return string
     */
    public function getFrom(): string;

    /**
     * Return the target MIME type.
     *
     * @return string
     */
    public function getTo(): string;
}//end interface

/**
 * Stub for OCP\Files\Conversion\IConversionManager
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
interface IConversionManager
{
    /**
     * Check whether any conversion providers are registered.
     *
     * @return bool
     */
    public function hasProviders(): bool;

    /**
     * Return all registered MIME-provider pairs.
     *
     * @return ConversionMimeProvider[]
     */
    public function getProviders(): array;

    /**
     * Convert a file to the target MIME type.
     *
     * @param \OCP\Files\File $file        Source file.
     * @param string          $targetMime  Target MIME type.
     * @param string          $destination Destination path.
     *
     * @return string Path to the converted file.
     */
    public function convert(\OCP\Files\File $file, string $targetMime, string $destination): string;
}//end interface
