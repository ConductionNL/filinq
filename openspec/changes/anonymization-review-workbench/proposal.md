---
kind: code
---

# Proposal: anonymization-review-workbench

## Why

Every serious buyer of anonymisation software demands a human-review surface,
and Filinq does not have one as a coherent workbench yet:

- The **joni-png wish set** (GH #45–#64, mirrored as CB #72–#87) asks for
  exactly this: side-by-side original/redacted preview, inline entity
  add/edit/toggle, select-text-to-create-entity, search and counters, and a
  per-document "checked" gate before anything is exported.
- The **Arnhem tender 407824** (anonymiseringssoftware for Arnhem + Renkum +
  Rheden, ~275 Woo dossiers / ~55.000 docs per year) requires automatic AND
  manual redaction with human control before irreversible blackout.
- Nine Dutch government **algoritmeregister** entries for anonymisation
  algorithms (Rotterdam, Zaanstad, Min AZ, mostly Octobox deployments) all
  mandate human review of machine detections — VNG estimates >50% of documents
  are partly auto-redactable but ALL need review.
- Seven competitors (xxllnc Anonimiseren, ZyLAB ONE, Octobox, CaseGuard,
  Redactable, Adobe Acrobat Pro, iOpenbaar) ship a suggest-then-approve review
  UI; it is NL-market table stakes. Filinq's counter-positioning (fully
  local, OR-native, EUPL) only lands if the review UX exists.

Filinq already has most of the machinery, verified at HEAD: an
`EntityReviewTable.vue` with search/type-filter/counters/bulk actions and
per-entity grondslag pickers, an `AddManualEntityModal.vue` posting to
OpenRegister's chunk-aware `POST /api/files/{fileId}/manual-entities` matcher,
`GrondslagProposalService` (CB #122 — per-entity-type grondslag auto-proposal,
fill-only-when-empty, config key `filinq.grondslagen.entity_type_bases`),
`PolicyMatchService::match()` returning `prohibition | standing_consent`
matches, and in-app PDF/Word/Text viewers. What is missing is the workbench
that composes them: a document preview next to the entity list, selection-to-
entity, pre-application of BOTH policy kinds (today only `prohibitionMatch`
is attached at extract time), a per-document human-checked gate, and grondslag
per org-level rule (GH #62/#63: org-wide "always anonymise" / "never
anonymise" lists).

## What

- A **review workbench view** per document: original document preview
  (reusing the existing `PdfViewer`/`WordViewer`/`TextViewer` components)
  side-by-side with the anonymized result preview (or a pending placeholder
  before the first anonymisation run), with the existing `EntityReviewTable`
  as the decision panel — one shared entity state model, no forked state.
- **Select text in the preview to create a manual entity**: selecting a text
  range opens the existing `AddManualEntityModal` pre-filled with the
  selection, plus an entity type picker and a grondslag (bases) picker; the
  created `EntityRelation` rows appear inline in the review table.
- **Inline accept/reject/toggle per entity** synced with the existing
  `included` / `_decisionSkip` / `_decisionBases` model of
  `EntityReviewTable.vue` — the workbench adds preview-side highlight and
  click-through, not a second model.
- **Realtime search, type filters and counters** over detections (the table
  already has these; the workbench keeps them and adds occurrence counters in
  the preview).
- A **per-document "checked" gate**: a reviewer marks a document as reviewed
  (persisted as a new `documentReview` OR object, mirroring the existing
  dossier-level `checkedOn` field); anonymize-commit and export for that
  document are blocked (HTTP 409) until the gate is satisfied. Batch
  anonymisation refuses to run while any file in the batch is unchecked.
- **Org-level "always anonymise" and "never anonymise" rule lists**: these ARE
  the existing policy objects (verified at HEAD) — `publicationProhibition`
  (= always anonymise, `entityType` is the category) and entity-scope
  `publicationConsent` standing consents (= never anonymise). Both schemas
  gain a `bases` array (grondslag per rule, GH #62/#63); both rule kinds are
  pre-applied during review (prohibition match → included + locked-on hint;
  standing-consent match → excluded + badge), and the existing
  `ProhibitionIndex`/`StandingConsentIndex` views become reachable from the
  workbench.
- **Grondslag auto-proposal surfaced**: the proposals `GrondslagProposalService`
  already writes at extract time (CB #122) are shown as pre-filled, overridable
  values in the workbench's grondslag pickers, including for manual entities.

## Capabilities

### New Capabilities

- `anonymization-review-workbench`: the interactive human-review workbench —
  side-by-side preview, selection-to-manual-entity, per-document checked gate,
  org rule-list pre-application, grondslag proposal surfacing.

### Modified Capabilities

- `anonymization-entity-review`: the consolidated-entities response gains a
  `standingConsentMatch` field (sibling of the existing `prohibitionMatch`),
  and the batch anonymize commit gains the per-document checked-gate
  precondition.

## Impact

- **Backend**: new `DocumentReviewController` (+ routes) for the checked gate;
  `AnonymizationService`/`EntityConsolidationService` attach
  `standingConsentMatch` next to `prohibitionMatch` (both via the existing
  `PolicyMatchService`); `BatchAnonymizationController::batchAnonymize` and
  `AnonymizationController::anonymize` enforce the gate; `PolicyCrudService`
  accepts the new `bases` property.
- **Register JSON** (`lib/Settings/filinq_register.json`): new
  `documentReview` schema; `bases` array added to `publicationProhibition`
  and `publicationConsent`.
- **Frontend**: new `src/views/anonymization/ReviewWorkbench.vue` (+ preview
  highlight layer), selection-to-entity wiring into `AddManualEntityModal`,
  checked-gate UI, policy badges in `EntityReviewTable`; policy form modals
  gain a grondslag picker.
- **No engine changes**: entity detection, text extraction and anonymisation
  stay in OpenRegister (`TextExtractionService`, `EntityRelationMapper`,
  `FileService::anonymizeDocument`); the workbench orchestrates and renders.
- **Dependencies**: OpenRegister manual-entities endpoint
  (`fileText#addManualEntity`, verified present at OR HEAD); no new external
  services; all processing stays local.
