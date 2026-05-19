<?php
/**
 * Health Controller
 *
 * Controller for exposing health check status.
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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\App\IAppManager;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Controller for health check endpoint
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class HealthController extends Controller
{


    /**
     * HealthController constructor
     *
     * @param string          $appName  The name of the app
     * @param IRequest        $request  The request object
     * @param IDBConnection   $database The database connection
     * @param LoggerInterface $logger   Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IDBConnection $database,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()


    /**
     * Return health check status
     *
     * @return JSONResponse JSON response with health status and checks
     *
     * @NoCSRFRequired
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function index(): JSONResponse
    {
        $checks = [];
        $status = 'ok';

        // Database check.
        try {
            $queryBuilder = $this->database->getQueryBuilder();
            $queryBuilder->select($queryBuilder->createFunction('1'));
            $result = $queryBuilder->executeQuery();
            $result->closeCursor();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $status = 'error';
            $this->logger->error('Health check: database failed', ['exception' => $e->getMessage()]);
        }

        // OpenRegister dependency check.
        try {
            $appManager = Server::get(IAppManager::class);
            $checks['openregister'] = 'missing';
            if ($appManager->isEnabledForUser('openregister') === true) {
                $checks['openregister'] = 'ok';
            }

            if ($checks['openregister'] !== 'ok') {
                $status = 'degraded';
            }
        } catch (\Exception $e) {
            $checks['openregister'] = 'unknown';
        }

        return new JSONResponse(
            [
                'status' => $status,
                'checks' => $checks,
            ]
        );

    }//end index()


}//end class
