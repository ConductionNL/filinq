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
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-5
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-9
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use RuntimeException;
use OCP\App\IAppManager;
use OCP\IUser;
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
     * @param LoggerInterface          $logger          Logger for error reporting
     * @param ContainerInterface       $container       Container for DI
     * @param IAppManager              $appManager      App manager interface
     * @param ObjectionDeadlineChecker $deadlineChecker Deadline checker
     * @param ConsentUpdateHandler     $updateHandler   Update and query handler
     * @param ConsentScopeValidator    $scopeValidator  Scope and transition validator
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ObjectionDeadlineChecker $deadlineChecker,
        private readonly ConsentUpdateHandler $updateHandler,
        private readonly ConsentScopeValidator $scopeValidator
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
     * Checks for a matching standing-consent (scope:entity) record first.
     * If one is found the record is auto-resolved with consentStatus=consent_given
     * and policyMatch set to the matching entity-scope record UUID.
     * If no match is found, the standard WOO objection-period workflow is used.
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
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
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

            // Check for a matching standing-consent (scope:entity) record.
            $standingConsent = $this->findMatchingStandingConsent(
                entityType: $entityType,
                entityText: $entityText,
                register: $register,
                schema: $schema
            );

            if ($standingConsent !== null) {
                // Auto-resolve: policy match found, skip objection period.
                $policyUuid  = $standingConsent['uuid'] ?? ($standingConsent['id'] ?? '');
                $consentData = array_merge(
                    [
                        'documentId'          => $documentId,
                        'entityType'          => $entityType,
                        'entityText'          => $entityText,
                        'notificationStatus'  => 'skipped',
                        'consentStatus'       => 'consent_given',
                        'publicationDecision' => 'publish_with_consent',
                        'objectionDeadline'   => null,
                        'scope'               => 'document',
                        'policyMatch'         => $policyUuid,
                    ],
                    $extra
                );
            } else {
                $deadline    = $this->deadlineChecker->calculateDeadline();
                $consentData = array_merge(
                    $this->buildConsentData(
                        documentId: $documentId,
                        entityType: $entityType,
                        entityText: $entityText,
                        deadline: $deadline
                    ),
                    $extra
                );
            }//end if

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
     * Create a standing-consent (scope:entity) record
     *
     * @param array<string, mixed> $data     The entity consent record data
     * @param string               $register The register ID
     * @param string               $schema   The schema ID
     * @param IUser                $user     The authenticated user
     *
     * @return array<string, mixed> The created entity consent record
     *
     * @throws \OCP\AppFramework\OCS\OCSForbiddenException When user lacks admin group membership
     * @throws \InvalidArgumentException When scope validation fails
     * @throws Exception When the save operation fails
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-9
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
     */
    public function createEntityConsent(array $data, string $register, string $schema, IUser $user): array
    {
        $this->scopeValidator->requireStandingConsentAdminGroup(user: $user);
        $this->scopeValidator->validateWrite(data: $data);

        $objectService = $this->getObjectService();
        $savedObject   = $objectService->saveObject(
            object: $data,
            register: $register,
            schema: $schema
        );

        return $savedObject->getObject();

    }//end createEntityConsent()

    /**
     * Validate and update a consent record, enforcing policy-transition rules
     *
     * @param string               $consentId The consent object UUID
     * @param string               $register  The register ID
     * @param string               $schema    The schema ID
     * @param array<string, mixed> $data      The update payload
     *
     * @return array<string, mixed> The updated consent record
     *
     * @throws \InvalidArgumentException When the transition is blocked by policy-match
     * @throws Exception When the update fails
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
     */
    public function validateAndUpdateConsent(
        string $consentId,
        string $register,
        string $schema,
        array $data
    ): array {
        $objectService = $this->getObjectService();

        $object = $objectService->find(
            id: $consentId,
            register: $register,
            schema: $schema
        );

        if ($object === null) {
            throw new Exception('Consent record not found: '.$consentId);
        }

        $existing = $object->getObject();

        $this->scopeValidator->validateTransition(existing: $existing, update: $data);

        return $this->updateConsentStatus(
            consentId: $consentId,
            register: $register,
            schema: $schema,
            data: $data
        );

    }//end validateAndUpdateConsent()

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
     * Find a matching standing-consent (scope:entity) record for the given entity
     *
     * @param string $entityType The entity type to match
     * @param string $entityText The entity text to match against match rules
     * @param string $register   The register ID
     * @param string $schema     The schema ID
     *
     * @return array<string, mixed>|null The first matching standing consent, or null
     *
     * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
     */
    private function findMatchingStandingConsent(
        string $entityType,
        string $entityText,
        string $register,
        string $schema
    ): ?array {
        try {
            $objectService = $this->getObjectService();

            $results = $objectService->searchObjects(
                [
                    '@self'      => ['register' => $register, 'schema' => $schema],
                    'scope'      => 'entity',
                    'active'     => true,
                    'entityType' => $entityType,
                ]
            );

            $normalizedText = mb_strtolower(trim($entityText));

            foreach ($results as $result) {
                $record = (array) $result;
                if (is_object($result) === true
                    && method_exists($result, 'getObject') === true
                ) {
                    $record = $result->getObject();
                }

                $matchRules = $record['matchRules'] ?? [];
                if (is_array($matchRules) === false) {
                    $matchRules = [];
                }

                foreach ($matchRules as $rule) {
                    if (is_array($rule) === true) {
                        $ruleText = $rule['value'] ?? '';
                    } else {
                        $ruleText = (string) $rule;
                    }

                    if (mb_strtolower(trim($ruleText)) === $normalizedText) {
                        return $record;
                    }
                }//end foreach
            }//end foreach

            return null;
        } catch (Exception $e) {
            $this->logger->warning(
                'Standing consent lookup failed, falling back to WOO workflow: '.$e->getMessage(),
                ['entityType' => $entityType, 'exception' => $e]
            );
            return null;
        }//end try

    }//end findMatchingStandingConsent()

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
}//end class
