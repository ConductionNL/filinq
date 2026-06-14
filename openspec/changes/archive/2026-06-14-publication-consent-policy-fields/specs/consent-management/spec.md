---
status: draft
---

# Consent Management — Delta for Entity Publication Policies

This delta extends the existing `consent-management` capability so the `publicationConsent` schema can model both per-document workflow records (today's behavior) and entity-level standing consents (new). It also adds a polymorphic `policyMatch` reference that, together with `notificationStatus`, discriminates "this record was pre-empted by a policy" from "this record went through the WOO workflow" — without introducing any new `consentStatus` enum values.

All requirements in this delta apply within publication-clearance flows. Generic anonymisation flows do not invoke `ConsentService::createConsentRequest()`, do not create `publicationConsent` records, and therefore do not participate in the publication-clearance workflow. Generic anonymisation flows MAY read `publicationProhibition` records from the consent register as a data source for safety checks (e.g. the prohibition gate specced in `anonymisation-prohibition-gate`); read access to a register is not workflow integration and does not trigger any of the requirements in this delta.

## ADDED Requirements

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
