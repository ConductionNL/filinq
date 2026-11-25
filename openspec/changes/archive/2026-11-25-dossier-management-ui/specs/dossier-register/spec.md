# dossier-register Specification (delta)

## Purpose

Adds an optional, declaratively-guarded `status` lifecycle and an optional
`documents[]` membership relation list to the existing `dossier` schema so the
dossier-management UI can render and transition dossier state
(`open → in-review → processed → published/closed`) and track multi-dossier
membership (one document in several dossiers, relaxing the strict
folder=dossier equivalence). The `base` grondslagen vocabulary, the
`@self.folder` home-folder binding, the `checkedOn` audit behaviour and the
seed set are unchanged; existing status-less, `documents`-less dossiers read
as `open` with their membership derived from the home folder alone.

## MODIFIED Requirements

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
