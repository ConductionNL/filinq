<?php

/**
 * Signing Audit Service
 *
 * Thin adapter that routes signing audit events through OR's native
 * audit trail (hash-chained, natively immutable) per ADR-022.
 * Complies with Archiefwet 1995 minimum 10-year retention.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\OpenRegister\Db\AuditTrail;
use OCA\OpenRegister\Db\AuditTrailMapper;
use OCA\OpenRegister\Db\ObjectEntity;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Service for immutable signing audit trail via OR audit-trail-immutable.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1
 */
class SigningAuditService
{

    /**
     * Valid audit action types.
     *
     * @var array<string>
     */
    private const VALID_ACTIONS = [
        'CREATED',
        'START',
        'SIGNED',
        'DECLINED',
        'CANCELLED',
        'EXPIRED',
        'COMPLETED',
        'VIEWED',
    ];

    /**
     * Constructor.
     *
     * @param AuditTrailMapper $auditTrailMapper OR audit trail mapper.
     * @param LoggerInterface  $logger           Logger.
     * @param SettingsService  $settingsService  Settings service (resolves the real
     *                                           signing-request ObjectEntity via OR's
     *                                           ObjectService, signing-trust-rebuild
     *                                           REQ-DDSTR-006).
     * @param IAppConfig       $config           App config (resolves the signingRequest
     *                                           register/schema).
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1.1
     */
    public function __construct(
        private readonly AuditTrailMapper $auditTrailMapper,
        private readonly LoggerInterface $logger,
        private readonly SettingsService $settingsService,
        private readonly IAppConfig $config
    ) {

    }//end __construct()

    /**
     * Log a signing audit event via OR's native audit trail.
     *
     * Binds the entry to the REAL signing-request object (register `signing`,
     * schema `signingRequest`) so its register/schema/object-id linkage is
     * real and the hash chain anchors to an actual row — closing the #289
     * residual where every entry was created from a uuid-only `ObjectEntity`
     * stub (signing-trust-rebuild REQ-DDSTR-006). Fail-soft: when the request
     * no longer resolves (deleted mid-flight) the entry is still written with
     * the uuid-only fallback and a warning is logged — an unlinked audit entry
     * is acceptable, a dropped one is not.
     *
     * @param string               $signingRequestId The signing request UUID.
     * @param string               $action           The action type (must be in VALID_ACTIONS).
     * @param string               $actorUserId      The actor user ID.
     * @param string               $actorDisplayName The actor display name.
     * @param string               $ipAddress        The actor IP address.
     * @param string               $signatureLevel   The signature level.
     * @param string               $provider         The signing provider.
     * @param array<string, mixed> $metadata         Additional metadata (pass-through).
     *
     * @return array<string, mixed> The created audit entry serialised.
     *
     * @throws RuntimeException If the action is not valid.
     *
     * @spec openspec/specs/signing-audit-via-or/spec.md
     */
    public function logEvent(
        string $signingRequestId,
        string $action,
        string $actorUserId,
        string $actorDisplayName,
        string $ipAddress,
        string $signatureLevel='',
        string $provider='',
        array $metadata=[]
    ): array {
        if (in_array($action, self::VALID_ACTIONS, true) === false) {
            throw new RuntimeException('Invalid audit action: '.$action);
        }

        $actionType = 'docudesk.signing.'.$action;

        // Context is persisted in the `changed` JSON column on openregister_audit_trails.
        $context = [
            'signRequestId'    => $signingRequestId,
            'actorUserId'      => $actorUserId,
            'actorDisplayName' => $actorDisplayName,
            'ipAddress'        => $ipAddress,
            'signatureLevel'   => $signatureLevel,
            'provider'         => $provider,
            'extra'            => $metadata,
        ];

        $entry = $this->auditTrailMapper->createAuditTrailEntry(
            object:  $this->resolveSigningRequestObject(signingRequestId: $signingRequestId),
            action:  $actionType,
            context: $context
        );

        return $entry->jsonSerialize();

    }//end logEvent()

    /**
     * Resolve the real signing-request ObjectEntity for audit binding.
     *
     * Uses OR's ObjectService so the returned entity carries a real
     * register/schema/object-id triple (`AuditTrailMapper::createAuditTrailEntry()`
     * reads `getId()`/`getRegister()`/`getSchema()`/`getUuid()` off it). Falls
     * back to a uuid-only stub — WITH a logged warning — when the request has
     * vanished mid-flight, so an audit entry is still written (fail-soft: an
     * unlinked entry is acceptable, a dropped one is not).
     *
     * @param string $signingRequestId The signing request UUID.
     *
     * @return ObjectEntity The resolved entity, or a uuid-only fallback stub.
     *
     * @spec openspec/specs/signing-audit-via-or/spec.md
     */
    private function resolveSigningRequestObject(string $signingRequestId): ObjectEntity
    {
        try {
            $objectService = $this->settingsService->getObjectService();
            if ($objectService === null) {
                throw new RuntimeException('OpenRegister is not available');
            }

            $register = $this->config->getValueString('docudesk', 'signingRequest_register', '');
            $schema   = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

            $object = $objectService->find(id: $signingRequestId, register: $register, schema: $schema);
            if ($object instanceof ObjectEntity) {
                return $object;
            }

            throw new RuntimeException('Signing request did not resolve to an ObjectEntity');
        } catch (Throwable $e) {
            $this->logger->warning(
                'DocuDesk: signing audit entry for '.$signingRequestId.' could not resolve the real '
                .'signing-request object; falling back to a uuid-only stub (unlinked but not dropped): '
                .$e->getMessage()
            );

            $objectStub = new ObjectEntity();
            $objectStub->setUuid($signingRequestId);

            return $objectStub;
        }//end try

    }//end resolveSigningRequestObject()

    /**
     * Get all audit entries for a signing request from OR's audit trail.
     *
     * Queries OR's audit trail scoped to the request's object identity
     * (`object_uuid` filter, pushed into `AuditTrailMapper::findAll()`)
     * instead of fetching every `docudesk.signing.*` entry fleet-wide and
     * filtering in PHP — closing the #289 residual (signing-trust-rebuild
     * REQ-DDSTR-007). The action-type filter is retained alongside it so a
     * non-signing entry that happened to share an objectUuid (should not
     * occur, but costs nothing to exclude) can never leak in.
     *
     * @param string $signingRequestId The signing request UUID.
     *
     * @return array<int, array<string, mixed>> Audit entries in chronological order.
     *
     * @spec openspec/specs/signing-audit-via-or/spec.md
     */
    public function getAuditTrail(string $signingRequestId): array
    {
        $actionFilter = implode(
            ',',
            array_map(
                fn(string $a): string => 'docudesk.signing.'.$a,
                self::VALID_ACTIONS
            )
        );

        $entries = $this->auditTrailMapper->findAll(
            filters: [
                'object_uuid' => $signingRequestId,
                'action'      => $actionFilter,
            ]
        );

        usort(
            $entries,
            function (AuditTrail $a, AuditTrail $b): int {
                $aTs = 0;
                if ($a->getCreated() !== null) {
                    $aTs = $a->getCreated()->getTimestamp();
                }

                $bTs = 0;
                if ($b->getCreated() !== null) {
                    $bTs = $b->getCreated()->getTimestamp();
                }

                return $aTs <=> $bTs;
            }
        );

        return array_values(
            array_map(
                fn(AuditTrail $e): array => $e->jsonSerialize(),
                $entries
            )
        );

    }//end getAuditTrail()
}//end class
