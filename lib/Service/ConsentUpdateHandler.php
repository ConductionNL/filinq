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

            // Server-controlled fields are immutable on EVERY record —
            // not just policy-pre-empted ones. The check has to run
            // ahead of the consent-status lock, otherwise records with
            // `policyMatch: null` (default WOO-objection state) can be
            // mutated into a fake standing-consent or fake prohibition
            // via a single PATCH carrying `matchKind` + `policyMatch`.
            // PR #147 sixth-pass review for the full exploit shape.
            $this->guardServerControlledFields(existing: $existing, data: $data);

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
     * Reject mutations to server-controlled consent fields on update.
     *
     * `matchKind` and `policyMatch` are computed at consent-create time
     * by `ConsentService::buildConsentData` (or by the retroactive sweep
     * in `PolicyRetroactiveService::forceResolveToAnonymized`) and are
     * immutable thereafter. This guard runs ahead of
     * `guardPolicyPreemptedTransition` because the immutability rule
     * applies to EVERY record — including records with `policyMatch:
     * null` (default WOO-objection state). Co-locating the check
     * inside `guardPolicyPreemptedTransition`, behind its early-return
     * for unmatched records, leaves a bypass shape where a single PATCH
     * carrying `{matchKind: "standing_consent", policyMatch: "<uuid>",
     * consentStatus: "consent_given", publicationDecision: "publish"}`
     * fabricates a fake standing-consent on a vanilla record and
     * defeats the WOO objection deadline. PR #147 sixth-pass review
     * walks the exploit; tests in `ConsentUpdateHandlerTest` lock the
     * regression shut on both the matched-record and unmatched-record
     * branches.
     *
     * @param array<string, mixed> $existing The record's current data.
     * @param array<string, mixed> $data     The proposed update.
     *
     * @return void
     *
     * @throws InvalidArgumentException When `matchKind` or `policyMatch` in the proposed update
     *                                   carries a value different from the persisted one.
     */
    private function guardServerControlledFields(array $existing, array $data): void
    {
        foreach (['matchKind', 'policyMatch'] as $serverOnlyField) {
            if (array_key_exists($serverOnlyField, $data) === false) {
                continue;
            }

            $newValue      = $data[$serverOnlyField];
            $existingValue = ($existing[$serverOnlyField] ?? null);
            if ($newValue !== $existingValue) {
                throw new InvalidArgumentException(
                    message: sprintf(
                        '%s is server-controlled and cannot be modified via update (existing=%s, attempted=%s).',
                        $serverOnlyField,
                        (string) ($existingValue ?? ''),
                        (string) ($newValue ?? '')
                    )
                );
            }
        }

    }//end guardServerControlledFields()

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
     * Server-controlled-field immutability is enforced separately by
     * `guardServerControlledFields`, which runs ahead of this guard
     * because the immutability rule applies to records WITHOUT a
     * policyMatch too.
     *
     * @param array<string, mixed> $existing The record's current data.
     * @param array<string, mixed> $data     The proposed update.
     *
     * @return void
     *
     * @throws InvalidArgumentException When the update would change consentStatus / publicationDecision
     *                                   on a policy-pre-empted record (standing-consent carve-out aside).
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

        // Standing-consent carve-out (spec §6.2): a record matched by a
        // standing publication consent MAY be manually overridden via
        // `publicationDecision` (e.g. operator flips the toggle on
        // ConsentDetail to anonymise an entity that a standing consent
        // would otherwise publish). The override is audit-logged
        // separately by the consent register's mutation trail.
        // Prohibition matches stay strictly locked — operators cannot
        // override a prohibition through this endpoint.
        $matchKind = (string) ($existing['matchKind'] ?? '');
        $standingConsentOverride = (
            $matchKind === 'standing_consent'
            && $publicationDecisionChanged === true
            && $consentStatusChanged === false
        );
        if ($standingConsentOverride === true) {
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
