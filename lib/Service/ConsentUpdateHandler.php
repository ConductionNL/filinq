<?php
/**
 * Consent Update Handler
 *
 * Service for updating consent records in OpenRegister.
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-14
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for updating and querying consent records
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConsentUpdateHandler
{
    /**
     * Constructor for ConsentUpdateHandler
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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-14
     */
    public function updateConsentStatus(
        string $consentId,
        string $register,
        string $schema,
        array $data
    ): array {
        try {
            $objectService = $this->getObjectService();

            // Let OpenRegister enforce per-object RBAC and multitenancy
            // access. Bypassing these (security finding #283) allowed any
            // authenticated user to overwrite consent records owned by
            // other users.
            $object = $objectService->find(
                id: $consentId,
                register: $register,
                schema: $schema
            );

            if ($object === null) {
                throw new Exception('Consent record not found: '.$consentId);
            }

            // Whitelist the fields a consent update may mutate so callers
            // cannot rewrite arbitrary consent attributes (e.g. documentId,
            // entityText) or inject extra keys (security finding #283).
            $mutableFields = [
                'notificationStatus',
                'consentStatus',
                'publicationDecision',
                'objectionReason',
                'objectionDeadline',
            ];
            $allowedData   = array_intersect_key($data, array_flip($mutableFields));

            $consentData = array_merge($object->getObject(), $allowedData);

            $savedObject = $objectService->saveObject(
                object: $consentData,
                register: $register,
                schema: $schema
            );

            $this->logger->info(
                'Consent status updated',
                [
                    'consentId'   => $consentId,
                    'updatedKeys' => array_keys($allowedData),
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
            throw new Exception(
                'Failed to update consent status: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end updateConsentStatus()

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
     */
    public function getConsentsByDocument(
        string $documentId,
        string $register,
        string $schema,
        ?string $ownerUid=null
    ): array {
        try {
            $objectService = $this->getObjectService();

            $selfScope = ['register' => $register, 'schema' => $schema];

            // Security (H1): scope the query to the caller's own records when
            // a non-admin UID is provided, so users cannot enumerate consent
            // records that belong to other users via the byDocument endpoint.
            if ($ownerUid !== null) {
                $selfScope['owner'] = $ownerUid;
            }

            $results = $objectService->searchObjects(
                [
                    '@self'      => $selfScope,
                    'documentId' => $documentId,
                ]
            );

            $consents = [];
            foreach ($results as $result) {
                $consent = (array) $result;
                if (is_object($result) === true
                    && method_exists($result, 'getObject') === true
                ) {
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
