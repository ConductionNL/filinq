<?php
/**
 * Consent Service
 *
 * Service for managing GDPR publication consent tracking.
 * Handles creating, updating, and querying consent records for
 * entities detected in documents that require notification before
 * publication under the Wet Open Overheid.
 * Delegates deadline checking to ObjectionDeadlineChecker.
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

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for GDPR publication consent management
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConsentService
{


    /**
     * Constructor for ConsentService
     *
     * @param LoggerInterface         $logger           Logger for error reporting
     * @param ContainerInterface      $container        Container for dependency injection
     * @param IAppManager             $appManager       App manager interface
     * @param ObjectionDeadlineChecker $deadlineChecker Objection deadline checker
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ObjectionDeadlineChecker $deadlineChecker
    ) {

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
     * Create a consent request for a detected entity in a document
     *
     * @param string               $documentId The document UUID
     * @param string               $entityType The entity type (PERSON or ORGANIZATION)
     * @param string               $entityText The detected entity text
     * @param string               $register   The register ID for consent objects
     * @param string               $schema     The schema ID for consent objects
     * @param array<string, mixed> $extra      Additional consent data
     *
     * @return array<string, mixed> The created consent record
     *
     * @throws Exception If consent creation fails
     */
    public function createConsentRequest(
        string $documentId,
        string $entityType,
        string $entityText,
        string $register,
        string $schema,
        array $extra=[]
    ): array {
        try {
            $objectService = $this->getObjectService();
            $deadline      = $this->deadlineChecker->calculateDeadline();

            $consentData = array_merge(
                    [
                        'documentId'          => $documentId,
                        'entityType'          => $entityType,
                        'entityText'          => $entityText,
                        'notificationStatus'  => 'pending',
                        'consentStatus'       => 'pending',
                        'publicationDecision' => 'pending',
                        'objectionDeadline'   => $deadline->format('c'),
                    ],
                    $extra
                    );

            $savedObject = $objectService->saveObject(
                object: $consentData,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

            $this->logger->info(
                'Consent request created',
                [
                    'documentId' => $documentId,
                    'entityType' => $entityType,
                    'entityText' => $entityText,
                ]
            );

            return $savedObject->getObject();
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create consent request: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception('Failed to create consent request: '.$e->getMessage(), 0, $e);
        }//end try

    }//end createConsentRequest()


    /**
     * Update consent status for a consent record
     *
     * @param string               $consentId The consent object UUID
     * @param string               $register  The register ID
     * @param string               $schema    The schema ID
     * @param array<string, mixed> $data      The data to update
     *
     * @return array<string, mixed> The updated consent record
     *
     * @throws Exception If update fails
     */
    public function updateConsentStatus(
        string $consentId,
        string $register,
        string $schema,
        array $data
    ): array {
        try {
            $objectService = $this->getObjectService();

            // Find the existing consent record.
            $object = $objectService->find(
                id: $consentId,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

            if ($object === null) {
                throw new Exception('Consent record not found: '.$consentId);
            }

            // Merge update data into existing record.
            $consentData = array_merge($object->getObject(), $data);

            // Save updated record.
            $savedObject = $objectService->saveObject(
                object: $consentData,
                register: $register,
                schema: $schema,
                _rbac: false,
                _multitenancy: false
            );

            $this->logger->info(
                'Consent status updated',
                [
                    'consentId'   => $consentId,
                    'updatedKeys' => array_keys($data),
                ]
            );

            return $savedObject->getObject();
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to update consent status: '.$e->getMessage(),
                [
                    'consentId' => $consentId,
                    'exception' => $e,
                ]
            );
            throw new Exception('Failed to update consent status: '.$e->getMessage(), 0, $e);
        }//end try

    }//end updateConsentStatus()


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
     */
    public function checkObjectionDeadline(string $consentId, string $register, string $schema): bool
    {
        return $this->deadlineChecker->checkObjectionDeadline($consentId, $register, $schema);

    }//end checkObjectionDeadline()


    /**
     * Get all consent records for a specific document
     *
     * @param string $documentId The document UUID
     * @param string $register   The register ID
     * @param string $schema     The schema ID
     *
     * @return array<int, array<string, mixed>> List of consent records
     *
     * @throws Exception If query fails
     */
    public function getConsentsByDocument(string $documentId, string $register, string $schema): array
    {
        try {
            $objectService = $this->getObjectService();

            $results = $objectService->searchObjects(
                [
                    '@self'      => ['register' => $register, 'schema' => $schema],
                    'documentId' => $documentId,
                ]
            );

            $consents = [];
            foreach ($results as $result) {
                $consent = (array) $result;
                if (is_object($result) === true && method_exists($result, 'getObject') === true) {
                    $consent = $result->getObject();
                }

                $consents[] = $consent;
            }

            return $consents;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to get consents for document: '.$e->getMessage(),
                [
                    'documentId' => $documentId,
                    'exception'  => $e,
                ]
            );
            throw new Exception(
                'Failed to get consents for document: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end getConsentsByDocument()


}//end class
