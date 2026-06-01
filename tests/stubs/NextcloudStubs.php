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
     * Get the HTTP method
     *
     * @return string
     */
    public function getMethod(): string;


    /**
     * Get an uploaded file
     *
     * @param string $key File key
     *
     * @return array<string, mixed>
     */
    public function getUploadedFile(string $key): array;


    /**
     * Get a request header
     *
     * @param string $name Header name
     *
     * @return string
     */
    public function getHeader(string $name): string;


    /**
     * Check user agent
     *
     * @param array<string> $agent Agent patterns
     *
     * @return bool
     */
    public function isUserAgent(array $agent): bool;


    /**
     * Get server protocol
     *
     * @return string
     */
    public function getServerProtocol(): string;


    /**
     * Get raw path info
     *
     * @return string
     */
    public function getRawPathInfo(): string;


    /**
     * Get path info
     *
     * @return string|false
     */
    public function getPathInfo(): string|false;


    /**
     * Get request URI
     *
     * @return string
     */
    public function getRequestUri(): string;


    /**
     * Get request ID
     *
     * @return string
     */
    public function getId(): string;


    /**
     * Get remote address
     *
     * @return string
     */
    public function getRemoteAddress(): string;


    /**
     * Get server host
     *
     * @return string
     */
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

    /**
     * Translate a string
     *
     * @param string       $text       The text to translate
     * @param array<mixed> $parameters Optional parameters
     *
     * @return string
     */
    public function t(string $text, array $parameters=[]): string;


    /**
     * Translate a plural string
     *
     * @param string       $text_singular Singular form
     * @param string       $text_plural   Plural form
     * @param int          $count         Count
     * @param array<mixed> $parameters    Parameters
     *
     * @return string
     */
    public function n(string $text_singular, string $text_plural, int $count, array $parameters=[]): string;


    /**
     * Format a value
     *
     * @param string       $type    Format type
     * @param mixed        $data    Data to format
     * @param array<mixed> $options Options
     *
     * @return mixed
     */
    public function l(string $type, mixed $data, array $options=[]): mixed;


    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguageCode(): string;


    /**
     * Get locale code
     *
     * @return string
     */
    public function getLocaleCode(): string;


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
     * Render response
     *
     * @return string
     */
    public function render(): string
    {
        return (string) json_encode($this->data);

    }//end render()


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


    /**
     * Get response headers
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
        parent::__construct($data);

    }//end __construct()


}//end class

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

    /**
     * Create a new notification
     *
     * @return INotification
     */
    public function createNotification(): INotification;


    /**
     * Notify a user
     *
     * @param INotification $notification The notification to send
     *
     * @return void
     */
    public function notify(INotification $notification): void;


    /**
     * Mark notifications as processed
     *
     * @param INotification $notification The notification filter
     *
     * @return void
     */
    public function markProcessed(INotification $notification): void;


    /**
     * Get the count of pending notifications
     *
     * @param INotification $notification The notification filter
     *
     * @return int
     */
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

    public function setApp(string $app): static;
    public function setUser(string $user): static;
    public function setDateTime(\DateTime $dateTime): static;
    public function setObject(string $type, string $id): static;
    public function setSubject(string $subject, array $parameters = []): static;
    public function setMessage(string $message, array $parameters = []): static;
    public function setLink(string $link): static;
    public function setIcon(string $icon): static;
    public function getApp(): string;
    public function getUser(): string;
    public function getDateTime(): \DateTime;
    public function getObjectType(): string;
    public function getObjectId(): string;
    public function getSubject(): string;
    public function getSubjectParameters(): array;
    public function getMessage(): string;
    public function getMessageParameters(): array;
    public function getLink(): string;
    public function getIcon(): string;
    public function isValid(): bool;
    public function isValidParsed(): bool;

}//end interface
