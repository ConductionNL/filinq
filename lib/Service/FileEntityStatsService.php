<?php
/**
 * File Entity Stats Service
 *
 * Service for retrieving entity counts, anonymization status, and risk levels
 * for files. Extracted from FileListingService to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for retrieving entity statistics and risk levels for files
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class FileEntityStatsService
{
    /**
     * Constructor for FileEntityStatsService
     *
     * @param LoggerInterface    $logger     Logger for error reporting
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager
    ) {

    }//end __construct()

    /**
     * Check if OpenRegister app is installed
     *
     * @return bool True if OpenRegister is installed
     */
    private function isOpenRegisterInstalled(): bool
    {
        return in_array('openregister', $this->appManager->getInstalledApps(), true) === true;

    }//end isOpenRegisterInstalled()

    /**
     * Try to get the EntityRelationMapper, returning null on failure
     *
     * @return \OCA\OpenRegister\Db\EntityRelationMapper|null The mapper or null
     */
    public function tryGetEntityRelationMapper(): ?\OCA\OpenRegister\Db\EntityRelationMapper
    {
        try {
            if ($this->isOpenRegisterInstalled() === true) {
                return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
            }

            return null;
        } catch (\RuntimeException $e) {
            $this->logger->warning('EntityRelationMapper not available: '.$e->getMessage());
            return null;
        }

    }//end tryGetEntityRelationMapper()

    /**
     * Try to get the RiskLevelService, returning null on failure
     *
     * @return \OCA\OpenRegister\Service\RiskLevelService|null The service or null
     */
    public function tryGetRiskLevelService(): ?\OCA\OpenRegister\Service\RiskLevelService
    {
        try {
            if ($this->isOpenRegisterInstalled() === true) {
                return $this->container->get('OCA\OpenRegister\Service\RiskLevelService');
            }

            return null;
        } catch (\RuntimeException $e) {
            $this->logger->warning('RiskLevelService not available: '.$e->getMessage());
            return null;
        }

    }//end tryGetRiskLevelService()

    /**
     * Get entity statistics for a file
     *
     * @param int                                            $fileId               The file ID
     * @param \OCA\OpenRegister\Db\EntityRelationMapper|null $entityRelationMapper The mapper
     *
     * @return array{entityCount: int, anonymizedCount: int, status: string} Entity stats
     */
    public function getEntityStats(
        int $fileId,
        ?\OCA\OpenRegister\Db\EntityRelationMapper $entityRelationMapper
    ): array {
        $stats = [
            'entityCount'     => 0,
            'anonymizedCount' => 0,
            'status'          => 'uploaded',
        ];

        if ($entityRelationMapper === null) {
            return $stats;
        }

        try {
            $relations            = $entityRelationMapper->findByFileId($fileId);
            $stats['entityCount'] = count($relations);
            $anonymized           = 0;

            foreach ($relations as $relation) {
                if ($relation->getAnonymized() === true) {
                    $anonymized++;
                }
            }

            $stats['anonymizedCount'] = $anonymized;
            $stats['status']          = $this->determineFileStatus(
                entityCount: $stats['entityCount'],
                anonymizedCount: $anonymized
            );
        } catch (\Exception $e) {
            $this->logger->debug(
                'Could not fetch entities for file '.$fileId.': '.$e->getMessage()
            );
        }//end try

        return $stats;

    }//end getEntityStats()

    /**
     * Determine file status based on entity counts
     *
     * @param int $entityCount     Total entity count
     * @param int $anonymizedCount Anonymized entity count
     *
     * @return string The status string
     */
    public function determineFileStatus(int $entityCount, int $anonymizedCount): string
    {
        if ($entityCount > 0 && $anonymizedCount === $entityCount) {
            return 'anonymized';
        }

        if ($entityCount > 0) {
            return 'extracted';
        }

        return 'uploaded';

    }//end determineFileStatus()

    /**
     * Get risk level for a file
     *
     * @param int                                             $fileId           The file ID
     * @param \OCA\OpenRegister\Service\RiskLevelService|null $riskLevelService The service
     *
     * @return string The risk level
     */
    public function getFileRiskLevel(
        int $fileId,
        ?\OCA\OpenRegister\Service\RiskLevelService $riskLevelService
    ): string {
        if ($riskLevelService === null) {
            return 'none';
        }

        try {
            return $riskLevelService->getRiskLevel($fileId);
        } catch (\Exception $e) {
            $this->logger->debug(
                'Could not fetch risk level for file '.$fileId.': '.$e->getMessage()
            );
            return 'none';
        }

    }//end getFileRiskLevel()
}//end class
