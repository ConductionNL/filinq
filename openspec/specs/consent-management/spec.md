---
status: implemented
---

# Consent Management

## Purpose

Provides GDPR-compliant publication consent tracking for entities (persons and organizations) detected in documents. When a document is destined for publication under the Wet Open Overheid (WOO), affected entities must be notified and given an objection period (minimum 4 weeks per WOO). This feature manages the full consent lifecycle: creation, notification tracking, objection handling, and publication decision-making. All consent records are stored as OpenRegister objects using the PublicationConsent schema.
## Requirements
### Requirement: Consent Record Creation (REQ-CONS-01)

**Priority:** MUST

Consent records are created for detected entities in documents, initialized with pending status and an automatic objection deadline.

#### Scenario: Create consent for a detected person
@e2e exclude ConsentService::createConsentRequest() has no REST API endpoint (CONS-048); creation is PHP-only; verified by PHPUnit
- GIVEN a document with detected PERSON entities
- WHEN a consent request is created via ConsentService for entity "Jan de Vries"
- THEN a PublicationConsent object is stored in OpenRegister
- AND the consentStatus is set to "pending"
- AND the notificationStatus is set to "pending"
- AND the publicationDecision is set to "pending"
- AND the objectionDeadline is set to current date + configured objection period days

#### Scenario: Create consent with extra data
@e2e exclude no REST API endpoint for consent creation (CONS-048); PHP-only; verified by PHPUnit
- GIVEN a document entity with additional contact information
- WHEN createConsentRequest is called with extra fields (contactEmail, contactAddress)
- THEN the extra fields are merged into the consent record
- AND the base consent data (statuses, deadline) is preserved

#### Scenario: Custom objection period
@e2e exclude no REST API endpoint for consent creation (CONS-048); deadline calculation verified by PHPUnit
- GIVEN the admin has configured an objection period of 42 days
- WHEN a consent request is created
- THEN the objectionDeadline is set to current date + 42 days
- AND the deadline is stored in ISO 8601 datetime format

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-001 | Consent records can be created for detected entities via ConsentService | MUST | Implemented |
| CONS-002 | Each consent record links to a document via documentId | MUST | Implemented |
| CONS-003 | Each consent record captures entityType (PERSON or ORGANIZATION) and entityText | MUST | Implemented |
| CONS-004 | Consent records are initialized with status `pending` for notificationStatus, consentStatus, and publicationDecision | MUST | Implemented |
| CONS-005 | An objection deadline is automatically calculated based on the configurable objection period (default 28 days) | MUST | Implemented |
| CONS-006 | Consent records are stored in OpenRegister via ObjectService using the configured register and schema | MUST | Implemented |

### Requirement: Consent Status Lifecycle (REQ-CONS-02)

**Priority:** MUST

Consent records progress through defined status transitions for consent, notification, and publication decision fields.

#### Scenario: Update consent status to consent_given
@e2e exclude status update via API (PUT /api/consents/{id}) — REST endpoint verified by PHPUnit; UI detail view covered by click-consent-to-view-details
- GIVEN a pending consent record
- WHEN an administrator updates the consent status to "consent_given"
- THEN the consent record is updated in OpenRegister
- AND the updated record is returned with the new status

#### Scenario: Record an objection
@e2e exclude objection recording via API — ConsentService::updateConsentStatus logic verified by PHPUnit; no seed data for UI test
- GIVEN a consent record with pending status
- WHEN the entity submits an objection with a reason
- THEN consentStatus transitions to "objection_received"
- AND objectionReceivedAt is set to the current datetime
- AND objectionReason stores the provided text

#### Scenario: Notification delivery tracking
@e2e exclude notification status field transitions — pure backend ConsentService logic; no UI surface for notification tracking
- GIVEN a consent record with notificationStatus "pending"
- WHEN the notification is sent successfully
- THEN notificationStatus transitions to "sent"
- AND when delivery is confirmed, it transitions to "delivered"
- AND notificationSentAt is set to the send datetime

#### Scenario: Publication decision after objection period
@e2e exclude publication decision after deadline — requires a consent record past its deadline; no UI to create records; verified by PHPUnit
- GIVEN a consent record where the objection deadline has passed
- AND consentStatus is "no_response"
- WHEN the administrator makes a publication decision
- THEN publicationDecision can be set to "publish_anonymized" or "publish_with_consent"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-010 | consentStatus transitions: `pending` -> `consent_given`, `objection_received`, `no_response`, or `anonymized` | MUST | Implemented |
| CONS-011 | notificationStatus transitions: `pending` -> `sent` -> `delivered` or `failed`; can also be `skipped` | MUST | Implemented |
| CONS-012 | publicationDecision options: `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` | MUST | Implemented |
| CONS-013 | Consent status can be updated via `PUT /api/consents/{id}` | MUST | Implemented |
| CONS-014 | Objection deadline expiry can be checked via ConsentService::checkObjectionDeadline() | MUST | Implemented |

### Requirement: Consent Listing and Querying (REQ-CONS-03)

**Priority:** MUST

Consent records can be listed, queried by ID, and filtered by document.

#### Scenario: List all consent records
@e2e exclude direct REST API call assertion — ConsentController::index() verified by PHPUnit; UI list view covered by view-consent-statistics test
- GIVEN multiple consent records exist across several documents
- WHEN GET /api/consents is called
- THEN all consent records are returned as serialized arrays
- AND each record includes all status fields and entity information

#### Scenario: Get consent by ID
@e2e exclude REST API detail endpoint — ConsentController::show() verified by PHPUnit; UI covered by click-consent-to-view-details test
- GIVEN a consent record with UUID "abc-123"
- WHEN GET /api/consents/abc-123 is called
- THEN the specific consent record is returned with all fields

#### Scenario: Get consents for a specific document
@e2e exclude document-scoped REST API endpoint — ConsentController::byDocument() verified by PHPUnit; no UI surface for document-specific consent listing
- GIVEN a document with 5 detected entities and 5 consent records
- WHEN GET /api/consents/document/{documentId} is called
- THEN all 5 consent records linked to that document are returned

#### Scenario: Register/schema not configured
@e2e exclude backend validation on missing register config — 400 error verified by PHPUnit; UI shows configuration notice (covered by settings tests)
- GIVEN the publicationConsent register and schema are not configured in settings
- WHEN any consent endpoint is called
- THEN a 400 error is returned with message "PublicationConsent register/schema not configured"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-020 | All consent records can be listed via `GET /api/consents` | MUST | Implemented |
| CONS-021 | A specific consent record can be retrieved via `GET /api/consents/{id}` | MUST | Implemented |
| CONS-022 | Consent records for a document can be queried via `GET /api/consents/document/{documentId}` | MUST | Implemented |
| CONS-023 | Consent listing requires the publicationConsent register and schema to be configured | MUST | Implemented |
| CONS-024 | If register/schema is not configured, a 400 error is returned | MUST | Implemented |

### Requirement: WOO Objection Period Compliance (REQ-CONS-04)

**Priority:** MUST

The objection period complies with Wet Open Overheid requirements for a minimum 4-week notification period before publication.

#### Scenario: Default 28-day objection period
@e2e exclude deadline calculation is backend-only — ObjectionDeadlineChecker verified by PHPUnit; no consent creation UI
- GIVEN DocuDesk is configured with the default objection period
- WHEN a consent record is created
- THEN the objectionDeadline is 28 days from creation date
- AND this satisfies the WOO minimum 4-week requirement

#### Scenario: Check if objection deadline has passed
@e2e exclude backend deadline boolean check — ObjectionDeadlineChecker::checkObjectionDeadline() verified by PHPUnit
- GIVEN a consent record created 30 days ago with a 28-day objection period
- WHEN checkObjectionDeadline() is called
- THEN it returns true (deadline has passed)
- AND the publication decision can proceed

#### Scenario: Objection deadline not yet passed
@e2e exclude backend deadline boolean check — ObjectionDeadlineChecker::checkObjectionDeadline() verified by PHPUnit
- GIVEN a consent record created 14 days ago with a 28-day objection period
- WHEN checkObjectionDeadline() is called
- THEN it returns false (deadline has not passed)
- AND publication should wait until the deadline expires

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-030 | The objection period defaults to 28 days per WOO requirements | MUST | Implemented |
| CONS-031 | The objection period is configurable via admin settings | MUST | Implemented |
| CONS-032 | The objection deadline is stored as ISO 8601 datetime | MUST | Implemented |

### Requirement: Controller Read Path Architecture (REQ-CONS-05)

**Priority:** MUST

ConsentController uses different service paths for read vs. write operations -- reading directly via ObjectService, writing via ConsentService.

#### Scenario: Controller lists consents via ObjectService
@e2e exclude internal controller architecture (ObjectService direct path vs ConsentService delegation) — verified by PHPUnit; not observable in UI
- GIVEN the consent endpoint is called for listing
- WHEN ConsentController::index() processes the request
- THEN it calls `settingsService->getAllSettings()` to get register/schema
- AND calls `settingsService->getObjectService()` to get ObjectService directly
- AND queries ObjectService via `searchObjects()` bypassing ConsentService

#### Scenario: Controller updates consent via ConsentService
@e2e exclude internal delegation path — ConsentController::update() routing verified by PHPUnit; not directly observable in UI
- GIVEN a consent update request is received
- WHEN ConsentController::update() processes the request
- THEN it delegates to `ConsentService::updateConsentStatus()`
- AND the update goes through the full service layer

#### Scenario: Controller queries by document via ConsentService
@e2e exclude internal delegation path — ConsentController::byDocument() routing verified by PHPUnit
- GIVEN a document-specific consent query is received
- WHEN ConsentController::byDocument() processes the request
- THEN it delegates to `ConsentService::getConsentsByDocument()`

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-040 | `ConsentController::index()` queries ObjectService directly via SettingsService | MUST | Implemented |
| CONS-041 | `ConsentController::show()` queries ObjectService directly via SettingsService | MUST | Implemented |
| CONS-042 | `ConsentController::update()` delegates to ConsentService | MUST | Implemented |
| CONS-043 | `ConsentController::byDocument()` delegates to ConsentService | MUST | Implemented |

### Requirement: RBAC and Multitenancy Configuration (REQ-CONS-06)

**Priority:** MUST

All consent ObjectService calls currently bypass RBAC and multitenancy, which is a known security concern for multi-tenant deployments.

#### Scenario: Single-tenant deployment
@e2e exclude RBAC flag values passed to ObjectService — backend API parameter verified by PHPUnit; not UI-observable
- GIVEN a single-tenant Nextcloud deployment
- WHEN consent operations are performed with `_rbac: false` and `_multitenancy: false`
- THEN all authenticated users can access all consent records
- AND this is acceptable for single-tenant use

#### Scenario: Multi-tenant deployment concern
@e2e exclude known security concern documented in spec — not a UI-testable behavior; tracked as CONS-044/045/046
- GIVEN a multi-tenant Nextcloud deployment with multiple organizations
- WHEN consent operations bypass RBAC and multitenancy
- THEN users from Organization A could view and modify consent records of Organization B
- AND this is a security concern that must be addressed

#### Scenario: RBAC bypass scope
@e2e exclude ObjectService call parameter inspection — verified by PHPUnit mock assertions; not UI-observable
- GIVEN ConsentService and ConsentController perform consent operations
- WHEN any create, read, update operation is executed
- THEN `_rbac: false` and `_multitenancy: false` are passed to ObjectService
- AND no role-based access control is applied

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-044 | All ConsentService ObjectService calls bypass RBAC (`_rbac: false`) | MUST | Bug |
| CONS-045 | All ConsentService ObjectService calls bypass multitenancy (`_multitenancy: false`) | MUST | Bug |
| CONS-046 | ConsentController::show() bypasses RBAC when querying directly | MUST | Bug |

### Requirement: Consent Creation API Gap (REQ-CONS-07)

**Priority:** MUST

ConsentService::createConsentRequest() exists but has no REST API endpoint or automated trigger, making consent records impossible to create via the frontend.

#### Scenario: Attempt to create consent via API
@e2e exclude documented absence of endpoint (CONS-048) — absence of POST /api/consents is a known gap; not a UI-testable behavior
- GIVEN a frontend user wants to create a consent record for a detected entity
- WHEN they look for a POST /api/consents endpoint
- THEN no such endpoint exists
- AND consent records cannot be created via the REST API

#### Scenario: Event listener does not create consents
@e2e exclude negative assertion on event listener code path — verified by PHPUnit static analysis; ConsentService import absence is code-level
- GIVEN the DocuDeskEventListener handles ObjectCreated events
- WHEN an object is created in OpenRegister
- THEN only metadata enrichment is performed
- AND ConsentService is NOT imported or called by the event listener

#### Scenario: Programmatic consent creation works
@e2e exclude PHP-only internal API — ConsentService::createConsentRequest() verified by PHPUnit
- GIVEN internal PHP code has access to ConsentService
- WHEN createConsentRequest() is called directly
- THEN a consent record is created successfully in OpenRegister
- AND all status fields are initialized to "pending"

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-047 | `ConsentService::createConsentRequest()` exists as a public method | MUST | Implemented |
| CONS-048 | No API endpoint exists for creating consent records | MUST | Dead Code |
| CONS-049 | The event listener does NOT trigger consent creation | MUST | Implemented |
| CONS-050 | Consent records cannot currently be created via REST API or UI | MUST | Bug |

### Requirement: Objection Period Configuration Reading (REQ-CONS-08)

**Priority:** MUST

ConsentService reads the objection period directly from IAppConfig, bypassing SettingsService.

#### Scenario: Read objection period from config
@e2e exclude IAppConfig direct-read path — ConsentService::getObjectionPeriodDays() verified by PHPUnit
- GIVEN the objection period is configured as 42 days in IAppConfig
- WHEN ConsentService::getObjectionPeriodDays() is called (via ObjectionDeadlineChecker)
- THEN it reads directly from IAppConfig key 'publication_objection_period_days'
- AND returns 42

#### Scenario: Default objection period
@e2e exclude hardcoded default in getValueString — PHPUnit verifiable; not UI-observable
- GIVEN no custom objection period is configured
- WHEN getObjectionPeriodDays() is called
- THEN it returns the default of 28 days (hardcoded in getValueString)

#### Scenario: Config key duplication risk
@e2e exclude maintenance risk documented in spec — not a behavioral assertion; purely a code-quality concern
- GIVEN SettingsService also reads the same config key in getAllSettings()
- WHEN the config key name would change in one place
- THEN the other place would break silently
- AND this duplication is a maintenance risk

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-051 | ConsentService reads objection period directly from IAppConfig | MUST | Implemented |
| CONS-052 | Default objection period is 28 days (hardcoded in getValueString default) | MUST | Implemented |

### Requirement: Duplicated ObjectService Resolution Pattern (REQ-CONS-09)

**Priority:** MUST

ConsentService and ObjectionDeadlineChecker have their own private getObjectService() methods duplicating the pattern found in SettingsService.

#### Scenario: ConsentService resolves ObjectService
@e2e exclude internal private method pattern — code duplication documented; verified by PHPUnit; not UI-observable
- GIVEN ConsentService needs to create a consent record
- WHEN it calls its private `getObjectService()`
- THEN the same `getInstalledApps()` + `container->get()` pattern is used
- AND this duplicates the public SettingsService::getObjectService()

#### Scenario: ObjectionDeadlineChecker resolves ObjectService
@e2e exclude internal private method pattern — code duplication documented; verified by PHPUnit; not UI-observable
- GIVEN ObjectionDeadlineChecker needs to check a deadline
- WHEN it calls its private `getObjectService()`
- THEN the same pattern is used again
- AND this is the third duplication of the same code

#### Scenario: OpenRegister unavailable
@e2e exclude backend RuntimeException path — not reproducible in env where OR is installed; verified by PHPUnit
- GIVEN OpenRegister is not installed
- WHEN any duplicated getObjectService() is called
- THEN a RuntimeException is thrown with the same pattern across all services

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-053 | ConsentService has its own private `getObjectService()` duplicating SettingsService pattern | MUST | Implemented |
| CONS-054 | ObjectionDeadlineChecker has its own private `getObjectService()` with the same pattern | MUST | Implemented |

### Requirement: Consent UI (REQ-CONS-10)

**Priority:** MUST

The consent management UI provides a list view with statistics and a detail view for editing consent records.

#### Scenario: View consent statistics
- GIVEN 12 consent records exist (3 pending, 7 approved, 2 objected)
- WHEN the ConsentIndex view loads
- THEN stat cards display Total: 12, Pending: 3, Approved: 7, Objected: 2
- AND cards are color-coded (pending: orange, approved: green, objected: red)

#### Scenario: Click consent to view details
- GIVEN the consent list is displayed
- WHEN the user clicks on a consent row
- THEN the ConsentDetail view is displayed
- AND entity information, consent status dropdowns, and objection details are shown
- AND a Save button allows updating the consent record

#### Scenario: Empty consent list
- GIVEN no consent records exist
- WHEN the ConsentIndex view loads
- THEN NcEmptyContent is displayed with AccountCheck icon
- AND guidance text indicates no records exist yet

#### Scenario: Consent store state management
@e2e exclude Pinia store getter API — internal store shape; UI observes rendered data (covered by view-consent-statistics test); unit-testable directly
- GIVEN the consent Pinia store is initialized
- WHEN consents are fetched
- THEN the store provides getters: pendingConsents, approvedConsents, objectedConsents, consentStats
- AND actions: fetchConsents, fetchConsent, updateConsent, fetchConsentsByDocument

| ID | Requirement | Priority | Status |
|----|------------|----------|--------|
| CONS-055 | Consent list view shows statistics with color-coded cards | MUST | Implemented |
| CONS-056 | Consent detail view shows editable status dropdowns | MUST | Implemented |
| CONS-057 | No automated consent creation exists -- manual or future automation needed | MUST | Implemented |

### Requirement: A listener MUST subscribe to `EntityRelationDecisionUpdatedEvent`

DocuDesk MUST register a Symfony event listener for `OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent`. The listener MUST inspect the event via the `isSkipAnonymizationActivated()` convenience helper and:

- Take action ONLY when the helper returns `true` (i.e. `skipAnonymization` transitioned from `false` to `true` in this change).
- Ignore events that report only a `bases` change.
- Ignore reversal events (`skipAnonymization: true → false`) to prevent infinite loops triggered by the listener's own reversal write.

When the helper returns `true`, the listener MUST resolve the relation's entity and document context and call `ConsentService::createConsentRequest()`.

#### Scenario: Skip activation triggers consent creation

- **GIVEN** an EntityRelation with `skipAnonymization: false`
- **WHEN** an operator PATCHes the relation setting `skipAnonymization: true`
- **THEN** OR persists the change AND dispatches `EntityRelationDecisionUpdatedEvent`
- **AND** the DocuDesk listener calls `ConsentService::createConsentRequest()` for the relation's entity

#### Scenario: Bases-only change does not trigger consent creation

- **GIVEN** an EntityRelation with `skipAnonymization: false`
- **WHEN** the operator PATCHes the relation setting `bases: ["persoonsgegevens"]` only
- **THEN** the listener inspects `isSkipAnonymizationActivated()` (returns false)
- **AND** `createConsentRequest()` is NOT called
- **AND** no consent record is created

#### Scenario: Reversal event does not trigger consent creation

- **GIVEN** an EntityRelation with `skipAnonymization: true`
- **WHEN** the listener (or any other actor) reverses the flag by PATCHing `skipAnonymization: false`
- **THEN** the resulting event has `isSkipAnonymizationActivated()` false
- **AND** the listener takes no further action
- **AND** the original consent record (if any) remains untouched by the reversal

### Requirement: `createConsentRequest` MUST be idempotent on `(documentId, entityKey)`

When `createConsentRequest` is called with a `documentId` and an `entityKey` (or, if `entityKey` is null, an `entityText`) that matches an existing `publicationConsent` record with `scope: "document"`, the method MUST update that record rather than create a duplicate. `scope: "entity"` (standing-consent) records MUST NEVER be matched as duplicates of a per-document call.

The fields updated on match MUST be: `entityType`, `legalBasis`, `notes`, `contactEmail`, `contactAddress`. The fields PRESERVED on match (i.e. NOT overwritten) MUST be: `notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `consentStatus`, `publicationDecision`. The `policyMatch` field MAY be SET if it was previously null and `PolicyMatchService::match()` now returns a match; it MUST NOT be cleared on match if it previously had a value.

The method's return shape MUST include a `wasUpdated` boolean — `true` if an existing record was matched-and-updated, `false` if a new record was created.

#### Scenario: First call creates a new record

- **GIVEN** no existing publicationConsent record matches `(documentId, entityKey, scope: "document")`
- **WHEN** `createConsentRequest($documentId, "PERSON", "Anneke Jansen", $register, $schema, [entityKey: "X"])` is called
- **THEN** a new record is created
- **AND** the result indicates `wasUpdated: false`

#### Scenario: Re-event for the same entity updates the existing record

- **GIVEN** a publicationConsent record exists with `(documentId: "doc-1", entityKey: "X", scope: "document")`, `legalBasis: "Old basis"`, `notificationStatus: "sent"`, `notificationSentAt: "2026-04-01T10:00:00Z"`
- **WHEN** another EntityRelation for the same entity on the same document fires `EntityRelationDecisionUpdatedEvent` with `skipAnonymization: false → true`
- **THEN** the listener calls `createConsentRequest` for the second time on the same `(documentId, entityKey)`
- **AND** the existing record is matched-and-updated rather than duplicated
- **AND** workflow state (`notificationStatus`, `notificationSentAt`) is preserved
- **AND** the result indicates `wasUpdated: true`

#### Scenario: Workflow state preserved across re-events

- **GIVEN** a publicationConsent record with `consentStatus: "objection_received"`, `objectionReceivedAt: "2026-04-15T..."`, `objectionReason: "..."`
- **WHEN** `createConsentRequest` is called for the same `(documentId, entityKey)` again
- **THEN** `consentStatus`, `objectionReceivedAt`, and `objectionReason` are preserved unchanged
- **AND** the WOO timer (`objectionDeadline`) is NOT reset

#### Scenario: Match falls back to entityText when entityKey is null

- **GIVEN** a legacy publicationConsent record with `entityKey: null` and `entityText: "Karin de Vries"`, `scope: "document"`
- **WHEN** `createConsentRequest` is called for the same documentId with `entityKey: null` and `entityText: "Karin de Vries"`
- **THEN** the lookup matches by `entityText`
- **AND** the record is updated rather than duplicated

#### Scenario: scope=entity records are not matched

- **GIVEN** a `scope: "entity"` standing-consent record for "Karin de Vries"
- **WHEN** `createConsentRequest` is called for documentId X (creating a `scope: "document"` record for the same entity)
- **THEN** the standing-consent record is NOT matched
- **AND** a new `scope: "document"` record is created
- **AND** the standing-consent record is still consulted by `PolicyMatchService` and MAY produce a `policyMatch` reference on the new record (per `entity-publication-policies`)

#### Scenario: policyMatch is set on update when newly applicable

- **GIVEN** a publicationConsent record with `policyMatch: null` and `consentStatus: "pending"`
- **AND** a new standing-consent record now matches the same entity
- **WHEN** `createConsentRequest` is called for the existing record's `(documentId, entityKey)`
- **THEN** the record's `policyMatch` is SET to the standing-consent UUID
- **AND** the record's `consentStatus` is unchanged (workflow state preserved)

#### Scenario: policyMatch is not cleared on update when no longer matching

- **GIVEN** a publicationConsent record with `policyMatch: "<some uuid>"`, `consentStatus: "consent_given"`
- **AND** the referenced rule has been deactivated since
- **WHEN** `createConsentRequest` is called again for the same `(documentId, entityKey)`
- **THEN** the record's `policyMatch` is NOT cleared
- **AND** the record's `consentStatus` is preserved

### Requirement: Prohibition match MUST throw `PolicyRejectedException`

When `PolicyMatchService::match()` returns a prohibition match during `createConsentRequest`, the method MUST throw `OCA\DocuDesk\Exception\PolicyRejectedException` (a new typed exception). The exception MUST carry the rule UUID and rule name so the listener can use them in operator-facing notification text. No publicationConsent record MUST be created or updated.

#### Scenario: Prohibition match throws typed exception

- **GIVEN** an active `publicationProhibition` rule matching "Beschermde Getuige A"
- **WHEN** `createConsentRequest` is called for that entity
- **THEN** `PolicyMatchService::match()` returns a prohibition outcome
- **AND** `createConsentRequest` throws `PolicyRejectedException` with the rule UUID and rule name
- **AND** no publicationConsent record is created or updated

### Requirement: Listener MUST handle PolicyRejectedException by reversing the PATCH and notifying

When the listener catches `PolicyRejectedException` from `createConsentRequest`, the listener MUST:

1. Call `EntityRelationMapper::updateDecisionMetadata($relation, ['skipAnonymization' => false], $actingUser)` to revert the relation to `skipAnonymization: false`.
2. Dispatch a Nextcloud notification (via `\OCP\Notification\IManager`) to the acting user. The notification MUST identify the entity text + the matching prohibition rule's name + a deep link / reference to the document review surface.
3. Allow the reversal write's own event to fire — the listener's `isSkipAnonymizationActivated()` filter MUST cause the reversal event to be a no-op, preventing an infinite loop.

#### Scenario: Prohibition rejection reverses the PATCH

- **GIVEN** an active prohibition rule matching the entity
- **WHEN** the operator PATCHes `skipAnonymization: true` on a relation for that entity
- **THEN** the original PATCH succeeds (post-commit event)
- **AND** the listener fires, catches `PolicyRejectedException` from `createConsentRequest`
- **AND** the listener calls `updateDecisionMetadata` reversing `skipAnonymization` to `false`
- **AND** a follow-up GET on the relation shows `skipAnonymization: false`
- **AND** a Nextcloud notification appears for the acting user with the rule name + entity reference

#### Scenario: Reversal event does not re-trigger the prohibition handler

- **GIVEN** the listener has just reversed a PATCH via `updateDecisionMetadata($relation, ['skipAnonymization' => false], ...)`
- **WHEN** OR dispatches a new `EntityRelationDecisionUpdatedEvent` for the reversal
- **THEN** the listener inspects `isSkipAnonymizationActivated()` (returns false)
- **AND** the listener takes no action on the reversal event
- **AND** no infinite loop occurs

### Requirement: Listener failure MUST NOT roll back the PATCH

When the listener catches an exception other than `PolicyRejectedException` from `createConsentRequest` (e.g. database error, third-party listener failure, OR transient outage), the listener MUST:

1. Log the failure at error level with the relation UUID, entity context, and exception detail.
2. Emit a Nextcloud notification to the acting user indicating that consent creation failed and they should retry (toggle skip off then on again).
3. NOT reverse the PATCH. The operator's decision stands; the consent record is missing, but the decision flag persists.

This contract differs from `PolicyRejectedException` because generic failures are operational issues, not policy decisions. Reversing the PATCH on every operational error would mask the operator's intent.

#### Scenario: Generic exception preserves the PATCH

- **GIVEN** an EntityRelation with `skipAnonymization: false`
- **AND** `createConsentRequest` will fail with a `RuntimeException` (e.g. database connection lost)
- **WHEN** the operator PATCHes the relation setting `skipAnonymization: true`
- **THEN** the listener fires and catches the RuntimeException
- **AND** the failure is logged at error level
- **AND** a Nextcloud notification is emitted to the acting user
- **AND** the relation's `skipAnonymization` remains `true` (NOT reversed)
- **AND** no consent record exists for the entity (the operational failure left a gap that the operator can fix by retrying)

### Requirement: Notification dispatch MUST stay stubbed in v1

This delta does NOT add automated email or postal notification dispatch for the publicationConsent workflow. publicationConsent records created via the new flow with `consentStatus: "pending"` MUST have `notificationStatus: "pending"` and a computed `objectionDeadline`, but NO email or postal notification is sent automatically.

Operators advance `notificationStatus` manually via the existing `PUT /api/consents/{id}` endpoint once they have sent the notification through their out-of-band channels.

This reaffirms existing requirement CONS-049 from the canonical `consent-management` spec. A separate change `publicationconsent-notification-dispatch` will add the real notification stack; this delta does not need replacing when that lands.

#### Scenario: Pending record does not trigger SMTP

- **GIVEN** the event listener creates a new publicationConsent record with `consentStatus: "pending"`
- **WHEN** the record is inspected
- **THEN** `notificationStatus: "pending"` and `notificationSentAt: null`
- **AND** no SMTP activity is observed (no log entry indicating a send attempt)
- **AND** `objectionDeadline` is set to the configured period (28 days by default) from the moment the listener created the record

#### Scenario: Operator advances notification status manually

- **GIVEN** a pending publicationConsent record
- **WHEN** the operator PUTs `{notificationStatus: "sent", notificationSentAt: "<ISO timestamp>"}` to `/api/consents/{id}`
- **THEN** the record is updated per existing CONS-002 transitions
- **AND** the WOO workflow proceeds normally from that point

### Requirement: The change MUST be additive and non-breaking

Existing direct callers of `createConsentRequest()` (e.g. via `POST /api/consents`) MUST see behaviour unchanged for inputs that don't match any existing record. The idempotency upgrade is invisible for callers that always provide unique `(documentId, entityKey)` combinations. The listener subscribes to a new event; it does NOT alter any existing event handlers or existing call paths.

#### Scenario: Direct API caller with unique key creates a record as before

- **GIVEN** a pre-change client calling `POST /api/consents` with a fresh `(documentId, entityKey)` that doesn't match any record
- **WHEN** the call is made
- **THEN** a new record is created
- **AND** behaviour is identical to pre-change

#### Scenario: Existing event handlers are unaffected

- **WHEN** other Symfony events fire (e.g. `ObjectCreatedEvent`, `ObjectUpdatedEvent`, `ObjectDeletedEvent`)
- **THEN** the new listener does NOT interfere
- **AND** the existing handlers for those events continue to behave per their existing contracts

### Requirement: Consent Affected Entity Links a NC Contact via the Contacts Leaf

A consent record SHALL be able to link its affected entity to a NC Contact through the OR
contacts integration leaf, stored in OR's integration link-table with role `affected-entity`,
rather than capturing the person/org only as free-text. When a contact is linked, the
person/org identity and notification channel (email, postal address) SHALL be resolved from the
linked contact's vCard fields. Free-text `entityText`, `contactEmail`, and `contactAddress`
SHALL be retained as a fallback for legacy or un-linked records only.

#### Scenario: Link a NC Contact to a consent record

- GIVEN a consent record for a detected PERSON entity "Jan de Vries"
- WHEN the user links the NC Contact for Jan de Vries via the contacts leaf
- THEN a link record SHALL be stored in OR's contacts link-table for the consent object with `role='affected-entity'`
- AND the consent detail page SHALL render Jan de Vries as a role-grouped person chip via `CnContactsTab`
- AND the consent record's notification channel SHALL resolve from the linked contact's vCard `EMAIL`

#### Scenario: Legacy free-text entity remains valid as fallback

- GIVEN a legacy consent record with `entityText='Jan de Vries'` and no linked contact
- WHEN the consent record is displayed
- THEN `entityText` SHALL be shown as the display label
- AND `contactEmail` / `contactAddress` SHALL remain the notification channel until a contact is linked
- AND no new free-text-only capture path SHALL be offered for newly created consent records

### Requirement: Letter Recipients Resolve Through the Contacts Leaf

Letter / correspondence recipient person/org data SHALL be resolved from a contacts-leaf-linked
NC Contact (vCard `FN`/`ORG` → name, `EMAIL` → channel, `ADR` → postal merge fields) when a
contact is linked to the recipient, falling back to free-text or ad-hoc OR object UUIDs only
when no contact is linked. The Twig merge engine, batch logic, and PDF output SHALL be unchanged.

#### Scenario: Recipient merge fields populated from a linked contact

- GIVEN a correspondence-generation request whose recipient is linked to a NC Contact via the contacts leaf
- WHEN `CorrespondenceService::generate()` resolves recipient data
- THEN `{{ recipient.naam }}` SHALL be populated from the contact's vCard `FN` / `ORG`
- AND the recipient email channel SHALL be the contact's vCard `EMAIL`
- AND no bespoke free-text person/org capture SHALL be required for the resolution

### Requirement: `publicationConsent` MUST gain a `scope` discriminator

The schema MUST add a new property `scope` with enum values `document` and `entity`, default `document`. All existing records (which are implicitly per-document) MUST be valid under the new schema with `scope` defaulted to `document`. The discriminator gates which other fields are required and which are not used.

#### Scenario: Schema accepts existing records as scope=document by default

- **GIVEN** a `publicationConsent` record stored before this change landed (no `scope` field present)
- **WHEN** the record is read after this change is deployed
- **THEN** the `scope` field is populated with the default value `document`
- **AND** all existing fields and their values are preserved

#### Scenario: New scope=document records behave as today

- **GIVEN** a publication-prep flow detecting an entity in a document
- **WHEN** the consent service creates a per-document record without specifying `scope` explicitly
- **THEN** the record is saved with `scope: "document"`
- **AND** `documentId`, `notificationStatus`, `consentStatus`, `publicationDecision` are required (per existing CONS-001 / CONS-002 / CONS-004)
- **AND** the WOO objection-deadline calculation runs as it does today (per existing CONS-005)

#### Scenario: scope=entity records accept the new field set

- **GIVEN** a privileged user creating a standing consent record
- **WHEN** they POST a `publicationConsent` with `scope: "entity"`, `matchRules`, `consentMethod`, `entityType`, `entityText`, `consentStatus: "consent_given"`, `active: true`, and optionally `validFrom` / `validUntil` / `consentDocument` / `consentScope`
- **THEN** the record is saved
- **AND** the workflow fields (`notificationStatus`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `publicationDecision`) are not populated and not required
- **AND** `documentId` is not required for the save to succeed

### Requirement: `documentId` MUST be required only for scope=document records

The existing canonical requirement (CONS-002: "Each consent record links to a document via documentId") is refined: this requirement MUST continue to hold for `scope: "document"` records. For `scope: "entity"` records, `documentId` MUST NOT be required, and the consent service MUST reject writes that include a `documentId` on a `scope: "entity"` record (sanity check — entity-wide standing consent does not belong to a single document).

#### Scenario: scope=document write missing documentId is rejected

- **WHEN** a write is attempted with `scope: "document"` and no `documentId`
- **THEN** the consent service rejects the write with a validation error citing the missing `documentId`

#### Scenario: scope=entity write with documentId is rejected

- **WHEN** a write is attempted with `scope: "entity"` and a non-null `documentId`
- **THEN** the consent service rejects the write with a validation error citing the inappropriate `documentId` on a standing-consent record

### Requirement: `publicationConsent` MUST gain entity-scope fields

The schema MUST add the following properties, populated only when `scope: "entity"`:

- `matchRules` — array of `{ type, value }` objects. Supported `type` values at v1: `exact`, `normalized`, `bsn`, `kvk`. (Detailed in the `entity-publication-policies` capability.)
- `validFrom`, `validUntil` — datetime bounds for the standing consent's validity. `validUntil` MAY be null (open-ended) but the UI SHOULD warn when omitted.
- `active` — boolean flag. When false, the record is preserved for audit but is not consulted by the matching service.
- `consentMethod` — enum: `paper`, `digital_signature`, `verbal_recorded`, `opt_in_form`. Required for `scope: "entity"` records.
- `consentDocument` — file reference (e.g. signed PDF). Optional; required by UI but not by schema (some consents are recorded without a digital artifact).
- `consentScope` — free-text string describing what publications the consent applies to (e.g. "documents related to the mayor's official duties only").

#### Scenario: scope=entity write without matchRules is rejected

- **WHEN** a write is attempted with `scope: "entity"` and no `matchRules` (or an empty array)
- **THEN** the consent service rejects the write with a validation error

#### Scenario: scope=entity write without consentMethod is rejected

- **WHEN** a write is attempted with `scope: "entity"` and no `consentMethod`
- **THEN** the consent service rejects the write with a validation error

#### Scenario: scope=entity write with valid fields succeeds

- **GIVEN** a valid standing-consent payload (`scope: "entity"`, `entityType: "PERSON"`, `entityText: "Burgemeester De Vries"`, `matchRules: [...]`, `consentStatus: "consent_given"`, `consentMethod: "opt_in_form"`, `active: true`, `validFrom`, `validUntil`)
- **WHEN** a privileged user POSTs the record
- **THEN** the record is saved
- **AND** the rule cache (per `entity-publication-policies`) is invalidated and rebuilt

### Requirement: `publicationConsent` MUST gain a typed polymorphic reference field `policyMatch`

The schema MUST add a new optional property `policyMatch`, valid only on `scope: "document"` records. This property MUST be a polymorphic reference constrained to point ONLY to a `publicationProhibition` record OR a `publicationConsent` record (with the latter expected to be `scope: "entity"`). Any UUID pointing to a different schema MUST be rejected by OpenRegister's `ValidateObject` pipeline at save time. The consent service MUST additionally enforce that a `publicationConsent` referent is in fact `scope: "entity"`.

#### Scenario: Schema definition uses items.oneOf for constraint

- **GIVEN** the updated `publicationConsent` schema in `lib/Settings/docudesk_register.json`
- **WHEN** the schema is imported by OpenRegister
- **THEN** the `policyMatch` property is defined with `type: "object"`, `oneOf` containing `$ref` to `publicationProhibition` and `publicationConsent`, and `objectConfiguration.handling: "related-object"`

#### Scenario: ValidateObject rejects an out-of-class reference

- **GIVEN** a `publicationConsent` record being saved with `policyMatch` set to the UUID of a record from an unrelated schema (e.g. a `template` or `signing` record)
- **WHEN** the save operation runs through `ValidateObject`
- **THEN** the operation is rejected with a validation error indicating the referenced object's schema is not in the allowed set

#### Scenario: Consent service rejects a publicationConsent referent with scope=document

- **GIVEN** a `scope: "document"` `publicationConsent` record being saved with `policyMatch` referencing another `publicationConsent` whose `scope` is `document`
- **WHEN** the save operation runs through the consent service
- **THEN** the operation is rejected with a validation error indicating the referent must be a `scope: "entity"` record

#### Scenario: Reference to a publicationProhibition record is accepted

- **GIVEN** a `scope: "document"` `publicationConsent` record being saved with `policyMatch` referencing the UUID of an existing `publicationProhibition` record
- **WHEN** the save operation runs
- **THEN** the record is persisted
- **AND** the reference is resolvable via the existing OpenRegister relation API

#### Scenario: Reference to a scope=entity publicationConsent is accepted

- **GIVEN** a `scope: "document"` `publicationConsent` record being saved with `policyMatch` referencing the UUID of an existing `scope: "entity"` `publicationConsent` record
- **WHEN** the save operation runs
- **THEN** the record is persisted

#### Scenario: policyMatch on scope=entity records is rejected

- **GIVEN** a `scope: "entity"` `publicationConsent` record being saved with a non-null `policyMatch`
- **WHEN** the save operation runs
- **THEN** the operation is rejected with a validation error — `policyMatch` is meaningful only on per-document records

### Requirement: `consentStatus` enum MUST remain unchanged

This change MUST NOT add new values to the `consentStatus` enum. The discriminator for "this record was pre-empted by a policy" is the combination of `policyMatch` (non-null + which schema it references) and `notificationStatus: "skipped"`. The existing values (`pending`, `consent_given`, `objection_received`, `no_response`, `anonymized`) cover all outcomes — pre-empted records use the same terminal values as workflow-resolved records, with `policyMatch` and `notificationStatus` carrying the path-of-arrival information.

#### Scenario: Prohibition match resolves to existing 'anonymized' status

- **GIVEN** an entity matching a `publicationProhibition` rule
- **WHEN** detection creates the corresponding `scope: "document"` `publicationConsent` record
- **THEN** `consentStatus: "anonymized"` (existing enum value)
- **AND** `notificationStatus: "skipped"`
- **AND** `notificationSentAt: null`
- **AND** `objectionDeadline: null`
- **AND** `publicationDecision: "anonymize"`
- **AND** `policyMatch` references the matching prohibition
- **AND** no email or postal notification is dispatched

#### Scenario: Standing-consent match resolves to existing 'consent_given' status

- **GIVEN** an entity matching an active `scope: "entity"` `publicationConsent` record
- **WHEN** detection creates the corresponding `scope: "document"` `publicationConsent` record
- **THEN** `consentStatus: "consent_given"` (existing enum value)
- **AND** `notificationStatus: "skipped"`
- **AND** `notificationSentAt: null`
- **AND** `objectionDeadline: null`
- **AND** `publicationDecision: "publish_with_consent"`
- **AND** `policyMatch` references the matching standing consent
- **AND** no email or postal notification is dispatched

### Requirement: `ConsentService` MUST consult the policy layer before defaulting to the WOO workflow

The existing `ConsentService::createConsentRequest()` (or its caller) MUST be extended to consult the policy-matching service before defaulting to the WOO workflow. The order MUST be: prohibition match → standing-consent match → fall through to existing WOO flow.

#### Scenario: Detection creates exactly one publicationConsent record per (document, entity)

- **GIVEN** an entity detected in a document during publication preparation
- **WHEN** the consent service processes the detection
- **THEN** exactly one `scope: "document"` `publicationConsent` record is created for this (document, entity) pair
- **AND** the record's `consentStatus` and `policyMatch` reflect the highest-priority policy match (prohibition > standing consent > none)
- **AND** the WOO workflow does not run for policy-pre-empted records

### Requirement: Records with non-null `policyMatch` MUST NOT transition to other terminal states via the WOO workflow

A `scope: "document"` `publicationConsent` record whose `policyMatch` is non-null is policy-pre-empted. Its terminal `consentStatus` (`anonymized` or `consent_given`) MUST NOT be transitioned to a different terminal state via the consent-update path. The only allowed mutation to such a record is updating `policyMatch` if the underlying rule is replaced (still pointing at a permitted referent type), or recording a publication-decision override on a standing-consent match (per the `entity-publication-policies` capability).

#### Scenario: Workflow transitions are rejected on policy-pre-empted records

- **GIVEN** a `scope: "document"` `publicationConsent` record with `policyMatch` non-null and `consentStatus: "anonymized"`
- **WHEN** any actor attempts to transition the status to `consent_given`, `objection_received`, or `no_response`
- **THEN** the transition MUST be rejected by the consent service
- **AND** the rejection error cites the policy-pre-empted state and references the matching rule UUID

#### Scenario: Override-up on a standing-consent match is allowed

- **GIVEN** a `scope: "document"` `publicationConsent` record with `policyMatch` referencing a `scope: "entity"` record and `consentStatus: "consent_given"`
- **WHEN** the user records a publication-decision override (anonymize anyway)
- **THEN** `publicationDecision` transitions to `"anonymize"`
- **AND** `consentStatus` remains `"consent_given"`
- **AND** `policyMatch` is preserved
- **AND** the override is recorded in the per-document record's audit history

### Requirement: Existing WOO behavior MUST remain unchanged for scope=document records with no policy match

For detected entities that match no policy rule, the existing consent-management requirements (REQ-CONS-01 through REQ-CONS-08, etc.) MUST continue to apply unchanged for `scope: "document"` records. The `policyMatch` field MUST be `null` on such records.

#### Scenario: Unmatched entity follows the existing flow

- **GIVEN** a detected entity matching no policy rule
- **WHEN** detection creates the `publicationConsent` record during publication preparation
- **THEN** `scope: "document"`, `consentStatus: "pending"`, `notificationStatus: "pending"`, `publicationDecision: "pending"`
- **AND** an `objectionDeadline` is calculated from the configured objection period
- **AND** the existing notification dispatch logic runs unchanged
- **AND** `policyMatch: null`

## Data Model

### PublicationConsent Schema

Defined in `lib/Settings/docudesk_register.json` and stored in OpenRegister.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| documentId | string | Yes | Reference to the document being published |
| entityType | string (enum) | Yes | `PERSON` or `ORGANIZATION` |
| entityText | string | Yes | Detected entity text (max 500 chars) |
| entityKey | string | No | Unique anonymization key (max 50 chars) |
| contactEmail | string (email) | No | Notification email address |
| contactAddress | string | No | Postal notification address (max 500 chars) |
| notificationStatus | string (enum) | Yes | `pending`, `sent`, `delivered`, `failed`, `skipped` |
| notificationSentAt | datetime | No | When notification was sent |
| consentStatus | string (enum) | Yes | `pending`, `consent_given`, `objection_received`, `no_response`, `anonymized` |
| objectionDeadline | datetime | No | Deadline for objections (WOO: min 4 weeks) |
| objectionReceivedAt | datetime | No | When objection was received |
| objectionReason | string (markdown) | No | Reason for objection (max 2000 chars) |
| publicationDecision | string (enum) | Yes | `pending`, `anonymize`, `publish_with_consent`, `publish_anonymized`, `reject` |
| legalBasis | string | No | Legal basis for publication (max 500 chars) |
| notes | string (markdown) | No | Internal process notes (max 2000 chars) |

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/consents` | List all consent records |
| GET | `/api/consents/{id}` | Get a specific consent record |
| PUT | `/api/consents/{id}` | Update a consent record |
| GET | `/api/consents/document/{documentId}` | Get consents for a specific document |

## Dependencies

- **OpenRegister ObjectService**: CRUD operations on consent records
- **Nextcloud IAppConfig**: Storing objection period and register/schema configuration
- **SettingsService**: Register/schema configuration and ObjectService access
- **ConsentService**: Write operations and document-scoped queries
- **ObjectionDeadlineChecker**: Deadline calculation and checking
- **ConsentUpdateHandler**: Update and query delegation
- **ConsentCrudService**: Controller-level CRUD operations

### Current Implementation Status
- **Implemented** with file paths:
  - `lib/Service/ConsentService.php` -- consent creation, update, deadline checking
  - `lib/Service/ConsentCrudService.php` -- controller-level CRUD operations
  - `lib/Service/ConsentUpdateHandler.php` -- update and query delegation
  - `lib/Service/ObjectionDeadlineChecker.php` -- deadline calculation and checking
  - `lib/Controller/ConsentController.php` -- REST API endpoints
  - `src/views/consent/ConsentIndex.vue` -- consent listing with stats
  - `src/views/consent/ConsentDetail.vue` -- consent detail/edit view
  - `src/store/modules/consent.js` -- Pinia store
  - `lib/Settings/docudesk_register.json` -- PublicationConsent schema
- **Known issues**:
  - No POST /api/consents endpoint (CONS-048/050)
  - RBAC/multitenancy disabled (CONS-044/045/046)
  - No automated consent creation from entity detection

### Standards & References
- **GDPR/AVG Articles 6, 7, 21**: Consent management and right to object
- **WOO (Wet open overheid) Article 4.4**: Minimum 4-week objection period
- **ISO 8601**: Objection deadline datetime format
