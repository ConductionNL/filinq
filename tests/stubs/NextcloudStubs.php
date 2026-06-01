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
     * Get a user config value
     *
     * @param string $userId  The user ID
     * @param string $appName The app name
     * @param string $key     The config key
     * @param string $default Default value
     *
     * @return string
     */
    public function getUserValue(string $userId, string $appName, string $key, string $default=''): string;

    /**
     * Get a system value
     *
     * @param string $key     The config key
     * @param mixed  $default Default value
     *
     * @return mixed
     */
    public function getSystemValue(string $key, mixed $default=''): mixed;


}//end interface


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
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * HTTP status code
     *
     * @var int
     */
    private int $status;


    /**
     * Constructor
     *
     * @param array<string, mixed> $data   Response data
     * @param int                  $status HTTP status code
     *
     * @return void
     */
    public function __construct(array $data=[], int $status=200)
    {
        $this->data   = $data;
        $this->status = $status;

    }//end __construct()


    /**
     * Get response data
     *
     * @return array<string, mixed>
     */
    public function getData(): array
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
