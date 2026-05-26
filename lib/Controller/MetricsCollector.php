<?php
/**
 * Metrics Collector
 *
 * Service for collecting document and template counts from the database.
 * Extracted from MetricsController to reduce class complexity.
 *
 * @category  Controller
 * @package   OCA\DocuDesk\Controller
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-21
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Controller;

use OCA\DocuDesk\AppInfo\Application;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Collects document and template counts for metrics reporting
 *
 * @category Controller
 * @package  OCA\DocuDesk\Controller
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class MetricsCollector
{
    /**
     * Constructor for MetricsCollector
     *
     * @param IConfig         $config   The config service
     * @param IDBConnection   $database The database connection
     * @param LoggerInterface $logger   Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly IConfig $config,
        private readonly IDBConnection $database,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Count documents managed by DocuDesk via OpenRegister
     *
     * @return int The total document count
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-21
     */
    public function countDocuments(): int
    {
        return $this->countObjects(type: 'document');

    }//end countDocuments()

    /**
     * Count templates managed by DocuDesk via OpenRegister
     *
     * @return int The total template count
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-21
     */
    public function countTemplates(): int
    {
        return $this->countObjects(type: 'template');

    }//end countTemplates()

    /**
     * Count objects of a given type in OpenRegister
     *
     * @param string $type The object type prefix (e.g. 'document', 'template')
     *
     * @return int The count of objects
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-21
     */
    private function countObjects(string $type): int
    {
        try {
            $registerId = $this->config->getAppValue(Application::APP_ID, $type.'_register', '');
            $schemaId   = $this->config->getAppValue(Application::APP_ID, $type.'_schema', '');

            if ($registerId === '' || $schemaId === '') {
                return 0;
            }

            $queryBuilder = $this->database->getQueryBuilder();
            $queryBuilder->select($queryBuilder->createFunction('COUNT(*) AS cnt'))
                ->from('openregister_objects')
                ->where(
                    $queryBuilder->expr()->eq(
                        'register',
                        $queryBuilder->createNamedParameter($registerId)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        'schema',
                        $queryBuilder->createNamedParameter($schemaId)
                    )
                );

            $result = $queryBuilder->executeQuery();
            $count  = (int) $result->fetchOne();
            $result->closeCursor();

            return $count;
        } catch (\Exception $e) {
            $this->logger->warning(
                'Could not count '.$type.'s for metrics',
                ['exception' => $e->getMessage()]
            );
            return 0;
        }//end try

    }//end countObjects()
}//end class
