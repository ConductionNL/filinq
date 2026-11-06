---
kind: code
---

# Proposal: image-redaction

## Summary

A reviewer redacts a whole page, a range of pages or a whole document in one action, as one decision with its grounds, with OpenRegister's image seam doing the burn.

- Rows: 4.24.
- Wave 2.
- Dependencies: `openregister/anonymisation-image-seam` (https://github.com/ConductionNL/openregister/issues/4380). The archive of this delta waits on `filinq/anonymization-main-spec-valid` (repairs the main `anonymization` spec).
- Decisions: D2 (the guarantees live in the engine), D5 (an OCR text layer over the burned raster is allowed, built by opencatalogi) and D6 (object detection belongs to anonymiq behind the seam).
- Build rules: openspec/woo-build-rules.md

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

## Amendment 2026-10-05: a page, a range of pages or a whole document in one action (Woo row 4.24)

Woo capability row 4.24, "A whole page, a range of pages, or a whole document
is redacted in one action". Our column reads `no`: "PdfTextReplacer takes a
substitution map keyed by entity text. No page, page-range or whole-document
redaction scope exists, and POST /api/files/{fileId}/anonymize acts on the
entity set, not on a region". The gap register names the missing half: "Add
page, page-range and whole-document scopes to the region model, so one action
burns or withholds them, with the OR burn path doing the pixel work." Build
plan: amend this change, wave 2, size M. Depends on
`openregister/anonymisation-image-seam`.

### What moved since this change was written

- `openregister/anonymisation-image-seam` now specifies the seam this change's
  task 2.1 asked for: `ImageRedactionService::detectImage()` and
  `redactImage()` in OpenRegister, regions stored as `EntityRelation` rows
  with `page` and `box` (REQ-AIS-001), and the burn plus the PDF and office
  reassembly done in OpenRegister (REQ-AIS-003, REQ-AIS-004). Task 2.1 is
  therefore met by that change, and filinq builds no burner or reassembly of
  its own (decision D2: the guarantees live in the engine). Task 3.1 shrinks
  to submission and review plumbing.
- Decision D6 gave object detection (signatures, faces, number plates) to
  anonymiq behind that seam. The non-goal "no face detection" still holds for
  filinq: filinq detects nothing, and its review shows whatever regions the
  seam stores, faces included.
- Decision D5 lifts the non-goal "no searchable-PDF authoring" for row 4.15:
  an OCR text layer over the burned raster, taken after the burn, is allowed.
  filinq does not build it (opencatalogi's `woo-redaction-scans-and-text-layer`
  does) and must not block it.

### What this amendment adds

1. Three scopes beside the region: `page` (one page), `pageRange` (from and
   to, inclusive) and `document`. Each is one decision row in the shared
   review model, with its grounds, made by one action in the workbench.
2. `page` and `pageRange` burn every page in scope completely. filinq turns
   the decision into one full-page region per page (`box` 0, 0, 1, 1) on the
   seam, so OpenRegister does the pixel work and removes the text on those
   pages. The output page carries a short notice naming the ground, so a
   reader sees a page was withheld and why.
3. `document` withholds the file. No redacted output is written for it, the
   `anonymizationLink` records `withheld: true` with the grounds, and the
   publication readiness treats it as not for publication.

### Fail closed

- A page scope whose burn the seam cannot verify fails the run; no output is
  reported as redacted.
- A whole-document scope never produces a black PDF that could be mistaken for
  a document. It produces no file.
- Every scope needs at least one ground, as every redaction does.

### App absent

OpenRegister without `anonymisation-image-seam`: page and range scopes are
offered but the commit refuses with "page redaction needs OpenRegister's image
seam", and nothing is written. The whole-document scope still works, because
withholding writes no pixels.
