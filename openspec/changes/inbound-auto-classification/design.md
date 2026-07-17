# Design: inbound-auto-classification

## Context

Verified at HEAD `9cc14407`:

- Enrichment path: `DocuDeskEventListener` (ObjectCreated/Updated/Deleted)
  → `DocuDeskEventHandler` → `EnrichmentRunner` →
  `MetadataService::enhanceMetadata(array $objectData)` →
  `saveEnrichedMetadata(...)`; on-demand via
  `POST /api/metadata/enrich` (`MetadataController`). Text is taken from
  object fields in priority `content` → `text` → `description`
  (REQ-META-10, `DocumentTextExtractor`).
- Classifier boundary (REQ-META-11): `LanguageClassifier` is stateless,
  owns `DUTCH_WORDS`/`ENGLISH_WORDS`/`TOPIC_KEYWORDS` constants, a
  minimum-match threshold and whitespace-boundary
  `countWordOccurrences()`; `TextAnalysisService::detectLanguage()` /
  `classifyTopic()` forward to it via DI. Existing `TOPIC_KEYWORDS` are
  subject domains (legal/financial/medical/technical) — NOT document
  types; document types are handled only by
  `standardizeDocumentType()`, which maps *file-format* strings
  (docx→word), not intake types.
- Entity detection: `AnonymizationService::extractAndDetectEntities()`
  delegates to OpenRegister extraction + `EntityRelationMapper::
  findEntitiesForFile()`; detected entities carry type
  (PERSON/ORGANIZATION/...) and text. Wave-1 `ocr-trigger-surface` adds
  OCR-recovered text for scans through the same OR pipeline.
- Suggest-then-approve precedents: `GlAccountSuggestionService::suggest()`
  + `recordBooking()` (corrections corpus for future tuning);
  `anonymization-review-workbench` `documentReview` checked gate (wave 1,
  in-flight).
- Dossiers: schema `dossier` (`name`, `description`, `bases[]`,
  `checkedOn`), folder binding via `@self.folder`; a first-class dossier
  UI is arriving in the sibling in-flight change `dossier-management-ui`
  (proposal stage). `zgw-document-bridge` (wave 1) pulls external
  documents in with dossier pick-up.
- Register JSON at v5.10.0, 18 schemas; `financialExtraction`
  establishes the additive `corrections[]`-style tuning-corpus pattern
  and the explicit retention-placeholder pattern.

## Goals / Non-Goals

**Goals:**

- Type + correspondent suggestions on inbound documents, cheap and local.
- A human confirmation loop whose corrections are recorded (the future
  training corpus).
- Suggestion-only dossier routing.

**Non-Goals:**

- No model training / learned classification this wave (paperless-ngx
  parity on *learning* is deferred; parity on *workflow* is this change).
- No auto-filing, no auto-tagging, no metadata writes without a human
  confirm — explicitly out, this is the change's safety posture.
- No department/role routing ("route to the responsible department" —
  requires an org model DocuDesk does not have; recorded as follow-up).
- No new NER; correspondent extraction reads entities the anonymisation
  pipeline already detected.
- No changes to the existing direct-write enrichment fields (language,
  keywords, topic, documentType standardisation, dates keep their
  skip-if-populated semantics).

## Decisions

### D1 — A dedicated `DocumentTypeClassifier`, not a bigger `LanguageClassifier`

REQ-META-11 fixed the class boundary: vocabularies + scoring live in one
stateless classifier class consumed via DI. Document-type classification
gets its own sibling class `DocumentTypeClassifier` (constants
`TYPE_KEYWORDS` — per-type Dutch keyword/phrase lists — plus per-type
structural cues such as "Factuurnummer"/"IBAN" for `factuur`,
"Besluit"/"overwegende dat" for `besluit`; a minimum-score threshold; the
same whitespace-boundary counting helper semantics). Rejected: stuffing
document types into `LanguageClassifier::TOPIC_KEYWORDS` — topics and
intake types are orthogonal axes (a `besluit` can be legal AND financial)
and REQ-META-03's scoring contract must not change. The scoring returns
`{type, confidence}` where confidence is a normalised 0–1 score;
below-threshold yields `overig` with low confidence rather than null, so
the intake queue is exhaustive.

### D2 — Correspondent = already-detected entities + position heuristics

The "backend model" leg (brief's rule/keyword + backend-model split)
reuses OpenRegister NER output: `InboundClassificationService` reads the
file's detected entities and ranks PERSON/ORGANIZATION candidates by
position (first-page/letterhead zone first), frequency and an
organisation-suffix heuristic (B.V., N.V., Gemeente, Stichting). The top
candidate becomes `suggestedCorrespondent {name, entityType, source:
"ner"}`; no candidate ⇒ no correspondent suggestion (never fabricate).
Rejected: running a second NER pass in DocuDesk (duplicate engine,
ADR-022) and regex-only extraction (strictly worse than the NER already
paid for). When a document has no entity detection yet (classification
can run before anonymisation intake), the correspondent leg is skipped
and marked `correspondentPending` — fail-flagged, not silent.

### D3 — Suggestions are `classificationResult` objects, never field writes

New register schema `classificationResult`, keyed by `fileId`
(idempotency key; re-classification supersedes): `fileId`, `fileName`,
`suggestedDocumentType` (enum `brief|besluit|factuur|rapport|contract|
formulier|overig`), `documentTypeConfidence` (0–1), `method`
(`rules|ner|mixed`), `suggestedCorrespondent` (object: `name`,
`entityType` PERSON|ORGANIZATION, `source`), `suggestedDossier` (dossier
object UUID, nullable), `status`
(`suggested|confirmed|rejected|superseded`), `confirmedDocumentType`,
`confirmedCorrespondent`, `confirmedDossier`, `confirmedBy`,
`confirmedAt`. The confirmed* columns double as the corrections corpus:
a confirm-with-correction stores both what the machine said and what the
human decided — exactly the `GlAccountSuggestionService::recordBooking()`
pattern, and the training set for the deferred learned classifier.
Rejected: writing `documentType` on the enriched object with a
"suggested" flag — one field cannot carry suggested vs confirmed without
every consumer learning the flag; a separate record keeps canonical
metadata trustworthy by construction.

### D4 — Human confirmation is the ONLY path to canonical effect

Confirming a suggestion (a) sets the canonical `documentType` on the
enriched object (through the normal save path), (b) records the
correspondent on the document's metadata, and (c) — only when a
`suggestedDossier`/`confirmedDossier` is present — attaches the document
via the dossier's existing folder binding (`@self.folder`), i.e. the file
moves into the dossier folder. Rejecting closes the suggestion with no
effect. AI Act posture: this is machine-assisted *preparation* of a human
decision (Art. 14-style oversight); DocuDesk never files, types or
routes a document autonomously — the same suggest-then-approve stance the
review workbench takes for entities. There is deliberately NO
"auto-confirm above confidence X" admin option in v1 (it would be a
silent-auto-file backdoor; revisit only with the learned classifier and
a documented risk assessment).

### D5 — Wiring: the enrichment path triggers, a toggle governs

Classification runs where enrichment runs: `EnrichmentRunner` invokes
`InboundClassificationService::classify()` after `enhanceMetadata()`
when `enable_inbound_classification` (IAppConfig, default true) is on,
and the on-demand `POST /api/metadata/enrich` gains the same hook.
Skip conditions: an existing non-superseded `classificationResult` in
`suggested`/`confirmed` (no churn on every update event), no extractable
text (REQ-META-10 empty ⇒ skip flagged `no_text`). The confirmation API
is a small `ClassificationController`
(`GET /api/classification/pending`, `POST
/api/classification/{fileId}/confirm`, `.../reject`) with auth attributes
and per-object access checks (route-auth + no-admin-idor gates).
Declarative-vs-imperative (ADR-031): the schema is declarative register
JSON; classification orchestration is imperative (NLP-pipeline exception
category, same ruling as wave-1); no lifecycle dialect on
`classificationResult` (status transitions are human actions through the
controller, audited by OR).

### D6 — Dossier matching is conservative

`suggestedDossier` is set only on a high-precision match: the suggested
correspondent name or an explicit case reference in the text matches a
dossier `name` (normalised exact/prefix match) — no fuzzy scoring in v1
(a wrong filing suggestion erodes trust faster than no suggestion).
Coordination: the confirm-attach action uses the dossier folder binding
that `dossier-management-ui` (in-flight) is building its membership flows
on; until that lands, confirm records `confirmedDossier` and moves the
file to the dossier's bound folder when resolvable, else records-only —
degradation is visible in the response, never silent.

## Seed Data

```json
// classificationResult — an inbound invoice, awaiting confirmation
{ "fileId": 812010,
  "fileName": "scan-factuur-heijmans.pdf",
  "suggestedDocumentType": "factuur",
  "documentTypeConfidence": 0.86,
  "method": "mixed",
  "suggestedCorrespondent": { "name": "Heijmans B.V.", "entityType": "ORGANIZATION", "source": "ner" },
  "suggestedDossier": null,
  "status": "suggested" }

// classificationResult — a confirmed-with-correction besluit (corpus row)
{ "fileId": 812011,
  "fileName": "brief-bezwaar-2026-114.pdf",
  "suggestedDocumentType": "brief",
  "documentTypeConfidence": 0.55,
  "method": "rules",
  "suggestedCorrespondent": { "name": "J. de Vries", "entityType": "PERSON", "source": "ner" },
  "suggestedDossier": "00000000-0000-0000-0000-00000000d055",
  "status": "confirmed",
  "confirmedDocumentType": "besluit",
  "confirmedDossier": "00000000-0000-0000-0000-00000000d055",
  "confirmedBy": "w.devries",
  "confirmedAt": "2026-07-17T10:20:00Z" }
```

Seed task: the two objects above (nil-pattern dossier UUID, fixture
fileIds) so the pending-classification list renders on a clean install.

## Security & GDPR Considerations

- `suggestedCorrespondent.name` / `confirmedCorrespondent` are personal
  data (AVG art. 4(1)) stored for the intake/registration purpose
  (art. 5(1)(b)); the schema declares `x-openregister-archival` retention
  `P1Y` as an explicit placeholder pending selectielijst-manager sign-off
  (same pattern as `financialExtraction`), and the record stores the
  correspondent name only — never the document text or other detected
  entities (data minimisation; entity values stay behind the review UI).
- Confirmation endpoints carry explicit auth attributes and resolve the
  file through the requesting user's access (no cross-user
  confirmation); confirm/reject actions are attributable
  (`confirmedBy`) and OR-audited.
- Classification is local (vocabularies + already-computed NER); no
  external calls; the admin toggle disables the whole surface.
- The absence of an auto-confirm option is a deliberate control, not a
  missing feature (D4).

## Risks / Trade-offs

- [Keyword vocabularies misclassify edge cases] → confidence is shown,
  `overig` is the honest fallback, and every correction is recorded —
  the corpus quantifies accuracy before anyone proposes auto-anything.
- [Correspondent depends on entity detection having run] →
  `correspondentPending` flag + the suggestion updates when detection
  completes (supersede path); never blocks the type suggestion.
- [Suggestion fatigue at postkamer volume] → pending list supports bulk
  confirm of high-confidence rows (each still a human action on visible
  rows) and per-folder toggle scoping is an open question below.
- [Parallel in-flight changes (dossier-management-ui, email-ingestion)] →
  this change only *reads* dossiers and consumes whatever ingest surface
  exists; coordination is one-directional and degradations are flagged
  (D6).

## Migration Plan

1. Register JSON: add `classificationResult` (additive, union-merge;
   re-validate JSON after merge).
2. Ship classifier + service + enrichment hook + API (suggestions start
   accumulating; no UI yet ⇒ no user-visible behaviour change).
3. Ship the confirmation surface (pending list + document-detail card).
4. Rollback: toggle off `enable_inbound_classification`; existing
   suggestion records remain readable; no data migration to unwind.

## Open Questions

- Should classification be scopeable to specific folders (e.g. only
  `/Inkomend`) instead of all enriched objects? Provisional: global
  toggle in v1; folder scoping composes naturally with `flow-operations`
  later (a Flow rule triggering classification per folder).
- Should `factuur` classification consume `FinancialExtractionService`
  signals (IBAN/KvK extractors) as a confidence boost? Deferred — keeps
  v1 free of cross-service coupling; the corpus will show whether it is
  needed.
- Threshold for "high-precision" dossier match (exact vs prefix) — pin
  during apply against real dossier names from the pilot.
