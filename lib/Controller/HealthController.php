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
use OCP\IDBConnection;
use OCP\IRequest;
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
     * @param string          $appName The name of the app
     * @param IRequest        $request The request object
     * @param IDBConnection   $db      The database connection
     * @param LoggerInterface $logger  Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IDBConnection $db,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Return health check status
     *
     * @return JSONResponse JSON response with health status and checks
     *
     * @NoCSRFRequired
     */
    public function index(): JSONResponse
    {
        $checks = [];
        $status = 'ok';

        // Database check.
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('1'));
            $result = $qb->executeQuery();
            $result->closeCursor();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error';
            $status              = 'error';
            $this->logger->error('Health check: database failed', ['exception' => $e->getMessage()]);
        }

        // OpenRegister dependency check.
        try {
            $appManager = \OCP\Server::get(\OCP\App\IAppManager::class);
            $checks['openregister'] = $appManager->isEnabledForUser('openregister') === true ? 'ok' : 'missing';
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
