# Design: image-redaction

## Context

Verified at merged HEAD (`development`, includes the nine wave-1 changes and
Robert's PR #314: KENTEKEN entity type, ODT anonymisation, PDF entity-review
viewer, grondslag/prohibition guards, eml assembly). Robert's baseline covers
entity-type and office-format anonymisation; the pixel/signature/embedded-image
gap below remains:

- OR `DocumentProcessingHandler` (lib/Service/File/) dispatches
  `replaceWords()` by extension/MIME: `pdf` → `PdfTextReplacer` (+
  `PdfMetadataSanitizer`), sanitisable Office → `OfficeDocumentSanitizer` +
  walker, else `TextReplacer`. No image branch; `resolveEngineName()` knows
  only those three engines. Pixels are never modified anywhere in the
  anonymisation stack.
- OR `AnonymisationBackendService` (lib/Service/Anonymisation/) probes
  `regex` / `presidio` / `openanonymiser` / `llm` backends; the Presidio
  probe targets the configured `presidioApiEndpoint` — the **text** analyzer.
  Presidio upstream ships a separate image-redactor engine and container
  (`ImageRedactorEngine`; QR-code support; DICOM engines — intelligence-DB
  competitor features), unused here.
- OR `EntityRecognitionHandler` owns the entity taxonomy as constants
  (`PERSON`, `ORGANIZATION`, `EMAIL`, `IBAN`, …) and accepts an
  `entity_types` filter. No `SIGNATURE` type exists anywhere.
- Filinq `OcrService` rasterises PDF pages with Imagick at
  `ocr.default_dpi` (default 300) for Tesseract; word-level geometry is
  discarded. Wave-1 `ocr-trigger-surface` (REQ-DDOCR-003/004) feeds recovered
  text into OR chunking + detection via the provided-text seam, so scans get
  **text** detection — and then `PdfTextReplacer` would "redact" a scan by
  editing a text layer the reader never sees, while the page image keeps
  showing everything.
- Wave-1 `anonymization-review-workbench` (REQ-DDARW-001..011) established:
  one shared entity decision model, best-effort preview highlights, the
  per-document checked gate, and that the entity table is the source of
  truth. This change adds a region overlay layer and region rows — it MUST
  NOT fork that model (its spec is referenced, not modified here).
- `anonymizationLink` schema (filinq register) records per-run evidence
  (`replacementCount`, `runCount`, output references) — the natural additive
  home for burn counters and pending flags.

## Goals / Non-Goals

**Goals:**

- Detection of PII inside image content (image files, embedded images,
  scanned-PDF pages) via the OR backend chain, with region geometry.
- `SIGNATURE` as a first-class, reviewable entity type.
- Irreversible pixel-burn redaction integrated into the existing anonymise
  commit and the review workbench.
- Fail-flagged degradation whenever the image path cannot run.

**Non-Goals:**

- No detection/redaction engine inside Filinq (ADR-017/ADR-022: engines
  live in OpenRegister's backend chain).
- No face detection/blurring, no video redaction (CaseGuard territory) —
  regions here come from PII entities and signatures, not biometrics.
- No draw-a-box free-form manual redaction canvas in v1 — manual additions
  ride the existing manual-entity flow; a free-region tool is a follow-up.
- No change to the OCR engine or the wave-1 OCR trigger surface (its spec is
  a dependency, referenced as-is).
- No searchable-PDF ("sandwich") authoring — the burned output keeps
  whatever honest text layer remains after region stripping.

## Decisions

### D1 — Division of labour: OR detects and burns per image; Filinq owns containers

Engines belong to OpenRegister; container/file handling around them is
Filinq's (same split as `ocr-trigger-surface` D1, where Filinq supplies
acquisition and OR owns detection). **Decision:**

- **OpenRegister** exposes an image seam on the backend chain (D2): given
  image bytes, return detected entities with normalised bounding boxes
  (`detect`), and given image bytes + regions, return the burned, re-encoded
  image (`redact`). The seam delegates to the configured image-capable
  backend — Presidio's image-redactor endpoint first (it performs
  detect-and-burn natively; OR composes the two operations from it).
- **Filinq** submits the right images: the file itself for image MIME
  types, page rasters (existing Imagick path, same DPI config as OCR) for
  scanned PDFs, extracted embedded images for born-digital PDFs with image
  XObjects; and reassembles the output container (replace page raster /
  embedded image, strip region text) after burn.

Rejected: (a) Filinq calling Presidio image-redactor directly — bypasses
the ADR-017 single backend-detection owner and forks backend configuration;
(b) OR owning rasterisation/reassembly — OR deliberately has no
Imagick/raster path (verified in wave 1) and container surgery is exactly
the file-plumbing Filinq already owns (`OcrService`, conversion cascade).

### D2 — Cross-app dependency: the OR image seam and the SIGNATURE type

OR has no image API and no `SIGNATURE` entity type at HEAD. **Decision:**
this change declares an explicit OpenRegister dependency, filed as an OR
issue + PR before the dependent tasks complete:

1. Image detection/redaction methods on the anonymisation backend surface
   (e.g. `detectImage(string $bytes, array $options): array{entities:
   list<array{type, confidence, boxes: list<array{page?, x, y, w, h}>}>}`
   and `redactImage(string $bytes, array $regions): string`), boxes
   normalised to [0..1] so DPI stays a Filinq-side concern.
2. `ENTITY_TYPE_SIGNATURE = 'SIGNATURE'` in `EntityRecognitionHandler`'s
   taxonomy plus backend capability flags (`supportsImages`,
   `supportsSignatures`) on the probe/state payload, so
   `AnonymiserBackendStateClient::getState()` lets Filinq render honest
   availability.

**Degraded behaviour until the seam lands (fail-flagged, never
fail-silent):** extract marks image-bearing files `imageDetectionSkipped`
(reason `backend_unavailable`), anonymise of a file with undetected image
content marks the run `imageRedactionPending`, and the review UI shows the
warning — mirroring REQ-DDOCR-004's `ocrDetectionPending` contract.

### D3 — Signature detection is a backend capability, not a Filinq model

All 9 algoritmeregister entries mask handwritten signatures, but no shipped
backend detects them today (Presidio image mode detects text-PII via OCR;
signatures need a small vision model or heuristic). **Decision:** the seam's
*contract* includes `SIGNATURE` with boxes; *which* backend provides it is
configuration (Presidio custom recognizer, the OpenAnonymiser ExApp, or a
future dedicated model — engine choice lives OR-side). Filinq consumes
`supportsSignatures` from the backend state: when false, the review UI shows
"signature detection not available on this instance" instead of an empty,
falsely-reassuring result. No signature heuristic is implemented in Filinq.

### D4 — Burn semantics: re-encode, strip, verify

A redaction that draws a rectangle over intact data is the classic redaction
failure (copy-paste recovers the text). **Decision:** committing image-region
redactions MUST:

1. paint each accepted region opaque in the raster and **re-encode** the
   image, replacing the original image object/page raster in the output —
   the source pixels do not exist in the output file;
2. remove text-layer content and request deletion of OR chunk text covering
   the burned region (scanned PDFs carry the OCR text layer/chunks from
   wave 1 — burning pixels while the text layer keeps the BSN would move the
   leak, not fix it);
3. verify irreversibility before reporting success: the output MUST NOT
   contain the original image stream, and MUST NOT rely on annotations,
   OCGs, or overlay drawing (checked structurally, same byte-level style as
   the existing validation heuristics).

The burned raster's resolution equals the detection raster (OCR DPI config).
Trade-off accepted: for a scanned page, the output page becomes a re-encoded
raster at that DPI — visually equivalent for scans, and the only honest
option ("onleesbaar" beats lossless).

### D5 — One decision model: regions are entity occurrences

Image detections enter the SAME consolidated entity list the workbench
reviews (REQ-DDARW-002): each image entity row carries `origin: "image"`
and its boxes; overlays render from the rows; accept/reject/bases use the
existing `included`/`_decisionSkip`/`_decisionBases` semantics; the checked
gate (REQ-DDARW-007/008) applies with zero changes. Prohibition- and
standing-consent pre-application (REQ-DDARW-004) applies to image entities
identically — the matcher works on entity text (OCR'd) and type. Rejected: a
separate "regions" store/panel — a second decision model is how detections
escape review.

### D6 — Placement in the pipeline

Detection: in `AnonymizationService::extractAndDetectEntities()`, after OR
text extraction and the wave-1 OCR fallback — image submission happens for
(a) image-MIME files, (b) PDFs flagged scan-like by `needsOcr()` semantics,
(c) born-digital PDFs with embedded raster XObjects. Burn: inside the
anonymise commit, before the `outputFormat` PDF-conversion gate, so the
cascade converts the already-burned artifact. Both stages are imperative
(NLP/engine orchestration — valid ADR-031 exceptions); the additive
`anonymizationLink` fields are declarative register JSON changes; no
lifecycle/notification annotations.

### D7 — OpenRegister usage (ADR-001) and frontend (ADR-012)

OR services consumed: `AnonymisationBackendService` state + image seam (D2),
`TextExtractionService` provided-text seam (wave 1, unchanged),
`EntityRelationMapper` reads, `FileService::anonymizeDocument` for the
text-side replacement of mixed documents (burn composes with it, never
replaces it). All persisted state is OR objects; `anonymizationLink` gains
additive fields only (union-merge the register JSON). ADR-011: no new
validation/format utilities — geometry normalisation is the only new math
and lives in one service. Frontend: overlays as an absolutely-positioned
layer on the existing workbench preview panes; `SIGNATURE` badge and
degradation banners use NC CSS variables (ADR-003); strings EN-keyed with NL
translations (ADR-005).

## Seed Data

```json
// anonymizationLink — additive fields after a burn run (Demostad scan)
{ "sourceFileId": 812005,
  "anonymizedFileId": 812441,
  "replacementCount": 12,
  "burnedRegionCount": 3,
  "imageRedactionPending": false,
  "runCount": 1 }

// consolidated entity row (API shape) — an image-origin signature detection
{ "entityType": "SIGNATURE",
  "text": "",
  "confidence": 0.83,
  "origin": "image",
  "boxes": [ { "page": 4, "x": 0.61, "y": 0.78, "w": 0.24, "h": 0.09 } ],
  "included": true }
```

Seed task: one scanned sample letter ("Aanvraag parkeervergunning Demostad",
tests/sample-documents) containing a printed BSN and a handwritten-style
signature block, so overlays, the SIGNATURE badge, and the burn flow demo on
a clean install.

## Risks / Trade-offs

- [OR image seam + SIGNATURE type are cross-app and may lag] → D2 fail-flagged
  degradation; detection surfacing, flags, UI and reassembly are
  independently testable against a seam fake pinned to the documented
  contract (and verified against OR HEAD at apply time).
- [Presidio image mode does its own internal OCR — double OCR with wave 1] →
  accepted for v1: the seam contract is engine-agnostic bytes-in/boxes-out;
  deduplication of OCR passes is an OR-side optimisation, noted in the OR
  issue.
- [Burned output loses born-digital fidelity when a mixed PDF has one
  embedded image] → only affected page objects are re-encoded; text-operator
  content on the same page is preserved by the reassembly path.
- [Region/text-layer mismatch (box drift between detection raster and
  output)] → same raster is used for detection and burn (single
  rasterisation per page, cached through the run); scenario-tested.
- [False sense of coverage when backend lacks signatures] → D3 honest
  capability surfacing; the workbench states what was NOT scanned for.

## Migration Plan

1. Register JSON: additive `anonymizationLink` fields (union-merge).
2. File the OR issue + PR (image seam, SIGNATURE type, capability flags).
3. Filinq detection leg + flags + workbench overlays (works fail-flagged
   without the seam).
4. Burn leg + irreversibility verification + reassembly; flip degradation
   off when the seam is live.
5. Rollback: image submission is gated on backend availability — without a
   configured image-capable backend the whole surface degrades to today's
   behaviour plus warnings; no data migration to unwind.

## Open Questions

- Embedded-image extraction for born-digital PDFs: **RESOLVED — in v1**
  (decision D1). All three submission sources ship in the first release:
  image-MIME files, scanned-PDF page rasters, and images extracted from image
  XObjects of born-digital PDFs (`Pdfa3ConversionService` already handles the
  XObject-wrapping direction; the extraction direction reuses the same fpdi
  parsing Robert added). Only if a specific PDF's XObject cannot be decoded
  does the file flag `imageDetectionSkipped` reason `embedded_images_unsupported`
  — a per-file honest-degradation reason, not a whole-feature deferral.
- Minimum confidence for auto-including SIGNATURE regions (signatures have
  no text to eyeball) — provisional: same threshold mechanism as other
  entities; the workbench preview overlay IS the review affordance.
