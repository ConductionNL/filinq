# document-register Specification (delta)

---
status: proposed
---

## Purpose delta

The document register gains a first-class, governed **document** object and a
**documentType** classification vocabulary, so that documents become queryable
OpenRegister objects with business metadata, lifecycle-governed status,
confidentiality, archival retention, and relations to cases and objects — built
entirely on OpenRegister capabilities that already exist at OR HEAD (lifecycle
runtime, archival annotation, `@self.folder` file binding, cross-register
`$ref`). DocuDesk ships schemas + seed data only: no PHP code, no OpenRegister
change, no database migration.

## ADDED Requirements

### Requirement: Governed document object schema (REQ-DREG-D01)

The document register MUST declare a `document` schema in
`lib/Settings/docudesk_register.json` under `components.schemas`, aligned to the
VNG DRC *EnkelvoudigInformatieObject*, with `hardValidation: true` and
`configuration.objectNameField: "title"`.

The schema MUST carry business metadata (`title` required, `identifier`,
`description`, `author`, `language`, `format`, `creationDate`, `receiptDate`,
`sendDate`), a `status` property governed by an `x-openregister-lifecycle`
state machine over the states `concept` → `in_bewerking` → `ter_vaststelling`
→ `definitief` → `gearchiveerd`, a `confidentiality` enum
(`openbaar`|`intern`|`vertrouwelijk`|`geheim`, default `intern`), a
`documentType` reference, a `retentionClass` reference, and relation arrays
`relatedCases` (`$ref: "case"`, `x-external-register: "procest"`) and
`relatedObjects`.

The schema MUST declare an `x-openregister-archival` annotation providing a
default retention that is overridable per object via `retentionClass`.

The document body MUST bind through OpenRegister's native `@self.folder` /
File Attachment contract; the change MUST NOT add a DocuDesk document upload
endpoint.

#### Scenario: Create and govern a document

- **GIVEN** the document register is imported with the `document` schema
- **WHEN** a client POSTs a document to `/api/objects/document` with `title`,
  `documentType` and `confidentiality`
- **THEN** the object is created with `status` at the lifecycle initial state
  and its name renders as `title`
- **AND** transitioning `status` follows the `x-openregister-lifecycle`
  machine (illegal transitions are rejected)
- **AND** an `x-openregister-archival` retention is stamped at creation.

### Requirement: Document type classification vocabulary (REQ-DREG-D02)

The document register MUST declare a `documentType` schema in
`docudesk_register.json` under `components.schemas`, aligned to the VNG ZTC
*InformatieObjectType*, with `hardValidation: true`. It MUST carry `name`
(required, name field), `description`, `identifier` (unique business key),
`category`, `retentionPeriod` (ISO-8601 duration), `selectielijstCategory`
(Archiefwet 1995 reference), a default `confidentiality`, and an `active`
boolean (default true).

A `document.documentType` value MUST reference a `documentType` object by slug
or UUID; the reference is resolved at read time and is NOT enforced by an
internal JSON-Schema `$ref` at register-import.

#### Scenario: Classify a document by type

- **GIVEN** a seeded `documentType` `besluit` with `retentionPeriod: P7Y`
- **WHEN** a `document` is created with `documentType: "besluit"`
- **THEN** the reference resolves on read to the `besluit` type object
- **AND** the type's `retentionPeriod` and `selectielijstCategory` are
  available to consumers for retention decisions.

### Requirement: Seeded canonical document types and sample documents (REQ-DREG-D03)

The document register MUST seed, under `components.objects`, a canonical Dutch
government `documentType` starter set (at minimum `brief`, `besluit`, `rapport`,
`factuur`, `contract`, `notulen`, `beleidsstuk`), each with a realistic
`retentionPeriod`, and 3–5 realistic `document` objects that reference the
seeded types with distinct `status` and `confidentiality` values.

#### Scenario: Seed data present after import

- **GIVEN** a clean DocuDesk install or an upgrade
- **WHEN** `SettingsInitializer::initialize()` imports the register
- **THEN** the canonical `documentType` set and the sample `document` objects
  exist and are queryable via the generic object routes.

### Requirement: Selectielijst placeholders resolved before production (REQ-DREG-D04)

Any `selectielijstCategory` seeded as a `TODO-*` placeholder MUST be replaced
with a real VNG selectielijst category (records-appraisal sign-off) before the
change is applied/done. A seed-lint unit test MUST FAIL while any `TODO-`
category remains.

#### Scenario: Gate blocks placeholder categories

- **GIVEN** a seeded `documentType` still carries `selectielijstCategory:
  "TODO-brief"`
- **WHEN** the seed-lint test runs
- **THEN** it fails, blocking apply/done until real categories are supplied.

### Requirement: Idempotent register re-import on version bump (REQ-DREG-D05)

Adding the two schemas MUST include a bump of the `document` register's
`info.version` so `ConfigurationService::importFromApp()` re-imports the
configuration idempotently on upgrade. An import-roundtrip test MUST pin that
the imported `document` schema retains `hardValidation`, its
`x-openregister-lifecycle` block, its `x-openregister-archival` block, and
`configuration.objectNameField`. If the import path drops any key, an
OpenRegister issue MUST be filed rather than a DocuDesk-side workaround added.

#### Scenario: Upgrade re-imports without duplication

- **GIVEN** an existing install with the pre-change register
- **WHEN** the app upgrades to the bumped `info.version`
- **THEN** the two new schemas and their seeds are imported once (upsert by
  slug), with lifecycle/archival/config keys intact and no duplicate objects.

### Requirement: Documents relate to cases and objects (REQ-DREG-D06)

The `document` schema MUST support relating a document to one or more cases
(`relatedCases`) and arbitrary objects (`relatedObjects`) via string slug/UUID
arrays, enabling "attach this document to that case/object" and "list all
documents of type X" queries through OpenRegister's generic object search
without a bespoke DocuDesk endpoint.

#### Scenario: Attach a document to a case

- **GIVEN** a `document` object and a Procest `case`
- **WHEN** the document's `relatedCases` includes the case slug/UUID
- **THEN** a consumer can query documents by related case and resolve the
  reference at read time.
