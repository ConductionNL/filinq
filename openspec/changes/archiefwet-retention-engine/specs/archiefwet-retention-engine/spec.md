# archiefwet-retention-engine Specification (delta)

---
status: proposed
---

## Purpose

Archiefwet 1995 retention for DocuDesk records, built entirely on
OpenRegister's records-management stack (verified at OR HEAD ebedbdd5a):
selectielijst master data with VNG waardering semantics
(bewaren/vernietigen + termijn), retention categories and schedule
computation delegated to OR (trigger event + term via afleidingswijze),
a vernietigingslijst review/approval workflow ending in a verklaring van
vernietiging, a transfer-to-archive (overbrenging) state for permanent
records, and destruction-date propagation into the wave-1 Woo publication
pipeline and zaaksysteem bridge. DocuDesk computes nothing itself and adds
no pass-through controllers; it ships master data, schema configuration,
UI surfaces and one propagation rule.

## ADDED Requirements

### Requirement: Archief register hosts selectielijst and disposal-workflow homes (REQ-DDARE-001)

The app MUST declare an `archief` register in
`lib/Settings/docudesk_register.json` with three schemas, all
`hardValidation: true`, stored as OpenRegister objects (ADR-001):
`selectielijstEntry` (`categorie` string unique, `omschrijving`,
`bewaartermijn` ISO-8601 duration string, `archiefnominatie` enum
`bewaren` | `vernietigen`, `bron`, `toelichting`) matching exactly the field
contract OpenRegister's `RetentionService::lookupSelectielijstEntry()`
reads; `destructionList` and `destructionCertificate` matching the shapes
OR's `DestructionService` writes (list: `status`, `createdAt`,
`objectCount`, `objects[]`, `approvals[]`, `rejections[]`; certificate:
`type`, `destructionDate`, `approvers[]`, per-schema and per-category
counts, `complianceStatement`, `immutable`). The register version MUST be
bumped so `ConfigurationService::importFromApp()` imports it on boot.
Seeded `selectielijstEntry` objects MUST carry explicit placeholder
category codes (`TODO-*`) pending selectielijst-manager confirmation.

#### Scenario: Register import creates the archief schemas and seeds

- GIVEN a fresh Nextcloud instance with DocuDesk and OpenRegister installed
- WHEN the app boots and `ConfigurationService::importFromApp()` runs
- THEN the `archief` register exists with schemas `selectielijstEntry`, `destructionList` and `destructionCertificate`
- AND the seeded selectielijst entries are queryable via `ObjectService::searchObjects()` with `@self.register = archief`
- AND every seeded `categorie` value starts with `TODO-` (placeholder pending appraisal sign-off)
- @e2e exclude register import is a boot-time backend concern with no UI surface of its own — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Selectielijst entry field contract matches the OR reader

- GIVEN the shipped `selectielijstEntry` schema and OpenRegister's `lookupSelectielijstEntry()` field list (`categorie`, `omschrijving`, `bewaartermijn`, `archiefnominatie`, `bron`, `toelichting`)
- WHEN the schema's properties are compared against that list in the unit suite
- THEN every field the OR reader consumes exists on the schema with the documented type
- AND a drift (renamed or dropped field) breaks the unit suite instead of silently emptying lookups
- @e2e exclude declarative schema/consumer cross-check with no UI surface — covered by a PHPUnit drift-pin test (tests/unit/Settings/)

### Requirement: OR archival settings are wired through an explicit admin action (REQ-DDARE-002)

The DocuDesk admin settings MUST gain an Archiefbeheer section that displays
OpenRegister's current archival settings (via OR's
`GET /api/settings/archival`) and offers an explicit "Koppel
archiefregister" action that sets `selectielijstRegister`,
`selectielijstSchema`, `destructionListRegister`, `destructionListSchema`
and `archivalRegister` to the `archief` register's identifiers (via OR's
`PUT /api/settings/archival`). The app MUST NOT rewrite these settings
silently (no repair step, no boot hook): when the settings already point at
a different register the panel MUST show the current owner and require the
admin action to rewire.

#### Scenario: Admin wires the archief register

- GIVEN an admin on the DocuDesk Archiefbeheer settings section with OR archival settings unset
- WHEN they click "Koppel archiefregister" and confirm
- THEN OR's archival settings point at the `archief` register for selectielijst, destruction lists and certificates
- AND the panel reflects the wired state
- @e2e tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: Existing wiring is never silently overwritten

- GIVEN OR's `selectielijstRegister` already points at another register
- WHEN DocuDesk boots or the admin opens the panel without clicking the action
- THEN the settings are unchanged
- AND the panel shows which register currently owns the selectielijst wiring
- @e2e exclude negative boot-time assertion (no write occurs) — covered by PHPUnit asserting the app registers no repair step or boot hook that writes OR archival settings

### Requirement: Record schemas carry retention categories computed by OpenRegister (REQ-DDARE-003)

Retention stamping and schedule computation MUST be fully delegated to
OpenRegister: record schemas declare an `archive` configuration (`enabled:
true`, `classificatie` referencing a selectielijst `categorie`, an
`afleidingswijze` of `afgehandeld`, `termijn` or `eigenschap` with its
trigger field) so that OR populates `retention.archiefnominatie`,
`retention.classificatie`, `retention.bewaartermijn` and
`retention.archiefactiedatum` at object creation and recalculates the date
when the trigger field changes. DocuDesk MUST NOT contain any retention
date arithmetic, eligibility scanning or destruction execution code
(ADR-022/ADR-011); its `RetentionSurfaceService` only reads retention state
for display and implements the propagation rule of REQ-DDARE-007.

#### Scenario: Record creation stamps selectielijst-driven retention

- GIVEN the `correspondence` schema carries `archive` config with a `classificatie` resolving to a seeded selectielijst entry (`vernietigen`, `P7Y`)
- WHEN a correspondence record is created
- THEN its `retention` block carries `archiefnominatie: vernietigen`, the entry's `bewaartermijn`, the `classificatie` and a computed `archiefactiedatum`
- AND the values were computed by OpenRegister, not by DocuDesk code
- @e2e exclude backend stamping performed by OpenRegister on save — covered by PHPUnit integration-style tests on created objects (tests/unit/Service/RetentionSurfaceServiceTest.php)

#### Scenario: Trigger-event change recomputes the schedule

- GIVEN a record whose schema derives `archiefactiedatum` from a closure field
- WHEN the closure field value changes
- THEN OpenRegister recalculates `retention.archiefactiedatum` from the new trigger date plus the bewaartermijn
- AND no DocuDesk code writes the date
- @e2e exclude recalculation is OR-side save behaviour — covered by PHPUnit on a saved object with a changed trigger field

#### Scenario: No retention arithmetic in DocuDesk

- GIVEN the DocuDesk codebase at this change's completion
- WHEN `lib/` is inspected
- THEN no class computes archiefactiedatum, scans for destruction eligibility, executes destruction or generates certificates
- @e2e exclude static codebase property, enforced by review + a PHPUnit architecture test, not a browser flow

### Requirement: Vernietigingslijst review and approval surface (REQ-DDARE-004)

The app MUST provide an archivist-facing Archiefbeheer UI over
OpenRegister's destruction-workflow API: an index of vernietigingslijsten
(status, object count, created date), a detail view listing each proposed
object (title, schema, register, archiefactiedatum, selectielijst
category), full approval, partial approval with a mandatory per-object
exclusion reason, and rejection with a mandatory reason. All operations
MUST call OR's `/api/archival/destruction-lists*` endpoints directly from
the frontend; DocuDesk MUST NOT add pass-through controllers or a parallel
approval path, and MUST rely on OR's authorization (403 for
non-archivists) rather than UI hiding for access control.

#### Scenario: Archivist reviews and partially approves a vernietigingslijst

- GIVEN a destruction list with status `in_review` containing eligible records
- WHEN the archivist excludes one object with a reason and approves the remainder from the Archiefbeheer UI
- THEN OR's approve endpoint is called with `approve_partial`, the excluded UUID and its reason
- AND the list status shown in the UI reflects OR's response
- @e2e tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: Rejection requires a reason

- GIVEN a destruction list with status `in_review`
- WHEN the archivist chooses reject without entering a reason
- THEN the UI blocks the submission until a reason is provided
- AND on submission OR's reject endpoint receives the reason
- @e2e tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: No DocuDesk pass-through controllers

- GIVEN the DocuDesk routes and controllers at this change's completion
- WHEN `appinfo/routes.php` and `lib/Controller/` are inspected
- THEN no DocuDesk endpoint proxies OR's destruction-list or certificate operations
- @e2e exclude static codebase property (redundant-controller gate) — enforced by review + gates, not a browser flow

### Requirement: Verklaring van vernietiging is listed and permanent (REQ-DDARE-005)

The Archiefbeheer UI MUST list destruction certificates from OR's
`GET /api/archival/certificates` (date, approvers, totals per schema and
per selectielijst category, compliance statement). The
`destructionCertificate` and `destructionList` schemas MUST carry `archive`
configuration with `defaultNominatie: bewaren` so that workflow artifacts
are permanent records and can never appear on a destruction list
themselves.

#### Scenario: Certificate appears after execution

- GIVEN an approved destruction list that OR has executed
- WHEN the archivist opens the certificates view
- THEN the verklaring van vernietiging is listed with its destruction date, approvers and object counts
- @e2e tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: Workflow artifacts are bewaren records

- GIVEN the shipped `destructionList` and `destructionCertificate` schemas
- WHEN their `archive` configuration is inspected after import
- THEN both declare `defaultNominatie: bewaren`
- AND neither schema's objects are eligible for OR's destruction scan
- @e2e exclude declarative schema configuration — covered by PHPUnit register-import assertions

### Requirement: Transfer-to-archive state for permanent records (REQ-DDARE-006)

The app MUST surface records with `retention.archiefnominatie = bewaren`
whose `archiefactiedatum` has passed in the Archiefbeheer UI as awaiting
overbrenging. Marking a record as transferred MUST result in
`retention.archiefstatus = overgebracht`, after which OpenRegister rejects
updates with 409 `OBJECT_TRANSFERRED` and the DocuDesk UI presents the
record read-only with a transfer indicator. The actual e-depot packaging
and delivery are owned by the dependent `tmlo-mdto-metadata` change; for a
dossier as transfer unit, this engine defines the stamping semantics while
the dossier schema's own field additions are owned by the dossier
capability (sibling change — see design.md D4).

#### Scenario: Bewaren record past its actiedatum awaits overbrenging

- GIVEN a record with nominatie `bewaren` and an `archiefactiedatum` in the past
- WHEN the archivist opens the overbrenging view
- THEN the record is listed as awaiting transfer, never on a vernietigingslijst
- @e2e tests/e2e/workflows/archiefwet-retention.spec.ts

#### Scenario: Transferred record is read-only

- GIVEN a record with `retention.archiefstatus = overgebracht`
- WHEN a user attempts to edit it through the DocuDesk UI
- THEN the UI presents the record read-only with a transfer indicator
- AND a forced API write is rejected by OpenRegister with 409 `OBJECT_TRANSFERRED`
- @e2e exclude the 409 guard is OR-side — covered by PHPUnit asserting the surfaced read-only state; UI indicator covered in tests/e2e/workflows/archiefwet-retention.spec.ts

### Requirement: Destruction dates propagate to the publication pipeline with source precedence (REQ-DDARE-007)

`RetentionSurfaceService` MUST supply
`publicationRecord.destructionDate`/`destructionDateSource` (wave-1
`woo-publicatie-pipeline` fields, reused verbatim) using this precedence:
(1) a zaaksysteem-supplied vernietigingsdatum from the `zgw-document-bridge`
staging metadata wins, with the source system named in
`destructionDateSource`; (2) otherwise `retention.archiefactiedatum` of a
`vernietigen` record is used with `destructionDateSource` naming the
Archiefwet selectielijst category; (3) with neither, `destructionDate`
stays empty and publication is NOT blocked. Every propagation MUST append a
`destruction_date_propagated` entry to the wave-1 `publicationLogEntry`
trail, and downstream OpenCatalogi propagation keeps the wave-1 RET-003
`retentionNote` behaviour unchanged.

#### Scenario: Engine-computed date fills the publication record

- GIVEN a publication record for a document whose retention carries nominatie `vernietigen` and a computed `archiefactiedatum`
- WHEN the publication record is prepared for handoff
- THEN `destructionDate` equals the archiefactiedatum and `destructionDateSource` names the selectielijst category
- AND a `destruction_date_propagated` log entry is appended
- @e2e exclude backend propagation rule — covered by PHPUnit precedence-matrix tests (tests/unit/Service/RetentionSurfaceServiceTest.php)

#### Scenario: Zaaksysteem-supplied date takes precedence

- GIVEN a publication record for a bridge-staged document whose staging metadata carries a source-supplied vernietigingsdatum differing from the engine-computed date
- WHEN the publication record is prepared for handoff
- THEN `destructionDate` equals the zaaksysteem-supplied date and `destructionDateSource` names the source system
- @e2e exclude backend propagation rule — covered by PHPUnit precedence-matrix tests

#### Scenario: Absent date never blocks publication

- GIVEN a publication record for a document without retention metadata
- WHEN readiness is evaluated and handoff is attempted
- THEN the absence of a destruction date does not block the handoff
- AND `destructionDate` remains empty
- @e2e exclude backend propagation rule — covered by PHPUnit precedence-matrix tests

### Requirement: No auto-delete annotation on Archiefwet-controlled schemas (REQ-DDARE-008)

The app MUST NOT declare the `x-openregister-archival` annotation on any
schema whose objects are Archiefwet-controlled records (all schemas
carrying `archive` configuration with a selectielijst `classificatie`) —
that annotation is unusable for records because OR's
hourly sweep deletes annotated rows without any vernietigingslijst
approval (verified at OR HEAD). The annotation remains permitted only for
operational-log schemas, and any remaining annotation in
`docudesk_register.json` MUST use the object shape OR HEAD validates
(`{"retention": {"default": "<ISO-8601>"}}`), never the legacy bare-string
shape.

#### Scenario: Record schemas carry no auto-delete annotation

- GIVEN the shipped `docudesk_register.json` at this change's completion
- WHEN every schema with `archive.classificatie` is checked for `x-openregister-archival`
- THEN none declares the annotation
- @e2e exclude declarative register-content rule — covered by a PHPUnit register-lint test (tests/unit/Settings/)

#### Scenario: Remaining annotations use the validated object shape

- GIVEN the shipped `docudesk_register.json`
- WHEN every `x-openregister-archival` occurrence is validated against OR's annotation shape (`retention` object with `default`)
- THEN each occurrence parses without validation errors
- AND no bare-string `retention` value remains
- @e2e exclude declarative register-content rule — covered by a PHPUnit register-lint test
