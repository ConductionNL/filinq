<?php

/**
 * ConsentRecordWriter — OpenRegister persistence for the consent workflow.
 *
 * Owns every OpenRegister read/write behind `ConsentService::createConsentRequest()`:
 * the idempotency lookup on `(documentId, entityKey|entityText, scope=document)`,
 * the idempotent-re-submit update path, and the create path.
 *
 * The two write paths share one "request context" array, documented on
 * {@see ConsentRecordWriter::createNewConsent()}, which carries the caller's
 * inputs plus the already-resolved policy-match discriminators. Passing that
 * context keeps the write signatures small and keeps the policy vocabulary
 * (which rule kinds exist) in ConsentService, where the matcher lives.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Exception;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Persistence layer for publicationConsent workflow records.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/consent-management/spec.md
 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
 */
class ConsentRecordWriter {

	/**
	 * Workflow-state fields that MUST NOT be overwritten on update.
	 *
	 * @var string[]
	 */
	private const PRESERVED_FIELDS = [
		'notificationStatus',
		'notificationSentAt',
		'objectionDeadline',
		'objectionReceivedAt',
		'objectionReason',
		'consentStatus',
		'publicationDecision',
	];

	/**
	 * Service-level scope + referent validator.
	 *
	 * @var ConsentPolicyReferentValidator
	 */
	private readonly ConsentPolicyReferentValidator $referentValidator;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger Logger for error reporting.
	 * @param ContainerInterface $container Container for DI.
	 * @param IAppManager $appManager App manager interface.
	 * @param ObjectionDeadlineChecker $deadlineChecker Objection-deadline calculator.
	 * @param ConsentNotesHelper $notesHelper Sentinel-tagged notes helper.
	 * @param ConsentScopeValidator $scopeValidator Consent scope validator.
	 * @param ConsentPolicyReferentValidator|null $referentValidator Scope + referent validator.
	 * @param ObjectResultExtractor $resultExtractor Coerces OpenRegister results to rows.
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly ContainerInterface $container,
		private readonly IAppManager $appManager,
		private readonly ObjectionDeadlineChecker $deadlineChecker,
		private readonly ConsentNotesHelper $notesHelper,
		private readonly ConsentScopeValidator $scopeValidator,
		?ConsentPolicyReferentValidator $referentValidator = null,
		private readonly ObjectResultExtractor $resultExtractor = new ObjectResultExtractor(),
	) {
		$this->referentValidator = ($referentValidator ?? new ConsentPolicyReferentValidator(
			logger: $logger,
			container: $container,
			appManager: $appManager
		));

	}//end __construct()

	/**
	 * Assert that OpenRegister is installed and reachable.
	 *
	 * Callers use this to fail fast, before any other work, exactly where the
	 * pre-extraction code resolved the ObjectService up front.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If OpenRegister is not available.
	 *
	 * @spec exclude infrastructure guard — asserts OpenRegister availability, no product behaviour
	 */
	public function requireOpenRegister(): void {
		$this->getObjectService();

	}//end requireOpenRegister()

	/**
	 * Validate publication consent data against the scope rules.
	 *
	 * @param array<string, mixed> $data Consent data to validate.
	 *
	 * @return void
	 *
	 * @throws \InvalidArgumentException When data violates scope constraints.
	 *
	 * @spec openspec/changes/publication-clearance-anonymise-payload/tasks.md#task-3
	 */
	public function validatePublicationConsentData(array $data): void {
		$this->referentValidator->validatePublicationConsentData(data: $data);

	}//end validatePublicationConsentData()

	/**
	 * Load a consent record's plain data by UUID.
	 *
	 * @param string $consentId The consent object UUID.
	 * @param string $register The register ID.
	 * @param string $schema The schema ID.
	 *
	 * @return array<string, mixed> The record's stored data.
	 *
	 * @throws Exception When the record does not exist.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 */
	public function loadConsentRecord(string $consentId, string $register, string $schema): array {
		$object = $this->getObjectService()->find(
			id: $consentId,
			register: $register,
			schema: $schema
		);

		if ($object === null) {
			throw new Exception('Consent record not found: ' . $consentId);
		}

		return $object->getObject();
	}//end loadConsentRecord()

	/**
	 * Look up an existing scope=document consent record by idempotency key.
	 *
	 * Primary key: (documentId, entityKey, scope=document).
	 * Fallback key when entityKey is null: (documentId, entityText, scope=document).
	 * scope=entity records are excluded from matching.
	 *
	 * @param string $documentId The document UUID.
	 * @param string|null $entityKey OR entity UUID, or null.
	 * @param string $entityText Detected entity text.
	 * @param string $register Register ID.
	 * @param string $schema Schema ID.
	 *
	 * @return array<string, mixed>|null Existing record data or null if not found.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-1
	 */
	public function findExistingConsent(
		string $documentId,
		?string $entityKey,
		string $entityText,
		string $register,
		string $schema,
	): ?array {
		$filter = $this->buildIdempotencyFilter(
			documentId: $documentId,
			entityKey: $entityKey,
			entityText: $entityText,
			register: $register,
			schema: $schema
		);

		try {
			$results = $this->getObjectService()->searchObjects(query: $filter);
		} catch (Exception $e) {
			// A failed lookup is non-fatal — fall through to create path.
			$this->logger->warning(
				message: 'ConsentService: idempotency lookup failed, falling through to create',
				context: ['documentId' => $documentId, 'error' => $e->getMessage()]
			);
			return null;
		}

		foreach ($this->resultExtractor->extractRows(result: $results) as $data) {
			// Belt-and-braces: confirm scope=document (the filter should have
			// already excluded scope=entity, but defence-in-depth matters here).
			if (($data['scope'] ?? 'document') !== 'entity') {
				return $data;
			}
		}

		return null;
	}//end findExistingConsent()

	/**
	 * Build the idempotency-lookup filter.
	 *
	 * @param string $documentId The document UUID.
	 * @param string|null $entityKey OR entity UUID, or null.
	 * @param string $entityText Detected entity text.
	 * @param string $register Register ID.
	 * @param string $schema Schema ID.
	 *
	 * @return array<string, mixed> The searchObjects() query.
	 */
	private function buildIdempotencyFilter(
		string $documentId,
		?string $entityKey,
		string $entityText,
		string $register,
		string $schema,
	): array {
		$filter = [
			'@self' => [
				'register' => $register,
				'schema' => $schema,
			],
			'documentId' => $documentId,
			'scope' => 'document',
		];

		if ($entityKey !== null && $entityKey !== '') {
			$filter['entityKey'] = $entityKey;
			return $filter;
		}

		$filter['entityText'] = $entityText;
		return $filter;
	}//end buildIdempotencyFilter()

	/**
	 * Update an existing consent record (idempotent re-submit path).
	 *
	 * Preserves workflow state fields; updates operator-set fields.
	 * Re-evaluates policyMatch: sets it if newly applicable, never clears it.
	 *
	 * @param array<string, mixed> $existing Current record data.
	 * @param array<string, mixed> $context The consent-request context.
	 * @param string $register Register ID.
	 * @param string $schema Schema ID.
	 *
	 * @return array<string, mixed> Updated record with `wasUpdated: true`.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-2
	 */
	public function updateExistingConsent(
		array $existing,
		array $context,
		string $register,
		string $schema,
	): array {
		// Start from the preserved record so all stored values survive.
		$updated = $existing;

		// Update operator-set fields.
		$updated['entityType'] = $context['entityType'];

		// Resolve legalBasis + notes from publicationBases.
		$publicationBases = $context['publicationBases'];
		$additionalBases = [];
		if (count($publicationBases) > 0) {
			$updated['legalBasis'] = $this->notesHelper->truncateAtWordBoundary(
				value: $publicationBases[0]
			);
			$additionalBases = array_slice(array: $publicationBases, offset: 1);
		}

		$currentNotes = (string)($existing['notes'] ?? '');
		$updated['notes'] = $this->notesHelper->updateSentinelRegion(
			currentNotes: $currentNotes,
			additionalBases: $additionalBases
		);

		if ($context['contactEmail'] !== null) {
			$updated['contactEmail'] = $context['contactEmail'];
		}

		if ($context['contactAddress'] !== null) {
			$updated['contactAddress'] = $context['contactAddress'];
		}

		// Re-evaluate pre-emption discriminator: set policyMatch when newly
		// applicable; never clear it when previously set (D2). Persist the
		// matchKind marker alongside it so the standing-consent carve-out in
		// ConsentUpdateHandler can fire on the idempotent-update path too.
		if ($context['policyMatchUuid'] !== null && ($existing['policyMatch'] ?? null) === null) {
			$updated['policyMatch'] = $context['policyMatchUuid'];
			$updated['matchKind'] = (string)$context['policyMatchKind'];
		}

		// Ensure all preserved workflow fields are kept (not overwritten).
		foreach (self::PRESERVED_FIELDS as $field) {
			if (isset($existing[$field]) === true) {
				$updated[$field] = $existing[$field];
			}
		}

		$savedObject = $this->getObjectService()->saveObject(
			object: $updated,
			register: $register,
			schema: $schema
		);

		$this->logger->info(
			message: 'Consent request updated (idempotent re-submit)',
			context: ['documentId' => $updated['documentId'] ?? '']
		);

		$result = $savedObject->getObject();
		$result['wasUpdated'] = true;
		return $result;
	}//end updateExistingConsent()

	/**
	 * Create a brand-new consent record.
	 *
	 * On the WOO fall-through path (no policy match) the record is created with
	 * notificationStatus=pending and a computed objectionDeadline. No email or
	 * postal notification is dispatched (CONS-049).
	 *
	 * On a standing-consent match the record is instead pre-empted — see
	 * {@see buildNewConsentPayload()} — and carries no objection deadline.
	 *
	 * The `$context` array carries the caller's inputs plus the resolved
	 * policy discriminators:
	 *   - `documentId`, `entityType`, `entityText` (string)
	 *   - `entityKey`, `contactEmail`, `contactAddress` (string|null)
	 *   - `publicationBases` (string[]): [0] → legalBasis, [1..] → notes sentinel
	 *   - `policyMatchUuid`, `policyMatchKind` (string|null): any policy match
	 *   - `standingConsentUuid` (string|null): set only when the match was a
	 *     standing consent, i.e. the only kind persisted on a new record
	 *
	 * @param array<string, mixed> $context The consent-request context.
	 * @param string $register Register ID.
	 * @param string $schema Schema ID.
	 *
	 * @return array<string, mixed> Created record with `wasUpdated: false`.
	 *
	 * @spec openspec/changes/consent-create-idempotency-and-notes/tasks.md#task-5
	 */
	public function createNewConsent(array $context, string $register, string $schema): array {
		$consentData = $this->buildNewConsentPayload(context: $context);

		// Service-level scope contract (publication-consent-policy-fields
		// task 5): the schema cannot express "matchRules required when
		// scope=entity but forbidden when scope=document". The validator
		// throws InvalidArgumentException on violations, which the
		// controller surfaces as HTTP 400.
		$this->scopeValidator->assertValid($consentData);

		// Save WITHOUT the bypass flags so OpenRegister stamps `@self.owner`
		// from the session user — that ownership stamp is what the read guard
		// in `ConsentController::canAccessConsent()` later compares against,
		// and it is the half of #283 that this call really delivers.
		//
		// ⚠️ The word "RBAC" was in this comment and has been removed: the
		// `publicationConsent` schema declares `"authorization": null`, so
		// OpenRegister's per-object RBAC permits this write for anyone
		// authenticated. Ownership is RECORDED here, not ENFORCED here.
		$savedObject = $this->getObjectService()->saveObject(
			object: $consentData,
			register: $register,
			schema: $schema
		);

		$this->logger->info(
			message: 'Consent request created',
			context: ['documentId' => $context['documentId']]
		);

		$result = $savedObject->getObject();
		$result['wasUpdated'] = false;
		return $result;
	}//end createNewConsent()

	/**
	 * Assemble the payload for a brand-new consent record.
	 *
	 * Two mutually exclusive outcomes, both mandated by the canonical specs:
	 *
	 * - **No policy match** — the WOO objection workflow runs:
	 *   `consentStatus/publicationDecision/notificationStatus: "pending"` plus a
	 *   computed `objectionDeadline` (`entity-publication-policies`, scenario
	 *   "No policy match falls through to WOO workflow").
	 * - **Standing-consent match** — the record is policy-pre-empted:
	 *   `consentStatus: "consent_given"`, `publicationDecision:
	 *   "publish_with_consent"`, `notificationStatus: "skipped"` and NO
	 *   objection deadline (`consent-management`, scenario "Standing-consent
	 *   match resolves to existing 'consent_given' status"; and
	 *   `entity-publication-policies`, scenario "Standing consent match
	 *   short-circuits when no prohibition match").
	 *
	 * The pre-empted branch was previously unimplemented: every new record got
	 * the `pending` triple, so a matched standing consent silently started the
	 * WOO objection clock it is supposed to short-circuit. A prohibition match
	 * never reaches here — it aborts in `ConsentService` with a
	 * `PolicyRejectedException` — so `standingConsentUuid` is the only match
	 * kind this method has to resolve.
	 *
	 * @param array<string, mixed> $context The consent-request context.
	 *
	 * @return array<string, mixed> The payload to persist.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	private function buildNewConsentPayload(array $context): array {
		$deadline = $this->deadlineChecker->calculateDeadline();

		// Resolve legalBasis and notes from publicationBases.
		$publicationBases = $context['publicationBases'];
		$legalBasis = null;
		$additionalBases = [];
		if (count($publicationBases) > 0) {
			$legalBasis = $this->notesHelper->truncateAtWordBoundary(value: $publicationBases[0]);
			$additionalBases = array_slice(array: $publicationBases, offset: 1);
		}

		$notes = $this->notesHelper->updateSentinelRegion(
			currentNotes: '',
			additionalBases: $additionalBases
		);

		$consentData = [
			'documentId' => $context['documentId'],
			'entityType' => $context['entityType'],
			'entityText' => $context['entityText'],
			'scope' => 'document',
			'notificationStatus' => 'pending',
			'consentStatus' => 'pending',
			'publicationDecision' => 'pending',
			'objectionDeadline' => $deadline->format(format: 'c'),
		];

		$consentData = $this->withStandingConsentPreEmption(
			consentData: $consentData,
			context: $context
		);

		$entityKey = $context['entityKey'];
		if ($entityKey !== null && $entityKey !== '') {
			$consentData['entityKey'] = $entityKey;
		}

		if ($legalBasis !== null) {
			$consentData['legalBasis'] = $legalBasis;
		}

		if ($notes !== '') {
			$consentData['notes'] = $notes;
		}

		if ($context['contactEmail'] !== null) {
			$consentData['contactEmail'] = $context['contactEmail'];
		}

		if ($context['contactAddress'] !== null) {
			$consentData['contactAddress'] = $context['contactAddress'];
		}

		return $consentData;
	}//end buildNewConsentPayload()

	/**
	 * Apply standing-consent pre-emption to a new-record payload.
	 *
	 * A standing consent short-circuits the WOO objection workflow: the record
	 * is born in its terminal state and carries no objection deadline. Both
	 * canonical specs state the same triple —
	 * `consentStatus: "consent_given"`, `publicationDecision:
	 * "publish_with_consent"`, `notificationStatus: "skipped"`, with
	 * `objectionDeadline: null` and `policyMatch` referencing the rule.
	 *
	 * No-op when the entity matched no standing consent, in which case the
	 * caller's WOO defaults (`pending` + computed deadline) stand.
	 *
	 * @param array<string, mixed> $consentData The payload assembled so far.
	 * @param array<string, mixed> $context The consent-request context.
	 *
	 * @return array<string, mixed> The payload, pre-empted where applicable.
	 *
	 * @spec openspec/specs/consent-management/spec.md
	 * @spec openspec/specs/entity-publication-policies/spec.md
	 */
	private function withStandingConsentPreEmption(array $consentData, array $context): array {
		if ($context['standingConsentUuid'] === null) {
			return $consentData;
		}

		$consentData['consentStatus'] = 'consent_given';
		$consentData['publicationDecision'] = 'publish_with_consent';
		$consentData['notificationStatus'] = 'skipped';
		unset($consentData['objectionDeadline']);

		$consentData['policyMatch'] = $context['standingConsentUuid'];

		// Persist the match discriminator so the standing-consent carve-out in
		// ConsentUpdateHandler::guardPolicyPreemptedTransition can fire (PR #147
		// Thread B regression: the carve-out keyed on `matchKind`, which was
		// never persisted here, so the operator override on publicationDecision
		// was 400-locked).
		$consentData['matchKind'] = (string)$context['policyMatchKind'];

		return $consentData;
	}//end withStandingConsentPreEmption()

	/**
	 * Get the ObjectService from OpenRegister.
	 *
	 * @return \OCA\OpenRegister\Service\ObjectService The ObjectService instance.
	 *
	 * @throws RuntimeException If OpenRegister is not available.
	 */
	private function getObjectService(): \OCA\OpenRegister\Service\ObjectService {
		if (in_array(needle: 'openregister', haystack: $this->appManager->getInstalledApps(), strict: true) === true) {
			return $this->container->get(id: 'OCA\OpenRegister\Service\ObjectService');
		}

		throw new RuntimeException(message: 'OpenRegister service is not available.');
	}//end getObjectService()
}//end class
