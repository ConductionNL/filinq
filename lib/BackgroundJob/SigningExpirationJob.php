<?php

/**
 * Signing Expiration Background Job
 *
 * Periodically checks all pending/in-progress signing requests for expired
 * deadlines and marks them as EXPIRED. Complies with SIGN-004 requirement
 * (deadline enforcement) from openspec/changes/document-signing/specs/document-signing/spec.md.
 *
 * @category BackgroundJob
 * @package  OCA\Filinq\BackgroundJob
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

namespace OCA\Filinq\BackgroundJob;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use OCA\Filinq\Kernel\Contracts\SettingsServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningAuditServiceInterface;
use OCA\Filinq\Kernel\Contracts\SigningServiceInterface;
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
 * @package  OCA\Filinq\BackgroundJob
 * @author   Conduction Development Team <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://conduction.nl
 *
 * @spec openspec/changes/document-signing/tasks.md#task-1
 */
class SigningExpirationJob extends TimedJob {
	/**
	 * Constructor
	 *
	 * @param ITimeFactory $time Time factory
	 * @param SettingsServiceInterface $settingsService Settings service
	 * @param SigningAuditServiceInterface $auditService Audit service
	 * @param IAppConfig $config App config
	 * @param LoggerInterface $logger Logger
	 * @param SigningServiceInterface $signingService Signing service (emits the cross-app conclusion event)
	 *
	 * @return void
	 */
	public function __construct(
		ITimeFactory $time,
		private readonly SettingsServiceInterface $settingsService,
		private readonly SigningAuditServiceInterface $auditService,
		private readonly IAppConfig $config,
		private readonly LoggerInterface $logger,
		private readonly SigningServiceInterface $signingService,
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
	 * @param mixed $argument Job arguments. The job is registered as a bare
	 *                        TimedJob and takes no arguments, so this is
	 *                        normally null; it is echoed into the start log so
	 *                        an unexpected payload is visible to operators.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/document-signing/tasks.md#task-1
	 */
	protected function run(mixed $argument): void {
		$this->logger->info(
			'[SigningExpirationJob] Starting deadline enforcement check',
			['argument' => $argument]
		);

		$objectService = $this->settingsService->getObjectService();
		if ($objectService === null) {
			$this->logger->warning('[SigningExpirationJob] OpenRegister not available, skipping');
			return;
		}

		$register = $this->config->getValueString('filinq', 'signingRequest_register', '');
		$schema = $this->config->getValueString('filinq', 'signingRequest_schema', '');

		if ($register === '' || $schema === '') {
			$this->logger->info('[SigningExpirationJob] Signing register/schema not configured, skipping');
			return;
		}

		$sweep = ['now' => new DateTimeImmutable(), 'objectService' => $objectService, 'register' => $register, 'schema' => $schema];
		$expired = 0;
		foreach (['PENDING', 'IN_PROGRESS'] as $status) {
			$expired += $this->expireStatus(status: $status, sweep: $sweep);
		}

		$this->logger->info('[SigningExpirationJob] Done. Expired ' . $expired . ' request(s).');

	}//end run()

	/**
	 * Expire the overdue requests of one status.
	 *
	 * @param string               $status The request status to sweep.
	 * @param array<string, mixed> $sweep  The sweep: now, objectService, register, schema.
	 *
	 * @return int How many requests expired, also when the sweep of this status failed part-way.
	 */
	private function expireStatus(string $status, array $sweep): int {
		$expired = 0;
		try {
			$results = $sweep['objectService']->findAll(
				[
					'filters' => [
						'register' => $sweep['register'],
						'schema' => $sweep['schema'],
						'status' => $status,
					],
				],
				_rbac: false
			);

			foreach ($results as $result) {
				if ($this->maybeExpire(request: $this->normaliseResult(result: $result), sweep: $sweep) === true) {
					$expired++;
				}
			}
		} catch (Exception $e) {
			$this->logger->error(
				'[SigningExpirationJob] Failed to process status ' . $status . ': ' . $e->getMessage(),
				['exception' => $e]
			);
		}//end try

		return $expired;
	}//end expireStatus()

	/**
	 * Normalise one findAll() result row into a plain associative array
	 *
	 * OpenRegister returns ObjectEntity instances, but a raw array is also
	 * accepted so the job keeps working against simplified test doubles.
	 *
	 * @param mixed $result A single row returned by ObjectService::findAll()
	 *
	 * @return array<string, mixed> The request data as a plain array
	 */
	private function normaliseResult(mixed $result): array {
		if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
			return $result->jsonSerialize();
		}

		return (array)$result;
	}//end normaliseResult()

	/**
	 * Expire a single request if its deadline has passed
	 *
	 * @param array<string, mixed> $request The signing request data
	 * @param array<string, mixed> $sweep   The sweep: now, objectService, register, schema.
	 *
	 * @return bool True when the request expired.
	 */
	private function maybeExpire(array $request, array $sweep): bool {
		$deadline = $request['deadline'] ?? '';
		if ($deadline === '') {
			return false;
		}

		// Deadlines are persisted as ISO-8601/ATOM strings, which the
		// DateTimeImmutable constructor parses natively; it also accepts the
		// looser formats older records may carry, so it covers both the strict
		// and the lenient path in one step. An unparseable value throws, and
		// the caller's try/catch turns that into a logged, non-fatal skip.
		if (new DateTimeImmutable($deadline) > $sweep['now']) {
			return false;
		}

		$requestId = (string)($request['id'] ?? $request['uuid'] ?? '');
		if ($requestId === '') {
			return false;
		}

		$request['status'] = 'EXPIRED';
		$sweep['objectService']->saveObject(object: $request, register: $sweep['register'], schema: $sweep['schema'], _rbac: false);

		// Cross-app delegated-signing contract (filinq-signing-events): an
		// expired request is terminal — emit SigningConcludedEvent
		// (status=expired) for a delegated (provenance-carrying) request.
		// Internal requests emit nothing. Fail-soft inside the service.
		$this->signingService->emitExpiredConclusion(request: $request);

		$this->auditExpiry(request: $request, sweep: $sweep);

		$this->logger->info('[SigningExpirationJob] Expired signing request ' . $requestId);
		return true;

	}//end maybeExpire()

	/**
	 * Write the audit event of an expired request; a failed write is logged, not thrown.
	 *
	 * @param array<string, mixed> $request The expired request.
	 * @param array<string, mixed> $sweep   The sweep, for its moment.
	 *
	 * @return void
	 */
	private function auditExpiry(array $request, array $sweep): void {
		$requestId = (string)($request['id'] ?? $request['uuid'] ?? '');
		try {
			$this->auditService->logEvent(
				signingRequestId: $requestId,
				action: 'EXPIRED',
				actorUserId: 'system',
				actorDisplayName: 'System',
				ipAddress: '127.0.0.1',
				signatureLevel: (string)($request['signatureLevel'] ?? ''),
				provider: (string)($request['provider'] ?? ''),
				metadata: ['deadline' => $request['deadline'], 'expiredAt' => $sweep['now']->format(DateTimeInterface::ATOM)]
			);
		} catch (Exception $e) {
			$this->logger->error(
				'[SigningExpirationJob] Failed to log audit event for ' . $requestId . ': ' . $e->getMessage()
			);
		}

	}//end auditExpiry()
}//end class
