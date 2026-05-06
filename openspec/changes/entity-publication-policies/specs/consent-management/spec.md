---
status: draft
---

# Consent Management — Delta for Entity Publication Policies

This delta extends the existing `consent-management` capability to recognize policy pre-emption: when a detected entity matches a `mandatoryAnonymization` or `publicationAllowance` rule, the resulting `publicationConsent` record carries a new `consentStatus` value and a typed reference back to the matching rule. The existing WOO workflow continues to run for entities that match no policy.

## ADDED Requirements

### Requirement: `consentStatus` enum MUST include two new policy-pre-emption values

The `publicationConsent.consentStatus` property's enum MUST be extended with `mandatory_anonymized` (entity matched the deny list) and `blanket_consent_given` (entity matched the allow list). These values are terminal — `publicationConsent` records carrying them MUST NOT transition to other states through the WOO workflow.

#### Scenario: Schema validation accepts the new enum values

- **GIVEN** the updated `publicationConsent` schema definition
- **WHEN** a record is saved with `consentStatus: "mandatory_anonymized"` or `consentStatus: "blanket_consent_given"`
- **THEN** OpenRegister's schema validation accepts the value

#### Scenario: WOO transitions are not allowed from policy-pre-empted states

- **GIVEN** a `publicationConsent` record with `consentStatus: "mandatory_anonymized"`
- **WHEN** any actor attempts to transition the status to `consent_given`, `objection_received`, or `no_response`
- **THEN** the transition MUST be rejected by the consent service
- **AND** the only allowed mutation is to update the `policyMatch` reference if the underlying rule is replaced (still pointing at a `mandatoryAnonymization` record)

### Requirement: `publicationConsent` MUST gain a typed polymorphic reference field `policyMatch`

The `publicationConsent` schema MUST add a new optional property `policyMatch`. This property MUST be a polymorphic reference constrained to point ONLY to a `mandatoryAnonymization` or `publicationAllowance` record. Any UUID pointing to a different schema MUST be rejected by OpenRegister's `ValidateObject` pipeline at save time.

#### Scenario: Schema definition uses items.oneOf for constraint

- **GIVEN** the updated `publicationConsent` schema in `lib/Settings/docudesk_register.json`
- **WHEN** the schema is imported by OpenRegister
- **THEN** the `policyMatch` property is defined with `type: "object"`, `oneOf` containing `$ref` to both policy schemas, and `objectConfiguration.handling: "related-object"`

#### Scenario: ValidateObject rejects an out-of-class reference

- **GIVEN** a `publicationConsent` record being saved with `policyMatch` set to the UUID of a record from an unrelated schema (e.g. a `template` or `signing` record)
- **WHEN** the save operation runs through `ValidateObject`
- **THEN** the operation is rejected with a validation error indicating the referenced object's schema is not in the allowed set

#### Scenario: Reference to a mandatoryAnonymization record is accepted

- **GIVEN** a `publicationConsent` record being saved with `policyMatch` referencing the UUID of an existing `mandatoryAnonymization` record
- **WHEN** the save operation runs
- **THEN** the record is persisted
- **AND** the reference is resolvable via the existing OpenRegister relation API

### Requirement: New records with `consentStatus = mandatory_anonymized` MUST skip notification

When a `publicationConsent` record is created with `consentStatus: "mandatory_anonymized"`, the consent service MUST NOT send a notification to the entity, MUST NOT compute an objection deadline, and MUST set `publicationDecision: "anonymize"` directly.

#### Scenario: No notification fires on deny-list match

- **GIVEN** an entity matching a `mandatoryAnonymization` rule
- **WHEN** detection creates the corresponding `publicationConsent` record
- **THEN** `notificationStatus: "skipped"`
- **AND** `notificationSentAt: null`
- **AND** `objectionDeadline: null`
- **AND** `publicationDecision: "anonymize"`
- **AND** no email or postal notification is dispatched

### Requirement: New records with `consentStatus = blanket_consent_given` MUST skip notification

When a `publicationConsent` record is created with `consentStatus: "blanket_consent_given"`, the consent service MUST NOT send a notification, MUST NOT compute an objection deadline, and MUST set `publicationDecision: "publish_with_consent"` as the default decision (subject to UI override per the entity-publication-policies capability).

#### Scenario: No notification fires on allow-list match

- **GIVEN** an entity matching a `publicationAllowance` rule
- **WHEN** detection creates the corresponding `publicationConsent` record
- **THEN** `notificationStatus: "skipped"`
- **AND** `notificationSentAt: null`
- **AND** `objectionDeadline: null`
- **AND** `publicationDecision: "publish_with_consent"`
- **AND** no email or postal notification is dispatched

### Requirement: ConsentService MUST consult policy schemas before creating WOO-flow records

The existing `ConsentService::createConsentRequest()` (or equivalent) MUST be extended to consult the policy-matching service before defaulting to the WOO workflow. The order MUST be: deny-list match → allow-list match → fall through to the existing WOO flow.

#### Scenario: Detection creates exactly one publicationConsent record per (document, entity)

- **GIVEN** an entity detected in a document
- **WHEN** the consent service processes the detection
- **THEN** exactly one `publicationConsent` record is created for this (document, entity) pair
- **AND** the record's `consentStatus` reflects the highest-priority policy match (deny > allow > none)
- **AND** the WOO workflow does not run for policy-pre-empted records

### Requirement: Existing WOO behavior MUST remain unchanged for unmatched entities

For detected entities that match no policy rule, the existing consent-management requirements (REQ-CONS-01 through REQ-CONS-08, etc.) MUST continue to apply unchanged. The new `policyMatch` field MUST be `null` on such records.

#### Scenario: Unmatched entity follows the existing flow

- **GIVEN** a detected entity matching no policy rule
- **WHEN** detection creates the `publicationConsent` record
- **THEN** `consentStatus: "pending"`, `notificationStatus: "pending"`, `publicationDecision: "pending"`
- **AND** an `objectionDeadline` is calculated from the configured objection period
- **AND** the existing notification dispatch logic runs unchanged
- **AND** `policyMatch: null`
