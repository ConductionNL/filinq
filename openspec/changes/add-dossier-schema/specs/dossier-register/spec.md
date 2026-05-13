## ADDED Requirements

### Requirement: Dossier register exists in DocuDesk's register configuration

The system SHALL define a new `dossier` register in `lib/Settings/docudesk_register.json` under `components.registers`, alongside the existing `consent`, `signing`, `templates`, and `document` registers. The register SHALL be applied to OpenRegister on app install/upgrade via the existing `ConfigurationService::importFromApp()` path — no new loader code is required.

#### Scenario: Register is installed on fresh install
- **WHEN** DocuDesk is installed on a Nextcloud instance that has OpenRegister enabled
- **THEN** `RegistersLoader` creates a register with slug `dossier`, title "Dossier Register", and schemas `dossier` and `base`
- **AND** the register is visible via `GET /api/registers?_extend=schemas`

#### Scenario: Register is idempotent on upgrade
- **WHEN** DocuDesk is upgraded on an instance that already has the `dossier` register installed
- **THEN** the register is not duplicated; the loader upserts by slug
- **AND** existing dossier objects are preserved

### Requirement: Dossier schema captures folder-level anonymisation metadata

The system SHALL define a `dossier` schema with the following properties: `name` (string, required, mirrors folder name at creation), `description` (string, optional), `bases` (array of `$ref` to `base`, optional, no `minItems`), and `checkedOn` (date-time, optional). The schema SHALL be stored under `components.schemas.dossier` in `docudesk_register.json`, follow OpenRegister's OpenAPI 3.0.0 schema conventions (ADR-006), and MUST include `slug: "dossier"`, `title`, `description`, and `configuration.objectNameField: "name"` so list UIs render the stored name.

#### Scenario: A dossier can be created with all fields set
- **WHEN** a client posts `POST /api/objects/dossier` with `name`, `description`, `bases: [<base-uuid>]`, `checkedOn`, and `@self.folder` set to an existing folder node ID
- **THEN** the response is 201 with a `uuid`, and subsequent `GET /api/objects/dossier/<uuid>` returns the same data

#### Scenario: A dossier can be created with only the required name
- **WHEN** a client posts `POST /api/objects/dossier` with only `name` and `@self.folder` set
- **THEN** the response is 201, `bases` is stored as `[]` (or absent), and `checkedOn` is `null` (or absent)

#### Scenario: Missing required name is rejected
- **WHEN** a client posts `POST /api/objects/dossier` without a `name`
- **THEN** the response is a validation error citing the missing required property

### Requirement: Base schema defines the Dutch Woo Art. 5 grondslagen vocabulary

The system SHALL define a `base` schema with the following properties: `name` (string, required, end-user-facing Dutch label), `description` (string, required, Dutch explanation referencing Woo Art. 5 and where relevant AVG articles). The schema SHALL be stored under `components.schemas.base` in `docudesk_register.json`, include `slug: "base"` and `configuration.objectNameField: "name"`, and be seeded with the six canonical Woo Art. 5 uitzonderingsgronden (see Seed Data in design.md). The seeded objects are not enforced as immutable in v1 — OpenRegister does not currently gate instance writes by a schema/object flag; the contract is operator-discipline + audit-log.

#### Scenario: All six canonical grondslagen are installed on fresh install
- **WHEN** DocuDesk is installed on a clean Nextcloud instance
- **THEN** `GET /api/objects/base` returns exactly six seed objects with slugs `persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`, `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`, and `nationale-veiligheid`

#### Scenario: Tenants can add custom grondslagen alongside the seeded ones
- **WHEN** a client posts `POST /api/objects/base` with a new `name` and `description` (and no collision with seed slugs)
- **THEN** the object is created, is editable, and is visible alongside the six seed objects

### Requirement: `bases` references are resolved via OpenRegister's `$ref` mechanism

The `dossier.bases` field SHALL use OpenRegister's `$ref` syntax on the `items` key (`items.$ref: "#/components/schemas/base"`) so `ReferentialIntegrityService::extractTargetRef` walks the array and validates each referenced `base` UUID. Referential integrity SHALL prevent deletion of a `base` object while any `dossier` references it.

#### Scenario: Referencing a valid base succeeds
- **WHEN** a client creates a dossier with `bases: [<uuid-of-persoonsgegevens>]`
- **THEN** the dossier is created and the base is reachable by expanding `bases` in a subsequent read

#### Scenario: Referencing a non-existent base fails validation
- **WHEN** a client creates a dossier with `bases: ["00000000-0000-0000-0000-000000000000"]`
- **THEN** OpenRegister's referential-integrity check rejects the write with a validation error identifying the invalid reference

#### Scenario: Deleting a referenced base is blocked
- **GIVEN** a dossier exists with `bases: [<uuid-of-persoonsgegevens>]`
- **WHEN** a client attempts `DELETE /api/objects/base/<uuid-of-persoonsgegevens>`
- **THEN** OpenRegister refuses the deletion and reports at least one referencing dossier

### Requirement: Dossier objects bind to a Nextcloud folder via `@self.folder`

A dossier SHALL be attached to a Nextcloud folder by setting the `@self.folder` metadata field to the folder's node ID (as a string) in the POST/PUT payload. The system SHALL reuse OpenRegister's existing `@self.folder` handling (`FolderManagementHandler::createObjectFolderById` returns the existing folder when `ObjectEntity::getFolder()` is non-empty) — no new DocuDesk endpoint, service, or PHP code is required for the binding.

#### Scenario: Creating a dossier with an existing folder ID binds to that folder
- **WHEN** a client posts `POST /api/objects/dossier` with `@self.folder: "<existing-folder-node-id>"`
- **THEN** the created dossier's stored `folder` matches the supplied node ID, no new folder is created, and the dossier is readable at `GET /api/objects/dossier/<uuid>`

#### Scenario: `@self.folder` can be updated on an existing dossier
- **WHEN** a client PUTs a dossier with a different `@self.folder` value
- **THEN** the stored folder reference is updated to the new node ID without side-effects on other fields

### Requirement: `checkedOn` updates are audit-logged by actor and timestamp

Because DocuDesk relies on OpenRegister's audit trail for review-history (design.md D3), every update that changes the `checkedOn` field SHALL produce an entry in the audit trail that identifies the acting user and the timestamp of the change. This requirement is satisfied by OpenRegister's existing audit-trail mapper; no DocuDesk code is needed, but the scenario SHALL be verified to prevent regressions.

#### Scenario: Setting `checkedOn` produces an audit-trail entry with the actor
- **WHEN** user `alice` PUTs a dossier with `checkedOn: "2026-04-23T10:00:00+00:00"`
- **THEN** `AuditTrailMapper::findByObject(<dossier-uuid>)` returns at least one entry with `actor = "alice"`, the new `checkedOn` value in the diff, and a timestamp at the moment of the write

#### Scenario: Reading a dossier exposes the last-reviewer via audit-trail lookup
- **GIVEN** a dossier has been reviewed by `alice` on 2026-04-23 and re-reviewed by `bob` on 2026-04-24
- **WHEN** the caller looks up the latest audit-trail entry for `checkedOn`
- **THEN** it identifies `bob` as the last reviewer with timestamp `2026-04-24T…`

### Requirement: Seed dossiers demonstrate the three ADR-016 personas

The `dossier` register SHALL ship with 3–5 seed dossier objects covering the municipality, consultancy, and travel-agency personas defined in ADR-016. At least one seed dossier SHALL have an empty `bases` array to demonstrate optionality, and at least one SHALL have `null` for `checkedOn` to demonstrate the unreviewed state. Seed dossiers SHALL reference `base` seed objects via their slugs (resolved by the loader to UUIDs).

#### Scenario: Seeded dossiers are visible after install
- **WHEN** DocuDesk is installed and `RegistersLoader` completes
- **THEN** `GET /api/objects/dossier` returns at least three seed objects with stable names matching the design.md seed list

#### Scenario: At least one seed dossier demonstrates the unreviewed state
- **WHEN** the caller inspects the seed dossier set
- **THEN** at least one seed has `bases: []` and `checkedOn: null`
