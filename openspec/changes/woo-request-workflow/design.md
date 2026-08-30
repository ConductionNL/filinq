# Design: woo-request-workflow

## Context

Verified at Filinq HEAD:

- The `dossier` register holds `dossier` (binds a Nextcloud folder via
  `@self.folder`; `name`, `description`, `bases[]` — array of `base` slugs —
  and `checkedOn`) and `base` (Woo Art. 5 uitzonderingsgrond: `name`,
  `description`, both required; **six canonical grounds seeded**:
  `persoonsgegevens`, `bijzondere-persoonsgegevens`, `strafrechtelijk`,
  `bedrijfs-fabricagegegevens`, `onevenredige-benadeling`,
  `nationale-veiligheid` — each description cites the Woo/AVG articles).
  `BasesResolverService`, `GrondslagProposalService` and
  `GrondslagenSummaryService` already resolve/propose grounds.
- Batch anonymisation exposes per-batch entity review
  (`api/anonymization/batch/{batchId}/entities`), reports, and manual entity
  handling; the entity-review data model carries per-entity grondslag
  (CB #122 auto-proposal in the sibling anonymization-review-workbench
  change).
- Document generation is backend-complete (`api/documents/generate`,
  `generatedDocument` schema, TemplateService/TemplateRenderer with seeded
  templates); correspondence generation produces letters
  (`api/correspondence/generate`, `correspondence` schema).
- The zgw-document-bridge change (sibling, this wave) defines
  `externalDocument` staging objects and dossier pick-up.
- The woo-publicatie-pipeline change (sibling, this wave) defines
  `publicationRecord` handoff to OpenCatalogi.
- OR access: `SettingsService::getObjectService()` with `@self.register` /
  `@self.schema` addressing; no custom tables anywhere (ADR-001).

Statutory frame: Woo Art. 4.4 — decision within 4 weeks of receipt, one
extension of at most 2 weeks with notified reason; disclosure to the
requester, then active publication of the disclosed set.

## Goals / Non-Goals

**Goals:**

- One case object per Woo-verzoek with statutory deadline arithmetic and a
  guarded lifecycle.
- Candidate collection from both Nextcloud folders and bridge-staged case
  system documents, deduped by content hash before assessment.
- Per-document assessment + per-passage exemption tagging that reuses the
  existing grondslagen register — one taxonomy across anonymisation and
  disclosure.
- Inventarislijst and disclosure package produced by the existing generation
  capabilities, never by new renderers.

**Non-Goals:**

- No cross-system enterprise *search* to discover candidate documents
  (INDICA's category) — collection starts from folders/zaak selections the
  operator provides.
- No email-thread / near-duplicate detection (hash-identical only; threading
  noted as future work).
- No requester portal or intake form for citizens (portaliq/portal-contribution
  territory; intake here is operator-side).
- No new anonymisation, generation or publication engines — orchestration
  only.
- No case-system write-back of disclosure decisions (bridge write-back covers
  redacted derivatives only).

## Decisions

### D1 — `wooRequest` + `requestDocument` schemas in the `dossier` register

- **`wooRequest`**: `requestNumber` (human key, e.g. "WOO-2026-021"),
  `subject`, `scopeDescription`, `requesterReference` (opaque operator-side
  reference to the requester's case/contact — **no requester name/email on
  the schema**, data minimisation AVG Art. 5(1)(c); identity stays in the NC
  contact/zaak the reference points at), `receivedAt`, `decisionDeadlineAt`,
  `extendedAt`, `extensionReason`, `status` (lifecycle, D3), `dossierRef`
  (the collection dossier), `inventoryFileRef`, `decisionFileRef`,
  `packageFolderRef`, `publicationRecordRef` (optional link to the
  woo-publicatie-pipeline record), `closedAt`.
- **`requestDocument`**: `wooRequestRef`, `origin` (enum `nc-folder` |
  `zgw-bridge`), `fileRef` (Nextcloud file id of the collected copy),
  `externalDocumentRef` (bridge staging object UUID when origin is
  `zgw-bridge`), `title`, `documentDate`, `contentHash` (sha256),
  `dedupeStatus` (enum `unique` | `duplicate`), `duplicateOfRef`,
  `assessment` (enum `pending` | `disclose` | `partially_disclose` |
  `withhold`), `exemptionGrounds[]` (array of `base` slugs),
  `passageTags[]` (array of objects `{locator, ground, note}` — locator is a
  page/passage reference; per-entity tags ride the anonymisation entity
  review, D5), `redactedFileRef`, `inventoryNumber`.

Both live in the `dossier` register: a Woo request is case/dossier domain,
and the grondslagen it references are already there. Rejected alternative: a
new `wooRequest` register — more surface, no isolation benefit, and the
request wants `maxDepth` relations into `dossier`/`base` anyway.

### D2 — Statutory deadline arithmetic (Woo Art. 4.4)

`decisionDeadlineAt = receivedAt + 4 weeks` computed at intake by
`WooRequestService` (clock-injected). One extension allowed: `extend()`
requires a reason, adds at most 2 weeks, sets `extendedAt`/`extensionReason`,
recomputes the deadline, and is refused on a second attempt. The UI shows a
deadline chip (on-track / due within 5 working days / overdue). This is
date arithmetic + a legal control ⇒ service-owned and exhaustively
unit-tested, mirroring how `ObjectionDeadlineChecker` owns the WOO objection
window (and per the publication-consent precedent: statutory terms are never
delegated to a generic helper with different legal semantics). ADR-011 check:
no OR helper models Woo Art. 4.4 terms (OR's `DataSubjectDeadline` is GDPR
Art. 12(3) — one month + two-month extension — explicitly the wrong law).

### D3 — Request lifecycle (declarative guard)

```
registered ──> collecting ──> assessing ──> decision ──> disclosed ──> published ──> closed
                   ^               │                          │
                   └───────────────┘ (reopen collection)      └──────────────> closed (no active publication)
```

Declared as `x-openregister-lifecycle` on `wooRequest` (canonical
`initial: registered`). Guards: `assessing → decision` requires every
non-duplicate `requestDocument` to have a non-`pending` assessment;
`decision → disclosed` requires `inventoryFileRef` + `decisionFileRef`;
`disclosed → published` requires a `publicationRecordRef`. The cross-object
guard conditions are evaluated imperatively in `WooRequestService` before it
requests the transition (ADR-031 exception: lifecycle guard over a
cross-schema aggregate), while OR's declarative lifecycle still rejects any
out-of-order transition written directly.

### D4 — Collection + hash dedupe

`collect()` accepts a Nextcloud folder selection and/or a set of
bridge-staged `externalDocument` objects (when the zgw-document-bridge is
installed; the option is hidden otherwise). Each collected file is copied
into the request's dossier folder (the existing dossier/folder unit, so
folder-batch anonymisation runs unchanged), a `requestDocument` row is
created with `contentHash = sha256(content)`, and dedupe runs within the
request: rows sharing a hash collapse to one `unique` row (first collected)
with the rest marked `duplicate` + `duplicateOfRef`. Duplicates are excluded
from assessment and the inventory but stay listed (accountability: the
requester may cite them). Email threading / near-duplicate detection is
explicitly out of scope and recorded as future work in the spec.

### D5 — Exemption-ground tagging reuses the `base` register (verified)

Three tagging levels, one taxonomy:

1. **Per document**: `assessment` + `exemptionGrounds[]` (base slugs).
2. **Per passage**: `passageTags[]` entries `{locator, ground, note}` for
   grounds that apply to a passage rather than the whole document (ZyLAB's
   per-passage annotation, the category's table-stakes feature).
3. **Per entity**: the existing anonymisation entity review already carries
   per-entity grondslag (GrondslagProposalService / CB #122); the workflow
   deep-links into the batch entity review for the dossier rather than
   duplicating it.

The `base` schema is **not modified**; the additional Woo Art. 5.1/5.2
grounds the assessment needs beyond the six seeded categories ship as new
seed `base` objects with article references in name/description (e.g.
"Art. 5.1.2e — Eerbiediging van de persoonlijke levenssfeer",
"Art. 5.1.2b — Economische of financiële belangen", "Art. 5.1.1c —
Vertrouwelijk verstrekte bedrijfs- en fabricagegegevens" where it refines an
existing category, "Art. 5.2 — Persoonlijke beleidsopvattingen in documenten
voor intern beraad"). Adding an explicit `article` property to `base` is
logged as a deferred question (it would touch the dossier-register
capability, not assigned to this change).

### D6 — Inventarislijst and disclosure package reuse existing generators

- **Inventarislijst**: a seeded template `woo-inventarislijst` (templates
  register) rendered through the existing `api/documents/generate` with the
  request + its `unique` documents (inventory number, title, date,
  assessment, grounds) as data; output stored, `inventoryFileRef` set.
  Inventory numbers are assigned stably (collection order) on first
  generation.
- **Besluit letter**: generated through the existing correspondence
  capability from a seeded `woo-besluit` template; `decisionFileRef` set.
- **Package**: `assemblePackage()` creates the package folder containing the
  redacted PDFs (each `disclose`/`partially_disclose` document's
  `redactedFileRef` produced by the existing dossier anonymisation flow — the
  package MUST refuse unredacted originals for `partially_disclose` items),
  the inventarislijst and the besluit; `packageFolderRef` set. Delivery of
  the package (mail/portal) stays out of scope (NC Mail / OR email leaf per
  the app boundary).

### D7 — Frontend per ADR-012

- **Woo-verzoeken index** (manifest page): `CnIndexPage` + `CnDataTable` —
  request number, subject, status chip, deadline chip.
- **Request detail**: header with lifecycle + deadline (extension action with
  mandatory reason); tabs/sections for Collection (add-from-folder,
  add-from-zaaksysteem when bridge present, dedupe results), Assessment
  (per-document assessment + grounds multi-select bound to the `base`
  register, passage tags editor, deep-link to batch entity review),
  Documents (inventory preview, generate inventarislijst/besluit, assemble
  package), and the request timeline.
- Modals/dialogs in their own files under `src/modals/`/`src/dialogs/`;
  `NcSelect` usages carry `inputLabel`; NL Design tokens via NC CSS
  variables (ADR-003).

## OpenRegister service usage (ADR-001)

| Operation | OR service |
|---|---|
| Request/requestDocument CRUD | `ObjectService::saveObject()` / `searchObjects()` on the `dossier` register (no custom tables) |
| Grounds lookup | `searchObjects()` on `dossier`/`base` (existing `BasesResolverService` reused) |
| Lifecycle | declarative `x-openregister-lifecycle` on `wooRequest` |
| Collection folder | existing dossier folder binding (`@self.folder`) |
| Inventory/besluit rendering | existing TemplateService / document + correspondence generation (their OR usage unchanged) |
| Audit | OR object audit trail |

ADR-011 check: hashing via PHP `hash('sha256', ...)`; deadline arithmetic is
app-owned by legal necessity (D2); no BSN/date/slug utilities duplicated.

## Declarative-vs-imperative decision (ADR-031)

- **Declarative**: `wooRequest` lifecycle (`x-openregister-lifecycle`,
  canonical `initial:`); grounds as register data (`base` objects);
  register-i18n on new user-facing string fields (matching the v5.4/v5.5
  pattern on dossier/base).
- **Imperative (justified exceptions)**: statutory deadline computation +
  extension control (legal control, exhaustively unit-tested — same class as
  `ObjectionDeadlineChecker`); collection + dedupe (file-system side effects
  + content hashing); cross-object lifecycle guard conditions (aggregate over
  `requestDocument` rows before transition); inventarislijst/package assembly
  (document generation — a named ADR-031 exception).

## Seed Data

Shipped in `filinq_register.json` `objects[]` (demo-municipality flavour,
placeholder identifiers only):

```json
{
  "@self": {"register": "dossier", "schema": "wooRequest", "slug": "demostad-woo-2026-021"},
  "requestNumber": "WOO-2026-021",
  "subject": "Handhavingsbesluiten horeca 2024-2025",
  "scopeDescription": "Alle handhavingsbesluiten en bijbehorende inspectierapporten voor horecagelegenheden in het centrum, januari 2024 t/m december 2025.",
  "requesterReference": "zaak-00000000-0000-0000-0000-000000000000",
  "receivedAt": "2026-06-20",
  "decisionDeadlineAt": "2026-07-18",
  "status": "assessing",
  "dossierRef": "demostad-woo-2025-017"
}
```

```json
{
  "@self": {"register": "dossier", "schema": "requestDocument", "slug": "demostad-woo-2026-021-doc-001"},
  "wooRequestRef": "demostad-woo-2026-021",
  "origin": "nc-folder",
  "fileRef": "seed-file-handhavingsbesluit-2024-113",
  "title": "Handhavingsbesluit 2024-113 Café De Kroon",
  "documentDate": "2024-09-12",
  "contentHash": "0000000000000000000000000000000000000000000000000000000000000000",
  "dedupeStatus": "unique",
  "assessment": "partially_disclose",
  "exemptionGrounds": ["persoonsgegevens", "art-5-1-2e-persoonlijke-levenssfeer"],
  "passageTags": [
    {"locator": "p. 2, alinea 3", "ground": "art-5-1-2e-persoonlijke-levenssfeer", "note": "Naam en adres exploitant"}
  ],
  "inventoryNumber": 1
}
```

Plus: a `duplicate` requestDocument pointing at doc-001; the additional Woo
Art. 5.1/5.2 `base` seed objects (D5); a `woo-inventarislijst` and a
`woo-besluit` template seed in the templates register.

## Risks / Trade-offs

- [Statutory-term errors are legal risk] → single clock-injected service,
  exhaustive unit tests incl. extension refusal; deadline always displayed,
  never enforced silently (the operator decides, Filinq tracks).
- [Hash dedupe misses near-duplicates/email threads] → explicitly scoped
  future work; verdict field is an enum so a `near_duplicate` value can be
  added without migration.
- [Package could leak unredacted content] → assembly refuses
  `partially_disclose` items without `redactedFileRef`; spec scenario pins
  it; package folder inherits dossier ACLs.
- [Grounds slug drift between seeds and tags] → tags store `base` slugs and
  render via `BasesResolverService`; unknown slugs render as warnings, never
  silently dropped.
- [Sibling-change coupling (bridge, pipeline)] → both integrations are
  presence-gated (option hidden / step skipped when absent); this change
  applies standalone.

## Migration Plan

Additive: schemas + seeds + templates (register version bump, boot import),
new service/controller/routes/views. No existing schema changes. Rollback =
remove routes/UI; request objects remain readable. No data migration.

## Open Questions

- Explicit `article` property on the `base` schema (vs article-in-name seed
  convention) — deferred to a dossier-register follow-up.
- Requester-facing intake/status via portaliq (portal-contribution A6
  actions) — deferred; this change is operator-side only.
- `near_duplicate`/email-threading detection — future wave, needs an
  OR-side text-similarity capability decision.
