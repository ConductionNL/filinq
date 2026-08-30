# Design: ocr-trigger-surface

## Context

Verified at HEAD:

- `OcrService` public seams: `isTesseractAvailable()`,
  `getTesseractVersion()`, `needsOcr(string $mimeType, ?string
  $existingText)`, `isOcrEnabled()` (`ocr_enabled`, default on),
  `getOcrLanguages()` (`ocr.default_languages` with legacy
  `ocr_languages` fallback, default `nld+eng`), `getOcrDpi()`
  (`ocr.default_dpi` / legacy `ocr_dpi`, default 300),
  `extractTextFromImage()`, `extractTextFromPdf()`, and
  `processFile(int $fileId): array{text, confidence, ocrProcessed}` —
  which already handles disabled-OCR, missing Tesseract, missing file and
  non-candidate MIME by returning `ocrProcessed: false` absent-safely.
- Callers: exactly one — `FinancialExtractionService::resolveText()`
  (scan-en-herken). No route, no UI, no anonymisation-pipeline use.
- `AnonymizationService::extractAndDetectEntities(int $fileId)` calls OR
  `TextExtractionService::extractFile($fileId, true)` then
  `EntityRelationMapper::findEntitiesForFile($fileId)`.
- OpenRegister does NO OCR (zero OCR/Tesseract references in
  `TextExtractionService` + `lib/Service/TextExtraction/` handlers,
  verified against the local openregister checkout). OR's pipeline is:
  extract (FileHandler/PdfExtractor/WordExtractor/...) → `textToChunks`
  → `persistChunksForSource` (private) →
  `EntityRecognitionHandler::processSourceChunks`. There is **no public
  seam to ingest externally-supplied text for a fileId** at HEAD.
- `FileListingService::buildFileInfo()` returns `ocrProcessed` =
  (OCR-candidate MIME && status !== 'uploaded') and `ocrConfidence` =
  `null` — a heuristic, not a record; the canonical
  `ocr-document-scanning` spec's REQ-OCR-05 and its Architecture note
  ("Called by `AnonymizationService::extractAndDetectEntities()`") do not
  match the code. This change makes the spec true instead of leaving it
  aspirational.

## Goals / Non-Goals

**Goals:**

- Give the existing OCR engine an invocation surface: route, UI action,
  and automatic anonymisation-pipeline fallback.
- Make per-file OCR status honest and persistent.
- Pin the Filinq↔OpenRegister OCR division of labour in a spec.

**Non-Goals:**

- No OCR engine work (Tesseract wrapping, rasterisation, confidence —
  all exist and stay as-is).
- No searchable-PDF ("sandwich PDF") output, no image redaction/blackout
  of scans — scans get *detection and review*; visual redaction of image
  content is a separate future change.
- No change to scan-en-herken (`FinancialExtractionService` keeps its own
  `resolveText()` path).
- No background/bulk OCR queue — bulk behaviour arrives via
  `redaction-at-scale` work units, which call the same pipeline.

## Decisions

### D1 — OCR stays in Filinq; OR ingests text (the boundary decision)

Per the project boundaries, extraction/detection engines live in
OpenRegister — but OCR is a *text acquisition* step OR deliberately does
not perform, and Filinq already owns a hardened local Tesseract path
used by scan-en-herken. **Decision:** Filinq remains the OCR provider;
OpenRegister remains the single owner of chunking + entity detection. The
recovered OCR text must therefore enter OR's pipeline, which requires an
OR-side ingestion seam (D2). Rejected alternatives: (a) moving OcrService
into OR — right long-term home, but a cross-app engine migration is out
of this change's scope and would orphan the scan-en-herken caller; (b)
Filinq running its own entity detection over OCR text — duplicates the
engine (ADR-022 violation) and forks entity storage.

### D2 — Cross-app dependency: an OR provided-text ingestion seam

OR has no public API to persist chunks for externally-supplied text
(`persistChunksForSource` is private; `chunkDocument()` is public but
returns arrays without persisting). **Decision:** this change declares an
explicit OpenRegister dependency — an `extractFile()` overload or sibling
(e.g. `extractFromProvidedText(int $fileId, string $text)`) that chunks,
persists and runs entity recognition for a fileId using caller-provided
text; filed as an OR issue and implemented as a small OR PR before this
change's task 2.3 can complete. **Degraded behaviour until it lands
(fail-flagged, never fail-silent):** the extract response and review UI
mark the file `ocrDetectionPending` — OCR ran, text was recovered, but
entity detection could not consume it. The requirement (REQ-DDOCR-004)
scenarios cover both states. Rejected: Filinq writing OR `Chunk` rows
via container-resolved mappers — reaching into another app's private
persistence dialect is how silent drift defects happen.

### D3 — Fallback placement: inside `extractAndDetectEntities()`, after OR extraction

**Decision:** the fallback branch runs in
`AnonymizationService::extractAndDetectEntities()`: call OR
`extractFile()` first (cheap for born-digital files, unchanged behaviour),
then consult `OcrService::needsOcr(mime, extractedText)` — for candidate
files whose extraction yielded empty/near-empty text, run
`OcrService::processFile()` and ingest via D2. This keeps the trigger at
the orchestration layer where prohibition matching and grondslag proposals
already hang, so OCR-recovered entities flow through the SAME
post-processing (proposals, prohibition/standing-consent matches, risk
level) with zero extra wiring. "Low text" uses `needsOcr()`'s existing
empty-after-trim semantics for PDFs — no new heuristic invented in v1.

### D4 — Honest OCR status: a new `ocrResult` OR object

`FileListingService`'s heuristic must go. Options: NC file metadata
(non-queryable, lost on rescan), extending `anonymizationLink` (wrong
lifecycle — OCR happens before/without anonymisation), or a dedicated OR
object. **Decision:** new `ocrResult` schema keyed by `fileId`
(idempotency key; re-running OCR updates the object): `confidence`,
`languages`, `dpi`, `textLength`, `ocrProcessedAt`, `triggeredBy`
(`manual` | `fallback`), `engineVersion` (Tesseract version string).
`FileListingService.buildFileInfo()` returns `ocrProcessed: true` +
`ocrConfidence` only when an `ocrResult` exists; candidates without one
return `ocrProcessed: false, ocrAvailable: true` so the UI can offer the
action. GDPR note: `ocrResult` stores metadata only — never the OCR text
itself (the text lives in OR chunks like all other extracted text; data
minimisation, no second PII store).

### D5 — Route/UI contract mirrors the admin settings exactly

`POST /api/ocr/{fileId}`: 200 with the result on success; **409** when
OCR is disabled by the admin toggle (client error, actionable); **503**
when enabled but Tesseract is not installed (server-side capability
missing, matches the settings-page status display); **404** unknown file;
**400** non-candidate MIME. The UI action renders only when the file
listing says `ocrAvailable` and settings say enabled+installed — but the
server checks again (UI visibility is not authorization). Response never
includes the extracted text (it goes to OR chunks; the API returns
`textLength` + confidence).

### D6 — Declarative vs imperative (ADR-031)

- `ocrResult` schema: **declarative** register addition; no lifecycle
  annotation (single-state metadata record).
- Controller, fallback branch, OR ingestion call: **imperative** — NLP
  pipeline orchestration and external-engine invocation are valid ADR-031
  exception categories. No aggregations/notifications/relations dialects
  involved.

### D7 — OpenRegister usage (ADR-001) and frontend (ADR-012)

`ocrResult` persisted via the existing AppHost/ObjectService pattern (no
custom tables). OR services consumed: `TextExtractionService` (extract +
the D2 ingestion seam), `EntityRelationMapper` (unchanged reads). ADR-011
check: no new validation/formatting utilities; Tesseract wrapping already
exists in `OcrService`. Frontend: the Run-OCR action joins the existing
MyDocuments action menus and file-viewer header; confidence badge uses NC
CSS variables (ADR-003); strings EN-keyed with NL translations (ADR-005).

## Seed Data

```json
// ocrResult — a scanned municipal letter, manually OCR'd
{ "fileId": 812004,
  "confidence": 91.4,
  "languages": "nld+eng",
  "dpi": 300,
  "textLength": 4231,
  "ocrProcessedAt": "2026-07-16T10:05:00Z",
  "triggeredBy": "manual",
  "engineVersion": "tesseract 5.3.0" }

// ocrResult — a scan swept up by the anonymisation fallback
{ "fileId": 812005,
  "confidence": 78.9,
  "languages": "nld+eng",
  "dpi": 300,
  "textLength": 1876,
  "ocrProcessedAt": "2026-07-16T10:06:12Z",
  "triggeredBy": "fallback",
  "engineVersion": "tesseract 5.3.0" }
```

Seed task: include one scanned-PDF sample document (tests/sample-documents
already exists) + its `ocrResult` so the badge and status render on a
clean install.

## Risks / Trade-offs

- [OR ingestion seam is cross-app and may lag] → D2 degraded-but-flagged
  behaviour; the Filinq surface (route, UI, status, settings) is
  independently shippable and testable; the fallback's detection leg
  activates when the seam lands.
- [OCR on large PDFs is slow (Imagick rasterisation at 300 DPI)] → the
  route is synchronous per file (matches the existing extract-one-file
  interaction); bulk OCR flows through `redaction-at-scale` ticks;
  the UI shows a busy state and the route enforces one concurrent OCR per
  user (same fail-fast style as the soffice lock).
- [Low-confidence OCR yields garbage entities] → confidence is persisted
  and surfaced on the review workbench (low-confidence visual flag
  already exists in the entity-review capability); no auto-commit ever
  happens without the human gate.
- [ocrProcessed semantics change for existing UI consumers
  (FileEntitiesDashboardWidget reads it)] → the field keeps its name and
  boolean type; only its truth improves. `ocrConfidence` goes from
  always-null to sometimes-set — additive.

## Migration Plan

1. Register JSON: add `ocrResult` (additive, union-merge).
2. Ship `OcrController` + routes + UI action + honest FileListingService
   reads (independently valuable: manual OCR works end-to-end minus
   detection).
3. Land the OR ingestion seam (cross-app PR); flip the fallback's
   detection leg from `ocrDetectionPending` to full ingestion.
4. Rollback: the admin `ocr_enabled` toggle disables the whole surface
   (route 409s, UI hides); no data migration to unwind.

## Open Questions

- Should `triggeredBy: fallback` OCR runs be rate-limited per batch to
  protect ticks (a 480-file scan dossier = 480 OCR runs)? Provisional:
  bounded by `redaction-at-scale`'s `seconds_per_tick` budget; no
  separate OCR budget in v1.
- Long-term: migrate `OcrService` into OpenRegister as a text-extraction
  handler so ALL OR consumers get OCR? Logged as the architectural
  follow-up in the OR issue of D2 — explicitly out of scope here.
