<?php
/**
 * Metrics Controller
 *
 * Controller for exposing Prometheus metrics in text exposition format.
 * Delegates count queries to MetricsCollector.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-22
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IConfig;
use OCP\IRequest;

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
     * @param string           $appName          The name of the app
     * @param IRequest         $request          The request object
     * @param IConfig          $config           The config service
     * @param MetricsCollector $metricsCollector Collector for document/template counts
     *
     * @return void
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IConfig $config,
        private readonly MetricsCollector $metricsCollector
    ) {
        parent::__construct(appName: $appName, request: $request);

    }//end __construct()

    /**
     * Expose Prometheus metrics
     *
     * @return TextPlainResponse Plain text response with Prometheus metrics
     *
     * @NoCSRFRequired
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-22
     */
    public function index(): TextPlainResponse
    {
        $lines = [];

        $appVersion = $this->config->getAppValue(Application::APP_ID, 'installed_version', '0.0.0');
        $phpVersion = PHP_VERSION;
        $ncVersion  = $this->config->getSystemValueString('version', '0.0.0');

        // Info gauge.
        $lines[]     = '# HELP docudesk_info Application information';
        $lines[]     = '# TYPE docudesk_info gauge';
        $infoLabels  = 'version="'.$appVersion.'",php_version="'.$phpVersion.'"';
        $infoLabels .= ',nextcloud_version="'.$ncVersion.'"';
        $lines[]     = 'docudesk_info{'.$infoLabels.'} 1';

        // Up gauge.
        $lines[] = '# HELP docudesk_up Whether the application is up';
        $lines[] = '# TYPE docudesk_up gauge';
        $lines[] = 'docudesk_up 1';

        // Documents total.
        $documentsTotal = $this->metricsCollector->countDocuments();
        $lines[]        = '# HELP docudesk_documents_total Total number of documents';
        $lines[]        = '# TYPE docudesk_documents_total gauge';
        $lines[]        = 'docudesk_documents_total '.$documentsTotal;

        // Templates total.
        $templatesTotal = $this->metricsCollector->countTemplates();
        $lines[]        = '# HELP docudesk_templates_total Total number of templates';
        $lines[]        = '# TYPE docudesk_templates_total gauge';
        $lines[]        = 'docudesk_templates_total '.$templatesTotal;

        // PDF generations counter.
        $pdfTotal = (int) $this->config->getAppValue(
            Application::APP_ID,
            'pdf_generations_total',
            '0'
        );
        $lines[]  = '# HELP docudesk_pdf_generations_total Total PDF generation operations';
        $lines[]  = '# TYPE docudesk_pdf_generations_total counter';
        $lines[]  = 'docudesk_pdf_generations_total '.$pdfTotal;

        // Anonymizations counter.
        $anonTotal = (int) $this->config->getAppValue(
            Application::APP_ID,
            'anonymizations_total',
            '0'
        );
        $lines[]   = '# HELP docudesk_anonymizations_total Total anonymization operations';
        $lines[]   = '# TYPE docudesk_anonymizations_total counter';
        $lines[]   = 'docudesk_anonymizations_total '.$anonTotal;

        $body     = implode("\n", $lines)."\n";
        $response = new TextPlainResponse($body);
        $response->addHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        return $response;

    }//end index()
}//end class
