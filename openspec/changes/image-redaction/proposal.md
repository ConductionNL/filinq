---
kind: code
---

# Proposal: image-redaction

## Why

Robert's project branch (merged into `development`, PR #314) is the new
baseline: it landed the `KENTEKEN` entity type (`src/services/entityTypes.js`,
`GrondslagProposalService`), ODT anonymisation (`AnonymizationService`,
`LibreOfficeHeadlessBackend`/`PhpWordBackend`), the PDF entity-review viewer,
grondslag/prohibition guards and eml assembly. This change therefore does NOT
re-spec entity-type coverage or ODT/office anonymisation — those ship. It
scopes to the gap Robert's branch did **not** close: Filinq can now *detect*
PII on scans (wave-1 `ocr-trigger-surface` feeds OCR-recovered text into
OpenRegister entity detection) but it still cannot *remove* PII from pixels,
detect **handwritten signatures**, or reach images **embedded** in born-digital
PDFs. Verified at HEAD:

- OpenRegister's `DocumentProcessingHandler::replaceWords()` dispatches PDF →
  `PdfTextReplacer` (text-operator replacement), DOCX/ODT → sanitize +
  PhpWord/ZIP walker, everything else → plain text replacement. There is **no
  image branch**: a photo/scan image file, an image embedded in a PDF, or the
  page raster of a scanned PDF is never touched. Anonymising a scanned PDF
  after the OCR fallback replaces the (invisible) text layer while the PII
  stays fully readable in the page image — the output reports success and
  still shows the BSN. This is the same silent-privacy-failure class as GH
  #285, one level deeper.
- The entire detection backend chain is text-only: Filinq reaches backends
  via `AnonymiserBackendStateClient` → OR `AnonymisationBackendService`
  (methods `regex` / `presidio` / `openanonymiser` / `llm` / `hybrid`, all
  probed as text APIs). No backend is ever asked about image content.
- `OcrService` already rasterises PDF pages via Imagick at configured DPI —
  the coordinate space needed for regions exists, but word geometry is thrown
  away (only text + mean confidence are kept).

Demand is concrete: the **algoritmeregister profile** (all 9 Dutch-government
anonymisation entries: Rotterdam, Zaanstad, Min AZ, mostly Octobox) lists
detection and masking of **handwritten signatures** as a standard capability;
the **Arnhem tender 407824** requires redacted passages to be made
"onleesbaar" — irreversibly unreadable, which for scans means pixels, not
text operators; **Microsoft Presidio** — already OR's first-class `presidio`
backend — ships a dedicated image-redaction mode (`ImageRedactorEngine`,
dedicated image-redactor containers, verified in the intelligence DB); and
competitors **CaseGuard** and **Redact.dev** sell image/face/signature
redaction as a headline feature (spectr `image-redaction` canonical feature).

## What

- **Image PII detection through the OR backend chain**: images (photos,
  standalone scans, images embedded in PDFs) and rasterised scanned-PDF pages
  are submitted to an OpenRegister image-detection seam that delegates to an
  image-capable backend (Presidio image mode first), returning detected
  entities **with per-page normalised bounding boxes**. Filinq never runs
  its own detection engine (ADR-017/ADR-022).
- **Handwritten-signature detection** modelled as a new entity type
  `SIGNATURE` in the shared taxonomy (sibling of `PERSON`, `ORGANIZATION`,
  `EMAIL`, `IBAN`, …): detected by an image-capable backend, reviewed and
  masked like any other entity. When no signature-capable backend is
  configured, the state is reported honestly — never silently "no
  signatures".
- **Pixel-burning redaction**: committing an image-region redaction
  rasterises the affected region, paints it opaque, **re-encodes the image**
  (no overlay, no annotation, no vector rectangle on top of intact pixels),
  removes any text-layer content and OR chunks covering the burned region,
  and produces the anonymised output from the burned raster — irreversible by
  construction ("onleesbaar").
- **Review-workbench integration**: detected image regions render as
  overlays in the wave-1 `anonymization-review-workbench` preview and appear
  as rows in the same entity decision model (`included` / skip / bases) —
  one model, no forked state; the per-document checked gate applies
  unchanged.
- **Honest degradation**: when no image-capable backend is available, image
  files and scanned pages are flagged (`imageDetectionSkipped` /
  `imageRedactionPending`) — an anonymised output that still shows PII
  pixels MUST never be reported as a clean success.

## Capabilities

### New Capabilities

- `image-redaction`: image PII detection with bounding boxes via the OR
  image-detection seam, `SIGNATURE` entity coverage, irreversible pixel-burn
  redaction, review-workbench region overlays, fail-flagged degradation.

### Modified Capabilities

- `anonymization`: the extract response gains image-origin entities with
  region geometry (additive), and the anonymise path gains the burn step for
  image-bearing content with a fail-flagged (never fail-silent) contract.

## Impact

- **Backend**: `AnonymizationService` gains the image-detection call (after
  the wave-1 OCR fallback) and the burn orchestration on anonymise; a new
  `ImageRedactionService` owns rasterisation reuse (existing Imagick path),
  page reassembly and irreversibility checks; `AnonymizationResultParser` /
  `FileListingService` carry the new flags.
- **Register JSON** (`lib/Settings/filinq_register.json`): additive fields
  on `anonymizationLink` (`burnedRegionCount`, `imageRedactionPending`);
  no new schema.
- **Frontend**: region overlays + region rows in the review workbench;
  `SIGNATURE` type label + badge; degradation warnings.
- **Cross-app dependency (OpenRegister)**: an image-detection/redaction seam
  on the backend chain (detect entities + boxes in an image; burn regions in
  an image) and the `SIGNATURE` entity type in the taxonomy — filed as an OR
  issue + PR, with fail-flagged degradation until it lands (same pattern as
  `ocr-trigger-surface` REQ-DDOCR-004).
- **No external services**: Presidio (image mode) and any signature model run
  as local/ExApp backends exactly like today's text backends; processing
  stays 100% local (algoritmeregister/EDPB posture).
