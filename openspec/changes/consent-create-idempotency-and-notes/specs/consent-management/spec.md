---
status: draft
---

# Consent Management — Delta for Event-Driven Consent Creation and Idempotent createConsentRequest

This delta closes the canonical `consent-management` REQ-CONS-07 (CONS-048 / CONS-050) gap by giving `ConsentService::createConsentRequest()` an automated caller. The caller is a Symfony event listener subscribed to `OCA\OpenRegister\Event\EntityRelationDecisionUpdatedEvent` (added in PR #1503's amend). When the event reports `skipAnonymization: false → true` on an `EntityRelation`, the listener creates (or updates) a `publicationConsent` record for the entity on the document. `PolicyMatchService` (per `entity-publication-policies`) decides the consent state; prohibition matches result in a typed exception, which the listener handles by reversing the PATCH and emitting an operator notification.

`createConsentRequest()` becomes idempotent on `(documentId, entityKey, scope: "document")` so multiple events for the same entity-on-document collapse to one record. Notification dispatch remains stubbed.

## ADDED Requirements

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
