## Why

The current anonymization pipeline processes files one at a time through a linear wizard (upload -> extract -> anonymize -> done). Government organizations handling WOO (Wet open overheid) disclosure requests need to anonymize dozens or hundreds of documents in a single batch. There is no way to select multiple files, review entities across a batch, selectively include/exclude entities before anonymization, or track batch progress. This blocks efficient WOO compliance workflows where entire dossiers must be anonymized before publication.

## What Changes

- **Batch file selection**: Accept multiple files via drag-and-drop or file picker in a single operation, with a batch queue that processes files in parallel (up to a configurable concurrency limit)
- **Entity review step**: After extraction, present all detected entities across all files in a consolidated review table where users can toggle individual entities on/off before anonymization proceeds
- **Batch progress tracking**: Real-time progress dashboard showing per-file status (queued/extracting/reviewing/anonymizing/completed/error) with overall batch completion percentage
- **Batch anonymization endpoint**: New API endpoint that accepts multiple fileIds and a shared entity inclusion/exclusion list, processing the entire batch in one call
- **Anonymization report**: After batch completion, generate a summary report listing per-file entity counts, replacement counts, and any errors — downloadable as CSV for audit trail
- **WOO entity categories**: Pre-configured entity category profiles aligned with WOO disclosure guidelines (e.g., always anonymize PERSON/BSN/PHONE, optionally keep ORGANIZATION for transparency)
- **Confidence threshold filter**: Allow users to set a minimum confidence threshold — entities below the threshold are flagged for manual review rather than auto-included

## Capabilities

### New Capabilities
- `batch-anonymization`: Batch file processing pipeline — multi-file upload, parallel extraction, consolidated entity review, batch anonymize with progress tracking and audit report
- `anonymization-entity-review`: Interactive entity review/selection UI — toggle entities on/off, confidence threshold filtering, WOO category profiles, entity search/filter

### Modified Capabilities
- `anonymization`: Extend existing single-file endpoints with batch variants; add entity inclusion/exclusion parameter to anonymize endpoint; add confidence threshold to extract endpoint

## Impact

- **Backend**: New `BatchAnonymizationService` orchestrating parallel extraction; extended `AnonymizationController` with batch endpoints; new route definitions in `appinfo/routes.php`
- **Frontend**: New `BatchAnonymizationView.vue` with multi-file upload, progress dashboard, and entity review table; extended Pinia store with batch state management
- **API**: New endpoints `POST /api/anonymization/batch/upload`, `POST /api/anonymization/batch/extract`, `POST /api/anonymization/batch/anonymize`, `GET /api/anonymization/batch/{batchId}/status`, `GET /api/anonymization/batch/{batchId}/report`
- **Dependencies**: No new external dependencies — continues to use OpenRegister's TextExtractionService and FileService for all processing (100% local)
- **Database**: No new database tables — batch state managed in-memory via PHP session or Nextcloud cache (ICache)
