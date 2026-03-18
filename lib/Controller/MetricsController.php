<?php
/**
 * Metrics Controller
 *
 * Controller for exposing Prometheus metrics in text exposition format.
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

use OCA\DocuDesk\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for exposing Prometheus metrics
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetricsController extends Controller
{


    /**
     * MetricsController constructor
     *
     * @param string          $appName The name of the app
     * @param IRequest        $request The request object
     * @param IConfig         $config  The config service
     * @param IDBConnection   $database The database connection
     * @param LoggerInterface $logger  Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IConfig $config,
        private readonly IDBConnection $database,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Expose Prometheus metrics
     *
     * @return TextPlainResponse Plain text response with Prometheus metrics
     *
     * @NoCSRFRequired
     */
    public function index(): TextPlainResponse
    {
        $lines = [];

        $appVersion = $this->config->getAppValue(Application::APP_ID, 'installed_version', '0.0.0');
        $phpVersion = PHP_VERSION;
        $ncVersion  = $this->config->getSystemValueString('version', '0.0.0');

        // Info gauge.
        $lines[] = '# HELP docudesk_info Application information';
        $lines[] = '# TYPE docudesk_info gauge';
        $lines[] = 'docudesk_info{version="'.$appVersion.'",php_version="'.$phpVersion.'",nextcloud_version="'.$ncVersion.'"} 1';

        // Up gauge.
        $lines[] = '# HELP docudesk_up Whether the application is up';
        $lines[] = '# TYPE docudesk_up gauge';
        $lines[] = 'docudesk_up 1';

        // Documents total.
        $documentsTotal = $this->countDocuments();
        $lines[]        = '# HELP docudesk_documents_total Total number of documents';
        $lines[]        = '# TYPE docudesk_documents_total gauge';
        $lines[]        = 'docudesk_documents_total '.$documentsTotal;

        // Templates total.
        $templatesTotal = $this->countTemplates();
        $lines[]        = '# HELP docudesk_templates_total Total number of templates';
        $lines[]        = '# TYPE docudesk_templates_total gauge';
        $lines[]        = 'docudesk_templates_total '.$templatesTotal;

        // PDF generations counter.
        $pdfTotal = (int) $this->config->getAppValue(Application::APP_ID, 'pdf_generations_total', '0');
        $lines[]  = '# HELP docudesk_pdf_generations_total Total PDF generation operations';
        $lines[]  = '# TYPE docudesk_pdf_generations_total counter';
        $lines[]  = 'docudesk_pdf_generations_total '.$pdfTotal;

        // Anonymizations counter.
        $anonTotal = (int) $this->config->getAppValue(Application::APP_ID, 'anonymizations_total', '0');
        $lines[]   = '# HELP docudesk_anonymizations_total Total anonymization operations';
        $lines[]   = '# TYPE docudesk_anonymizations_total counter';
        $lines[]   = 'docudesk_anonymizations_total '.$anonTotal;

        $body     = implode("\n", $lines)."\n";
        $response = new TextPlainResponse($body);
        $response->addHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        return $response;

    }//end index()


    /**
     * Count documents managed by DocuDesk via OpenRegister
     *
     * @return int The total document count
     */
    private function countDocuments(): int
    {
        try {
            $registerId = $this->config->getAppValue(Application::APP_ID, 'document_register', '');
            $schemaId   = $this->config->getAppValue(Application::APP_ID, 'document_schema', '');

            if ($registerId === '' || $schemaId === '') {
                return 0;
            }

            $queryBuilder =$this->database->getQueryBuilder();
            $queryBuilder->select($queryBuilder->createFunction('COUNT(*) AS cnt'))
                ->from('openregister_objects')
                ->where($queryBuilder->expr()->eq('register', $queryBuilder->createNamedParameter($registerId)))
                ->andWhere($queryBuilder->expr()->eq('schema', $queryBuilder->createNamedParameter($schemaId)));

            $result = $queryBuilder->executeQuery();
            $count  = (int) $result->fetchOne();
            $result->closeCursor();

            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('Could not count documents for metrics', ['exception' => $e->getMessage()]);
            return 0;
        }

    }//end countDocuments()


    /**
     * Count templates managed by DocuDesk via OpenRegister
     *
     * @return int The total template count
     */
    private function countTemplates(): int
    {
        try {
            $registerId = $this->config->getAppValue(Application::APP_ID, 'template_register', '');
            $schemaId   = $this->config->getAppValue(Application::APP_ID, 'template_schema', '');

            if ($registerId === '' || $schemaId === '') {
                return 0;
            }

            $queryBuilder =$this->database->getQueryBuilder();
            $queryBuilder->select($queryBuilder->createFunction('COUNT(*) AS cnt'))
                ->from('openregister_objects')
                ->where($queryBuilder->expr()->eq('register', $queryBuilder->createNamedParameter($registerId)))
                ->andWhere($queryBuilder->expr()->eq('schema', $queryBuilder->createNamedParameter($schemaId)));

            $result = $queryBuilder->executeQuery();
            $count  = (int) $result->fetchOne();
            $result->closeCursor();

            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('Could not count templates for metrics', ['exception' => $e->getMessage()]);
            return 0;
        }

    }//end countTemplates()


}//end class
