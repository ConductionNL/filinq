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
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use InvalidArgumentException;
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
     */
    public function updateConsentStatus(
        string $consentId,
        string $register,
        string $schema,
        array $data
    ): array {
        try {
            $objectService = $this->getObjectService();

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

            $existing = $object->getObject();

            $this->guardPolicyPreemptedTransition(existing: $existing, data: $data);

            $consentData = array_merge($existing, $data);

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
            throw new Exception(
                'Failed to update consent status: '.$e->getMessage(),
                0,
                $e
            );
        }//end try

    }//end updateConsentStatus()


    /**
     * Reject `consentStatus` changes on records pre-empted by a policy.
     *
     * When an existing consent record has a non-null `policyMatch`, its
     * `consentStatus` is bound to the matched rule (prohibition → anonymized,
     * standing consent → consent_given). Only updates that do NOT change
     * `consentStatus` are permitted — including overrides like setting
     * `publicationDecision: "anonymize"` on a standing-consent-matched record
     * while leaving `consentStatus: "consent_given"` in place.
     *
     * @param array<string, mixed> $existing The record's current data.
     * @param array<string, mixed> $data     The proposed update.
     *
     * @return void
     *
     * @throws InvalidArgumentException When the update would change consentStatus on a policy-pre-empted record.
     */
    private function guardPolicyPreemptedTransition(array $existing, array $data): void
    {
        $existingMatch = ($existing['policyMatch'] ?? null);
        if ($existingMatch === null || $existingMatch === '') {
            return;
        }

        // Guard the two operator-controlled transition fields. The
        // prohibition lock applies to BOTH `consentStatus` AND
        // `publicationDecision` — a record that's been pre-empted by a
        // policy match must not be coaxed into "publish" via either
        // field. Without this both-fields check, a PATCH carrying only
        // `publicationDecision: "publish"` would bypass the lock.
        $consentStatusChanged       = (
            array_key_exists('consentStatus', $data) === true
            && (string) $data['consentStatus'] !== (string) ($existing['consentStatus'] ?? '')
        );
        $publicationDecisionChanged = (
            array_key_exists('publicationDecision', $data) === true
            && (string) $data['publicationDecision'] !== (string) ($existing['publicationDecision'] ?? '')
        );

        if ($consentStatusChanged === false && $publicationDecisionChanged === false) {
            return;
        }

        if ($consentStatusChanged === true) {
            $rejectedField = 'consentStatus';
        } else {
            $rejectedField = 'publicationDecision';
        }

        $rejectedValue = (string) $data[$rejectedField];
        $currentValue  = (string) ($existing[$rejectedField] ?? '');

        throw new InvalidArgumentException(
            message: sprintf(
                '%s "%s" rejected on policy-pre-empted record (policyMatch=%s, current=%s).',
                $rejectedField,
                $rejectedValue,
                (string) $existingMatch,
                $currentValue
            )
        );

    }//end guardPolicyPreemptedTransition()


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
    public function getConsentsByDocument(
        string $documentId,
        string $register,
        string $schema
    ): array {
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
