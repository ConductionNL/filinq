<?php
/**
 * Template Request Handler
 *
 * Handles request parameter parsing and error response building
 * for template controller operations. Extracted from TemplatesController
 * to reduce class complexity.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use Exception;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Handles request parameter parsing and error responses for templates
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class TemplateRequestHandler
{


    /**
     * Constructor for TemplateRequestHandler
     *
     * @param LoggerInterface $logger Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Parse list request parameters from the request
     *
     * @param IRequest $request The request object
     *
     * @return array{filters: array, limit: int, offset: int} Parsed parameters
     */
    public function parseListParams(IRequest $request): array
    {
        $filters   = [];
        $namespace = $request->getParam('namespace');
        $search    = $request->getParam('_search');
        $limit     = (int) $request->getParam('_limit', '20');
        $offset    = (int) $request->getParam('_offset', '0');

        if (empty($namespace) === false) {
            $filters['namespace'] = $namespace;
        }

        if (empty($search) === false) {
            $filters['_search'] = $search;
        }

        return [
            'filters' => $filters,
            'limit'   => $limit,
            'offset'  => $offset,
        ];

    }//end parseListParams()


    /**
     * Parse create/update request body and strip framework params
     *
     * @param IRequest     $request    The request object
     * @param array<string> $stripKeys Keys to strip from params
     *
     * @return array<string, mixed> Cleaned request data
     */
    public function parseBodyParams(IRequest $request, array $stripKeys=[]): array
    {
        $data = $request->getParams();
        unset($data['_route']);

        foreach ($stripKeys as $key) {
            unset($data[$key]);
        }

        return $data;

    }//end parseBodyParams()


    /**
     * Build a JSON error response from an exception
     *
     * @param Exception $exception  The exception
     * @param string    $logMessage The log message prefix
     *
     * @return JSONResponse The error response
     */
    public function buildErrorResponse(Exception $exception, string $logMessage): JSONResponse
    {
        $statusCode = 500;
        if ($exception->getCode() >= 400 && $exception->getCode() < 600) {
            $statusCode = $exception->getCode();
        }

        $this->logger->error(
            message: $logMessage.$exception->getMessage(),
            context: ['exception' => $exception]
        );

        return new JSONResponse(
            data: ['error' => $exception->getMessage()],
            statusCode: $statusCode
        );

    }//end buildErrorResponse()


}//end class
