---
kind: code
tracking_issue: https://github.com/ConductionNL/filinq/issues/85
---

# Proposal: ocr-trigger-surface

## Why

Filinq ships a complete local OCR engine that the anonymisation product
cannot reach. Verified at HEAD:

- `lib/Service/OcrService.php` (634 LOC, Tesseract via
  `thiagoalessio/tesseract_ocr` + Imagick page rasterisation) implements
  availability probing, MIME/text-based `needsOcr()` detection, image and
  scanned-PDF extraction with confidence, admin-config languages/DPI and
  an enable toggle, and a ready-made `processFile(int $fileId)` entry
  point.
- Its ONLY caller is `FinancialExtractionService` (the scan-en-herken
  invoice pipeline). The anonymisation pipeline
  (`AnonymizationService::extractAndDetectEntities()`) delegates straight
  to OpenRegister's `TextExtractionService::extractFile()`, which does
  **no OCR** (verified: zero OCR/Tesseract references in OR's
  TextExtractionService and its handlers). A scanned PDF therefore
  extracts empty text, yields zero entities, and sails through review as
  "nothing to redact" — a silent privacy failure, the exact class GH #285
  documented for numeric PII.
- There is no OCR route in `appinfo/routes.php`, no UI trigger, and
  `FileListingService` fakes the `ocrProcessed` flag (it returns "is an
  OCR-candidate MIME and not status uploaded", with `ocrConfidence`
  hardcoded `null`) — while the canonical `ocr-document-scanning` spec
  (REQ-OCR-05, marked Implemented) claims real per-file OCR metadata, and
  its Architecture section claims `AnonymizationService` integration that
  does not exist. This is the orphaned-capability defect class: spec done,
  engine green, nothing invokes it.

Demand is concrete: **GH #85** (this change's tracking issue) asks for OCR
in the anonymisation flow; the **algoritmeregister profile** (9 orgs incl.
Rotterdam, Zaanstad, Min AZ) lists OCR of scans as a standard capability
of deployed anonymisation systems; the Arnhem tender volume (~55.000
docs/yr) is heavy on scanned correspondence.

## What

- An **OCR API surface**: `OcrController` with
  `POST /api/ocr/{fileId}` (run OCR, returns text length, confidence,
  `ocrProcessed`, language/DPI used) and `GET /api/ocr/{fileId}` (status),
  wrapping the existing `OcrService::processFile()`.
- A **"Run OCR" UI action** on scanned/image documents (OCR-candidate
  MIME types) in the MyDocuments listing and the file viewer, visible only
  when OCR is enabled and Tesseract is available.
- **Automatic OCR fallback in the anonymisation extract pipeline**: when
  OR text extraction yields empty/low text for a PDF/image
  (`OcrService::needsOcr()` semantics), the pipeline runs OCR and feeds
  the recovered text into OpenRegister chunking + entity detection, so
  scans get entity detection instead of silently skipping. Files where
  OCR is unavailable/disabled are flagged, never silently passed.
- **Honest OCR status on files**: a new `ocrResult` OR object per file
  (confidence, languages, DPI, text length, `ocrProcessedAt`);
  `FileListingService` returns real `ocrProcessed`/`ocrConfidence` from it
  instead of the MIME heuristic.
- **Admin settings respected end-to-end**: enable toggle hides the UI
  action and turns the route into a 409; languages
  (`ocr.default_languages`, legacy `ocr_languages` fallback) and DPI
  (`ocr.default_dpi`, legacy `ocr_dpi`) drive every run (existing
  `OcrService` config reads).
- **Documented OR relationship**: OpenRegister owns text extraction,
  chunking and entity detection and does no OCR; Filinq's Tesseract path
  is the local OCR provider that hands recovered text to OR. The OR-side
  ingestion seam for externally-provided text is specified as this
  change's cross-app dependency.

## Capabilities

### New Capabilities

- `ocr-trigger-surface`: the OCR invocation surface — API route, UI
  action, automatic anonymisation-pipeline fallback, honest per-file OCR
  status, admin-setting enforcement.

### Modified Capabilities

- `ocr-document-scanning`: REQ-OCR-05 (OCR metadata) is corrected from
  the heuristic to real persisted per-file results; the capability's
  claimed-but-absent `AnonymizationService` integration becomes a real,
  specified requirement.

## Impact

- **Backend**: new `OcrController` + routes; `AnonymizationService::
  extractAndDetectEntities()` gains the fallback branch;
  `FileListingService::buildFileInfo()` reads `ocrResult` objects;
  `OcrService` itself is unchanged (it already has every needed seam).
- **Register JSON**: new `ocrResult` schema (additive).
- **Frontend**: "Run OCR" action in MyDocuments/file viewer; OCR badge
  with confidence; existing OCR admin settings section untouched.
- **Cross-app dependency (OpenRegister)**: an ingestion seam for
  externally-supplied text per fileId (so OCR text enters OR chunking +
  entity detection); tracked as an explicit task with a degraded-but-
  flagged behaviour until it lands.
- **No external services**: Tesseract runs on the server; processing
  stays 100% local (algoritmeregister/EDPB posture).
