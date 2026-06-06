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
use Psr\Log\LoggerInterface;
use RuntimeException;

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
     *
     * @return void
     *
     * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-1.1
     */
    public function __construct(
        private readonly AuditTrailMapper $auditTrailMapper,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Log a signing audit event via OR's native audit trail.
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
     * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-2.1
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

        // Create a minimal ObjectEntity so the mapper can populate objectUuid on the entry.
        // Full OR object resolution (integer ID, register, schema) happens in the broader
        // migrate-signing-to-or-approval-workflow change where ObjectEntity is available.
        $objectStub = new ObjectEntity();
        $objectStub->setUuid($signingRequestId);

        $entry = $this->auditTrailMapper->createAuditTrailEntry(
            object:  $objectStub,
            action:  $actionType,
            context: $context
        );

        return $entry->jsonSerialize();

    }//end logEvent()

    /**
     * Get all audit entries for a signing request from OR's audit trail.
     *
     * Queries OR's audit trail for all docudesk.signing.* actions and filters
     * by objectUuid in PHP. AuditTrailMapper::findAll() does not expose a
     * direct objectUuid filter; action-scoped pre-filtering keeps the result
     * set bounded to signing events only.
     *
     * @param string $signingRequestId The signing request UUID.
     *
     * @return array<int, array<string, mixed>> Audit entries in chronological order.
     *
     * @spec openspec/changes/migrate-signing-audit-to-or-audit/tasks.md#D-3.1
     */
    public function getAuditTrail(string $signingRequestId): array
    {
        // Pre-filter by all docudesk.signing.* action types to bound the result set.
        $actionFilter = implode(
            ',',
            array_map(
                fn(string $a): string => 'docudesk.signing.'.$a,
                self::VALID_ACTIONS
            )
        );

        $allSigningEntries = $this->auditTrailMapper->findAll(
            filters: ['action' => $actionFilter]
        );

        // Filter by objectUuid because findAll() does not support objectUuid directly.
        $entries = array_filter(
            $allSigningEntries,
            fn(AuditTrail $e): bool => $e->getObjectUuid() === $signingRequestId
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
