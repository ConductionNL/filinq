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
     * @param PolicyMatchService       $policyMatcher   Detection-time policy matcher
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly ObjectionDeadlineChecker $deadlineChecker,
        private readonly ConsentUpdateHandler $updateHandler,
        private readonly PolicyMatchService $policyMatcher
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
     * The detection-time policy layer is consulted before falling through
     * to the standard WOO workflow. See `PolicyMatchService` for the match
     * semantics. The resulting record is always `scope: "document"` — this
     * entry point is exclusively the per-document workflow surface.
     *
     * @param string               $documentId          The document UUID
     * @param string               $entityType          The entity type (PERSON/ORGANIZATION/OTHER)
     * @param string               $entityText          The detected entity text
     * @param string               $register            The register ID
     * @param string               $schema              The schema ID
     * @param array<string, mixed> $extra               Additional consent data merged on top of defaults
     * @param array<string, mixed> $resolvedIdentifiers Optional structured identifiers (e.g. ['bsn'=>'...', 'kvk'=>'...'])
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
        array $extra=[],
        array $resolvedIdentifiers=[]
    ): array {
        try {
            // Defense-in-depth gate symmetric to the UPDATE-side
            // immutability guard. The HTTP path strips these in
            // `ConsentCrudService::createFromRequest`, but the service
            // method is also a public DI surface — any internal caller
            // who forwards user-influenced data without stripping
            // would otherwise re-open the CREATE-side bypass (PR #147
            // fifth-pass review). Throwing makes the misuse loud at
            // the developer-facing layer; the controller-side strip
            // handles HTTP-input quietly.
            $injected = array_intersect_key($extra, array_flip(ConsentCrudService::SERVER_CONTROLLED_FIELDS));
            if (empty($injected) === false) {
                $msg = sprintf(
                    'Server-controlled consent fields cannot be passed via $extra (rejected: %s).',
                    implode(', ', array_keys($injected))
                );
                throw new InvalidArgumentException(message: $msg);
            }

            $objectService = $this->getObjectService();

            $policyMatch = $this->policyMatcher->match(
                entityText: $entityText,
                entityType: $entityType,
                resolvedIdentifiers: $resolvedIdentifiers
            );

            $consentData = $this->buildConsentData(
                documentId: $documentId,
                entityType: $entityType,
                entityText: $entityText,
                policyMatch: $policyMatch
            );

            $consentData = array_merge($consentData, $extra);

            $this->validatePublicationConsentData(data: $consentData);

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
                    'documentId'  => $documentId,
                    'policyMatch' => ($policyMatch['uuid'] ?? null),
                    'matchKind'   => ($policyMatch['kind'] ?? null),
                ]
            );

            return $savedObject->getObject();
        } catch (InvalidArgumentException $e) {
            // Programmer-error path (injected server-controlled fields,
            // validation failures). Surface as-is so the caller / test
            // assertions see the precise reason; do NOT rewrap as the
            // generic "Failed to create consent request" message, which
            // would obscure the security signal at the DI boundary.
            throw $e;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to create consent request: '.$e->getMessage(),
                ['documentId' => $documentId, 'exception' => $e]
            );
            throw new Exception('Failed to create consent request: '.$e->getMessage(), 0, $e);
        }//end try

    }//end createConsentRequest()


    /**
     * Build the base consent data array.
     *
     * Outcome is determined by `$policyMatch`:
     *   - null               → WOO workflow defaults (pending across the board, deadline set)
     *   - kind=prohibition   → short-circuit to `anonymized` with `notificationStatus: skipped`
     *   - kind=standing_consent → short-circuit to `consent_given` + `publish_with_consent`
     *
     * @param string                    $documentId  The document UUID
     * @param string                    $entityType  The entity type
     * @param string                    $entityText  The entity text
     * @param array<string, mixed>|null $policyMatch Result from PolicyMatchService::match (or null)
     *
     * @return array<string, mixed> The consent data
     */
    private function buildConsentData(
        string $documentId,
        string $entityType,
        string $entityText,
        ?array $policyMatch
    ): array {
        $base = [
            'documentId' => $documentId,
            'entityType' => $entityType,
            'entityText' => $entityText,
            'scope'      => 'document',
        ];

        if ($policyMatch === null) {
            $deadline = $this->deadlineChecker->calculateDeadline();
            return array_merge(
                $base,
                [
                    'notificationStatus'  => 'pending',
                    'consentStatus'       => 'pending',
                    'publicationDecision' => 'pending',
                    'objectionDeadline'   => $deadline->format('c'),
                    'policyMatch'         => null,
                    'matchKind'           => null,
                ]
            );
        }

        $kind = ($policyMatch['kind'] ?? '');
        $uuid = (string) ($policyMatch['uuid'] ?? '');

        if ($kind === PolicyMatchService::KIND_PROHIBITION) {
            return array_merge(
                $base,
                [
                    'notificationStatus'  => 'skipped',
                    'consentStatus'       => 'anonymized',
                    'publicationDecision' => 'anonymize',
                    'objectionDeadline'   => null,
                    'policyMatch'         => $uuid,
                    'matchKind'           => PolicyMatchService::KIND_PROHIBITION,
                ]
            );
        }

        if ($kind === PolicyMatchService::KIND_STANDING_CONSENT) {
            return array_merge(
                $base,
                [
                    'notificationStatus'  => 'skipped',
                    'consentStatus'       => 'consent_given',
                    'publicationDecision' => 'publish_with_consent',
                    'objectionDeadline'   => null,
                    'policyMatch'         => $uuid,
                    'matchKind'           => PolicyMatchService::KIND_STANDING_CONSENT,
                ]
            );
        }

        // Unknown match kind — fall back to WOO defaults defensively.
        $this->logger->warning(
            'ConsentService: unknown policyMatch kind, falling back to WOO defaults',
            ['kind' => $kind]
        );
        $deadline = $this->deadlineChecker->calculateDeadline();
        return array_merge(
            $base,
            [
                'notificationStatus'  => 'pending',
                'consentStatus'       => 'pending',
                'publicationDecision' => 'pending',
                'objectionDeadline'   => $deadline->format('c'),
                'policyMatch'         => null,
                'matchKind'           => null,
            ]
        );

    }//end buildConsentData()


    /**
     * Validate a `publicationConsent` payload at write time per spec REQ task 4.3.
     *
     * Rejects:
     *   - `scope: "document"` without `documentId`
     *   - `scope: "entity"` with a `documentId`
     *   - `scope: "entity"` missing `matchRules` or `consentMethod`
     *   - `policyMatch` set on a `scope: "entity"` record
     *   - `policyMatch` pointing at a `publicationConsent` referent whose own scope != "entity"
     *
     * Exposed publicly so admin endpoints that write `scope: "entity"` records
     * directly can call the same gate. The createConsentRequest path also calls it.
     *
     * @param array<string, mixed> $data The proposed publicationConsent data.
     *
     * @return void
     *
     * @throws InvalidArgumentException On any of the above conditions.
     */
    public function validatePublicationConsentData(array $data): void
    {
        $scope         = (string) ($data['scope'] ?? 'document');
        $documentId    = (string) ($data['documentId'] ?? '');
        $hasDocumentId = ($documentId !== '');

        if ($scope === 'document' && $hasDocumentId === false) {
            throw new InvalidArgumentException(
                'publicationConsent scope=document requires a documentId.'
            );
        }

        if ($scope === 'entity' && $hasDocumentId === true) {
            throw new InvalidArgumentException(
                'publicationConsent scope=entity must not include a documentId.'
            );
        }

        if ($scope === 'entity') {
            $matchRules    = ($data['matchRules'] ?? null);
            $consentMethod = (string) ($data['consentMethod'] ?? '');
            $hasMatchRules = is_array($matchRules) === true && count($matchRules) > 0;

            if ($hasMatchRules === false) {
                throw new InvalidArgumentException(
                    'publicationConsent scope=entity requires at least one matchRule.'
                );
            }

            if ($consentMethod === '') {
                throw new InvalidArgumentException(
                    'publicationConsent scope=entity requires consentMethod.'
                );
            }

            if (array_key_exists('policyMatch', $data) === true
                && $data['policyMatch'] !== null
                && $data['policyMatch'] !== ''
            ) {
                throw new InvalidArgumentException(
                    'publicationConsent scope=entity must not set policyMatch.'
                );
            }
        }//end if

        $policyMatch = ($data['policyMatch'] ?? null);
        if ($scope === 'document'
            && is_string($policyMatch) === true
            && $policyMatch !== ''
        ) {
            $this->assertPolicyMatchReferentValid(uuid: $policyMatch);
        }

    }//end validatePublicationConsentData()


    /**
     * Verify a policyMatch UUID points at a permitted referent.
     *
     * Permitted: a `publicationProhibition` record, or a `publicationConsent`
     * record with `scope: "entity"`. Rejects: a `publicationConsent` with
     * `scope: "document"` (or missing scope). Dangling UUIDs are not blocked
     * here — the spec leaves that to OpenRegister's referential-integrity
     * surface — but they are logged.
     *
     * @param string $uuid The candidate UUID.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the referent's scope is not entity.
     */
    private function assertPolicyMatchReferentValid(string $uuid): void
    {
        try {
            $objectService = $this->getObjectService();

            $prohibitionHits = $objectService->findAll(
                config: [
                    'filters' => [
                        'register' => 'consent',
                        'schema'   => 'publicationProhibition',
                        'uuid'     => $uuid,
                    ],
                    'limit'   => 1,
                ],
                _rbac: false
            );
            if ($this->resultHasAny(result: $prohibitionHits) === true) {
                return;
            }

            $consentHits = $objectService->findAll(
                config: [
                    'filters' => [
                        'register' => 'consent',
                        'schema'   => 'publicationConsent',
                        'uuid'     => $uuid,
                    ],
                    'limit'   => 1,
                ],
                _rbac: false
            );

            $consentObject = $this->firstObject(result: $consentHits);
            if ($consentObject === null) {
                $msg = 'policyMatch UUID "%s" does not resolve to a known prohibition or entity-scope publicationConsent record.';
                throw new InvalidArgumentException(message: sprintf($msg, $uuid));
            }

            $referentScope = (string) ($consentObject['scope'] ?? 'document');
            if ($referentScope !== 'entity') {
                throw new InvalidArgumentException(
                    message: sprintf(
                        'policyMatch points at a publicationConsent with scope=%s; only entity-scope records are permitted.',
                        $referentScope
                    )
                );
            }
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Exception $e) {
            // Treat lookup failure as a hard error rather than a silent
            // pass — a write referencing a `policyMatch` we cannot
            // validate must not be persisted, even if the underlying
            // ObjectService threw an infrastructure error. Surfacing
            // the failure (mapped to HTTP 5xx by the controller) is
            // strictly safer than masking it with a warning log.
            $this->logger->error(
                'ConsentService: policyMatch referent lookup failed — rejecting write',
                ['policyMatch' => $uuid, 'error' => $e->getMessage()]
            );
            throw new InvalidArgumentException(
                message: sprintf(
                    'policyMatch UUID "%s" could not be validated against the policy registry: %s',
                    $uuid,
                    $e->getMessage()
                ),
                previous: $e
            );
        }//end try

    }//end assertPolicyMatchReferentValid()


    /**
     * Return true when an ObjectService findAll result is non-empty.
     *
     * @param mixed $result The findAll return value.
     *
     * @return bool
     */
    private function resultHasAny($result): bool
    {
        return $this->firstObject(result: $result) !== null;

    }//end resultHasAny()


    /**
     * Coerce the first hit of an ObjectService findAll result into a plain array.
     *
     * @param mixed $result The findAll return value.
     *
     * @return array<string, mixed>|null
     */
    private function firstObject($result): ?array
    {
        $candidates = [];
        if (is_array($result) === true) {
            $hasResultsKey = (isset($result['results']) === true && is_array($result['results']) === true);
            if ($hasResultsKey === true) {
                $candidates = $result['results'];
            } else {
                $candidates = $result;
            }
        } else if (is_iterable($result) === true) {
            foreach ($result as $candidate) {
                $candidates[] = $candidate;
                break;
            }
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate) === true) {
                return $candidate;
            }

            if (is_object($candidate) === true && method_exists($candidate, 'getObject') === true) {
                $payload = $candidate->getObject();
                if (is_array($payload) === true) {
                    if (isset($payload['@self']) === false) {
                        $self = null;
                        if (method_exists($candidate, 'getUuid') === true) {
                            $self = $candidate->getUuid();
                        }

                        if ($self !== null) {
                            $payload['@self'] = ['id' => $self];
                        }
                    }

                    return $payload;
                }
            }
        }//end foreach

        return null;

    }//end firstObject()


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
        return $this->updateHandler->getConsentsByDocument($documentId, $register, $schema);

    }//end getConsentsByDocument()


}//end class
