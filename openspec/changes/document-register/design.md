# Design: document-register

## Context

DocuDesk owns the document content type for the Conduction fleet (ADR-001,
ADR-022: content types live in leaf apps; OpenRegister supplies storage and
abstraction). Its `document` register today holds operational data
(correspondence audit logs, huisstijl config, batch-job tracking, financial
extractions) but no first-class governed *document* object. This change adds two
schemas — `document` and `documentType` — to the existing register envelope in
`lib/Settings/docudesk_register.json`, with zero PHP code and zero OpenRegister
change. All runtime behaviour (lifecycle, archival retention, file binding,
cross-register relations) is delegated to OpenRegister capabilities that already
exist at OR HEAD.

Verified against OpenRegister HEAD before authoring:
- `x-openregister-lifecycle` state-machine runtime — consumed for `status`.
- `x-openregister-archival` / schema `archive` config — consumed for retention.
- `@self.folder` + File Attachment contract — consumed for the file body.
- Cross-register `$ref` + `x-external-register` — already used by
  `correspondence.caseReference`; reused for `relatedCases`.
- `configuration.objectNameField` — controls list-title rendering.

## Goals / Non-goals

**Goals:** a queryable, governed document object with business metadata,
classification (informatieobjecttype), confidentiality, retention class, and
relations to cases/objects; a seeded classification vocabulary aligned to VNG
ZTC *InformatieObjectType*; idempotent re-import on version bump.

**Non-goals:** UI (deferred to a `document-detail` frontend change); hard
referential integrity on relations (string-slug references resolved at read
time, per the `dossier-register` precedent — opis/json-schema rejects internal
`$ref` at register-import); auto-classification of inbound documents (tracked by
`inbound-auto-classification`); backfill of existing File-Attachment report data
into `document` objects (separate change once the schema is live).

## Decisions

### D1 — `document` schema (VNG DRC *EnkelvoudigInformatieObject* aligned)

Fields (schema.org-aligned, Dutch titles per ADR-006):
`title` (string, required, name field), `identifier` (string, business key),
`description`, `author`, `language` (ISO 639-1), `format` (MIME), `creationDate`
/ `receiptDate` / `sendDate` (date), `status` (enum, lifecycle-governed:
`concept` → `in_bewerking` → `ter_vaststelling` → `definitief` → `gearchiveerd`),
`confidentiality` (enum VNG vertrouwelijkheidaanduiding:
`openbaar`|`intern`|`vertrouwelijk`|`geheim`, default `intern`),
`documentType` (string slug/uuid → `documentType`), `retentionClass` (string →
selectielijst category), `relatedCases` (array of string, `$ref: "case"` +
`x-external-register: "procest"`), `relatedObjects` (array of string, free
slug/uuid). `hardValidation: true`. `x-openregister-lifecycle` on `status`.
`x-openregister-archival` binding default retention (overridable per object via
`retentionClass`). `configuration.objectNameField: "title"`.

### D2 — `documentType` schema (VNG ZTC *InformatieObjectType* aligned)

Fields: `name` (required, name field), `description`, `identifier` (unique
business key), `category`, `retentionPeriod` (ISO-8601 duration, e.g. `P7Y`),
`selectielijstCategory` (Archiefwet 1995 reference string), `confidentiality`
(default enum, same domain as D1), `active` (boolean, default true).
`hardValidation: true`.

### D3 — relations resolved at read time, not enforced at import

`documentType`, `relatedObjects`, `relatedCases` are string references, not
internal JSON-Schema `$ref` to sibling schemas (which register-import rejects).
Consumers resolve them via a second object fetch. This matches the
`dossier-register` D1 precedent and keeps import validation green.

### D4 — idempotent re-import via version bump

Bump `document` register `info.version` in `docudesk_register.json`.
`SettingsInitializer::initialize()` → `ConfigurationService::importFromApp()`
re-imports on upgrade; seed objects upsert by slug. No DB migration.

### D5 — seed data (ADR-016)

Seed `documentType` with a canonical Dutch-gov starter set (e.g. `brief`,
`besluit`, `rapport`, `factuur`, `contract`, `notulen`, `beleidsstuk`), each
with a realistic `retentionPeriod` + placeholder `selectielijstCategory`
(`TODO-*` during authoring, replaced before apply — records-appraisal
sign-off, same posture as `archiefwet-retention-engine` task 1.4). Seed 3–5
`document` objects referencing them.

## Risks / Trade-offs

- **Retention placeholders**: `selectielijstCategory` codes are records-management
  master data; authoring uses `TODO-*` and an apply-blocker task requires real
  VNG codes before done (a seed-lint PHPUnit test fails on any `TODO-`).
- **Import drops `archive`/lifecycle keys**: pinned by an import-roundtrip unit
  test; if a key is dropped, file an OpenRegister issue (degradation is OR-side
  schema config, never a DocuDesk-side code workaround).
- **Relation integrity is soft**: acceptable for v1; hard FK is an explicit
  non-goal and a possible OR-core follow-up.

## Migration / Rollout

Additive. On upgrade the register re-imports; existing operational schemas
(`correspondence` etc.) are untouched. Downstream apps (Procest, OpenCatalogi)
gain a document object to relate to; nothing they depend on changes.
