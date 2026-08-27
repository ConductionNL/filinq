---
kind: code
---

# Proposal: dossier-management-ui

## Why

The dossier is Filinq's unit of municipal work — folder-batch anonymisation
operates on it, the grondslagen summary reports on it, the Woo-verzoek
collects into it, the publication pipeline publishes it — yet **there is no
dossier UI**. Verified at HEAD: the `dossier` register (schema `dossier`:
`name`, `description`, `bases[]`, `checkedOn`; folder binding via
`@self.folder`; `base` grondslagen vocabulary; audit-logged `checkedOn`) is
live, but `src/manifest.json` has no dossier page — the only surface that
touches dossiers is `FolderAnonymizationView`, which can optionally create
one as a side quest of a folder batch. The repo inventory lists the missing
dossier UI as a notable absence.

The demand is explicit and concrete:

- The **GH #47/#48/#50/#51 wish set** asks for exactly this UI: auto-create a
  dossier when multiple documents are uploaded (modal with name +
  grondslagen preselect; no modal for a single document, #47), a "+ Document
  toevoegen" CTA that adds an upload to an existing dossier with the list
  updating immediately (#48), switching between a dossier's documents in the
  viewer without a page reload plus a per-document mini-menu (mark as
  checked, delete) (#50), and inline renaming of the dossier title (#51).
- **CB #148** (municipal pilot) flags that the pilot's dossier flow bypassed
  the intended folder-analysis architecture — a first-class dossier surface
  wired to the *existing* folder-batch capabilities is the fix the pilot
  needs.
- Every wave-1 capability that operates on dossiers (redaction-at-scale
  batches, grondslagen PDF, woo-publicatie-pipeline) currently has no home
  where an operator can see one dossier's documents, status, grondslagen,
  batch runs and publication state together.

One backend defect makes the gap worse: the dossier grondslagen-PDF route is
broken at HEAD (`dossier#generateGrondslagenPdf` route → the controller only
has `generateGrondslagenSummary()`; every real click 500s). The sibling
change `fix-dossier-grondslagen-route-mismatch` corrects it; this change
depends on that fix and wires the action into the dossier detail.

## What Changes

- **Dossier index** (manifest page): browsable list — name, status chip,
  document count, grondslagen summary chips, last reviewed (`checkedOn`),
  publication state (when the woo-publicatie-pipeline is installed).
- **Dossier detail**: documents section (inline viewer switch without reload,
  per-document mini-menu: mark as checked / remove — GH #50), grondslagen
  section (resolved `bases` + generate grondslagen-PDF via the fixed route),
  batch-runs section (folder batches for the dossier's folder, deep-linking
  to batch progress), publication section (publication record state + publish
  entry, presence-gated).
- **Create / rename / membership**: create-dossier dialog (name, description,
  bases, folder); inline title rename that also renames the bound home folder
  to keep them in sync (GH #51, E4); "+ Document toevoegen" CTA — upload into
  the home folder or add an existing document by reference (GH #48); remove
  document (confirmed — trashbin when home-folder-owned, unlink-only when
  referenced elsewhere); auto-dossier modal on multi-upload — one document
  uploads without a modal, several trigger the name + grondslagen-preselect
  modal (GH #47).
- **Multi-dossier membership** (E4): membership is an explicit `documents[]`
  relation list over a bound home folder, so one document may belong to
  several dossiers — relaxing the strict folder=dossier equivalence of the
  first draft while keeping the home folder as the physical/processing anchor.
- **Dossier lifecycle, declaratively**: the `dossier` schema gains an
  optional `status` governed by an `x-openregister-lifecycle` annotation
  (canonical `initial: open`; `open → in-review → processed →
  published/closed`), rendered as status chips and transition actions; a
  status-less existing dossier reads as `open`.

## Capabilities

### New Capabilities

- `dossier-management-ui`: the first-class dossier surface — index + detail,
  create/rename, document membership incl. auto-dossier on multi-upload,
  dossier-level actions wired to the existing batch/grondslagen/publication
  capabilities, and declarative lifecycle rendering.

### Modified Capabilities

- `dossier-register`: the `dossier` schema gains the optional `status`
  property with a declarative `x-openregister-lifecycle` (canonical
  `initial: open`) and the optional `documents[]` membership relation list
  (multi-dossier membership, E4). The `base` vocabulary, the `@self.folder`
  home-folder binding and the seed set are unchanged; status-less,
  `documents`-less dossiers read as `open` with folder-derived membership.

## Impact

- `lib/Settings/filinq_register.json`: `status` + lifecycle annotation and
  the optional `documents[]` membership relation on the `dossier` schema;
  register version bump. No new schemas.
- New `lib/Service/DossierManagementService.php` (membership operations,
  aggregation for detail: documents, batch runs, publication state) + new
  routes/actions on a `DossierManagementController` (the existing
  `DossierController::generateGrondslagenSummary` and its route fix stay
  as-is in the sibling change).
- `src/manifest.json` + new views: Dossiers index, DossierDetail, create
  dialog, auto-dossier modal, rename affordance; upload-surface wiring for
  the multi-upload modal.
- Consumes (unchanged): folder-batch analysis/anonymisation +
  redaction-at-scale progress (presence-gated deep links), grondslagen PDF
  endpoint (depends on `fix-dossier-grondslagen-route-mismatch`),
  woo-publicatie-pipeline `publicationRecord` (presence-gated),
  anonymization-review-workbench per-document checked gate (presence-gated
  for the mark-as-checked mini-menu item).
- Evidence: GH #47/#48/#50/#51 wish set, CB #148 pilot, repo-inventory
  notable absence (no dossier UI).
