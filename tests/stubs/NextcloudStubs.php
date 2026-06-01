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

    public function __construct(string $appName, array $urlParams=[])
    {
    }

    public function getContainer(): ?\Psr\Container\ContainerInterface
    {
        return null;
    }

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

    public function register(\OCP\AppFramework\Bootstrap\IRegistrationContext $context): void;
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

    public function registerService(string $name, callable $factory, bool $shared=true): void;
    public function registerAlias(string $alias, string $target): void;
    public function registerServiceAlias(string $alias, string $target): void;
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

    private string $data;
    private int $status;

    public function __construct(string $text='', int $statusCode=200)
    {
        $this->data   = $text;
        $this->status = $statusCode;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function addHeader(string $name, string $value): self
    {
        return $this;
    }

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

    private string $appName;
    private string $templateName;
    private array $params;
    private string $renderAs;

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
    }

    public function getStatus(): int
    {
        return 200;
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getRenderAs(): string
    {
        return $this->renderAs;
    }

    public function getParams(): array
    {
        return $this->params;
    }

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

    public const PERMISSION_READ = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_CREATE = 4;
    public const PERMISSION_DELETE = 8;
    public const PERMISSION_SHARE = 16;
    public const PERMISSION_ALL = 31;

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

    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl=0): mixed;
    public function hasKey(string $key): bool;
    public function remove(string $key): mixed;
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

    public function createDistributed(string $prefix=''): ICache;
    public function createLocal(string $prefix=''): ICache;
    public function createInMemory(int $capacity=512): ICache;
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

    public function getTime(): int;
    public function getDateTime(string $time='', ?\DateTimeZone $timezone=null): \DateTime;
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

    protected mixed $argument;

    /**
     * @param \OCP\AppFramework\Utility\ITimeFactory $time Time factory
     */
    public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time)
    {
    }

    /**
     * @param mixed $argument Job argument
     *
     * @return void
     */
    abstract protected function run(mixed $argument): void;

    /**
     * @param mixed $argument Job argument
     *
     * @return void
     */
    public function execute(\OCP\BackgroundJob\IJobList $jobList, ?\Psr\Log\LoggerInterface $logger=null): void
    {
    }

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
    }

    /**
     * Get the argument
     *
     * @return mixed
     */
    public function getArgument(): mixed
    {
        return $this->argument;
    }

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

    public function add(string $job, mixed $argument=null): void;
    public function remove(string $job, mixed $argument=null): void;
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

    public function setSystemValue(string $key, mixed $value): void;
    public function getSystemValue(string $key, mixed $default=''): mixed;
    public function getSystemValueBool(string $key, bool $default=false): bool;
    public function getSystemValueInt(string $key, int $default=0): int;
    public function getSystemValueString(string $key, string $default=''): string;
    public function setAppValue(string $appName, string $key, string $value): void;
    public function getAppValue(string $appName, string $key, string $default=''): string;
    public function deleteAppValue(string $appName, string $key): void;
    public function setUserValue(string $userId, string $appName, string $key, string $value, ?string $preCondition=null): void;
    public function getUserValue(string $userId, string $appName, string $key, string $default=''): string;
    public function deleteUserValue(string $userId, string $appName, string $key): void;

}//end interface
