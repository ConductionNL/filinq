# Design: woo-publicatie-pipeline

## Context

Verified at DocuDesk HEAD:

- `publicationConsent` (register `consent`) carries `consentStatus`
  (`pending` | `consent_given` | `objection_received` | `no_response` |
  `anonymized`), `objectionDeadline`, `publicationDecision` (`pending` |
  `anonymize` | `publish_with_consent` | `publish_anonymized` | `reject`),
  scoped `document`/`entity`. `ObjectionDeadlineChecker` computes the
  configurable WOO objection window (default 28 days,
  `publication_objection_period_days`) — the publication-consent spec forbids
  delegating this to OR's GDPR Art-12(3) helper.
- `publicationProhibition` (register `consent`) models active prohibitions
  with `matchRules`, `active`, `validFrom/validUntil`;
  `PolicyMatchService`/`PolicyCrudService` exist.
- The `document` register already hosts processing artifacts
  (`generatedDocument`, `correspondence`, `anonymizationLink`, ...); the
  anonymisation pipeline records source↔anonymised file mapping in
  `anonymizationLink` (`status`, `anonymizedFileId`).
- Dossiers (`dossier` register) carry `bases[]` grondslagen + `checkedOn`
  review timestamp.
- OR access pattern: `SettingsService::getObjectService()` →
  `searchObjects(['@self' => ['register' => ..., 'schema' => ...]])` /
  `saveObject()`.

Verified at OpenCatalogi HEAD
(`opencatalogi/lib/Settings/publication_register.json` + openspec):

- Register slug `publication`, schema slug `publication` with properties
  `title`, `summary`, `description`, `organization`, `themes`,
  `publicatiedatum` (past date ⇒ live via OR published-predicate),
  `depublicatiedatum` (past ⇒ withdrawn; future ⇒ scheduled),
  `retentionTermMonths`, `retentionExpiresAt` (manual override requires
  `retentionNote`, RET-003), `retentionCategory`, `retentionAction`,
  `status` (lifecycle incl. `archived`, RET-006).
- DiWoo/Woo-index emission is OpenCatalogi's: sitemaps per informatiecategorie
  (WOO-001..005), `diwoo:Document` field mapping (WOO-006/010), TOOI value-list
  binding for informatiecategorie/publisher (WOO-TOOI-001/002) resolved by
  `TooiVocabularyService` from bundled `tooi_waardelijsten.json`.

Boundary (shared brief): DocuDesk prepares + hands off + tracks state;
OpenCatalogi/OpenWoo is the publication endpoint; DocuDesk builds no portal.

## Goals / Non-Goals

**Goals:**

- One auditable pipeline object per publication with a readiness gate that
  cannot be bypassed.
- DiWoo metadata assembled once, on the DocuDesk side, from operator input +
  document metadata, in the vocabulary OpenCatalogi validates.
- Handoff, de-publication and destruction-date propagation as first-class,
  logged operations against the OpenCatalogi `publication` object.

**Non-Goals:**

- No public portal, sitemap, search or DCAT surface (OpenCatalogi/OpenWoo own
  these).
- No change to the objection-window computation or consent CRUD.
- No automatic publication without an operator release (human-in-the-loop is
  the market's hard requirement).
- No TMLO/MDTO or full Archiefwet retention engine (next wave, per gap
  shortlist) — only recording + propagating a supplied destruction date.

## Decisions

### D1 — `publicationRecord` + `publicationLogEntry` in the `document` register

New schemas (no new register — the `document` register already models
processing artifacts, and a new register slug near "publication" would court
the cross-app slug collision with OpenCatalogi's `publication` register):

- **`publicationRecord`**: `subjectType` (`document` | `dossier`),
  `documentFileRef`, `dossierRef`, `redactedFileRef` (the derivative that
  will be published — the original is never handed off), readiness block
  (`entitiesReviewed`, `consentClear`, `prohibitionsClear` booleans +
  `readinessEvaluatedAt`), DiWoo block (`wooCategory` — TOOI informatiecategorie
  code e.g. `c_8c840238`, `documentsoort`, `publisher` — TOOI organisatie URI,
  `officieleTitel`, `creatiedatum`, `publicatiedatum`), `status` (lifecycle,
  D3), `endpointPublicationRef` (UUID of the OpenCatalogi publication object),
  `handoffAt`, `depublicationReason`, `depublicationRequestedAt`,
  `destructionDate`, `destructionDateSource`.
- **`publicationLogEntry`**: `publicationRecordRef`, `action` (enum
  `created` | `readiness_evaluated` | `metadata_assembled` | `handed_off` |
  `published` | `depublication_requested` | `depublished` |
  `destruction_date_propagated`), `actor`, `timestamp`, `details` (string),
  `snapshot` (JSON of the readiness/DiWoo state at that moment). Append-only:
  no update/delete route exists and the schema is flagged immutable.

### D2 — Readiness is service-evaluated, persisted with timestamp; the gate is a lifecycle guard

`PublicationPipelineService::evaluateReadiness()` computes three verdicts:

1. **Entities reviewed** — every entity detected for the document(s) has a
   review decision (source: the anonymisation entity-review state; for a
   dossier: the dossier's `checkedOn` is set and the batch report exists).
2. **Consent clear** — the new clearance query on the consent side (D6): every
   `publicationConsent` for the document is in a publication-permitting
   terminal state: `consent_given`, `anonymized`, or `no_response` with
   `objectionDeadline` in the past; no record has `consentStatus =
   objection_received` unresolved and none has `publicationDecision = reject`.
   (WOO objection window; personal-data handling per AVG Art. 6(1)(e) — the
   readiness snapshot stores only verdicts + record UUIDs, no entity text.)
3. **Prohibitions clear** — `PolicyMatchService` finds no active
   `publicationProhibition` matching the document's entities.

Declarative-vs-imperative: this is a **cross-object aggregation over three
registers with date arithmetic and policy matching** — beyond the
`x-openregister-calculations` dialect; imperative evaluation is a justified
ADR-031 exception (same class as `document-validation-checks`' fallback).
The *gate itself* is declarative where it can be: the `publicationRecord`
lifecycle (D3) declares `draft → ready` guarded on the three booleans, and
handoff requires status `ready`, so a stale/false readiness can never be
bypassed by a direct status write.

Readiness is re-evaluated (and the record demoted to `draft` if it regresses)
on every handoff attempt — evaluation results carry `readinessEvaluatedAt` and
are never trusted beyond that instant.

### D3 — Publication lifecycle

```
draft ──> ready ──> handed_off ──> published ──> depublication_requested ──> depublished
  ^         │
  └─────────┘ (readiness regression)
```

Declared as `x-openregister-lifecycle` (canonical `initial: draft`).
`published` is set when the endpoint publication goes live
(`publicatiedatum` in the past — checked on read, confirmed by a lightweight
status sync when the record is opened); `depublished` when
`depublicatiedatum` has passed.

### D4 — Handoff writes an OpenCatalogi `publication` object via OR (endpoint addressing, verified)

`PublicationPipelineService::handoff()`:

1. Re-evaluates readiness (D2); aborts unless `ready`.
2. Creates (or updates, when `endpointPublicationRef` exists) an OR object
   addressed `['@self' => ['register' => 'publication', 'schema' =>
   'publication']]` — the OpenCatalogi register/schema slugs verified at HEAD —
   mapping `officieleTitel → title`, summary/description from the record,
   `publicatiedatum`, and the DiWoo block. The redacted derivative file
   (`redactedFileRef`, never the original) is attached as the publication's
   file/attachment following OpenCatalogi's attachment convention.
3. Stores `endpointPublicationRef` + `handoffAt`, transitions to
   `handed_off`, appends a `handed_off` log entry.

If OpenCatalogi is not installed (register absent), handoff is disabled with
an explanatory UI state — no silent no-op. Rejected alternative: calling
OpenCatalogi's REST API — the OR object write is the fleet pattern
(ADR-022-consistent, works cross-app via slugs, RBAC/audit for free) and
avoids an HTTP dependency inside one instance.

**Uncertainty, stated**: the exact attachment convention for publication files
(file-attach via OR file handling vs. an `attachments` property) follows
OpenCatalogi's `publication-attachment-defaults` spec at apply time; if the
convention requires an OpenCatalogi-side helper, attachment is routed through
it rather than duplicated.

### D5 — De-publication and destruction-date propagation reuse endpoint semantics

- **Withdraw**: operator supplies a mandatory reason; DocuDesk sets
  `depublicatiedatum = now` on the endpoint publication object (past date ⇒
  withdrawn from all public surfaces per OpenCatalogi's published-predicate),
  transitions the record `depublication_requested → depublished` once the
  date has passed, and logs both steps with the reason. The endpoint object is
  never deleted — Woo accountability requires the trace.
- **Destruction date**: when the source system supplies a destruction date
  (Archiefwet/selectielijst — e.g. via the zgw-document-bridge metadata or
  operator entry), DocuDesk records it on `publicationRecord.destructionDate`
  and propagates it to the endpoint publication as a manual
  `retentionExpiresAt` override **with** a `retentionNote` naming the source
  ("Vernietigingsdatum uit zaaksysteem, Archiefwet 1995/selectielijst"), as
  OpenCatalogi RET-003 requires for overrides. Actual disposal is
  OpenCatalogi's retention job (RET-005/006) — DocuDesk propagates, never
  destroys.

### D6 — Consent-clearance signal (the publication-consent modification)

A read-only query, exposed as
`ConsentService::isDocumentConsentClear(string $documentId): array`
(verdict + per-record reasons) and consumed by D2. It changes **no** consent
behaviour: the objection window stays `ObjectionDeadlineChecker`'s, creation
stays programmatic, the app-owned boundary from the publication-consent spec
is untouched. Spec-wise this is an ADDED requirement on the
publication-consent capability (adding a new concern, not modifying existing
requirements — safe for archive).

### D7 — Frontend per ADR-012

- **Publications index + detail** (manifest pages): `CnIndexPage` +
  `CnDataTable` listing publication records with status chips; detail shows
  the readiness checklist (three verdicts with reasons), the DiWoo metadata
  form (`CnFormDialog`; wooCategory select bound to the 17 TOOI
  informatiecategorieën), the publication log timeline, and
  withdraw/destruction-date actions.
- **Publish wizard entry**: "Publiceren" action on document detail (MyDocuments)
  and dossier context creating a `publicationRecord` and walking anonymize →
  consent → publish state (each step deep-links to the existing capability
  surfaces; the wizard orchestrates, never reimplements).
- NL Design tokens via NC CSS variables (ADR-003); no hardcoded colors.

## OpenRegister service usage (ADR-001)

| Operation | OR service |
|---|---|
| Publication records + log CRUD | `ObjectService::saveObject()` / `searchObjects()` on `document` register (no custom tables) |
| Consent clearance query | `searchObjects()` on `consent`/`publicationConsent` filtered by `documentId` |
| Prohibition check | existing `PolicyMatchService` (consent register) |
| Endpoint handoff | `saveObject()` addressed to OpenCatalogi's `publication`/`publication` (cross-register slugs) |
| Lifecycle gates | declarative `x-openregister-lifecycle` on `publicationRecord` |
| Audit | OR audit trail + append-only `publicationLogEntry` |

ADR-011 check: date arithmetic reuses `ObjectionDeadlineChecker` outputs; no
new validation/formatting utilities; TOOI codes are passed as opaque values
(OpenCatalogi's `TooiVocabularyService` is the authority — the 17-category
list used in the UI select is sourced from its bundled value list, not
re-declared).

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: `publicationRecord` lifecycle + guard
  (`x-openregister-lifecycle`, canonical `initial:`); log schema immutability;
  endpoint live/withdrawn semantics ride OpenCatalogi's OR
  published-predicate (`publicatiedatum`/`depublicatiedatum`).
- **Imperative (justified exceptions)**: readiness evaluation (cross-register
  aggregation + date/policy logic — beyond the calculations dialect); handoff
  (cross-app object write + file attachment = external integration in ADR-031
  terms); destruction-date propagation (writes another app's object with a
  RET-003 note). Each imperative path appends a `publicationLogEntry`, keeping
  accountability declarative-readable.

## Seed Data

Shipped in `docudesk_register.json` `objects[]` (demo-municipality flavour,
placeholder identifiers only):

```json
{
  "@self": {"register": "document", "schema": "publicationRecord", "slug": "demostad-woo-2025-017-besluit"},
  "subjectType": "document",
  "documentFileRef": "seed-file-demostad-besluit-subsidie",
  "redactedFileRef": "seed-file-demostad-besluit-subsidie-geanonimiseerd",
  "dossierRef": "demostad-woo-2025-017",
  "entitiesReviewed": true,
  "consentClear": true,
  "prohibitionsClear": true,
  "readinessEvaluatedAt": "2026-07-01T09:15:00+00:00",
  "wooCategory": "c_8c840238",
  "documentsoort": "besluit",
  "publisher": "https://identifier.overheid.nl/tooi/id/gemeente/gm0000",
  "officieleTitel": "Besluit subsidietoekenning cultuur 2024-088 (geanonimiseerd)",
  "creatiedatum": "2024-11-03",
  "publicatiedatum": "2026-07-02",
  "status": "published",
  "endpointPublicationRef": "00000000-0000-0000-0000-000000000000",
  "handoffAt": "2026-07-01T09:20:00+00:00",
  "destructionDate": "2034-11-03",
  "destructionDateSource": "selectielijst 2020, procestype 6"
}
```

Plus a `draft` record with `consentClear: false` (objection window still
open) and matching `publicationLogEntry` rows (`created`,
`readiness_evaluated`, `handed_off`, `published`,
`destruction_date_propagated`) so demos show the full accountability trail.

## Risks / Trade-offs

- [OpenCatalogi schema drift (property renames on `publication`)] → the
  handoff field map lives in one service constant + a drift-pin unit test
  against a fixture of the OpenCatalogi register JSON; gate 28/30-style
  cross-ref at review.
- [Readiness snapshot staleness] → re-evaluation on every handoff attempt +
  lifecycle demotion on regression (D2); `readinessEvaluatedAt` always shown
  in UI.
- [Consent taxonomy ambiguity: `no_response` + elapsed deadline] → the
  clearance rule follows the existing consent lifecycle semantics
  (no_response after deadline = publish permitted under WOO active-disclosure;
  `publicationDecision = reject` always blocks); rule is a single pure
  function, unit-tested exhaustively.
- [Cross-app write authorization] → handoff runs under the acting operator's
  OR permissions on the OpenCatalogi register; a 403 is surfaced as
  "publication endpoint declined" with the OR error, never swallowed.
- [Dutch-only DiWoo vocabulary vs EN UI] → labels i18n'd EN+NL; codes stay
  TOOI-canonical.

## Migration Plan

Additive: schemas + seeds (register version bump, boot import), new
service/controller/routes/views. No existing schema or consent behaviour
changes. Rollback = drop routes/UI; records remain readable. Publications
already handed off live in OpenCatalogi and are unaffected by rollback.

## Open Questions

- Dossier-level publication fan-out (one record per dossier vs per document
  inside it): this change ships one record per handed-off unit (document, or
  dossier published as a bundle); per-document fan-out inside a dossier is a
  follow-up once volume demands it.
- Whether `published`/`depublished` confirmation should be event-driven
  (OpenCatalogi → DocuDesk notification) instead of checked-on-read — deferred
  until OpenCatalogi emits such events.
