---
status: in-progress
---

# dossier-register Specification

**Status**: in-progress
**Scope**: filinq
**OpenSpec changes**:
- [dossier-management-ui](../../changes/dossier-management-ui/) _(active)_ — the `dossier` schema gains an optional `status` property governed by a declarative `x-openregister-lifecycle` (canonical `initial: open`; `open → in-review → processed → published/closed`); all other properties, the `base` vocabulary, folder binding and seeds unchanged (kind: code)

## Purpose

@e2e exclude Backend data-model spec for the dossier-domain schemas: register/schema import and idempotent upsert, strict property validation, the seeded Woo Art. 5 grondslagen vocabulary and slug→UUID resolution. No browser surface — the dossier UI is specified separately in the dossier-management-ui change. Covered by PHPUnit tests/unit/Settings/DossierRegisterConfigTest.php and tests/unit/Controller/DossierControllerTest.php.

Defines the `dossier` and `base` schemas that Filinq contributes to OpenRegister, carried by the consolidated `filinq` register: a `dossier` schema for folder-level anonymisation metadata (name, description, legal bases, and review date) and a `base` schema that encodes the Dutch Woo Art. 5 grondslagen vocabulary, seeded with the six canonical exception grounds. The register is installed and upserted idempotently via the existing configuration-import path on app install and upgrade. This gives folder-based anonymisation work a structured, queryable record of which legal grounds apply and when a dossier was last reviewed.

## Requirements

### Requirement: Dossier register exists in Filinq's register configuration

The system SHALL define the `dossier` and `base` schemas in `lib/Settings/filinq_register.json` under `components.schemas`, listed in the `schemas` array of the single `filinq` register declared under `components.registers`. (The former `consent`, `signing`, `templates`, `document`, `dossier` and `docudesk` registers were consolidated into that one register; `filinq` is now the only register slug this app declares.) The register SHALL be applied to OpenRegister on app install/upgrade via the existing `ConfigurationService::importFromApp()` path — no new loader code is required.

#### Scenario: Register is installed on fresh install
- **WHEN** Filinq is installed on a Nextcloud instance that has OpenRegister enabled
- **THEN** `RegistersLoader` creates a register with slug `filinq`, title "Filinq Register", whose `schemas` array includes `dossier` and `base`
- **AND** the register is visible via `GET /api/registers?_extend=schemas`

#### Scenario: Register is idempotent on upgrade
- **WHEN** Filinq is upgraded on an instance that already has the `filinq` register installed
- **THEN** the register is not duplicated; the loader upserts by slug
- **AND** existing dossier objects are preserved

### Requirement: Dossier schema captures folder-level anonymisation metadata

The system SHALL define a `dossier` schema under `components.schemas.dossier`
in `filinq_register.json` (OpenAPI 3.0.0, ADR-006) with `slug: "dossier"`,
`title`, `description` and `configuration.objectNameField: "name"`, and the
properties `name` (required, kept in sync with the home folder), and the
optional `description`, `bases`, `checkedOn`, `status` and `documents`, as
the scenarios below define. Existing objects SHALL NOT be migrated.

#### Scenario: A dossier can be created with all fields set
- **GIVEN** the dossier register has been installed and the six canonical seed `base` objects exist
- **WHEN** a client posts `POST /api/objects/dossier` with `name`, `description`, `bases: ["persoonsgegevens"]` (a known seed slug), `checkedOn`, and `@self.folder` set to an existing folder node ID
- **THEN** the response is 201 with a `uuid`, and subsequent `GET /api/objects/dossier/<uuid>` returns the same data including `bases: ["persoonsgegevens"]` (slug preserved verbatim, not resolved to UUID)
- @e2e exclude pre-existing register/API contract scenario carried over unchanged — covered by the existing register-import PHPUnit assertions and Newman collections

#### Scenario: A dossier can be created with only the required name
- **WHEN** a client posts `POST /api/objects/dossier` with only `name` and `@self.folder` set
- **THEN** the response is 201, `bases` is stored as `[]` (or absent), `checkedOn` is `null` (or absent), and `status` is absent (read by consumers as `open`)
- @e2e exclude pre-existing register/API contract scenario (extended for the optional `status`) — covered by register-import PHPUnit assertions

#### Scenario: Missing required name is rejected
- **WHEN** a client posts `POST /api/objects/dossier` without a `name`
- **THEN** the response is a validation error citing the missing required property
- @e2e exclude pre-existing validation contract carried over unchanged — covered by Newman register contract tests

#### Scenario: A membership reference is stored verbatim and shared across dossiers
- **GIVEN** two dossiers A and B and a file node reference `f`
- **WHEN** `f` is appended to both dossiers' `documents` arrays via full-payload saves
- **THEN** `GET` on each dossier returns `documents` containing `f` (stored verbatim), and neither save nulls the other dossier's fields
- @e2e exclude register/API contract for the new relation property — covered by register-import PHPUnit assertions and the membership scenarios in tests/e2e/workflows/dossier-management.spec.ts

#### Scenario: Lifecycle transitions are declaratively guarded
- **GIVEN** a dossier without a `status` value (treated as `open`)
- **WHEN** a client saves `status: "in-review"` and subsequently attempts `status: "closed"` directly
- **THEN** the first save succeeds and the second is rejected by OpenRegister's lifecycle guard (`in-review → closed` is not a declared transition)
- @e2e exclude server-side declarative lifecycle guard — covered by PHPUnit transition tests (tests/unit/Service/DossierManagementServiceTest.php); the UI transition happy path is covered in tests/e2e/spec-coverage/dossier-management.spec.ts

#### Scenario: The properties have their declared shapes
- **GIVEN** the shipped `dossier` schema
- **WHEN** its properties are read
- **THEN** `name` is a required string that mirrors the bound home-folder name at creation and is kept in sync on rename, `description` is an optional string, and `checkedOn` an optional date-time
- **AND** `bases` is an optional array of strings with no `minItems`, each the slug of a `base` object
- **AND** `status` is an optional string (the lifecycle state) and `documents` an optional array of strings with no `minItems`, each a Nextcloud file node reference recording an explicit membership
- @e2e exclude schema shape — covered by register-import PHPUnit assertions

#### Scenario: Membership without documents comes from the home folder
- **GIVEN** a dossier without `documents`
- **WHEN** its membership is read
- **THEN** it is the files in its bound `@self.folder` home folder alone
- **AND** with `documents` present, membership is the deduplicated union of the home-folder files and the references, and a reference whose target no longer exists is surfaced as a visible marker, never silently dropped
- @e2e exclude covered by PHPUnit (tests/unit/Service/DossierManagementServiceTest.php::testUnresolvableMembershipReferenceIsSurfaced)

#### Scenario: The lifecycle is declared on the schema
- **GIVEN** the shipped `dossier` schema
- **WHEN** its `x-openregister-lifecycle` annotation is read
- **THEN** it uses the canonical `initial` key (`initial: open`) with exactly the transitions `open → in-review`, `in-review → processed`, `in-review → open` (reopen), `processed → published`, `processed → closed` and `published → closed`
- **AND** OpenRegister's lifecycle guard rejects any out-of-order status write, and a dossier without `status` is treated by consumers as `open`
- @e2e exclude covered by PHPUnit transition tests (tests/unit/Service/DossierManagementServiceTest.php::testOnlyDeclaredTransitionsAreOffered, ::testAbsentStatusReadsAsOpen)

### Requirement: Base schema defines the Dutch Woo Art. 5 grondslagen vocabulary

The system SHALL define a `base` schema with the following properties: `name` (string, required, end-user-facing Dutch label), `description` (string, required, Dutch explanation referencing Woo Art. 5 and where relevant AVG articles). The schema SHALL be stored under `components.schemas.base` in `filinq_register.json`, include `slug: "base"` and `configuration.objectNameField: "name"`, and be seeded with the six canonical Woo Art. 5 uitzonderingsgronden (see Seed Data in design.md). The seeded objects are not enforced as immutable in v1 — OpenRegister does not currently gate instance writes by a schema/object flag; the contract is operator-discipline + audit-log.

#### Scenario: All six canonical grondslagen are installed on fresh install
- **WHEN** Filinq is installed on a clean Nextcloud instance
- **THEN** `GET /api/objects/base` returns exactly six seed objects with slugs `persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`, `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, and `nationale-veiligheid`

#### Scenario: Tenants can add custom grondslagen alongside the seeded ones
- **WHEN** a client posts `POST /api/objects/base` with a new `name` and `description` (and no collision with seed slugs)
- **THEN** the object is created, is editable, and is visible alongside the six seed objects

### Requirement: `bases` is a string-array of base slugs, resolved at runtime by consumers

The `dossier.bases` field SHALL be a JSON array of strings (`items.type: "string"`). Each element is the slug of a `base` object in the `filinq` register (e.g. `"persoonsgegevens"`).

OpenRegister's `$ref`-based referential-integrity walker (`ReferentialIntegrityService::extractTargetRef`) is NOT used here in v1: the register-config import path runs schemas through a strict JSON-schema validator (`opis/json-schema`) that rejects `#/components/schemas/<x>` references when the schema is validated in isolation. Storing slugs as plain strings sidesteps that constraint. Consumer apps (Filinq's `anonymisation-grondslagen-summary` is the first one) resolve the slug against the `base` schema at read time.

**v1 consequences of the slug-only model:**

- OpenRegister does NOT validate that the slug resolves to a real `base` object. Storing `"bases": ["does-not-exist"]` succeeds; downstream readers MUST handle the unresolvable case gracefully (e.g. render as "onbekende grondslag").
- OpenRegister does NOT block deletion of a `base` while a dossier references it. The seed bases are stable by operator-discipline + audit-log (per the `entity-relation-grondslagen` rework's analogous decision); tenant-created bases SHOULD NOT be deleted while in use, but this is not enforced at v1.

If hard referential integrity becomes a real need, a follow-up change can either (a) make OR's `$ref` shape pass through the JSON-schema validator (e.g. an OR-specific `or-ref` keyword) or (b) introduce a separate validation step that resolves slugs before write.

#### Scenario: Referencing a known base succeeds

- **WHEN** a client creates a dossier with `bases: ["persoonsgegevens"]`
- **THEN** the dossier is created with the slug stored verbatim
- **AND** consumer code reading the dossier MUST be able to resolve `"persoonsgegevens"` → the seeded `base` object via the `base` register

#### Scenario: Referencing an unknown slug is accepted by OR

- **WHEN** a client creates a dossier with `bases: ["does-not-exist"]`
- **THEN** the create succeeds and the slug is stored verbatim
- **AND** downstream consumers MUST render the unresolvable case gracefully (this is consumer-side responsibility, not OR's)

#### Scenario: Empty bases array is valid and distinct from absent

- **WHEN** a client creates a dossier with `bases: []`
- **THEN** the create succeeds and the row stores an empty array (semantically "no grondslagen yet")
- **AND** the dossier with `bases` absent from the payload behaves equivalently for v1 reading

### Requirement: Dossier objects bind to a Nextcloud folder via `@self.folder`

A dossier SHALL be attached to a Nextcloud folder by setting the `@self.folder` metadata field to the folder's node ID (as a string) in the POST/PUT payload. The system SHALL reuse OpenRegister's existing `@self.folder` handling (`FolderManagementHandler::createObjectFolderById` returns the existing folder when `ObjectEntity::getFolder()` is non-empty) — no new Filinq endpoint, service, or PHP code is required for the binding.

#### Scenario: Creating a dossier with an existing folder ID binds to that folder
- **WHEN** a client posts `POST /api/objects/dossier` with `@self.folder: "<existing-folder-node-id>"`
- **THEN** the created dossier's stored `folder` matches the supplied node ID, no new folder is created, and the dossier is readable at `GET /api/objects/dossier/<uuid>`

#### Scenario: `@self.folder` can be updated on an existing dossier
- **WHEN** a client PUTs a dossier with a different `@self.folder` value
- **THEN** the stored folder reference is updated to the new node ID without side-effects on other fields

### Requirement: `checkedOn` updates are audit-logged by actor and timestamp

Because Filinq relies on OpenRegister's audit trail for review-history (design.md D3), every update that changes the `checkedOn` field SHALL produce an entry in the audit trail that identifies the acting user and the timestamp of the change. This requirement is satisfied by OpenRegister's existing audit-trail mapper; no Filinq code is needed, but the scenario SHALL be verified to prevent regressions.

#### Scenario: Setting `checkedOn` produces an audit-trail entry with the actor
- **WHEN** user `alice` PUTs a dossier with `checkedOn: "2026-04-23T10:00:00+00:00"`
- **THEN** `AuditTrailMapper::findByObject(<dossier-uuid>)` returns at least one entry with `actor = "alice"`, the new `checkedOn` value in the diff, and a timestamp at the moment of the write

#### Scenario: Reading a dossier exposes the last-reviewer via audit-trail lookup
- **GIVEN** a dossier has been reviewed by `alice` on 2026-04-23 and re-reviewed by `bob` on 2026-04-24
- **WHEN** the caller looks up the latest audit-trail entry for `checkedOn`
- **THEN** it identifies `bob` as the last reviewer with timestamp `2026-04-24T…`

### Requirement: Seed dossiers demonstrate the three ADR-016 personas

The `filinq` register SHALL ship with 3–5 seed dossier objects covering the municipality, consultancy, and travel-agency personas defined in ADR-016. At least one seed dossier SHALL have an empty `bases` array to demonstrate optionality, and at least one SHALL have `null` for `checkedOn` to demonstrate the unreviewed state. Seed dossiers SHALL reference `base` seed objects via their slugs (resolved by the loader to UUIDs).

#### Scenario: Seeded dossiers are visible after install
- **WHEN** Filinq is installed and `RegistersLoader` completes
- **THEN** `GET /api/objects/dossier` returns at least three seed objects with stable names matching the design.md seed list

#### Scenario: At least one seed dossier demonstrates the unreviewed state
- **WHEN** the caller inspects the seed dossier set
- **THEN** at least one seed has `bases: []` and `checkedOn: null`

### Requirement: Grondslagen Summary Endpoint Is Reachable

The `POST api/anonymization/dossier/{dossierId}/grondslagen-pdf` route SHALL
resolve to a controller method that exists on `DossierController`. The route
entry in `appinfo/routes.php` MUST name the controller's actual public method
(`generateGrondslagenSummary`), not a stale or renamed method name.

#### Scenario: The route resolves without a ReflectionException

- GIVEN Filinq is installed with the `dossier` register configured
- AND a valid dossier UUID exists
- WHEN a client sends `POST /apps/filinq/api/anonymization/dossier/{dossierId}/grondslagen-pdf`
- THEN the request SHALL reach `DossierController::generateGrondslagenSummary()`
- AND the response SHALL be the generated file's metadata (or a handled error
  payload), never a router-level `ReflectionException` / HTTP 500

#### Scenario: Frontend regeneration action succeeds end-to-end

- GIVEN a user viewing `FolderAnonymizationView.vue` with "Append a
  grondslagen-summary page" enabled
- WHEN the user triggers the regeneration action
- THEN the underlying HTTP call SHALL succeed (not 500) and the dossier's
  `configuration.grondslagen.{fileId, lastGeneratedAt}` SHALL be updated
