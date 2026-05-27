<?php

/**
 * Signing Audit Service
 *
 * Creates and retrieves immutable audit trail entries for signing events.
 * Complies with Archiefwet 1995 minimum 10-year retention.
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

use DateTimeImmutable;
use DateTimeInterface;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for immutable signing audit trail
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class SigningAuditService
{

    /**
     * Valid audit action types
     *
     * @var array<string>
     */
    private const VALID_ACTIONS = [
        'CREATED',
        'SIGNED',
        'DECLINED',
        'CANCELLED',
        'EXPIRED',
        'COMPLETED',
        'VIEWED',
    ];

    /**
     * Constructor
     *
     * @param SettingsService $settingsService Settings service
     * @param IAppConfig      $config          App config
     * @param LoggerInterface $logger          Logger
     *
     * @return void
     */
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()

    /**
     * Log a signing audit event
     *
     * @param string               $signingRequestId The signing request ID
     * @param string               $action           The action type
     * @param string               $actorUserId      The actor user ID
     * @param string               $actorDisplayName The actor display name
     * @param string               $ipAddress        The actor IP address
     * @param string               $signatureLevel   The signature level
     * @param string               $provider         The signing provider
     * @param array<string, mixed> $metadata         Additional metadata
     *
     * @return array<string, mixed> The created audit entry
     *
     * @throws RuntimeException If logging fails
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#4-2
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

        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingAuditEntry_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingAuditEntry_schema', '');

        $entry = [
            'signingRequestId' => $signingRequestId,
            'action'           => $action,
            'actorUserId'      => $actorUserId,
            'actorDisplayName' => $actorDisplayName,
            'timestamp'        => (new DateTimeImmutable())->format(DateTimeInterface::ATOM),
            'ipAddress'        => $ipAddress,
            'signatureLevel'   => $signatureLevel,
            'provider'         => $provider,
            'metadata'         => $metadata,
        ];

        return $objectService->saveObject($register, $schema, $entry);

    }//end logEvent()

    /**
     * Get all audit entries for a signing request
     *
     * @param string $signingRequestId The signing request ID
     *
     * @return array<int, array<string, mixed>> The audit entries
     *
     * @spec openspec/changes/digital-signing-integration/tasks.md#4-1
     */
    public function getAuditTrail(string $signingRequestId): array
    {
        $objectService = $this->settingsService->getObjectService();
        $register      = $this->config->getValueString('docudesk', 'signingAuditEntry_register', '');
        $schema        = $this->config->getValueString('docudesk', 'signingAuditEntry_schema', '');

        $allEntries = $objectService->getObjects($register, $schema);

        $entries = array_filter(
            $allEntries,
            function (array $entry) use ($signingRequestId): bool {
                return ($entry['signingRequestId'] ?? '') === $signingRequestId;
            }
        );

        usort(
            $entries,
            function (array $entryA, array $entryB): int {
                return strcmp($entryA['timestamp'] ?? '', $entryB['timestamp'] ?? '');
            }
        );

        return array_values($entries);

    }//end getAuditTrail()

    // Archiefwet 1995 immutability of audit entries is enforced by the
    // OpenRegister storage layer: the `signingAuditEntry` schema is declared
    // `immutable: true, appendOnly: true` in
    // `lib/Settings/docudesk_register.json`, so any update or delete request
    // against an existing audit entry is rejected at the OR mapper level
    // regardless of which code path tries it. The previously-shipped
    // `rejectUpdate()` / `rejectDelete()` methods on this service were never
    // wired into any mutation path and were misleading dead code (finding
    // #289); they have been removed in favour of the storage-layer guard.
}//end class
