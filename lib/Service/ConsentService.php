<?php
/**
 * Consent Service
 *
 * Service for managing GDPR publication consent tracking.
 * Handles creating consent records for entities detected in documents.
 * Delegates deadline checking to ObjectionDeadlineChecker and
 * update/query operations to ConsentUpdateHandler.
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
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-14
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
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
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-12
 */
class ConsentService
{
    /**
     * Constructor for ConsentService
     *
     * @param LoggerInterface          $logger          Logger for error reporting
     * @param ContainerInterface       $container       Container for DI
     * @param IAppManager              $appManager      App manager interface
     * @param ObjectionDeadlineChecker $deadlineChecker Deadline checker
     * @param ConsentUpdateHandler     $updateHandler   Update and query handler
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ObjectionDeadlineChecker $deadlineChecker,
        private readonly ConsentUpdateHandler $updateHandler
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
     * @param string               $entityType The entity type
     * @param string               $entityText The detected entity text
     * @param string               $register   The register ID
     * @param string               $schema     The schema ID
     * @param array<string, mixed> $extra      Additional consent data
     *
     * @return array<string, mixed> The created consent record
     *
     * @throws Exception If consent creation fails
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
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
                $this->buildConsentData(
                    documentId: $documentId,
                    entityType: $entityType,
                    entityText: $entityText,
                    deadline: $deadline
                ),
                $extra
            );

            // Let OpenRegister enforce RBAC and multitenancy so the consent
            // record is owned by the creating user (security finding #283).
            $savedObject = $objectService->saveObject(
                object: $consentData,
                register: $register,
                schema: $schema
            );

            $this->logger->info('Consent request created', ['documentId' => $documentId]);

            return $savedObject->getObject();
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create consent request: '.$e->getMessage(),
                ['documentId' => $documentId, 'exception' => $e]
            );
            throw new Exception('Failed to create consent request: '.$e->getMessage(), 0, $e);
        }//end try

    }//end createConsentRequest()

    /**
     * Build the base consent data array
     *
     * @param string    $documentId The document UUID
     * @param string    $entityType The entity type
     * @param string    $entityText The entity text
     * @param \DateTime $deadline   The objection deadline
     *
     * @return array<string, string> The consent data
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-13
     */
    private function buildConsentData(
        string $documentId,
        string $entityType,
        string $entityText,
        \DateTime $deadline
    ): array {
        return [
            'documentId'          => $documentId,
            'entityType'          => $entityType,
            'entityText'          => $entityText,
            'notificationStatus'  => 'pending',
            'consentStatus'       => 'pending',
            'publicationDecision' => 'pending',
            'objectionDeadline'   => $deadline->format('c'),
        ];

    }//end buildConsentData()

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
        return $this->updateHandler->updateConsentStatus($consentId, $register, $schema, $data);

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
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-37
     */
    public function checkObjectionDeadline(
        string $consentId,
        string $register,
        string $schema
    ): bool {
        return $this->deadlineChecker->checkObjectionDeadline($consentId, $register, $schema);

    }//end checkObjectionDeadline()

    /**
     * Get all consent records for a specific document
     *
     * @param string      $documentId The document UUID
     * @param string      $register   The register ID
     * @param string      $schema     The schema ID
     * @param string|null $ownerUid   UID to scope results to, or null for all
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
        return $this->updateHandler->getConsentsByDocument($documentId, $register, $schema, $ownerUid);

    }//end getConsentsByDocument()

    /**
     * Validate publication consent data against scope rules.
     *
     * @param array<string, mixed> $data Consent data to validate
     *
     * @return void
     *
     * @throws \InvalidArgumentException When data violates scope constraints
     *
     * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
     */
    public function validatePublicationConsentData(array $data): void
    {
        $scope = ($data['scope'] ?? null);

        if ($scope === 'document') {
            if (empty($data['documentId']) === true) {
                throw new \InvalidArgumentException('scope=document requires a non-empty documentId');
            }

            return;
        }

        if ($scope === 'entity') {
            if (isset($data['documentId']) === true) {
                throw new \InvalidArgumentException('scope=entity must not include documentId');
            }

            if (empty($data['matchRules']) === true) {
                throw new \InvalidArgumentException('scope=entity requires a non-empty matchRules array');
            }

            if (empty($data['consentMethod']) === true) {
                throw new \InvalidArgumentException('scope=entity requires a non-empty consentMethod');
            }

            if (isset($data['policyMatch']) === true) {
                throw new \InvalidArgumentException('scope=entity must not include policyMatch');
            }
        }//end if

    }//end validatePublicationConsentData()
}//end class
