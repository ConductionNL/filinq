## Why

Filinq owns the "document" domain across the Conduction fleet, yet a document
today is only ever a **file plus derived analysis** — an OpenRegister File
Attachment enriched with `x-openregister-calculations` (risk score, OCR
confidence, anonymisation coverage). The `document` register (title "Document
Register") holds correspondence audit logs, huisstijl config, batch-job
tracking, financial extractions and GL bookings — but **no first-class,
governed document object**. There is nowhere to record a document's business
identifier, its classification (informatieobjecttype), its confidentiality
level, its retention class, or its relations to the cases and objects it
belongs to. Consumers who want "attach this document to that case/object", "list
all documents of type X", or "which documents fall under selectielijst category
3.2" have no queryable object to hit.

Demand for exactly this is the strongest signal in the intelligence-db tender
corpus (research-A5 of the 2026-07-23 OpenRegister market-gap synthesis, W6
row): the top recurring themes are **document-type management**
(informatieobjecttype), **attach-document-to-object**, **classification**, and
**retention**. Per the fleet rule that *content types live in leaf apps while
OpenRegister provides storage and abstraction* (ADR-001, ADR-022), the document
content type belongs in Filinq, not in OpenRegister core.

## What Changes

Add two schemas to Filinq's existing `document` register in
`lib/Settings/filinq_register.json`, making documents first-class governed
objects:

- A **`document`** schema — the governed document object (VNG ZGW/DRC
  *EnkelvoudigInformatieObject* equivalent). Carries business metadata
  (`title`, `identifier`, `description`, `author`, `language`, `format`,
  key dates), a lifecycle-governed `status` (concept → in review → definitive →
  archived), a `confidentiality` level (VNG *vertrouwelijkheidaanduiding*:
  openbaar / intern / vertrouwelijk / geheim), a `documentType` classification
  reference, `relatedCases` / `relatedObjects` relations, and a
  `retentionClass` that binds it to an archival category.
- A **`documentType`** schema — the classification vocabulary (VNG ZTC
  *InformatieObjectType* equivalent). Carries `name`, `description`,
  `identifier`, `category`, a default `retentionPeriod` (ISO-8601 duration,
  e.g. `P7Y`), a `selectielijstCategory` (Archiefwet 1995 reference), a default
  `confidentiality`, and an `active` flag. Seeded with a canonical set of Dutch
  government document types.

Additional mechanics:

- The `document` schema declares `hardValidation: true`, an
  `x-openregister-lifecycle` on `status`, an `x-openregister-archival`
  annotation for the default retention, and `configuration.objectNameField:
  "title"` so list UIs render the document title.
- File binding uses OpenRegister's native `@self.folder` / file-attachment
  contract — no new Filinq endpoint. Attaching a document to a case/object is
  done through `relatedCases` / `relatedObjects` slug/UUID arrays plus the
  `$ref` cross-register convention already used by `correspondence.caseReference`
  (`$ref: "case"`, `x-external-register: "procest"`).
- The register `info.version` is bumped so `SettingsInitializer::initialize()`
  re-imports the config idempotently via `ConfigurationService::importFromApp()`
  on upgrade.
- `documentType` is seeded with a canonical starter set per ADR-016 (mandatory
  seed data), and a handful of realistic `document` objects reference them.

Not in scope (deliberate follow-ups):

- UI for a document list / detail surface (deferred to a `document-detail`
  frontend change — data model only here).
- Hard referential integrity on `documentType` / `relatedObjects` — string-slug
  references resolved at read time, per the `dossier-register` D1 precedent
  (opis/json-schema rejects internal `$ref` at register-import time).
- Auto-classification of inbound documents (already tracked by
  `inbound-auto-classification`).
- Migration of existing File-Attachment-based report data into `document`
  objects — a separate backfill change once the schema is live.

## Capabilities

### Modified Capabilities

- `document-register`: the existing capability (correspondence, huisstijl,
  batchCorrespondenceJob audit-log data model) gains new requirements defining
  the first-class `document` object and the `documentType` classification
  vocabulary, their lifecycle / retention / confidentiality annotations, the
  relation model to cases and objects, and the register version bump. All new
  requirements are **ADDED**; no existing requirement changes.

### New Capabilities

None. The document register already exists as a capability; this change extends
it rather than creating a parallel one.

## Impact

**Affected code (Filinq):**

- `lib/Settings/filinq_register.json` — add `document` and `documentType`
  under `components.schemas`; add both slugs to the `document` register's
  `schemas` array; add seed objects under `components.objects`; bump
  `info.version`.
- No PHP code change. All CRUD flows through OpenRegister's generic
  `/api/objects/{register}` routes and `ObjectEntityService`; Filinq does not
  add a `DocumentService`.

**Affected code (OpenRegister):** None. The `@self.folder` file binding, the
lifecycle runtime (`x-openregister-lifecycle`), the archival runtime
(`x-openregister-archival`), and the cross-register `$ref` convention all
already exist and are consumed here — not modified.

**Affected downstream apps:** Additive only. Procest (cases), OpenCatalogi
(publication) and other consumers gain a queryable `document` object to relate
to; nothing they depend on today changes.

**APIs / dependencies:** New endpoints surface automatically via OpenRegister's
generic object routes — `POST /api/objects/document`,
`GET /api/objects/documentType`, etc. No new OCS controllers.

**Data / migrations:** Filinq's `SettingsInitializer` / repair step re-imports
the register on the version bump; seed objects are upserted. No database
migration — everything lives in OpenRegister's `object` table.

**Architectural alignment:**

- ADR-001 / ADR-022: content type in the leaf app (Filinq); OpenRegister
  supplies storage and abstraction only.
- ADR-006 (Schema Standards): schema.org-aligned vocabulary, explicit types,
  OpenAPI 3.0.0 shape, Dutch property titles.
- ADR-013 (Loadable Register Templates): schemas + seed data ship in the
  `filinq_register.json` envelope.
- ADR-016 (Mandatory Seed Data): `documentType` seeded with canonical types;
  realistic `document` seeds reference them.
- ADR-031 (Schema-declarative logic): status is governed by
  `x-openregister-lifecycle`, retention by `x-openregister-archival` — not by
  ad-hoc service writes.
