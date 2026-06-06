<?php

/**
 * Signing Expiration Background Job
 *
 * Periodically checks all pending/in-progress signing requests for expired
 * deadlines and marks them as EXPIRED. Complies with SIGN-004 requirement
 * (deadline enforcement) from openspec/changes/document-signing/specs/document-signing/spec.md.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/document-signing/tasks.md#task-1
 */

declare(strict_types=1);

namespace OCA\DocuDesk\BackgroundJob;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\DocuDesk\Service\SettingsService;
use OCA\DocuDesk\Service\SigningAuditService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/**
 * TimedJob to enforce signing request deadlines
 *
 * Runs hourly. For each PENDING or IN_PROGRESS signing request whose
 * deadline has passed, sets status to EXPIRED, unlocks the document,
 * and logs an audit entry.
 *
 * @category BackgroundJob
 * @package  OCA\DocuDesk\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/document-signing/tasks.md#task-1
 */
class SigningExpirationJob extends TimedJob
{
    /**
     * Constructor
     *
     * @param ITimeFactory        $time            Time factory
     * @param SettingsService     $settingsService Settings service
     * @param SigningAuditService $auditService    Audit service
     * @param IAppConfig          $config          App config
     * @param LoggerInterface     $logger          Logger
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly SettingsService $settingsService,
        private readonly SigningAuditService $auditService,
        private readonly IAppConfig $config,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(time: $time);
        // Run every hour to enforce deadline expiry.
        $this->setInterval(seconds: 3600);

    }//end __construct()

    /**
     * Execute the expiration check
     *
     * Fetches all active signing requests and marks those past their deadline
     * as EXPIRED.
     *
     * @param mixed $argument Job arguments (unused)
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @spec openspec/changes/document-signing/tasks.md#task-1
     */
    protected function run(mixed $argument): void
    {
        $this->logger->info('[SigningExpirationJob] Starting deadline enforcement check');

        $objectService = $this->settingsService->getObjectService();
        if ($objectService === null) {
            $this->logger->warning('[SigningExpirationJob] OpenRegister not available, skipping');
            return;
        }

        $register = $this->config->getValueString('docudesk', 'signingRequest_register', '');
        $schema   = $this->config->getValueString('docudesk', 'signingRequest_schema', '');

        if ($register === '' || $schema === '') {
            $this->logger->info('[SigningExpirationJob] Signing register/schema not configured, skipping');
            return;
        }

        $now     = new DateTimeImmutable();
        $expired = 0;

        foreach (['PENDING', 'IN_PROGRESS'] as $status) {
            try {
                $results = $objectService->findAll(
                    [
                        'filters' => [
                            'register' => $register,
                            'schema'   => $schema,
                            'status'   => $status,
                        ],
                    ]
                );

                foreach ($results as $result) {
                    if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
                        $request = $result->jsonSerialize();
                    } else {
                        $request = (array) $result;
                    }

                    $this->maybeExpire(
                        request: $request,
                        now: $now,
                        objectService: $objectService,
                        register: $register,
                        schema: $schema,
                        expired: $expired
                    );
                }//end foreach
            } catch (Exception $e) {
                $this->logger->error(
                    '[SigningExpirationJob] Failed to process status '.$status.': '.$e->getMessage(),
                    ['exception' => $e]
                );
            }//end try
        }//end foreach

        $this->logger->info('[SigningExpirationJob] Done. Expired '.$expired.' request(s).');

    }//end run()

    /**
     * Expire a single request if its deadline has passed
     *
     * @param array<string, mixed> $request       The signing request data
     * @param DateTimeImmutable    $now           The current time
     * @param object               $objectService The OR object service
     * @param string               $register      The signing register
     * @param string               $schema        The signingRequest schema
     * @param int                  $expired       Counter incremented on each expiry (by-ref)
     *
     * @return void
     */
    private function maybeExpire(
        array $request,
        DateTimeImmutable $now,
        object $objectService,
        string $register,
        string $schema,
        int &$expired
    ): void {
        $deadline = $request['deadline'] ?? '';
        if ($deadline === '') {
            return;
        }

        $deadlineDate = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $deadline);
        if ($deadlineDate === false) {
            // Non-ISO-8601 deadline — try a lenient parse.
            $deadlineDate = new DateTimeImmutable($deadline);
        }

        if ($deadlineDate > $now) {
            return;
        }

        $requestId = (string) ($request['id'] ?? $request['uuid'] ?? '');
        if ($requestId === '') {
            return;
        }

        $request['status'] = 'EXPIRED';
        $objectService->saveObject(object: $request, register: $register, schema: $schema);

        try {
            $this->auditService->logEvent(
                signingRequestId: $requestId,
                action: 'EXPIRED',
                actorUserId: 'system',
                actorDisplayName: 'System',
                ipAddress: '127.0.0.1',
                signatureLevel: (string) ($request['signatureLevel'] ?? ''),
                provider: (string) ($request['provider'] ?? ''),
                metadata: ['deadline' => $deadline, 'expiredAt' => $now->format(DateTimeInterface::ATOM)]
            );
        } catch (Exception $e) {
            $this->logger->error(
                '[SigningExpirationJob] Failed to log audit event for '.$requestId.': '.$e->getMessage()
            );
        }

        $expired++;
        $this->logger->info('[SigningExpirationJob] Expired signing request '.$requestId);

    }//end maybeExpire()
}//end class
