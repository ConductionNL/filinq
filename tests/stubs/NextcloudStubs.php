<?php

/**
 * Stubs for Nextcloud OCP classes used in unit tests (no NC server required)
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCP;

/**
 * Stub for OCP\IRequest
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IRequest {
	/**
	 * Get a parameter from the request
	 *
	 * @param string $key Parameter key
	 * @param mixed $default Default value
	 *
	 * @return mixed
	 */
	public function getParam(string $key, mixed $default = null): mixed;

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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IL10N {
	/**
	 * Translate a string
	 *
	 * @param string $text The text to translate
	 * @param array<mixed> $parameters Optional parameters
	 *
	 * @return string
	 */
	public function t(string $text, array $parameters = []): string;
}//end interface

/**
 * Stub for OCP\Constants
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Constants {
	public const PERMISSION_READ = 1;
	public const PERMISSION_UPDATE = 2;
	public const PERMISSION_CREATE = 4;
	public const PERMISSION_DELETE = 8;
	public const PERMISSION_SHARE = 16;
	public const PERMISSION_ALL = 31;
}//end class

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Controller
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Controller {
	/**
	 * Constructor
	 *
	 * @param string $appName App name
	 * @param \OCP\IRequest $request The request object
	 *
	 * @return void
	 */
	public function __construct(
		protected string $appName,
		protected \OCP\IRequest $request,
	) {

	}//end __construct()
}//end class

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\Http
 *
 * Provides the HTTP status code constants referenced throughout the
 * Filinq controllers (e.g. Http::STATUS_OK, Http::STATUS_NOT_FOUND).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Http {
	public const STATUS_OK = 200;

	public const STATUS_CREATED = 201;

	public const STATUS_ACCEPTED = 202;

	public const STATUS_NO_CONTENT = 204;

	public const STATUS_MULTI_STATUS = 207;

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

	public const STATUS_BAD_GATEWAY = 502;

	public const STATUS_SERVICE_UNAVAILABLE = 503;
}//end class

namespace OCP\AppFramework\OCS;

/**
 * Stub for OCP\AppFramework\OCS\OCSForbiddenException
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class OCSForbiddenException extends \RuntimeException {
	/**
	 * Constructor
	 *
	 * @param string $message Exception message
	 *
	 * @return void
	 */
	public function __construct(string $message = '') {
		parent::__construct(message: $message, code: 403);
	}//end __construct()
}//end class

namespace OCP\AppFramework\Http;

/**
 * Stub for OCP\AppFramework\Http\JSONResponse
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
/**
 * Stub for the OCP\AppFramework\Http\Response base class.
 *
 * Tracks headers so tests can assert addHeader() calls without a full NC
 * stack. Minimal — only the surface our middleware + tests use.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.filinq.app
 */
class Response {
	/**
	 * Headers keyed by name.
	 *
	 * @var array<string,string>
	 */
	private array $headers = [];

	/**
	 * Add a header.
	 *
	 * @param string $name Header name.
	 * @param string $value Header value.
	 *
	 * @return self
	 */
	public function addHeader(string $name, string $value): self {
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Get response headers.
	 *
	 * @return array<string,string>
	 */
	public function getHeaders(): array {
		return $this->headers;
	}
}//end class

class JSONResponse extends Response {

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
	 * @param mixed $data Response data
	 * @param int $statusCode HTTP status code
	 *
	 * @return void
	 */
	public function __construct(mixed $data = [], int $statusCode = 200) {
		$this->data = $data;
		$this->status = $statusCode;

	}//end __construct()

	/**
	 * Get response data
	 *
	 * @return mixed
	 */
	public function getData(): mixed {
		return $this->data;
	}//end getData()

	/**
	 * Get HTTP status code
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}//end getStatus()
}//end class

/**
 * Stub for OCP\AppFramework\Http\DataDownloadResponse
 *
 * Extends the local Response stub (matching the real
 * DataDownloadResponse -> DownloadResponse -> Response inheritance
 * chain) so addHeader()/getHeaders() are available — needed by
 * Pdfa3ConversionController / PdfController::renderPdfA, which surface
 * checksum/pages/conformance as response headers.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class DataDownloadResponse extends Response {

	/**
	 * The response data
	 *
	 * @var string
	 */
	private string $data;

	/**
	 * Constructor
	 *
	 * @param string $data Response data
	 * @param string $filename Filename
	 * @param string $contentType Content-Type header
	 *
	 * @return void
	 */
	public function __construct(string $data, string $filename, string $contentType) {
		$this->data = $data;
		$this->addHeader('Content-Type', $contentType);
		$this->addHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

	}//end __construct()

	/**
	 * Get response data
	 *
	 * @return string
	 */
	public function getData(): string {
		return $this->data;
	}//end getData()

	/**
	 * Get HTTP status code. DataDownloadResponse always defaults to 200
	 * (matching the real OCP class, which inherits Http::STATUS_OK).
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return 200;
	}//end getStatus()
}//end class

namespace OCP\AppFramework;

/**
 * Stub for OCP\AppFramework\App
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class App {
	/**
	 * Construct the application.
	 *
	 * @param string $appName Application name
	 * @param mixed[] $urlParams URL parameters
	 *
	 * @return void
	 */
	public function __construct(string $appName, array $urlParams = []) {
	}//end __construct()

	/**
	 * Get the application container.
	 *
	 * @return \Psr\Container\ContainerInterface|null
	 */
	public function getContainer(): ?\Psr\Container\ContainerInterface {
		return null;
	}//end getContainer()
}//end class

namespace OCP\AppFramework\Bootstrap;

/**
 * Stub for OCP\AppFramework\Bootstrap\IBootstrap
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IBootstrap {
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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IRegistrationContext {
	/**
	 * Register a service.
	 *
	 * @param string $name Service name
	 * @param callable $factory Factory callable
	 * @param bool $shared Whether shared
	 *
	 * @return void
	 */
	public function registerService(string $name, callable $factory, bool $shared = true): void;

	/**
	 * Register an alias.
	 *
	 * @param string $alias Alias name
	 * @param string $target Target class
	 *
	 * @return void
	 */
	public function registerAlias(string $alias, string $target): void;

	/**
	 * Register a service alias.
	 *
	 * @param string $alias Alias name
	 * @param string $target Target class
	 *
	 * @return void
	 */
	public function registerServiceAlias(string $alias, string $target): void;

	/**
	 * Register a parameter.
	 *
	 * @param string $name Parameter name
	 * @param mixed $value Parameter value
	 *
	 * @return void
	 */
	public function registerParameter(string $name, mixed $value): void;

	/**
	 * Register an event listener.
	 *
	 * @param string $event Event class name
	 * @param string $listener Listener class name
	 * @param int $priority Listener priority
	 *
	 * @return void
	 */
	public function registerEventListener(string $event, string $listener, int $priority = 0): void;
}//end interface

/**
 * Stub for OCP\AppFramework\Bootstrap\IBootContext
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IBootContext {
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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TextPlainResponse {

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
	 * @param string $text Response text
	 * @param int $statusCode HTTP status code
	 *
	 * @return void
	 */
	public function __construct(string $text = '', int $statusCode = 200) {
		$this->data = $text;
		$this->status = $statusCode;
	}//end __construct()

	/**
	 * Get response data.
	 *
	 * @return string
	 */
	public function getData(): string {
		return $this->data;
	}//end getData()

	/**
	 * Get HTTP status code.
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}//end getStatus()

	/**
	 * Add a response header.
	 *
	 * @param string $name Header name
	 * @param string $value Header value
	 *
	 * @return self
	 */
	public function addHeader(string $name, string $value): self {
		return $this;
	}//end addHeader()
}//end class

/**
 * Stub for OCP\AppFramework\Http\TemplateResponse
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TemplateResponse {

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
	 * @param string $appName Application name
	 * @param string $templateName Template name
	 * @param mixed[] $params Template parameters
	 * @param string $renderAs Render mode
	 *
	 * @return void
	 */
	public function __construct(
		string $appName,
		string $templateName,
		array $params = [],
		string $renderAs = 'user',
	) {
		$this->appName = $appName;
		$this->templateName = $templateName;
		$this->params = $params;
		$this->renderAs = $renderAs;
	}//end __construct()

	/**
	 * Get HTTP status code.
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return 200;
	}//end getStatus()

	/**
	 * Get the app the template belongs to.
	 *
	 * Mirrors OCP\AppFramework\Http\TemplateResponse::getApp(), which exists on
	 * the real class; the stub omitted it, so any test asserting which app a
	 * TemplateResponse renders from could not run.
	 *
	 * @return string
	 */
	public function getApp(): string {
		return $this->appName;
	}//end getApp()

	/**
	 * Get template name.
	 *
	 * @return string
	 */
	public function getTemplateName(): string {
		return $this->templateName;
	}//end getTemplateName()

	/**
	 * Get render mode.
	 *
	 * @return string
	 */
	public function getRenderAs(): string {
		return $this->renderAs;
	}//end getRenderAs()

	/**
	 * Get template parameters.
	 *
	 * @return mixed[]
	 */
	public function getParams(): array {
		return $this->params;
	}//end getParams()
}//end class

namespace OCP;

/**
 * Stub for OCP\ICache
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface ICache {
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
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int $ttl TTL in seconds
	 *
	 * @return mixed
	 */
	public function set(string $key, mixed $value, int $ttl = 0): mixed;

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
	public function clear(string $prefix = ''): mixed;
}//end interface

/**
 * Stub for OCP\ICacheFactory
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface ICacheFactory {
	/**
	 * Create a distributed cache.
	 *
	 * @param string $prefix Key prefix
	 *
	 * @return ICache
	 */
	public function createDistributed(string $prefix = ''): ICache;

	/**
	 * Create a local cache.
	 *
	 * @param string $prefix Key prefix
	 *
	 * @return ICache
	 */
	public function createLocal(string $prefix = ''): ICache;

	/**
	 * Create an in-memory cache.
	 *
	 * @param int $capacity Max entries
	 *
	 * @return ICache
	 */
	public function createInMemory(int $capacity = 512): ICache;

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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class NotFoundException extends \RuntimeException {
}//end class

/**
 * Stub for OCP\Files\NotPermittedException
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class NotPermittedException extends \RuntimeException {
}//end class

namespace OCP\AppFramework\Utility;

/**
 * Stub for OCP\AppFramework\Utility\ITimeFactory
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface ITimeFactory {
	/**
	 * Get the current Unix timestamp.
	 *
	 * @return int
	 */
	public function getTime(): int;

	/**
	 * Get a DateTime object.
	 *
	 * @param string $time Time string
	 * @param \DateTimeZone|null $timezone Timezone
	 *
	 * @return \DateTime
	 */
	public function getDateTime(string $time = '', ?\DateTimeZone $timezone = null): \DateTime;

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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
abstract class QueuedJob {

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
	public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time) {
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
	 * @param \OCP\BackgroundJob\IJobList $jobList Job list
	 * @param \Psr\Log\LoggerInterface|null $logger Logger
	 *
	 * @return void
	 */
	public function execute(\OCP\BackgroundJob\IJobList $jobList, ?\Psr\Log\LoggerInterface $logger = null): void {
	}//end execute()

	/**
	 * Set the argument
	 *
	 * @param mixed $argument Job argument
	 *
	 * @return void
	 */
	public function setArgument(mixed $argument): void {
		$this->argument = $argument;
	}//end setArgument()

	/**
	 * Get the argument
	 *
	 * @return mixed
	 */
	public function getArgument(): mixed {
		return $this->argument;
	}//end getArgument()
}//end class

/**
 * Stub for OCP\BackgroundJob\TimedJob
 *
 * Mirrors QueuedJob but adds the interval/last-run scheduling helpers used
 * by Filinq's TimedJob subclasses (e.g. SigningExpirationJob).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
abstract class TimedJob {

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
	public function __construct(\OCP\AppFramework\Utility\ITimeFactory $time) {
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
	public function setInterval(int $seconds): void {
		$this->interval = $seconds;
	}//end setInterval()

	/**
	 * Set the time sensitivity.
	 *
	 * @param int $sensitivity Sensitivity flag
	 *
	 * @return void
	 */
	public function setTimeSensitivity(int $sensitivity): void {
		$this->timeSensitivity = $sensitivity;
	}//end setTimeSensitivity()

	/**
	 * Execute the job.
	 *
	 * @param \OCP\BackgroundJob\IJobList $jobList Job list
	 * @param \Psr\Log\LoggerInterface|null $logger Logger
	 *
	 * @return void
	 */
	public function execute(\OCP\BackgroundJob\IJobList $jobList, ?\Psr\Log\LoggerInterface $logger = null): void {
	}//end execute()

	/**
	 * Set the argument
	 *
	 * @param mixed $argument Job argument
	 *
	 * @return void
	 */
	public function setArgument(mixed $argument): void {
		$this->argument = $argument;
	}//end setArgument()

	/**
	 * Get the argument
	 *
	 * @return mixed
	 */
	public function getArgument(): mixed {
		return $this->argument;
	}//end getArgument()
}//end class

/**
 * Stub for OCP\BackgroundJob\IJobList
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IJobList {
	/**
	 * Add a job to the list.
	 *
	 * @param string $job Job class name
	 * @param mixed $argument Job argument
	 *
	 * @return void
	 */
	public function add(string $job, mixed $argument = null): void;

	/**
	 * Remove a job from the list.
	 *
	 * @param string $job Job class name
	 * @param mixed $argument Job argument
	 *
	 * @return void
	 */
	public function remove(string $job, mixed $argument = null): void;

	/**
	 * Check if a job exists.
	 *
	 * @param string $job Job class name
	 * @param mixed $argument Job argument
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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IConfig {
	/**
	 * Set a system-level config value.
	 *
	 * @param string $key Config key
	 * @param mixed $value Config value
	 *
	 * @return void
	 */
	public function setSystemValue(string $key, mixed $value): void;

	/**
	 * Get a system-level config value.
	 *
	 * @param string $key Config key
	 * @param mixed $default Default value
	 *
	 * @return mixed
	 */
	public function getSystemValue(string $key, mixed $default = ''): mixed;

	/**
	 * Get a system-level config value as boolean.
	 *
	 * @param string $key Config key
	 * @param bool $default Default value
	 *
	 * @return bool
	 */
	public function getSystemValueBool(string $key, bool $default = false): bool;

	/**
	 * Get a system-level config value as integer.
	 *
	 * @param string $key Config key
	 * @param int $default Default value
	 *
	 * @return int
	 */
	public function getSystemValueInt(string $key, int $default = 0): int;

	/**
	 * Get a system-level config value as string.
	 *
	 * @param string $key Config key
	 * @param string $default Default value
	 *
	 * @return string
	 */
	public function getSystemValueString(string $key, string $default = ''): string;

	/**
	 * Set an app-level config value.
	 *
	 * @param string $appName App identifier
	 * @param string $key Config key
	 * @param string $value Config value
	 *
	 * @return void
	 */
	public function setAppValue(string $appName, string $key, string $value): void;

	/**
	 * Get an app-level config value.
	 *
	 * @param string $appName App identifier
	 * @param string $key Config key
	 * @param string $default Default value
	 *
	 * @return string
	 */
	public function getAppValue(string $appName, string $key, string $default = ''): string;

	/**
	 * Delete an app-level config value.
	 *
	 * @param string $appName App identifier
	 * @param string $key Config key
	 *
	 * @return void
	 */
	public function deleteAppValue(string $appName, string $key): void;

	/**
	 * Set a user-level config value.
	 *
	 * @param string $userId User identifier
	 * @param string $appName App identifier
	 * @param string $key Config key
	 * @param string $value Config value
	 * @param string|null $preCondition Pre-condition
	 *
	 * @return void
	 */
	public function setUserValue(string $userId, string $appName, string $key, string $value, ?string $preCondition = null): void;

	/**
	 * Get a user-level config value.
	 *
	 * @param string $userId User identifier
	 * @param string $appName App identifier
	 * @param string $key Config key
	 * @param string $default Default value
	 *
	 * @return string
	 */
	public function getUserValue(string $userId, string $appName, string $key, string $default = ''): string;

	/**
	 * Delete a user-level config value.
	 *
	 * @param string $userId User identifier
	 * @param string $appName App identifier
	 * @param string $key Config key
	 *
	 * @return void
	 */
	public function deleteUserValue(string $userId, string $appName, string $key): void;

	/**
	 * Every user id holding one exact value for one app-scoped preference key.
	 *
	 * The only enumeration IConfig offers over `oc_preferences`: there is no
	 * "list every key this app stored for every user" call, which is why
	 * MigrateUserPreferences enumerates by value instead. Omitted from this
	 * stub until that step needed it, and its absence made the step
	 * untestable rather than failing anywhere visible.
	 *
	 * @param string $appName App identifier
	 * @param string $key Config key
	 * @param string $value The exact stored value to match
	 *
	 * @return array<int, string> Matching user ids
	 */
	public function getUsersForUserValue(string $appName, string $key, string $value): array;
}//end interface

/**
 * Stub for OCP\IAppConfig
 *
 * The lazy/typed app-configuration API used by Filinq services and
 * background jobs (getValueString/Int/Float, setValueString, etc.).
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IAppConfig {
	/**
	 * Get a string app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param string $default Default value
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return string
	 */
	public function getValueString(string $app, string $key, string $default = '', bool $lazy = false): string;

	/**
	 * Get an integer app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param int $default Default value
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return int
	 */
	public function getValueInt(string $app, string $key, int $default = 0, bool $lazy = false): int;

	/**
	 * Get a float app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param float $default Default value
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return float
	 */
	public function getValueFloat(string $app, string $key, float $default = 0.0, bool $lazy = false): float;

	/**
	 * Get a boolean app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param bool $default Default value
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return bool
	 */
	public function getValueBool(string $app, string $key, bool $default = false, bool $lazy = false): bool;

	/**
	 * Set a string app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param string $value Config value
	 * @param bool $lazy Whether the value is lazy-loaded
	 * @param bool $sensitive Whether the value is sensitive
	 *
	 * @return bool
	 */
	public function setValueString(string $app, string $key, string $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Set an integer app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param int $value Config value
	 * @param bool $lazy Whether the value is lazy-loaded
	 * @param bool $sensitive Whether the value is sensitive
	 *
	 * @return bool
	 */
	public function setValueInt(string $app, string $key, int $value, bool $lazy = false, bool $sensitive = false): bool;

	/**
	 * Set a boolean app-config value.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param bool $value Config value
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return bool
	 */
	public function setValueBool(string $app, string $key, bool $value, bool $lazy = false): bool;

	/**
	 * Determine whether an app-config key exists.
	 *
	 * @param string $app App identifier
	 * @param string $key Config key
	 * @param bool $lazy Whether the value is lazy-loaded
	 *
	 * @return bool
	 */
	public function hasKey(string $app, string $key, ?bool $lazy = false): bool;

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
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IAppManager {
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
	 * @param string $appId App identifier
	 * @param \OCP\IUser|null $user The user, or null for the current user
	 *
	 * @return bool
	 */
	public function isEnabledForUser(string $appId, $user = null): bool;

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

	/**
	 * Absolute path to an app's directory on disk.
	 *
	 * Signature mirrors OCP\App\IAppManager::getAppPath(). A stub that omits a
	 * method the production interface declares does not fail loudly: PHPUnit
	 * refuses to configure it ("Trying to configure method ... which cannot be
	 * configured because it does not exist"), so the test errors somewhere far
	 * from the omission, and reads as a broken test rather than a short stub.
	 *
	 * @param string $appId App identifier
	 * @param bool $ignoreCache Whether to bypass the path cache
	 *
	 * @return string
	 */
	public function getAppPath(string $appId, bool $ignoreCache = false): string;
}//end interface

namespace OCP\Notification;

/**
 * Stub for OCP\Notification\INotification
 *
 * A minimal fluent builder mirror of the Nextcloud notification model.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface INotification {
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
	 * @param string $id Object id
	 *
	 * @return INotification
	 */
	public function setObject(string $type, string $id): INotification;

	/**
	 * Set the notification subject.
	 *
	 * @param string $subject Subject key
	 * @param array<mixed> $parameters Subject parameters
	 *
	 * @return INotification
	 */
	public function setSubject(string $subject, array $parameters = []): INotification;
}//end interface

/**
 * Stub for OCP\Notification\IManager
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IManager {
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
 * Minimal base class so filinq's LanguageNegotiationMiddleware compiles
 * in the standalone unit-test runner.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://www.filinq.app
 */
class Middleware {
	/**
	 * Hook fired before the controller method.
	 *
	 * @param mixed $controller The controller instance.
	 * @param string $methodName The method name being called.
	 *
	 * @return void
	 */
	public function beforeController($controller, $methodName): void {
	}

	/**
	 * Hook fired after the controller method returns.
	 *
	 * @param mixed $controller The controller instance.
	 * @param string $methodName The method name that was called.
	 * @param \OCP\AppFramework\Http\Response $response The response object.
	 *
	 * @return \OCP\AppFramework\Http\Response
	 */
	public function afterController($controller, $methodName, \OCP\AppFramework\Http\Response $response): \OCP\AppFramework\Http\Response {
		return $response;
	}

	/**
	 * Hook fired when an exception is thrown.
	 *
	 * @param mixed $controller The controller instance.
	 * @param string $methodName The method name being called.
	 * @param \Throwable $exception The exception thrown.
	 *
	 * @return mixed
	 *
	 * @throws \Throwable
	 */
	public function afterException($controller, $methodName, \Throwable $exception) {
		throw $exception;
	}
}//end class

namespace OCP;

/**
 * Stub for OCP\ITempManager
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface ITempManager {
	public function getTemporaryFile(string $postfix = ''): string|false;
	public function getTemporaryFolder(string $postfix = ''): string|false;
	public function clean(): void;
	public function cleanOld(): void;
}//end interface

/**
 * Stub for OCP\IDBConnection
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IDBConnection {
	public function getQueryBuilder(): mixed;
	public function prepare(string $sql, ?int $limit = null, ?int $offset = null): mixed;
	public function executeQuery(string $sql, array $params = [], array $types = []): mixed;
	public function executeStatement(string $sql, array $params = [], array $types = []): int;

	/**
	 * The active database platform.
	 *
	 * Real OCP declares this and RenameDutchColumns calls it to quote identifiers.
	 * A stub that omits a method the production code calls does not fail loudly —
	 * the mock simply has no such method, and the test errors somewhere unrelated.
	 *
	 * @return mixed
	 */
	public function getDatabasePlatform(): mixed;
}//end interface

/**
 * Stub for OCP\Server
 *
 * Delegates static get() to the \OC::$server container set up by the test
 * bootstrap, mirroring the real Nextcloud service-locator shim.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Server {
	/**
	 * Resolve a service from the test container (\OC::$server).
	 *
	 * @param string $class Service class or interface name.
	 *
	 * @return mixed
	 */
	public static function get(string $class): mixed {
		if (\OC::$server === null) {
			throw new \Exception('Server container not available in unit tests');
		}

		return \OC::$server->get($class);
	}//end get()
}//end class

namespace OCP\Lock;

/**
 * Stub for OCP\Lock\ILockingProvider
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface ILockingProvider {
	public const LOCK_SHARED = 1;
	public const LOCK_EXCLUSIVE = 2;

	public function acquireLock(string $path, int $type, ?string $readablePath = null): void;
	public function releaseLock(string $path, int $type): void;
	public function changeLock(string $path, int $targetType): void;
	public function isLocked(string $path, int $type): bool;
}//end interface

/**
 * Stub for OCP\Lock\LockedException
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class LockedException extends \Exception {
	/**
	 * Constructor mirroring OCP's four-argument signature.
	 *
	 * `OwnerLockedException` (loaded from vendor/nextcloud/ocp, not stubbed)
	 * calls `parent::__construct($path, null, $owner, $readablePath)`. A stub
	 * that inherits \Exception's three-argument constructor therefore raises
	 * ArgumentCountError the moment anything constructs a real lock conflict —
	 * a stub drifting from the class it stands in for.
	 *
	 * @param string $path The locked path.
	 * @param \Throwable|null $previous The previous exception.
	 * @param string|null $existingLock The existing lock's owner.
	 * @param string|null $readablePath A human-readable path.
	 */
	public function __construct(
		string $path = '',
		?\Throwable $previous = null,
		?string $existingLock = null,
		?string $readablePath = null,
	) {
		unset($readablePath);
		parent::__construct('"' . $path . '" is locked' . ($existingLock === null ? '' : ' by ' . $existingLock), 0, $previous);
	}//end __construct()
}//end class

namespace OCP\Files\Conversion;

/**
 * Stub for OCP\Files\Conversion\IConversionManager
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IConversionManager {
	public function hasProviders(): bool;
	public function getProviders(): array;
	public function convert(\OCP\Files\File $file, string $targetMimeType, ?string $path = null): string;
}//end interface

namespace OCP\TaskProcessing;

/**
 * Stub for OCP\TaskProcessing\IManager (financial-document-field-extraction
 * REQ-FIN-06 optional AI-enhancement seam). Mirrors the real NC 30+ surface:
 * `runTask()` executes synchronously and returns the completed Task.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IManager {
	public function runTask(Task $task): Task;
	public function scheduleTask(Task $task): void;
	public function getTask(int $id): Task;
}//end interface

/**
 * Stub for OCP\TaskProcessing\Task.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Task implements \JsonSerializable {
	public const STATUS_UNKNOWN = 0;
	public const STATUS_SCHEDULED = 1;
	public const STATUS_RUNNING = 2;
	public const STATUS_SUCCESSFUL = 3;
	public const STATUS_FAILED = 4;
	public const STATUS_CANCELLED = 5;

	private ?array $output = null;

	private int $status = self::STATUS_UNKNOWN;

	private ?string $errorMessage = null;

	public function __construct(
		protected readonly string $taskTypeId,
		protected array $input,
		protected readonly string $appId,
		protected readonly ?string $userId,
		protected readonly ?string $customId = '',
	) {
	}//end __construct()

	public function getStatus(): int {
		return $this->status;
	}//end getStatus()

	public function setStatus(int $status): void {
		$this->status = $status;
	}//end setStatus()

	public function getOutput(): ?array {
		return $this->output;
	}//end getOutput()

	public function setOutput(?array $output): void {
		$this->output = $output;
	}//end setOutput()

	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}//end getErrorMessage()

	public function getInput(): array {
		return $this->input;
	}//end getInput()

	public function jsonSerialize(): array {
		return ['taskTypeId' => $this->taskTypeId, 'input' => $this->input];
	}//end jsonSerialize()
}//end class

namespace OCP\TaskProcessing\TaskTypes;

/**
 * Stub for OCP\TaskProcessing\TaskTypes\TextToText.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class TextToText {
	public const ID = 'core:text2text';
}//end class

namespace OCP\TextProcessing;

/**
 * Stub for OCP\TextProcessing\IManager (legacy fallback AI-enhancement seam).
 * Mirrors the real (deprecated) NC surface: `runTask()` executes
 * synchronously and returns the output string directly.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IManager {
	public function runTask(Task $task): string;
}//end interface

/**
 * Stub for OCP\TextProcessing\Task.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Task implements \JsonSerializable {
	public function __construct(
		protected string $type,
		protected string $input,
		protected string $appId,
		protected ?string $userId,
		protected string $identifier = '',
	) {
	}//end __construct()

	public function jsonSerialize(): array {
		return ['type' => $this->type, 'input' => $this->input];
	}//end jsonSerialize()
}//end class

/**
 * Stub for OCP\TextProcessing\FreePromptTaskType.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class FreePromptTaskType {
}//end class

namespace OCP\DB;

/**
 * Stub for OCP\DB\Exception.
 *
 * The real class extends Doctrine\DBAL\Exception, and this app ships
 * doctrine/deprecations but not doctrine/dbal — so the real file cannot be
 * loaded in the serverless unit suite.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class Exception extends \RuntimeException {
}//end class

/**
 * Stub for OCP\DB\IResult.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IResult {
	/**
	 * Close the cursor.
	 *
	 * @return bool
	 */
	public function closeCursor(): bool;

	/**
	 * Fetch the next row.
	 *
	 * @param int $fetchMode PDO fetch mode.
	 *
	 * @return mixed
	 */
	public function fetch(int $fetchMode = 2): mixed;

	/**
	 * Fetch every remaining row.
	 *
	 * @param int $fetchMode PDO fetch mode.
	 *
	 * @return array<int, mixed>
	 */
	public function fetchAll(int $fetchMode = 2): array;

	/**
	 * Fetch the first column of the next row.
	 *
	 * @return mixed
	 */
	public function fetchColumn(): mixed;

	/**
	 * Fetch a single value.
	 *
	 * @return mixed
	 */
	public function fetchOne(): mixed;

	/**
	 * Number of rows.
	 *
	 * @return int
	 */
	public function rowCount(): int;
}//end interface

/**
 * Stub for OCP\DB\IPreparedStatement.
 *
 * bindValue() drops the real signature's Doctrine ParameterType default; the
 * type argument is untyped here so nothing in the stub reaches for a package
 * this app does not ship.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
interface IPreparedStatement {
	/**
	 * Close the cursor.
	 *
	 * @return bool
	 */
	public function closeCursor(): bool;

	/**
	 * Fetch the next row.
	 *
	 * @param int $fetchMode PDO fetch mode.
	 *
	 * @return mixed
	 */
	public function fetch(int $fetchMode = 2): mixed;

	/**
	 * Fetch every remaining row.
	 *
	 * @param int $fetchMode PDO fetch mode.
	 *
	 * @return array<int, mixed>
	 */
	public function fetchAll(int $fetchMode = 2): array;

	/**
	 * Fetch the first column of the next row.
	 *
	 * @return mixed
	 */
	public function fetchColumn(): mixed;

	/**
	 * Fetch a single value.
	 *
	 * @return mixed
	 */
	public function fetchOne(): mixed;

	/**
	 * Bind a value to a placeholder.
	 *
	 * @param mixed $param Placeholder name or position.
	 * @param mixed $value Value to bind.
	 * @param mixed $type Parameter type; untyped here so the stub never reaches
	 *                    for Doctrine's ParameterType, which this app does not ship.
	 *
	 * @return bool
	 */
	public function bindValue(mixed $param, mixed $value, mixed $type = null): bool;

	/**
	 * Execute the statement.
	 *
	 * @param mixed $params Optional bound parameters.
	 *
	 * @return mixed
	 */
	public function execute(mixed $params = null): mixed;

	/**
	 * Number of affected rows.
	 *
	 * @return int
	 */
	public function rowCount(): int;
}//end interface

namespace OCP\Http\Client;

/**
 * Stub of OCP\Http\Client\IResponse.
 *
 * Signatures transcribed from server lib/public/Http/Client/IResponse.php.
 * getBody() is deliberately UNTYPED there (it returns string|resource), and the
 * stub must not "tidy" that to `string` — a stub tightened past the real class is
 * how a test starts asserting a contract the runtime does not have.
 */
interface IResponse {

	/**
	 * The response body.
	 *
	 * @return string|resource
	 */
	public function getBody();

	/**
	 * The HTTP status code.
	 *
	 * @return int
	 */
	public function getStatusCode(): int;

	/**
	 * A single response header.
	 *
	 * @param string $key The header name.
	 *
	 * @return string
	 */
	public function getHeader(string $key): string;

	/**
	 * All response headers.
	 *
	 * @return array
	 */
	public function getHeaders(): array;
}//end interface

/**
 * Stub of OCP\Http\Client\IClient.
 *
 * Only the verbs Filinq uses are declared.
 */
interface IClient {

	/**
	 * Issue a GET request.
	 *
	 * @param string $uri The URI.
	 * @param array $options Request options.
	 *
	 * @return IResponse
	 */
	public function get(string $uri, array $options = []): IResponse;
}//end interface

/**
 * Stub of OCP\Http\Client\IClientService.
 */
interface IClientService {

	/**
	 * Build a client.
	 *
	 * @return IClient
	 */
	public function newClient(): IClient;
}//end interface
