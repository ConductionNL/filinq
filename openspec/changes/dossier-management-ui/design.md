# Design: dossier-management-ui

## Context

Verified at DocuDesk HEAD:

- `dossier` register: schema `dossier` (`name` required, `description`,
  `bases[]` of `base` slugs, `checkedOn`; `@self.folder` binds an NC folder
  node id; `configuration.objectNameField: name`), schema `base` (six
  canonical Woo Art. 5 grondslagen seeded), 3–5 seed dossiers.
  `DossierCheckedOnListener` already audit-reacts to `checkedOn` updates;
  `BasesResolverService` resolves slugs; `GrondslagenSummaryService` +
  `DossierController::generateGrondslagenSummary()` produce the grondslagen
  PDF — but the route `dossier#generateGrondslagenPdf`
  (`appinfo/routes.php:62`) names a method that does not exist, so the
  endpoint 500s at HEAD; the sibling change
  `fix-dossier-grondslagen-route-mismatch` corrects the route name (URL
  unchanged). This change **depends on** that fix and does not duplicate it.
- **No dossier page exists**: `src/manifest.json` pages are Dashboard,
  Consent(+Detail), Anonymization, FolderAnonymization, Templates(+Detail),
  SigningRequests(+Detail), MyDocuments, PrintPreview, Comparison, Versions,
  FeaturesRoadmap. Only `src/store/modules/folderAnonymization.js` creates a
  dossier (optionally, from the folder-batch flow).
- Folder batches: `FolderBatchService` / `FolderExtractionJob` keyed by
  folder; the wave-1 `redaction-at-scale` change adds OR-object batch state,
  progress and an operations list (referenced, presence-gated).
- Publication: wave-1 `woo-publicatie-pipeline` adds `publicationRecord`
  objects (document register) with readiness/lifecycle (referenced,
  presence-gated).
- Review gate: the sibling `anonymization-review-workbench` change adds the
  per-document `documentReview` checked gate (referenced, presence-gated).
- OR gotcha honoured throughout: `saveObject` is PUT-semantic — every dossier
  update carries ALL schema fields forward (a rename must not null `bases`
  or `checkedOn`).

Evidence: GH #47 (auto-dossier on multi-upload: 1 document → no modal;
several → modal with name + "grondslagen allemaal geselecteerd" toggle),
GH #48 ("+ Document toevoegen" CTA, list updates directly), GH #50 (inline
document switch without reload; mini-menu: mark as checked, delete), GH #51
(inline title rename), CB #148 (pilot's dossier flow must ride the
folder-analysis architecture), repo-inventory notable absence.

## Goals / Non-Goals

**Goals:**

- One browsable home for dossiers: index + detail with documents, status,
  grondslagen, batch runs and publication state in one place.
- Membership operations (create, rename, add, remove, auto-dossier on
  multi-upload) exactly as the GH wish set describes them.
- Dossier-level actions that *wire* existing capabilities — never re-implement
  them.
- A declarative dossier lifecycle.

**Non-Goals:**

- No new anonymisation/batch/report/publication engines — actions call the
  existing endpoints.
- No change to the `base` vocabulary, seeds, folder binding or
  `checkedOn` audit behaviour.
- No Woo-verzoek surfaces (wave-1 `woo-request-workflow` owns those; a
  request's dossier renders here like any other dossier).
- No folder-ACL model of its own — file visibility stays NC's.

## Decisions

### D1 — Membership = the bound folder (no second membership store)

A dossier's documents ARE the files in its `@self.folder` folder — the
existing contract that folder-batch anonymisation, the grondslagen summary
and the Woo collection step already rely on (CB #148's point). Therefore:

- **Add document** (GH #48) = upload/copy the file into the dossier folder;
  the documents list refreshes immediately.
- **Remove document** (GH #50) = delete the file from the dossier folder to
  the NC trashbin after confirmation (recoverable; never a silent hard
  delete).
- No `documents[]` array is added to the schema — a second membership store
  would drift from the folder truth that the processing capabilities use.

Rejected alternative: object-relation membership (dossier → file refs) —
would let one file join many dossiers, but breaks the 1-folder-batch
architecture and duplicates truth; multi-dossier membership is recorded as an
open question.

### D2 — Index + detail aggregation

`GET api/dossiers` (index) and `GET api/dossiers/{id}` (detail) on a new
`DossierManagementController` → `DossierManagementService`, which aggregates:

| Facet | Source (existing, unchanged) |
|---|---|
| Dossier rows | OR ObjectService search on `dossier`/`dossier` |
| Document list | folder listing of `@self.folder` (caller ACLs apply) |
| Per-document anonymisation state | faceted `anonymizationLink` by `sourceFileId` |
| Per-document checked state | `documentReview` objects (presence-gated on the review-workbench change) |
| Grondslagen chips | `BasesResolverService` (unknown slug → visible warning, existing rule) |
| Batch runs | folder batches for the folder (redaction-at-scale operations data when present; legacy batch state otherwise) |
| Publication state | `publicationRecord` for the dossier (presence-gated) |

ADR-022 justification (redundant-controller gate): these endpoints aggregate
five sources plus NC filesystem listings — not a pass-through; plain dossier
CRUD (create/rename) stays on OR's generic objects API via the store, as the
folder-anonymization store already does.

### D3 — Auto-dossier on multi-upload (GH #47)

On DocuDesk's upload surfaces: uploading **one** document never shows a
modal; uploading **more than one** in a single action opens the auto-dossier
modal — dossier name (prefilled with a sensible default), optional
description, and the "grondslagen allemaal geselecteerd" toggle that
preselects all six canonical bases when on. Confirming creates the dossier
(bound to a new folder containing the uploads); cancelling uploads the files
without a dossier. The modal lives in its own file under `src/modals/`.

### D4 — Declarative lifecycle (dossier-register delta)

The `dossier` schema gains optional `status` with `x-openregister-lifecycle`
(canonical `initial: open` — the dialect-drift trap is known: only the
canonical `initial` key is honoured):

```
open ──> in-review ──> processed ──> published ──> closed
  ^          │              │
  └──────────┘ (reopen)     └────────────────────> closed
```

- Transitions are purely declarative — OR's lifecycle guard rejects
  out-of-order writes; this change adds **no imperative cross-object guard**
  (unlike wooRequest's, none is legally required here). UI affordances set
  status via normal saves.
- `published` is reachable whether or not the publication pipeline is
  installed (an operator may publish through other means); when the pipeline
  IS installed the publish action goes through it and sets status on
  success.
- Backwards compatibility: `status` is optional; an existing dossier without
  it renders as `open`, and the first transition writes the field. Seeds are
  unchanged (absent status = open) — the canonical seed requirement is not
  touched.

### D5 — Dossier-level actions (wire, don't build)

| Action | Wiring |
|---|---|
| Batch anonymize | starts/links the existing folder-batch flow for the bound folder; with redaction-at-scale present, deep-links to its progress/cancel/resume view |
| Grondslagen PDF | `POST api/anonymization/dossier/{dossierId}/grondslagen-pdf` — functional only once `fix-dossier-grondslagen-route-mismatch` lands (declared dependency) |
| Publish | woo-publicatie-pipeline publish wizard entry with the dossier preselected (hidden without the pipeline) |
| Mark document checked | `documentReview` gate from anonymization-review-workbench (mini-menu item hidden without it) |

Every presence gate is hidden-not-broken.

### D6 — Frontend per ADR-012

- **Dossiers index** (manifest page): `CnIndexPage` + `CnDataTable` — name,
  status chip, document count, grondslagen chips, checkedOn, publication
  chip. Manifest schema refs use slugs.
- **Dossier detail**: header (inline-editable title per GH #51 — text field
  on click, saved on blur/enter with full-payload PUT; lifecycle status +
  transition actions; header actions: batch anonymize, grondslagen PDF,
  publish); Documents section (list + inline viewer switch WITHOUT page
  reload per GH #50 — selection swaps the preview component, router
  untouched; per-row mini-menu: mark checked / remove-with-confirm; "+
  Document toevoegen" CTA bottom-left per GH #48); Grondslagen section;
  Batch runs section; Publication section.
- Modals/dialogs in own files (`src/modals/`, `src/dialogs/`); `NcSelect`
  with `inputLabel`; NL Design tokens via NC CSS variables (ADR-003); store
  per the Options API + createObjectStore pattern.

## OpenRegister service usage (ADR-001)

| Operation | OR service |
|---|---|
| Dossier CRUD + rename | ObjectService via the generic objects API (full-payload PUT — PUT-semantic rule) |
| Lifecycle | declarative `x-openregister-lifecycle` on `dossier` |
| Folder binding | existing `@self.folder` handling (unchanged) |
| Anonymisation/publication/review lookups | ObjectService faceted searches (`anonymizationLink`, `publicationRecord`, `documentReview`) |
| Audit (rename, status, checkedOn) | OR object audit trail (existing) |

ADR-011 check: no utilities duplicated; document counting is a folder
listing; no new validation/formatting code.

## Declarative vs imperative

- **Declarative**: the lifecycle (D4); grondslagen as register data;
  manifest pages; register-i18n on the new user-facing enum values.
- **Imperative (justified)**: detail aggregation (cross-register +
  filesystem joins, D2); membership file operations (copy/upload/trash are
  filesystem side effects, D1); the multi-upload modal trigger (frontend
  interaction logic, D3).

## Seed Data

No new seed objects: the existing 3–5 seed dossiers gain nothing (absent
`status` = `open` demonstrates backwards compatibility by construction), and
the e2e suite creates dossiers through the UI. Register version bump only
for the schema change.

## Security Considerations

- All document listings/operations run under the caller's NC filesystem
  ACLs; the dossier object grants no file access by itself.
- Remove = trashbin, always confirmed; no bulk delete surface.
- Controller methods carry explicit auth attributes + per-object guards
  (dossier readability checked before aggregation — no IDOR via dossier id).
- Rename and status changes are audit-trailed by OR (existing).
- No new external calls.

## Risks / Trade-offs

- [Folder = membership means moving a file out silently changes the dossier]
  → accepted: it is the architecture every processing capability already
  assumes (CB #148); the detail view always renders the live folder truth.
- [PUT-semantic saves could null fields on rename/status writes] → store
  carries all fields forward; unit test pins that a rename survives `bases`
  and `checkedOn` (the known saveObject trap).
- [Sibling-change coupling (route fix, workbench, pipeline,
  redaction-at-scale)] → the route fix is a hard dependency (tiny,
  bug-only); all others are presence-gated hidden-not-broken; this change
  applies standalone with reduced sections.
- [Lifecycle on a schema with live objects] → optional property + absent-
  reads-as-open; no migration touch of existing rows.

## Migration Plan

Additive: `status` + lifecycle annotation on `dossier` (register version
bump, boot import — existing objects untouched), new
service/controller/routes/views. Rollback = remove routes/UI; `status`
values remain inert data. No data migration.

## Open Questions

- Multi-dossier membership (one file in several dossiers) — needs an
  object-relation model and a folder-truth decision; deferred.
- Should renaming the dossier offer to rename the bound folder too (they
  diverge after a rename)? Deferred UX decision; v1 renames the object only.
- Dossier deletion/archival UX (with Archiefwet implications) — deferred to
  the retention/archival programme (`archiefwet-retention-engine` sibling).
