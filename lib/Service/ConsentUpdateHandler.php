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
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use InvalidArgumentException;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Service for updating and querying consent records
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class ConsentUpdateHandler {
	/**
	 * Constructor for ConsentUpdateHandler
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param ContainerInterface $container Container for dependency injection
	 * @param IAppManager $appManager App manager interface
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
	) {

	}//end __construct()

	/**
	 * Get the ObjectService from OpenRegister
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance
	 *
	 * @throws \RuntimeException If OpenRegister is not available
	 */
	private function getObjectService(): \OCA\OpenRegister\Service\ObjectService {
		if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
			return $this->container->get('OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException('OpenRegister service is not available.');
	}//end getObjectService()

	/**
	 * Update consent status for a consent record
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $data The data to update
	 *
	 * @return array<string, mixed> The updated consent record
	 *
	 * @throws Exception If update fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function updateConsentStatus(
		string $consentId,
		string $register,
		string $schema,
		array $data,
	): array {
		try {
			$objectService = $this->getObjectService();

			// Let OpenRegister enforce per-object RBAC and multitenancy
			// access. Bypassing these (security finding #283) allowed any
			// authenticated user to overwrite consent records owned by
			// other users.
			$object = $objectService->find(
				id: $consentId,
				register: $register,
				schema: $schema
			);

			if ($object === null) {
				throw new Exception('Consent record not found: ' . $consentId);
			}

			// Whitelist the fields a consent update may mutate so callers
			// cannot rewrite arbitrary consent attributes (e.g. documentId,
			// entityText) or inject extra keys (security finding #283).
			$mutableFields = [
				'notificationStatus',
				'consentStatus',
				'publicationDecision',
				'objectionReason',
				'objectionDeadline',
			];
			$allowedData = array_intersect_key($data, array_flip($mutableFields));

			$existing = $object->getObject();

			// Server-controlled-fields immutability gate: this always-on
			// guard runs AHEAD of the policy-pre-emption lock so a PATCH that
			// fabricates or swaps `policyMatch` / `matchKind` is rejected on
			// BOTH the matched and unmatched branches (PR #147 sixth-pass).
			// We pass the FULL inbound $data here (not the mutable-field
			// whitelist) because server-controlled fields are deliberately
			// absent from $mutableFields — checking the whitelist would never
			// see the injection attempt.
			$this->guardServerControlledFields(existing: $existing, data: $data);

			// Policy pre-emption lock: records bound to a prohibition /
			// standing-consent match must not have their transition fields
			// overridden by operators (restored after 917b80e7 wiped it).
			$this->guardPolicyPreemptedTransition(existing: $existing, data: $allowedData);

			$consentData = array_merge($existing, $allowedData);

			$savedObject = $objectService->saveObject(
				object: $consentData,
				register: $register,
				schema: $schema
			);

			$this->logger->info(
				'Consent status updated',
				[
					'consentId' => $consentId,
					'updatedKeys' => array_keys($allowedData),
				]
			);

			return $savedObject->getObject();
		} catch (Exception $e) {
			$this->logger->error(
				'Failed to update consent status: ' . $e->getMessage(),
				[
					'consentId' => $consentId,
					'exception' => $e,
				]
			);
			throw new Exception(
				'Failed to update consent status: ' . $e->getMessage(),
				0,
				$e
			);
		}//end try

	}//end updateConsentStatus()

	/**
	 * Reject any caller mutation of server-controlled consent fields.
	 *
	 * The fields `policyMatch` and `matchKind` are set exclusively by the
	 * server when a prohibition / standing-consent rule matches an entity.
	 * A caller may RE-SEND the current value (idempotent full-record PUTs),
	 * but may never change it to a different value — nor fabricate it on a
	 * record that does not yet carry it. This is an always-on immutability
	 * gate that runs AHEAD of `guardPolicyPreemptedTransition` so it cannot
	 * be bypassed on either the matched or the unmatched branch
	 * (PR #147 sixth-pass).
	 *
	 * @param array<string, mixed> $existing The record's current data.
	 * @param array<string, mixed> $data The proposed update.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When a server-controlled field would be mutated.
	 */
	private function guardServerControlledFields(array $existing, array $data): void {
		// Fields the server alone owns. A caller may RE-SEND the current
		// value (idempotent full-record PUTs), but may never mutate them to
		// a different value — nor fabricate them on a record that does not
		// yet carry them. This guard is always-on and runs ahead of the
		// policy-pre-emption lock so it cannot be bypassed by either the
		// matched or the unmatched branch (PR #147 sixth-pass).
		$serverOwnedFields = [
			'policyMatch',
			'matchKind',
		];

		foreach ($serverOwnedFields as $field) {
			if (array_key_exists($field, $data) === false) {
				continue;
			}

			$proposed = $data[$field];
			$current = ($existing[$field] ?? null);

			// Equal values are a no-op for an immutable field — allow them so
			// idempotent clients that echo the full record state still work.
			if ($proposed === $current) {
				continue;
			}

			// Tolerate loose scalar equality (e.g. "" vs null) so a client
			// re-sending an empty marker against an unset field is not
			// rejected, while a genuine value change still trips.
			if (($proposed === null || $proposed === '')
				&& ($current === null || $current === '')
			) {
				continue;
			}

			throw new InvalidArgumentException(
				message: sprintf(
					'%s is server-controlled and cannot be modified by the caller.',
					$field
				)
			);
		}//end foreach

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
	 * Standing-consent carve-out: when `matchKind` is the persisted
	 * 'standing_consent' marker the operator MAY flip `publicationDecision`
	 * (e.g. consent_given → anonymize) as long as `consentStatus` itself is
	 * preserved. A prohibition match locks BOTH fields.
	 *
	 * @param array<string, mixed> $existing The record's current data.
	 * @param array<string, mixed> $data The proposed update.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException When the update would change consentStatus on a policy-pre-empted record.
	 */
	private function guardPolicyPreemptedTransition(array $existing, array $data): void {
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
		$statusChanged = $this->isTransitionFieldChanged(
			existing: $existing,
			data: $data,
			field: 'consentStatus'
		);
		$decisionChanged = $this->isTransitionFieldChanged(
			existing: $existing,
			data: $data,
			field: 'publicationDecision'
		);

		if ($this->isStandingConsentOverride(
			existing: $existing,
			statusChanged: $statusChanged,
			decisionChanged: $decisionChanged
		) === true
		) {
			return;
		}

		if ($statusChanged === false && $decisionChanged === false) {
			return;
		}

		$rejectedField = 'publicationDecision';
		if ($statusChanged === true) {
			$rejectedField = 'consentStatus';
		}

		$rejectedValue = (string)$data[$rejectedField];
		$currentValue = (string)($existing[$rejectedField] ?? '');

		throw new InvalidArgumentException(
			message: sprintf(
				'%s "%s" rejected on policy-pre-empted record (policyMatch=%s, current=%s).',
				$rejectedField,
				$rejectedValue,
				(string)$existingMatch,
				$currentValue
			)
		);

	}//end guardPolicyPreemptedTransition()

	/**
	 * Determine whether an update actually changes a transition field.
	 *
	 * A field that is absent from the payload, or present with the same
	 * stringified value it already holds, is not a change.
	 *
	 * @param array<string, mixed> $existing The record's current data.
	 * @param array<string, mixed> $data The proposed update.
	 * @param string $field The transition field to inspect.
	 *
	 * @return bool True when the payload would change the field's value.
	 */
	private function isTransitionFieldChanged(array $existing, array $data, string $field): bool {
		if (array_key_exists($field, $data) === false) {
			return false;
		}

		return (string)$data[$field] !== (string)($existing[$field] ?? '');
	}//end isTransitionFieldChanged()

	/**
	 * Determine whether the update qualifies for the standing-consent carve-out.
	 *
	 * A record matched to a standing-consent rule (matchKind ===
	 * 'standing_consent') still permits the operator to flip
	 * `publicationDecision` (e.g. consent_given → anonymize) as long as
	 * `consentStatus` itself is preserved. Only a prohibition match locks both
	 * transition fields. The carve-out is driven by the PERSISTED `matchKind`
	 * marker (PR #147 Thread B regression: pre-fix the marker was never
	 * persisted, so the carve-out never fired and the override was 400-locked).
	 *
	 * @param array<string, mixed> $existing The record's current data.
	 * @param bool $statusChanged Whether consentStatus would change.
	 * @param bool $decisionChanged Whether publicationDecision would change.
	 *
	 * @return bool True when the update is an allowed standing-consent override.
	 */
	private function isStandingConsentOverride(
		array $existing,
		bool $statusChanged,
		bool $decisionChanged,
	): bool {
		if ((string)($existing['matchKind'] ?? '') !== 'standing_consent') {
			return false;
		}

		return ($statusChanged === false && $decisionChanged === true);
	}//end isStandingConsentOverride()

	/**
	 * Get all consent records for a specific document
	 *
	 * @param string $documentId The document UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param string|null $ownerUid UID to scope results to, or null for all
	 *
	 * @return array<int, array<string, mixed>> List of consent records
	 *
	 * @throws Exception If query fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function getConsentsByDocument(
		string $documentId,
		string $register,
		string $schema,
		?string $ownerUid = null,
	): array {
		try {
			$objectService = $this->getObjectService();

			$selfScope = ['register' => $register, 'schema' => $schema];

			// Security (H1): scope the query to the caller's own records when
			// a non-admin UID is provided, so users cannot enumerate consent
			// records that belong to other users via the byDocument endpoint.
			if ($ownerUid !== null) {
				$selfScope['owner'] = $ownerUid;
			}

			$results = $objectService->searchObjects(
				[
					'@self' => $selfScope,
					'documentId' => $documentId,
				]
			);

			$consents = [];
			foreach ($results as $result) {
				$consent = (array)$result;
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
				'Failed to get consents for document: ' . $e->getMessage(),
				[
					'documentId' => $documentId,
					'exception' => $e,
				]
			);
			throw new Exception(
				'Failed to get consents for document: ' . $e->getMessage(),
				0,
				$e
			);
		}//end try

	}//end getConsentsByDocument()
}//end class
