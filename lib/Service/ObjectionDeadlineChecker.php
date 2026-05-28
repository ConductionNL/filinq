<?php
/**
 * Objection Deadline Checker
 *
 * Service for checking GDPR publication objection deadlines.
 * Extracted from ConsentService to reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-47
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTime;
use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for checking objection deadlines on consent records
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ObjectionDeadlineChecker
{

    /**
     * The application name
     *
     * @var string
     */
    private readonly string $appName;

    /**
     * Constructor for ObjectionDeadlineChecker
     *
     * @param LoggerInterface    $logger     Logger for error reporting
     * @param ContainerInterface $container  Container for dependency injection
     * @param IAppManager        $appManager App manager interface
     * @param IAppConfig         $config     App configuration interface
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly IAppConfig $config
    ) {
        $this->appName = 'docudesk';

    }//end __construct()

    /**
     * Get the ObjectService from OpenRegister
     *
     * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
     *
     * @throws \RuntimeException If OpenRegister is not available
     */
    private function getObjectService(): \OCA\OpenRegister\Service\ObjectService
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get('OCA\OpenRegister\Service\ObjectService');
        }

        throw new RuntimeException('OpenRegister service is not available.');

    }//end getObjectService()

    /**
     * Get the objection period in days from settings
     *
     * @return int Number of days for the objection period
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-47
     */
    public function getObjectionPeriodDays(): int
    {
        return (int) $this->config->getValueString(
            $this->appName,
            'publication_objection_period_days',
            '28'
        );

    }//end getObjectionPeriodDays()

    /**
     * Calculate the objection deadline from now
     *
     * @return DateTime The deadline date
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
     */
    public function calculateDeadline(): DateTime
    {
        $objectionDays = $this->getObjectionPeriodDays();
        $deadline      = new DateTime();
        $deadline->modify("+{$objectionDays} days");

        return $deadline;

    }//end calculateDeadline()

    /**
     * Check if an objection deadline has expired
     *
     * @param string $consentId The consent object UUID
     * @param string $register  The register ID
     * @param string $schema    The schema ID
     *
     * @return bool True if the deadline has passed
     *
     * @throws Exception If check fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
     */
    public function checkObjectionDeadline(string $consentId, string $register, string $schema): bool
    {
        try {
            $objectService = $this->getObjectService();

            $object = $objectService->find(
                id: $consentId,
                register: $register,
                schema: $schema
            );

            if ($object === null) {
                throw new Exception('Consent record not found: '.$consentId);
            }

            $objectData = $object->getObject();
            $deadline   = $objectData['objectionDeadline'] ?? null;

            if ($deadline === null) {
                return false;
            }

            $deadlineDate = new DateTime($deadline);
            $now          = new DateTime();

            return $now > $deadlineDate;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to check objection deadline: '.$e->getMessage(),
                [
                    'consentId' => $consentId,
                    'exception' => $e,
                ]
            );
            throw new Exception(
                'Failed to check objection deadline: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end checkObjectionDeadline()
}//end class
