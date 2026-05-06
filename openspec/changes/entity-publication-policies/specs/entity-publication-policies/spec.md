---
status: draft
---

# Entity Publication Policies

## Purpose

Defines two entity-level policy schemas (`mandatoryAnonymization` for deny-list rules and `publicationAllowance` for blanket-consent rules), the detection-time matching contract that pre-empts the WOO per-document workflow when a policy fires, the deterministic conflict-resolution rule (deny wins), the asymmetric retroactive-application semantics, and the UI toggle behavior. Together these establish a policy layer that sits above the existing per-(document, entity) `publicationConsent` workflow: where a policy applies, the workflow is short-circuited; where it doesn't, the existing WOO flow runs unchanged.

## Current State

- The `publicationConsent` schema (in the consent register, `lib/Settings/docudesk_register.json`) tracks per-(document, entity) workflow state for WOO compliance: notification → 28-day objection → decision.
- There is no entity-level representation of "always anonymize" rules (court orders, threat assessments, minor protections, categorical AVG exemptions) or "blanket consent" rules (public officials' standing consent, signed opt-in by individuals or organizations).
- Detection-time matching has no policy layer; every detection unconditionally creates a `publicationConsent` record and starts the WOO workflow.
- OpenRegister's `ValidateObject::extractObjectConfigurationHandling` already supports `items.oneOf` + `$ref` polymorphic references with `objectConfiguration.handling: "related-object"`. This is the mechanism that makes the new `policyMatch` field type-safe.

## ADDED Requirements

### Requirement: A `mandatoryAnonymization` schema MUST exist in the consent register

The schema represents an entity-level deny-list rule. Each record describes one real-world person or organization that must always be anonymized when detected in a publication-bound document, regardless of any per-document consent process.

#### Scenario: Schema is registered on app install

- **GIVEN** a fresh DocuDesk install
- **WHEN** the app's register configuration is imported
- **THEN** the `consent` register contains a `mandatoryAnonymization` schema with the documented properties: `primaryName`, `entityType`, `matchRules`, `reason`, `legalAuthority`, `caseReference`, `severity`, `jurisdiction`, `addedBy`, `validFrom`, `validUntil`, `active`, `notes`

#### Scenario: Schema is queryable via OpenRegister API

- **GIVEN** the schema has been imported
- **WHEN** an authorized user queries `GET /api/objects/{registerId}/{schemaId}`
- **THEN** they receive a list of `mandatoryAnonymization` objects (empty by default, populated as rules are added)

### Requirement: A `publicationAllowance` schema MUST exist in the consent register

The schema represents an entity-level allow-list rule. Each record describes one real-world person or organization that has granted blanket consent to be mentioned in publications, allowing detection-time pre-emption of the WOO notification/objection workflow.

#### Scenario: Schema is registered on app install

- **GIVEN** a fresh DocuDesk install
- **WHEN** the app's register configuration is imported
- **THEN** the `consent` register contains a `publicationAllowance` schema with: `primaryName`, `entityType`, `matchRules`, `reason`, `consentDocument`, `consentMethod`, `consentScope`, `addedBy`, `validFrom`, `validUntil`, `active`, `notes`

#### Scenario: Allow-list records SHOULD include a validUntil

- **GIVEN** a publication officer creating an allow-list record
- **WHEN** the record is created without a `validUntil` value
- **THEN** the system MAY accept the record but SHOULD surface a UI warning explaining that signed consent typically has a finite term and recommending an explicit `validUntil`
- **AND** the record is persisted as `active: true` with `validUntil: null` only when the user confirms

### Requirement: Both policy schemas MUST support the v1 match rule types

`matchRules` is an array of `{ type, value }` objects. The supported `type` values at v1 are exactly: `exact`, `normalized`, `bsn`, `kvk`. The types `regex` and `reference` are explicitly out of scope for this change.

#### Scenario: Exact match

- **GIVEN** a `mandatoryAnonymization` rule with `matchRules: [{ type: "exact", value: "Jan Janssen" }]`
- **WHEN** a document is detected to contain the entity text "Jan Janssen"
- **THEN** the rule matches
- **AND** the rule does NOT match the entity text "jan janssen", "J. Janssen", or "Jan Janssens"

#### Scenario: Normalized match strips case and accents

- **GIVEN** a rule with `matchRules: [{ type: "normalized", value: "andre dupont" }]`
- **WHEN** a document detects "André Dupont", "ANDRE DUPONT", or "André  Dupont"
- **THEN** the rule matches (case-insensitive and accent-stripped)

#### Scenario: BSN match for PERSON entities

- **GIVEN** a rule with `entityType: "PERSON"` and `matchRules: [{ type: "bsn", value: "111222333" }]`
- **WHEN** the detector resolves a detected PERSON entity to BSN `111222333`
- **THEN** the rule matches
- **AND** the rule does NOT match if the detected entity has no BSN resolved

#### Scenario: KVK match for ORGANIZATION entities

- **GIVEN** a rule with `entityType: "ORGANIZATION"` and `matchRules: [{ type: "kvk", value: "12345678" }]`
- **WHEN** the detector resolves a detected ORGANIZATION entity to KvK `12345678`
- **THEN** the rule matches

#### Scenario: Unknown match types are rejected

- **WHEN** a user attempts to create a rule with `matchRules: [{ type: "regex", value: "/janss?en/i" }]`
- **THEN** the schema validation rejects the record with an error indicating that `regex` is not a supported match type at this version

### Requirement: Detection-time matching MUST evaluate the deny list before the allow list

For every detected PERSON or ORGANIZATION entity, the consent service MUST consult the deny list first. If a deny-list rule matches, the service MUST NOT consult the allow list and MUST NOT enter the WOO workflow.

#### Scenario: No policy match falls through to WOO workflow

- **GIVEN** an entity that matches no `mandatoryAnonymization` and no `publicationAllowance` rule
- **WHEN** the entity is detected during publication preparation
- **THEN** a `publicationConsent` record is created with `consentStatus: "pending"` and the WOO notification workflow starts
- **AND** the `policyMatch` field on the record is null

#### Scenario: Deny-list match short-circuits

- **GIVEN** an entity matching a `mandatoryAnonymization` rule with UUID `R-DENY-1`
- **WHEN** the entity is detected
- **THEN** a `publicationConsent` record is created with `consentStatus: "mandatory_anonymized"`, `publicationDecision: "anonymize"`, and `policyMatch` referencing `R-DENY-1`
- **AND** no notification is sent
- **AND** no objection deadline is calculated
- **AND** the WOO workflow does not run

#### Scenario: Allow-list match short-circuits when no deny match

- **GIVEN** an entity matching a `publicationAllowance` rule with UUID `R-ALLOW-1` and matching no `mandatoryAnonymization` rule
- **WHEN** the entity is detected
- **THEN** a `publicationConsent` record is created with `consentStatus: "blanket_consent_given"`, `publicationDecision: "publish_with_consent"`, and `policyMatch` referencing `R-ALLOW-1`
- **AND** no notification is sent

### Requirement: Conflict resolution MUST be deny-wins, deterministically

When a detected entity matches BOTH a `mandatoryAnonymization` rule and a `publicationAllowance` rule, the resulting `publicationConsent` record MUST resolve to `mandatory_anonymized` with `policyMatch` referencing the deny-list rule. The matching allow-list rule MUST be recorded in audit logs but MUST NOT be referenced in the `publicationConsent.policyMatch` field. There MUST NOT be a configuration option to invert this rule.

#### Scenario: Both lists match — deny wins

- **GIVEN** an entity matching deny rule `R-DENY-2` AND allow rule `R-ALLOW-2`
- **WHEN** the entity is detected
- **THEN** the resulting `publicationConsent` has `consentStatus: "mandatory_anonymized"` and `policyMatch` references `R-DENY-2`
- **AND** the audit log records both `R-DENY-2` and `R-ALLOW-2` as matching rules for the detection event
- **AND** the system does not surface a "configurable" preference to override this behavior

#### Scenario: Multiple deny rules match — deterministic precedence

- **GIVEN** an entity matching two `mandatoryAnonymization` rules `R-DENY-3` and `R-DENY-4`
- **WHEN** the entity is detected
- **THEN** the resulting `publicationConsent.policyMatch` references the rule with the lower UUID (lexicographic ordering) for deterministic test outcomes
- **AND** the audit log records both `R-DENY-3` and `R-DENY-4`

### Requirement: Detector MUST consult an in-memory rule cache

The matching service MUST NOT issue a database query per detected entity. Instead, it MUST load all `active: true` rules from both schemas into an in-memory lookup index at service init or on rule-mutation event. The lookup MUST be O(1) per `(matchType, entityType, value)` tuple.

#### Scenario: Page-level batch detection issues no per-entity rule queries

- **GIVEN** a document with 30 detected entities and a deny+allow list of 100 active rules total
- **WHEN** the consent service processes the document
- **THEN** the rule cache is queried in memory (no database calls per entity for matching)
- **AND** detection performance does not scale linearly with the number of rules

#### Scenario: Cache invalidation on rule change

- **GIVEN** the rule cache is loaded
- **WHEN** an admin creates, updates, or deletes any record in either schema
- **THEN** an object-changed event triggers cache invalidation
- **AND** the next detection rebuilds the cache from the current rule set

### Requirement: Time bounds MUST be honored at match time

A rule with `validFrom` in the future or `validUntil` in the past MUST NOT match, even if `active: true`. A rule with `active: false` MUST NOT match regardless of time bounds.

#### Scenario: Rule with future validFrom does not match

- **GIVEN** a deny rule with `validFrom: "2030-01-01T00:00:00Z"`, `active: true`
- **WHEN** an entity matching the rule is detected today
- **THEN** the rule does NOT match
- **AND** detection falls through to allow-list / WOO workflow

#### Scenario: Rule with past validUntil does not match

- **GIVEN** a deny rule with `validUntil: "2020-01-01T00:00:00Z"`, `active: true`
- **WHEN** an entity matching the rule is detected today
- **THEN** the rule does NOT match

#### Scenario: Rule with active false does not match

- **GIVEN** a deny rule with `active: false` and otherwise-valid time bounds
- **WHEN** an entity matching the rule is detected
- **THEN** the rule does NOT match

### Requirement: Adding a deny-list rule MUST force-resolve in-flight records

When a `mandatoryAnonymization` record is created (or an existing one is updated to `active: true` for matching entities), all in-flight `publicationConsent` records (any non-terminal status: `pending`, `consent_given`, `objection_received`, `no_response`) for matching entities MUST be force-resolved.

#### Scenario: New deny rule retroactively resolves matching in-flight records

- **GIVEN** an in-flight `publicationConsent` record with `consentStatus: "pending"` for entity "Jan Janssen"
- **AND** no deny-list rule currently matches
- **WHEN** an admin creates a new `mandatoryAnonymization` rule with `matchRules: [{ type: "exact", value: "Jan Janssen" }]`
- **THEN** the existing in-flight record is updated: `consentStatus: "mandatory_anonymized"`, `publicationDecision: "anonymize"`, `policyMatch` references the new rule
- **AND** any pending notification for that record is canceled
- **AND** the audit log records the retroactive update with timestamp and triggering rule UUID

### Requirement: Adding an allow-list rule MUST NOT alter in-flight records

When a `publicationAllowance` record is created (or updated to `active: true`), in-flight `publicationConsent` records for matching entities MUST be left untouched. The allow-list applies only to NEW detections after the rule was added.

#### Scenario: New allow rule does not override existing objection

- **GIVEN** an in-flight `publicationConsent` record with `consentStatus: "objection_received"` for entity "M. Jansen"
- **WHEN** an admin creates a new `publicationAllowance` rule matching "M. Jansen"
- **THEN** the existing in-flight record is unchanged
- **AND** the rule applies to future detections of "M. Jansen" only

### Requirement: Rule removal or expiry MUST NOT alter past records

When a rule is deleted, deactivated, or expires (`validUntil` reached), `publicationConsent` records that were resolved by it MUST keep their final state. Future detections fall through to the next layer.

#### Scenario: Deny rule deletion does not retroactively unmask past records

- **GIVEN** a `publicationConsent` record with `consentStatus: "mandatory_anonymized"` and `policyMatch` referencing rule `R-DENY-X`
- **WHEN** rule `R-DENY-X` is deleted
- **THEN** the existing record remains `mandatory_anonymized`
- **AND** the `policyMatch` reference becomes a dangling reference (the OpenRegister referential integrity behavior governs how this is surfaced — but the record's status is not changed)
- **AND** future detections of the same entity fall through to allow-list / WOO workflow

### Requirement: Already-published documents MUST NOT be retroactively modified

Rule changes (additions, updates, removals) MUST NOT trigger any modification to `publicationConsent` records linked to documents whose publication is complete. Audit reports MAY surface a list of "documents containing now-deny-listed entities" for human review, but the system MUST NOT initiate automatic redaction or republication.

#### Scenario: New deny rule does not republish past documents

- **GIVEN** a published document containing entity "Jan Janssen" anonymized at publication time per the WOO workflow
- **AND** a new deny rule for "Jan Janssen" is added
- **THEN** the published document is unchanged
- **AND** any past `publicationConsent` records for that document are unchanged
- **AND** the system MAY emit an audit event flagging the document for human review

### Requirement: UI toggle behavior MUST reflect the consentStatus

The frontend anonymization toggle (per-entity, in the publication-prep screen) MUST behave per the table:

| Status | Toggle default | User can override? |
|---|---|---|
| `pending` / `consent_given` / `objection_received` / `no_response` | per existing UX | yes |
| `mandatory_anonymized` | **on, locked** | **no** |
| `blanket_consent_given` | **off, defaulted** | **yes (override-up to anonymize anyway)** |

#### Scenario: Toggle is locked for mandatory_anonymized

- **GIVEN** a publication-prep screen showing a `publicationConsent` with `consentStatus: "mandatory_anonymized"`
- **WHEN** the user inspects the anonymization toggle for that entity
- **THEN** the toggle is rendered as ON and is non-interactive (disabled, locked, or visually equivalent)
- **AND** the toggle's tooltip / accessibility description clearly states the entity is on the mandatoryAnonymization list and the decision cannot be overridden

#### Scenario: Toggle is overridable for blanket_consent_given

- **GIVEN** a publication-prep screen showing a `publicationConsent` with `consentStatus: "blanket_consent_given"`
- **WHEN** the user inspects the anonymization toggle for that entity
- **THEN** the toggle is rendered as OFF (do not anonymize) but is interactive
- **AND** the user can flip the toggle to ON to anonymize the entity anyway
- **AND** flipping the toggle records the override in the `publicationConsent` audit history

### Requirement: RBAC governs writes to both policy schemas

Writes to `mandatoryAnonymization` and `publicationAllowance` are governed by OpenRegister's standard schema-level authorization. There is no formal approval workflow at this version — privileged users may write directly. A separate change is tracked for adding two-eyes approval semantics.

#### Scenario: Unprivileged user cannot write to deny list

- **GIVEN** a user without write permission on the `mandatoryAnonymization` schema
- **WHEN** they attempt to POST a new record
- **THEN** the request is rejected with a 403 (or equivalent) per existing OpenRegister RBAC behavior

#### Scenario: Privileged user can write to deny list directly

- **GIVEN** a user with write permission on the `mandatoryAnonymization` schema
- **WHEN** they POST a valid record
- **THEN** the record is created
- **AND** the rule cache is invalidated and rebuilt

### Requirement: Out-of-scope behaviors MUST remain unchanged

This change MUST NOT modify:

- Match types `regex` or `reference` — both are deferred to a future change.
- Approval workflow on writes to either policy schema — separate change.
- Retroactive sweep of already-published documents — never touched.
- The OpenRegister codebase — the polymorphic-reference pattern via `items.oneOf` + `$ref` already exists.
- The `publicationConsent` workflow for entities that match no policy — the existing WOO flow runs unchanged.

#### Scenario: Existing WOO flow is unaffected for unmatched entities

- **GIVEN** an entity that matches no policy
- **WHEN** the entity is detected
- **THEN** the existing `publicationConsent` creation, notification, objection-deadline-setting, and decision flow runs identically to its pre-change behavior
- **AND** none of the new fields (`policyMatch`) are populated

#### Scenario: PublicationsController::attachments() is unchanged

- **WHEN** any consumer queries the existing publication / attachments endpoints
- **THEN** their behavior is unchanged by this capability
