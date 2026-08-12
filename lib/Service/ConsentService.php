<?php

/**
 * Consent Service
 *
 * Service for managing GDPR publication consent tracking.
 * Handles creating consent records for entities detected in documents.
 * Delegates deadline checking to ObjectionDeadlineChecker, update/query
 * operations to ConsentUpdateHandler, and OpenRegister persistence to
 * ConsentRecordWriter.
 *
 * createConsentRequest() is idempotent on (documentId, entityKey):
 * a second call for the same pair updates the existing record rather
 * than creating a duplicate, preserving workflow state fields.
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
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-5
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-9
 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-10
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-3
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use Exception;
use OCA\DocuDesk\Exception\PolicyRejectedException;
use OCP\IUser;
use Psr\Log\LoggerInterface;

/**
 * Service for GDPR publication consent management
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */
class ConsentService {
	/**
	 * Constructor for ConsentService
	 *
	 * The OpenRegister read/write surface is reached exclusively through the
	 * injected {@see ConsentRecordWriter}; the container, app manager and notes
	 * helper it needs are its own constructor dependencies, not this class's.
	 *
	 * @param LoggerInterface $logger Logger for error reporting
	 * @param ObjectionDeadlineChecker $deadlineChecker Deadline checker
	 * @param ConsentUpdateHandler $updateHandler Update and query handler
	 * @param PolicyMatchService $policyMatcher Policy rule matcher
	 * @param ConsentScopeValidator $scopeValidator Consent scope validator
	 * @param ConsentRecordWriter $recordWriter Consent persistence layer
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ObjectionDeadlineChecker $deadlineChecker,
		private readonly ConsentUpdateHandler $updateHandler,
		private readonly PolicyMatchService $policyMatcher,
		private readonly ConsentScopeValidator $scopeValidator,
		private readonly ConsentRecordWriter $recordWriter,
	) {

	}//end __construct()

	/**
	 * Create (or idempotently update) a consent request for a detected entity.
	 *
	 * Idempotency key: `(documentId, entityKey, scope: "document")`.
	 * When entityKey is null the fallback key is `(documentId, entityText, scope: "document")`.
	 * scope=entity standing-consent records are never matched as duplicates.
	 *
	 * Return shape includes `wasUpdated: bool` — true when an existing record
	 * was matched and updated, false when a new record was created.
	 *
	 * CONS-049 reaffirmed: new records receive `notificationStatus: "pending"` and
	 * a computed `objectionDeadline`; NO email or postal notification is dispatched.
	 *
	 * Checks for a matching standing-consent (scope:entity) record first.
	 * If one is found the record is auto-resolved with consentStatus=consent_given
	 * and policyMatch set to the matching entity-scope record UUID.
	 * If no match is found, the standard WOO objection-period workflow is used.
	 *
	 * @param string $documentId The document UUID
	 * @param string $entityType The entity type
	 * @param string $entityText The detected entity text
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $extra Additional consent data.
	 *                                    Recognised keys:
	 *                                    - entityKey (string|null): OR entity UUID.
	 *                                    - publicationBases (string[]): bases array;
	 *                                    [0] → legalBasis, [1..] → notes sentinel.
	 *                                    - contactEmail (string): contact e-mail.
	 *                                    - contactAddress (string): contact address.
	 *
	 * @return array<string, mixed> The created or updated consent record (includes `wasUpdated`).
	 *
	 * @throws PolicyRejectedException If a prohibition rule matches the entity.
	 * @throws Exception If consent creation/update fails.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-4
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
	 * @spec openspec/specs/consent-management/spec.md
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-6
	 */
	public function createConsentRequest(
		string $documentId,
		string $entityType,
		string $entityText,
		string $register,
		string $schema,
		array $extra = [],
	): array {
		try {
			$this->recordWriter->requireOpenRegister();

			$context = $this->buildRequestContext(
				documentId: $documentId,
				entityType: $entityType,
				entityText: $entityText,
				extra: $extra
			);

			// Policy check — prohibition match blocks the entire request.
			$policyResult = $this->policyMatcher->match(
				entityText: $entityText,
				entityType: $entityType
			);
			if ($policyResult !== null && $policyResult['kind'] === PolicyMatchService::KIND_PROHIBITION) {
				throw new PolicyRejectedException(
					ruleUuid: (string)$policyResult['uuid'],
					ruleName: (string)$policyResult['primaryName']
				);
			}

			$context = $this->withPolicyMatch(context: $context, policyResult: $policyResult);

			// Idempotency lookup — find an existing scope=document record.
			$existing = $this->recordWriter->findExistingConsent(
				documentId: $documentId,
				entityKey: $context['entityKey'],
				entityText: $entityText,
				register: $register,
				schema: $schema
			);

			if ($existing !== null) {
				return $this->recordWriter->updateExistingConsent(
					existing: $existing,
					context: $context,
					register: $register,
					schema: $schema
				);
			}

			return $this->recordWriter->createNewConsent(
				context: $context,
				register: $register,
				schema: $schema
			);
		} catch (PolicyRejectedException $e) {
			// Re-throw without wrapping so callers can catch the typed exception.
			throw $e;
		} catch (Exception $e) {
			$this->logger->error(
				message: 'Failed to create consent request: ' . $e->getMessage(),
				context: ['documentId' => $documentId, 'exception' => $e]
			);
			throw new Exception(message: 'Failed to create consent request: ' . $e->getMessage(), code: 0, previous: $e);
		}//end try

	}//end createConsentRequest()

	/**
	 * Build the consent-request context from the caller's inputs.
	 *
	 * Normalises the loosely-typed `$extra` bag into the explicit shape the
	 * writer consumes. The policy-match discriminators are filled in later, by
	 * {@see ConsentService::withPolicyMatch()}, once the matcher has run.
	 *
	 * @param string $documentId The document UUID.
	 * @param string $entityType The entity type.
	 * @param string $entityText The detected entity text.
	 * @param array<string, mixed> $extra Additional consent data.
	 *
	 * @return array<string, mixed> The consent-request context.
	 */
	private function buildRequestContext(
		string $documentId,
		string $entityType,
		string $entityText,
		array $extra,
	): array {
		$publicationBases = [];
		if (is_array($extra['publicationBases'] ?? null) === true) {
			$publicationBases = array_values(
				array_filter(array_map('strval', $extra['publicationBases']))
			);
		}

		return [
			'documentId' => $documentId,
			'entityType' => $entityType,
			'entityText' => $entityText,
			'entityKey' => $this->optionalString(extra: $extra, key: 'entityKey'),
			'publicationBases' => $publicationBases,
			'contactEmail' => $this->optionalString(extra: $extra, key: 'contactEmail'),
			'contactAddress' => $this->optionalString(extra: $extra, key: 'contactAddress'),
			'policyMatchUuid' => null,
			'policyMatchKind' => null,
			'standingConsentUuid' => null,
		];

	}//end buildRequestContext()

	/**
	 * Read an optional string entry from the `$extra` bag.
	 *
	 * @param array<string, mixed> $extra The caller-supplied extra data.
	 * @param string $key The key to read.
	 *
	 * @return string|null The value as a string, or null when absent.
	 */
	private function optionalString(array $extra, string $key): ?string {
		if (isset($extra[$key]) === true) {
			return (string)$extra[$key];
		}

		return null;
	}//end optionalString()

	/**
	 * Fill the policy-match discriminators on a consent-request context.
	 *
	 * `standingConsentUuid` is set only for a standing-consent match — the one
	 * kind that is persisted on a brand-new record. Prohibition matches never
	 * reach here (they abort the request), so the distinction only matters for
	 * the idempotent-update path, which records any match kind.
	 *
	 * @param array<string, mixed> $context The consent-request context.
	 * @param array<string, mixed>|null $policyResult The matcher's result, or null.
	 *
	 * @return array<string, mixed> The context with policy fields resolved.
	 */
	private function withPolicyMatch(array $context, ?array $policyResult): array {
		if ($policyResult === null) {
			return $context;
		}

		$context['policyMatchUuid'] = ($policyResult['uuid'] ?? null);
		$context['policyMatchKind'] = (string)($policyResult['kind'] ?? '');

		if ($context['policyMatchKind'] === PolicyMatchService::KIND_STANDING_CONSENT) {
			$context['standingConsentUuid'] = $context['policyMatchUuid'];
		}

		return $context;
	}//end withPolicyMatch()

	/**
	 * Validate and update a consent record, enforcing policy-transition rules
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 * @param array<string, mixed> $data The update payload
	 * @param IUser|null $user The acting user, or null for system context
	 *
	 * @return array<string, mixed> The updated consent record
	 *
	 * @throws \InvalidArgumentException When the transition is blocked by policy-match
	 * @throws Exception When the update fails
	 *
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-7
	 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-8
	 */
	public function validateAndUpdateConsent(
		string $consentId,
		string $register,
		string $schema,
		array $data,
		?IUser $user = null,
	): array {
		$existing = $this->recordWriter->loadConsentRecord(
			consentId: $consentId,
			register: $register,
			schema: $schema
		);

		// Standing-consent (scope=entity) records carry policy-level
		// authority for whole classes of documents — revoke/expire on
		// such a record MUST be gated on the same admin group as the
		// create path (PolicyCrudService::createStandingConsent). Without this check
		// a regular consent officer could revoke a standing consent
		// directly via the update API. Backwards-compat: skip the
		// check when no user was plumbed through (existing callers
		// for scope=document keep working unchanged).
		$existingScope = (string)($existing['scope'] ?? 'document');
		if ($existingScope === 'entity' && $user !== null) {
			$this->scopeValidator->requireStandingConsentAdminGroup(user: $user);
		}

		$this->scopeValidator->validateTransition(existing: $existing, update: $data);

		return $this->updateConsentStatus(
			consentId: $consentId,
			register: $register,
			schema: $schema,
			data: $data
		);

	}//end validateAndUpdateConsent()

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
		return $this->updateHandler->updateConsentStatus(
			consentId: $consentId,
			register: $register,
			schema: $schema,
			data: $data
		);

	}//end updateConsentStatus()

	/**
	 * Check if an objection deadline has expired
	 *
	 * @param string $consentId The consent object UUID
	 * @param string $register The register ID
	 * @param string $schema The schema ID
	 *
	 * @return bool True if the deadline has passed
	 *
	 * @throws Exception If check fails
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function checkObjectionDeadline(
		string $consentId,
		string $register,
		string $schema,
	): bool {
		return $this->deadlineChecker->checkObjectionDeadline(
			consentId: $consentId,
			register: $register,
			schema: $schema
		);

	}//end checkObjectionDeadline()

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
		return $this->updateHandler->getConsentsByDocument(
			documentId: $documentId,
			register: $register,
			schema: $schema,
			ownerUid: $ownerUid
		);

	}//end getConsentsByDocument()

	/**
	 * Validate publication consent data against scope rules.
	 *
	 * @param array<string, mixed> $data Consent data to validate
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException When data violates scope constraints
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 */
	public function validatePublicationConsentData(array $data): void {
		$this->recordWriter->validatePublicationConsentData(data: $data);

	}//end validatePublicationConsentData()
}//end class
