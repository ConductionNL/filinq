<?php

/**
 * LibreSign completion
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Signing
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Signing;

use OCA\Filinq\Service\SettingsService;
use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Concludes a LibreSign request when LibreSign has.
 *
 * LibreSign runs the signer flow, so nothing in Filinq sees the last
 * signature. This reads every open LibreSign request back from LibreSign:
 * signed means the signed PDF becomes a new version of the document through
 * the same path an in-app signature takes, the request completes and the
 * audit trail records it; withdrawn in LibreSign means cancelled here. A
 * request LibreSign has not finished is left alone.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/libresign-signing-provider/specs/libresign-signing-provider/spec.md
 */
class LibreSignCompletion {

	/**
	 * Constructor
	 *
	 * @param SettingsService          $settings  The register binding and object service
	 * @param SigningProviderFactory   $providers Resolves the LibreSign provider
	 * @param SignedArtifactProducer   $producer  Stores the signed file as a new version
	 * @param SigningAuditService      $audit     The hash-chained audit trail
	 * @param SigningConclusionEmitter $emitter   Tells a waiting consumer app
	 * @param LoggerInterface          $logger    Logger
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SettingsService $settings,
		private readonly SigningProviderFactory $providers,
		private readonly SignedArtifactProducer $producer,
		private readonly SigningAuditService $audit,
		private readonly SigningConclusionEmitter $emitter,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Read every open LibreSign request back from LibreSign.
	 *
	 * @return int How many requests concluded (completed or cancelled).
	 *
	 * @spec openspec/changes/libresign-signing-provider/tasks.md#task-3.1
	 */
	public function syncAll(): int {
		$objects = $this->settings->getObjectService();
		$binding = $this->settings->resolveSigningRequestBinding();
		if ($objects === null || $binding === null) {
			return 0;
		}

		try {
			$provider = $this->providers->getProvider(identifier: LibreSignProvider::IDENTIFIER);
		} catch (Throwable) {
			// LibreSign is not enabled: nothing can conclude there.
			return 0;
		}

		$concluded = 0;
		foreach (['PENDING', 'IN_PROGRESS'] as $status) {
			$found = $objects->findAll(
				['filters' => ['register' => $binding['register'], 'schema' => $binding['schema'], 'status' => $status]]
			);
			foreach ($found as $result) {
				$request = $this->toArray(result: $result);
				if (($request['provider'] ?? '') !== LibreSignProvider::IDENTIFIER || (string)($request['externalId'] ?? '') === '') {
					continue;
				}

				try {
					$concluded += $this->sync(request: $request, provider: $provider, objects: $objects, binding: $binding);
				} catch (Throwable $e) {
					$this->logger->error(
						'LibreSign request ' . (string)($request['id'] ?? '') . ' could not be concluded: ' . $e->getMessage(),
						['exception' => $e]
					);
				}
			}
		}//end foreach

		return $concluded;

	}//end syncAll()

	/**
	 * Conclude one request if LibreSign has.
	 *
	 * @param array<string, mixed>                   $request  The open request
	 * @param SigningProviderInterface               $provider The LibreSign provider
	 * @param object                                 $objects  OpenRegister's object service
	 * @param array{register: string, schema: string} $binding  Where requests are kept
	 *
	 * @return int 1 when the request concluded, 0 when LibreSign is not done.
	 */
	private function sync(array $request, SigningProviderInterface $provider, object $objects, array $binding): int {
		$state = $provider->checkStatus(externalId: (string)$request['externalId']);
		$metadata = ['externalId' => $request['externalId'], 'libresignSigners' => $state['signers']];

		if ($state['status'] === 'CANCELLED') {
			$request['status'] = 'CANCELLED';
			$objects->saveObject(object: $request, register: $binding['register'], schema: $binding['schema']);
			$this->record(request: $request, action: 'CANCELLED', metadata: $metadata);
			$this->emitter->emitIfDelegated(request: $request, status: 'cancelled');
			return 1;
		}

		if ($state['status'] !== 'COMPLETED') {
			return 0;
		}

		// The declared lifecycle only reaches COMPLETED from IN_PROGRESS.
		if ($request['status'] === 'PENDING') {
			$request['status'] = 'IN_PROGRESS';
			$request = $this->toArray(
				result: $objects->saveObject(object: $request, register: $binding['register'], schema: $binding['schema'])
			);
		}

		$reference = $this->producer->produce(request: $request);
		$request['status'] = 'COMPLETED';
		$request['signedDocumentRef'] = $reference;
		$objects->saveObject(object: $request, register: $binding['register'], schema: $binding['schema']);
		$this->record(request: $request, action: 'COMPLETED', metadata: $metadata);
		$this->emitter->emitIfDelegated(request: $request, status: 'signed', signedDocumentRef: $reference);

		return 1;

	}//end sync()

	/**
	 * Write the audit entry for a conclusion.
	 *
	 * @param array<string, mixed> $request  The request
	 * @param string               $action   COMPLETED or CANCELLED
	 * @param array<string, mixed> $metadata The LibreSign details
	 *
	 * @return void
	 */
	private function record(array $request, string $action, array $metadata): void {
		$this->audit->logEvent(
			signingRequestId: (string)($request['id'] ?? $request['uuid'] ?? ''),
			action: $action,
			actorUserId: 'system',
			actorDisplayName: 'LibreSign',
			ipAddress: '127.0.0.1',
			signatureLevel: (string)($request['signatureLevel'] ?? ''),
			provider: LibreSignProvider::IDENTIFIER,
			metadata: $metadata
		);

	}//end record()

	/**
	 * An object as an array.
	 *
	 * @param mixed $result What OpenRegister returned
	 *
	 * @return array<string, mixed>
	 */
	private function toArray(mixed $result): array {
		if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
			return $result->jsonSerialize();
		}

		return (array)$result;

	}//end toArray()
}//end class
