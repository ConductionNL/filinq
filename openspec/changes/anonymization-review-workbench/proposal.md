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

## Amendment 2026-10-05: one reviewable rendition, and certain versus uncertain (Woo rows 4.25 and 14.15)

Woo capability row 4.25, "Every input format is re-rendered into one
reviewable rendition with a text layer, and the product says which rendition
the review was done on". Our column reads `no`: "each format is redacted in its
own container: SAPP for PDF, PhpWord and ZipArchive for docx ... a per-format
fallback, not a common rendition, and AnonymisationLog records the mimeType
that was processed rather than stating which rendition a review was done on".
Gap: "Normalise every input to one reviewable PDF rendition with a text layer
before review, and record on the anonymizationLink which rendition the review
was done on."

Woo capability row 14.15, "A confidence threshold is set by the organisation,
and the screen tells a certain finding from an uncertain one". Our column
reads `partial`: "every openregister EntityRelation carries a confidence ...
The threshold is a private const, not a setting". Gap: "The organisation sets
the confidence threshold as a setting (openregister file configuration), and
the review screen marks findings below it as uncertain."

Build plan: amend this change, wave 2, size L. Depends on
`openregister/anonymisation-discloses-itself`, whose REQ-ADI-007 makes
`anonymisation.confidenceThreshold` an OpenRegister file setting (default
0.5), returned by `GET /api/settings/files`. Implements decision D2's split:
detection stays in OpenRegister, review stays in filinq.

### One point the row and the dependency disagree on

OpenRegister's REQ-ADI-007 makes the setting a detection floor: a finding
below it is not stored ("a raised threshold drops weak findings"). So a
finding below that number never reaches the review screen to be marked
uncertain, as the gap text puts it. This amendment keeps the row's meaning
with two organisation settings: OpenRegister's floor (below it, nothing is
found), and filinq's `filinq.review.certain_from` (default 0.85, never below
the floor). A finding between the two is uncertain, a finding at or above
`certain_from` is certain, and a manual entity is certain. The screen shows
both numbers and where each comes from. The report records this as a
reading of the row, not a change to it.

### What this amendment adds, on top of tasks 1 to 4 (none of which is built)

1. A review rendition. Before review, every input becomes one PDF with a text
   layer: a PDF with native text is used as it is; an office document, an
   e-mail or an HTML file is converted with `PdfConversionService`; a scan or
   an image gets an OCR text layer from `OcrService`. Detection and review run
   on that rendition.
2. The link says which rendition. `anonymizationLink` gains `reviewRendition`:
   `fileId`, `sha256`, `kind` (`native-pdf`, `converted`, `ocr-text-layer`),
   `backend`, `textLayer` (`native`, `ocr`, `none`) and `preparedAt`. The
   `documentReview` check records the same `sha256`.
3. Certain and uncertain on the screen, from the two settings above, with a
   filter and a counter for uncertain findings.
4. An uncertain finding needs a decision. A document cannot be marked checked
   while an uncertain finding has no explicit include or skip.

### Fail closed

- A rendition without a text layer (`textLayer: none`, for example OCR not
  installed for a scan) makes detection unable to see the text. The
  workbench says so, and the document cannot be marked checked until a person
  confirms they reviewed the pages by eye and adds every entity by hand.
- If the rendition changes after the check (a different `sha256`), the check
  no longer holds and the anonymize commit is refused until re-review.
- An uncertain finding defaults to included (redacted), never to skipped.
- `filinq.review.certain_from` below OpenRegister's floor is refused on save.

### App absent

OpenRegister absent: there is no detection, and the workbench is unavailable
as today. OpenRegister present without REQ-ADI-007 (an older release): the
floor is read as the old literal 0.5 and the screen says "floor not
configurable in this OpenRegister version".
