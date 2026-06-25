---
status: done
---

# entity-publication-policies Specification

## Purpose
Defines the `publicationProhibition` schema in the consent register, an entity-level deny-list where each rule names a person or organisation that must always be anonymised when detected in a publication-bound document, regardless of any consent. Rules carry match criteria (exact, normalized, BSN, KVK), legal authority, severity, validity window, and active state, and are importable on app install and queryable via the OpenRegister API. This provides the policy data that anonymisation and publication-clearance flows match detected entities against.
## Requirements
### Requirement: A `publicationProhibition` schema MUST exist in the consent register

The `publicationProhibition` schema MUST be defined in the consent register and MUST be importable on app install. It represents an entity-level deny-list rule: each record describes one real-world person or organization that MUST always be anonymized when detected in a publication-bound document, regardless of any per-document consent process or any standing consent that might otherwise apply. A prohibition is not a consent — it asserts the absence of permission to publish unredacted, with prohibition-specific metadata.

#### Scenario: Schema is registered on app install

- **GIVEN** a fresh DocuDesk install
- **WHEN** the app's register configuration is imported
- **THEN** the `consent` register contains a `publicationProhibition` schema with the documented properties: `primaryName`, `entityType`, `matchRules`, `reason`, `legalAuthority`, `caseReference`, `severity`, `jurisdiction`, `addedBy`, `validFrom`, `validUntil`, `active`, `notes`

#### Scenario: Schema is queryable via OpenRegister API

- **GIVEN** the schema has been imported
- **WHEN** an authorized user queries `GET /api/objects/{registerId}/{schemaId}`
- **THEN** they receive a list of `publicationProhibition` objects (empty by default, populated as rules are added)

### Requirement: `publicationProhibition` MUST support the v1 match rule types

`matchRules` MUST be an array of `{ type, value }` objects. The supported `type` values at v1 MUST be exactly: `exact`, `normalized`, `bsn`, `kvk`. The types `regex` and `reference` MUST be rejected at write time and are explicitly out of scope for this change.

#### Scenario: Exact match

- **GIVEN** a `publicationProhibition` rule with `matchRules: [{ type: "exact", value: "Jan Janssen" }]`
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

### Requirement: Detection-time matching MUST evaluate prohibitions before standing consents

Inside a publication-clearance flow, for every detected PERSON or ORGANIZATION entity, the consent service MUST consult the `publicationProhibition` cache first. If a prohibition matches, the service MUST NOT consult the standing-consent cache and MUST NOT enter the WOO workflow. The order is: prohibition match → standing consent match → fall through to existing WOO workflow.

#### Scenario: No policy match falls through to WOO workflow

- **GIVEN** an entity that matches no `publicationProhibition` and no active `scope: "entity"` `publicationConsent`
- **WHEN** the entity is detected during publication preparation
- **THEN** a `publicationConsent` record is created with `scope: "document"`, `consentStatus: "pending"`, and the WOO notification workflow starts
- **AND** the `policyMatch` field on the record is null

#### Scenario: Prohibition match short-circuits

- **GIVEN** an entity matching a `publicationProhibition` rule with UUID `R-PROHIBIT-1`
- **WHEN** the entity is detected during publication preparation
- **THEN** a `publicationConsent` record is created with `scope: "document"`, `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `objectionDeadline: null`, and `policyMatch` referencing `R-PROHIBIT-1`
- **AND** no notification is sent
- **AND** the WOO workflow does not run

#### Scenario: Standing consent match short-circuits when no prohibition match

- **GIVEN** an entity matching an active `scope: "entity"` `publicationConsent` with UUID `R-STANDING-1` and matching no `publicationProhibition` rule
- **WHEN** the entity is detected during publication preparation
- **THEN** a `publicationConsent` record is created with `scope: "document"`, `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `publicationDecision: "publish_with_consent"`, and `policyMatch` referencing `R-STANDING-1`
- **AND** no notification is sent

### Requirement: Conflict resolution MUST be prohibition-wins, deterministically

When a detected entity matches BOTH a `publicationProhibition` rule and an active `scope: "entity"` `publicationConsent` record, the resulting per-document record MUST resolve to `consentStatus: "anonymized"` with `policyMatch` referencing the prohibition. The matching standing consent MUST be recorded in audit logs but MUST NOT be referenced in the per-document record's `policyMatch` field. There MUST NOT be a configuration option to invert this rule.

#### Scenario: Both surfaces match — prohibition wins

- **GIVEN** an entity matching prohibition `R-PROHIBIT-2` AND standing consent `R-STANDING-2`
- **WHEN** the entity is detected during publication preparation
- **THEN** the resulting per-document `publicationConsent` has `scope: "document"`, `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, and `policyMatch` references `R-PROHIBIT-2`
- **AND** the audit log records both `R-PROHIBIT-2` and `R-STANDING-2` as matching rules for the detection event
- **AND** the system does not surface a "configurable" preference to override this behavior

#### Scenario: Multiple prohibitions match — deterministic precedence

- **GIVEN** an entity matching two `publicationProhibition` rules `R-PROHIBIT-3` and `R-PROHIBIT-4`
- **WHEN** the entity is detected during publication preparation
- **THEN** the resulting per-document `publicationConsent.policyMatch` references the rule with the lower UUID (lexicographic ordering) for deterministic test outcomes
- **AND** the audit log records both `R-PROHIBIT-3` and `R-PROHIBIT-4`

### Requirement: Detector MUST consult an in-memory rule cache

The matching service MUST NOT issue a database query per detected entity. Instead, it MUST load all `active: true` `publicationProhibition` records and all `active: true` `scope: "entity"` `publicationConsent` records (with current time bounds open) into an in-memory lookup index at service init or on rule-mutation event. The lookup MUST be O(1) per `(matchType, entityType, value)` tuple.

#### Scenario: Page-level batch detection issues no per-entity rule queries

- **GIVEN** a document with 30 detected entities and a combined prohibition + standing-consent set of 100 active records total
- **WHEN** the consent service processes the document
- **THEN** the rule cache is queried in memory (no database calls per entity for matching)
- **AND** detection performance does not scale linearly with the number of rules

#### Scenario: Cache invalidation on rule change

- **GIVEN** the rule cache is loaded
- **WHEN** an admin creates, updates, or deletes a `publicationProhibition` record
- **THEN** an object-changed event triggers cache invalidation for the prohibition portion of the cache
- **AND** the next detection rebuilds that portion from the current rule set

#### Scenario: Cache invalidation filters publicationConsent events by scope

- **GIVEN** the rule cache is loaded
- **WHEN** an object-changed event fires for a `publicationConsent` record with `scope: "document"` (i.e., a normal workflow operation)
- **THEN** the standing-consent portion of the cache is NOT invalidated
- **AND** when an event fires for a record with `scope: "entity"`, the standing-consent portion IS invalidated

### Requirement: Time bounds MUST be honored at match time

A rule with `validFrom` in the future or `validUntil` in the past MUST NOT match, even if `active: true`. A rule with `active: false` MUST NOT match regardless of time bounds. This applies to both `publicationProhibition` records and `scope: "entity"` `publicationConsent` records.

#### Scenario: Rule with future validFrom does not match

- **GIVEN** a `publicationProhibition` rule with `validFrom: "2030-01-01T00:00:00Z"`, `active: true`
- **WHEN** an entity matching the rule is detected today during publication preparation
- **THEN** the rule does NOT match
- **AND** detection falls through to standing-consent / WOO workflow

#### Scenario: Rule with past validUntil does not match

- **GIVEN** a `publicationProhibition` rule with `validUntil: "2020-01-01T00:00:00Z"`, `active: true`
- **WHEN** an entity matching the rule is detected today
- **THEN** the rule does NOT match

#### Scenario: Rule with active false does not match

- **GIVEN** a `publicationProhibition` rule with `active: false` and otherwise-valid time bounds
- **WHEN** an entity matching the rule is detected
- **THEN** the rule does NOT match

#### Scenario: Standing consent with past validUntil does not match

- **GIVEN** a `scope: "entity"` `publicationConsent` with `validUntil: "2020-01-01T00:00:00Z"`, `active: true`
- **WHEN** an entity matching the standing consent is detected today
- **THEN** the standing consent does NOT match
- **AND** detection falls through to the WOO workflow

### Requirement: Adding a prohibition MUST force-resolve in-flight per-document records

When a `publicationProhibition` record is created (or an existing one is updated to `active: true` for matching entities, including by adding a new `matchRule`, flipping `active` to true, or extending `validUntil`), all in-flight `scope: "document"` `publicationConsent` records (any non-terminal status: `pending`, `consent_given`, `objection_received`, `no_response`) for matching entities MUST be force-resolved.

#### Scenario: New prohibition retroactively resolves matching in-flight records

- **GIVEN** an in-flight `scope: "document"` `publicationConsent` record with `consentStatus: "pending"` for entity "Jan Janssen"
- **AND** no prohibition currently matches
- **WHEN** an admin creates a new `publicationProhibition` rule with `matchRules: [{ type: "exact", value: "Jan Janssen" }]`
- **THEN** the existing in-flight record is updated: `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `policyMatch` references the new rule
- **AND** any pending notification for that record is canceled
- **AND** the audit log records the retroactive update with timestamp and triggering rule UUID

#### Scenario: New prohibition retroactively resolves a record in objection_received state

- **GIVEN** an in-flight `scope: "document"` `publicationConsent` record with `consentStatus: "objection_received"` for entity "Jan Janssen"
- **WHEN** an admin creates a new `publicationProhibition` rule matching "Jan Janssen"
- **THEN** the existing record transitions to `consentStatus: "anonymized"`, `policyMatch` populated
- **AND** the existing `notificationSentAt` and `objectionReceivedAt` timestamps are preserved for audit
- **AND** `objectionDeadline` is cleared

### Requirement: Adding a standing consent MUST NOT alter in-flight records

When a `scope: "entity"` `publicationConsent` record is created (or updated to `active: true`), in-flight `scope: "document"` records for matching entities MUST be left untouched. The standing consent applies only to NEW detections after the rule was added.

#### Scenario: New standing consent does not override existing objection

- **GIVEN** an in-flight `scope: "document"` `publicationConsent` record with `consentStatus: "objection_received"` for entity "M. Jansen"
- **WHEN** an admin creates a new `scope: "entity"` `publicationConsent` matching "M. Jansen"
- **THEN** the existing per-document record is unchanged
- **AND** the standing consent applies to future detections of "M. Jansen" only

### Requirement: Rule removal or expiry MUST NOT alter past records

When a prohibition or standing consent is deleted, deactivated, or expires (`validUntil` reached), `scope: "document"` `publicationConsent` records that were resolved by it MUST keep their final state. Future detections fall through to the next layer.

#### Scenario: Prohibition deletion does not retroactively unmask past records

- **GIVEN** a `scope: "document"` `publicationConsent` record with `consentStatus: "anonymized"` and `policyMatch` referencing prohibition `R-PROHIBIT-X`
- **WHEN** rule `R-PROHIBIT-X` is deleted
- **THEN** the existing record remains `consentStatus: "anonymized"`
- **AND** the `policyMatch` reference becomes a dangling reference (the OpenRegister referential integrity behavior governs how this is surfaced — but the record's status is not changed)
- **AND** future detections of the same entity fall through to standing-consent / WOO workflow

### Requirement: Already-published documents MUST NOT be retroactively modified

Rule changes (additions, updates, removals) MUST NOT trigger any modification to `publicationConsent` records linked to documents whose publication is complete. Audit reports MAY surface a list of "documents containing now-prohibited entities" for human review, but the system MUST NOT initiate automatic redaction or republication.

#### Scenario: New prohibition does not republish past documents

- **GIVEN** a published document containing entity "Jan Janssen" anonymized at publication time per the WOO workflow
- **AND** a new prohibition for "Jan Janssen" is added
- **THEN** the published document is unchanged
- **AND** any past `publicationConsent` records for that document are unchanged
- **AND** the system MAY emit an audit event flagging the document for human review

### Requirement: UI toggle behavior MUST be derived from `policyMatch` referent type

The frontend per-entity anonymization toggle on the publication-prep screen MUST derive its default state and override-permission from the type of the referent of `policyMatch`, NOT from `consentStatus`. This decouples the toggle's behavior from any future changes to the consent enum and uses the polymorphic reference as the single source of truth for "this record was pre-empted by a policy".

| `policyMatch` referent | Toggle default | User can override? |
|---|---|---|
| `null` | per existing UX based on `consentStatus` | yes |
| `publicationProhibition` | **on, locked** | **no** |
| `publicationConsent` (scope=entity) | **off, defaulted** | **yes (override-up to anonymize anyway)** |

#### Scenario: Toggle is locked when policyMatch references a prohibition

- **GIVEN** a publication-prep screen showing a `scope: "document"` `publicationConsent` whose `policyMatch` references a `publicationProhibition` record
- **WHEN** the user inspects the anonymization toggle for that entity
- **THEN** the toggle is rendered as ON and is non-interactive (disabled, locked, or visually equivalent)
- **AND** the toggle's tooltip / accessibility description clearly states the entity is on the publicationProhibition list and the decision cannot be overridden

#### Scenario: Toggle is overridable when policyMatch references a standing consent

- **GIVEN** a publication-prep screen showing a `scope: "document"` `publicationConsent` whose `policyMatch` references a `scope: "entity"` `publicationConsent` record
- **WHEN** the user inspects the anonymization toggle for that entity
- **THEN** the toggle is rendered as OFF (do not anonymize) but is interactive
- **AND** the user can flip the toggle to ON to anonymize the entity anyway
- **AND** flipping the toggle records the override by setting `publicationDecision: "anonymize"` while preserving `consentStatus: "consent_given"` and `policyMatch`
- **AND** the override is captured in the per-document record's audit history

### Requirement: Three separate admin surfaces MUST exist

The frontend MUST provide three distinct admin pages, each addressing one operational concern. They MUST NOT be conflated into one consolidated screen.

| Page | Filter | Purpose |
|---|---|---|
| Consent Workflow | `publicationConsent` where `scope: "document"` | Per-document workflow records — the existing surface, extended with a "policy pre-empted" indicator on rows whose `policyMatch` is non-null. |
| Standing Publication Consents | `publicationConsent` where `scope: "entity"` | List, edit, expire, revoke standing consents. The create-form requires `consentMethod` and surfaces a UI warning when `validUntil` is left blank. |
| Publication Prohibitions | all `publicationProhibition` records | CRUD for prohibitions. The create-form encourages adding stable identifiers (BSN/KvK) and warns when only a name-based rule is added. |

#### Scenario: Standing Publication Consents page filters by scope

- **GIVEN** a mix of `publicationConsent` records (some with `scope: "document"`, some with `scope: "entity"`)
- **WHEN** the user opens the "Standing Publication Consents" admin page
- **THEN** only records with `scope: "entity"` are listed
- **AND** records with `scope: "document"` do not appear on this page

#### Scenario: Consent Workflow page filters by scope

- **GIVEN** a mix of `publicationConsent` records
- **WHEN** the user opens the "Consent Workflow" admin page
- **THEN** only records with `scope: "document"` are listed

#### Scenario: Publication Prohibitions page is the only surface for prohibition records

- **GIVEN** at least one `publicationProhibition` record
- **WHEN** the user navigates the admin UI
- **THEN** prohibition records are visible on the "Publication Prohibitions" page only
- **AND** they do not appear on either of the consent pages

#### Scenario: Standing consent create form requires consentMethod

- **GIVEN** the user is on the "Standing Publication Consents" page and clicks "Add"
- **WHEN** they attempt to submit the form without selecting a `consentMethod`
- **THEN** the form blocks submission and surfaces a validation error
- **AND** when `validUntil` is left blank, the form surfaces a non-blocking warning recommending an explicit term

### Requirement: RBAC MUST govern writes to both policy surfaces

Writes to `publicationProhibition` records and to `scope: "entity"` `publicationConsent` records MUST be governed by OpenRegister's standard schema-level authorization, augmented by service-level enforcement for the scope-discriminated case. There MUST be no formal approval workflow at this version — privileged users MAY write directly. A separate change is tracked for adding two-eyes approval semantics.

#### Scenario: Unprivileged user cannot write to prohibitions

- **GIVEN** a user without write permission on the `publicationProhibition` schema
- **WHEN** they attempt to POST a new record
- **THEN** the request is rejected with a 403 (or equivalent) per existing OpenRegister RBAC behavior

#### Scenario: Privileged user can write to prohibitions directly

- **GIVEN** a user with write permission on the `publicationProhibition` schema
- **WHEN** they POST a valid record
- **THEN** the record is created
- **AND** the rule cache is invalidated and rebuilt

#### Scenario: Standing-consent write requires standing-consent permission

- **GIVEN** a user with write permission on `publicationConsent` for `scope: "document"` only (i.e., the consent-officer role) and NOT for `scope: "entity"`
- **WHEN** they attempt to POST a `publicationConsent` record with `scope: "entity"`
- **THEN** the consent service rejects the write with a 403-equivalent error citing missing standing-consent permission
- **AND** the same user CAN still write `scope: "document"` records normally

### Requirement: Out-of-scope behaviors MUST remain unchanged

This change MUST NOT modify:

- Match types `regex` or `reference` — both are deferred to a future change.
- Approval workflow on writes to either policy surface — separate change.
- Retroactive sweep of already-published documents — never touched.
- The OpenRegister codebase — the polymorphic-reference pattern via `items.oneOf` + `$ref` already exists.
- The publication-prep flow that calls `createConsentRequest()` — separate change. This capability assumes the entry point and specifies what it does when called.
- Generic anonymisation flows (file sanitisation not destined for publication) — these do not invoke `createConsentRequest()` and therefore do not create `publicationConsent` records or pre-empt any workflow. They MAY read the `publicationProhibition` list as a data source for safety checks (e.g. the prohibition gate specced in `anonymisation-prohibition-gate`); read access to a register is not workflow integration.
- The WOO workflow for entities that match no policy — runs unchanged for `scope: "document"` records with `policyMatch: null`.

#### Scenario: Existing WOO flow is unaffected for unmatched entities

- **GIVEN** an entity that matches no policy
- **WHEN** the entity is detected during publication preparation
- **THEN** the existing `publicationConsent` creation, notification, objection-deadline-setting, and decision flow runs identically to its pre-change behavior
- **AND** none of the new fields (`policyMatch`) are populated
- **AND** the resulting record has `scope: "document"` (the default)

#### Scenario: Generic anonymisation is unaffected

- **GIVEN** a generic anonymisation flow (e.g. file sanitisation prior to email or storage) that does not call `ConsentService::createConsentRequest()`
- **WHEN** entities are detected and anonymised in that flow
- **THEN** no `publicationConsent` records are created
- **AND** no policy-cache lookups occur
- **AND** prohibition and standing-consent records are not consulted

