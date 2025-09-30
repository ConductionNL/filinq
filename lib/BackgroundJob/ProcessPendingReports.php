<?php

/**
 * Background job to process pending reports
 *
 * @category  BackgroundJob
 * @package   OCA\DocuDesk\BackgroundJob
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * DocuDesk is free software: you can redistribute it and/or modify
 * it under the terms of the European Union Public License (EUPL),
 * version 1.2 only (the "Licence"), appearing in the file LICENSE
 * included in the packaging of this file.
 *
 * DocuDesk is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * European Union Public License for more details.
 *
 * You should have received a copy of the European Union Public License
 * along with DocuDesk. If not, see <https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12>.
 */

namespace OCA\DocuDesk\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCA\DocuDesk\Service\ObjectService;
use OCA\DocuDesk\Service\ReportingService;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Background job to process pending reports
 *
 * This job runs periodically to find and process reports that are in the 'pending' state.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2
 * @link     https://github.com/conductionnl/docudesk
 */
class ProcessPendingReports extends TimedJob
{
    /**
     * Maximum number of reports to process in a single run
     *
     * @var int
     */
    private const MAX_REPORTS_PER_RUN = 10;


    /**
     * Constructor for ProcessPendingReports
     *
     * @param ObjectService    $objectService    Service for handling objects
     * @param ReportingService $reportingService Service for generating reports
     * @param IRootFolder      $rootFolder       Root folder service
     * @param IConfig          $config           Configuration service
     * @param LoggerInterface  $logger           Logger for error reporting
     *
     * @return void
     */
    public function __construct(
        private readonly ObjectService $objectService,
        private readonly ReportingService $reportingService,
        private readonly IRootFolder $rootFolder,
        private readonly IConfig $config,
        private readonly LoggerInterface $logger
    ) {
        // Run every 15 minutes.
        $this->setInterval(15 * 60);

    }//end __construct()


    /**
     * Execute the background job
     *
     * @param array<string, mixed> $argument The job arguments
     *
     * @return void
     *
     * @psalm-param   array<string, mixed> $argument
     * @phpstan-param array<string, mixed> $argument
     */
    protected function run($argument): void
    {
        try {
            // Use the ReportingService to process pending reports.
            $processedCount = $this->reportingService->processPendingReports(self::MAX_REPORTS_PER_RUN);

            if ($processedCount > 0) {
                $this->logger->info("Successfully processed {$processedCount} pending reports");
            } else {
                $this->logger->debug('No pending reports were processed');
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error in ProcessPendingReports job: '.$e->getMessage(),
                    [
                        'exception' => $e,
                    ]
            );
        }

    }//end run()


    /**
     * Check if reporting is enabled
     *
     * @return bool True if reporting is enabled, false otherwise
     *
     * @psalm-return   bool
     * @phpstan-return bool
     */
    private function isReportingEnabled(): bool
    {
        return $this->reportingService->isReportingEnabled();

    }//end isReportingEnabled()


}//end class
