---
kind: code
tracking_issue: https://github.com/ConductionNL/docudesk/issues/236
---

# Proposal: redaction-at-scale

## Why

The tenders DocuDesk is being measured against are municipal-scale:

- **Arnhem 407824** (with Renkum + Rheden): ~275 Woo dossiers and ~55.000
  documents per year, ~50 staff, irreversible blackout, "SaaS tenzij".
- **Groningen 421583**: laksoftware for ~200 users, 25.000–35.000 documents
  per year.
- **CB #148** tracks a municipal pilot at this volume; **GH #236** (the
  tracking issue for this change) and the intelligence-DB backlog item
  `redaction-at-scale` record the same demand. GH #84 asks for async dossier
  processing.

Verified at HEAD, DocuDesk's batch pipeline cannot carry that load:

- Batch state lives in **ICache** (`BatchStateService`, TTL 7200s, prefix
  `docudesk_batch_`) — a cache eviction or restart loses a running batch.
- `POST /api/anonymization/batch/{batchId}/extract` processes **one file per
  HTTP call** (client-driven polling loop).
- `POST /api/anonymization/batch/{batchId}/anonymize` runs a **synchronous
  foreach over every file inside a single HTTP request**
  (`BatchAnonymizeService::anonymizeBatch`) — at hundreds of files this hits
  PHP/webserver timeouts.
- `FolderExtractionJob` (QueuedJob) processes an entire folder batch
  **sequentially in one run** with no cancellation, no resume and no
  per-run budget; a mid-run crash strands the batch.
- The only resource guard is the LibreOffice lock
  (`LibreOfficeHeadlessBackend`, global `ILockingProvider` lock
  `soffice:headless:convert`); nothing bounds extraction fan-out or job run
  time, so a big batch can starve the instance.
- Batch size is capped at 100 files (`DEFAULT_MAX_FILES`) — a single Woo
  dossier at Arnhem scale exceeds it.

The existing `batch-anonymization` spec (REQ-BANON-00, status implementing)
already points the way: OR-object batch state with lifecycle child objects
and OR Background Jobs. This change builds the scale layer on that
foundation. VNG's finding that >50% of documents are partly auto-redactable
but ALL need review also makes a **sampling QA** loop (random N% of
auto-redacted documents routed to human review) a hard requirement for
trustworthy volume processing.

## What

- **Durable, chunked background processing**: batches above the synchronous
  threshold are processed by cron-scheduled background jobs in bounded work
  units (N files per tick, run-time budget per tick), with batch and
  per-file state persisted as OR objects (extending REQ-BANON-00) so a
  restart never loses a batch.
- **Progress, cancellation and resume**: per-batch progress
  (files done/total, current phase, throughput), `POST
  /api/anonymization/batch/{batchId}/cancel` (graceful, between files) and
  `POST /api/anonymization/batch/{batchId}/resume` (re-enqueue only
  non-succeeded files; idempotent per file).
- **Throughput and failure reporting**: the batch report gains
  documents/hour, per-phase durations and failures grouped by reason; an
  operations view lists active/recent batches.
- **Sampling QA**: an admin-configurable random sample (default 5%) of
  auto-anonymized documents per batch is routed to human review (the
  `anonymization-review-workbench` checked gate), with sample outcomes
  recorded on the batch report.
- **Rate/resource guards**: bounded files-per-tick and seconds-per-tick,
  extraction concurrency cap, reuse of the existing serialized LibreOffice
  lock, and load-aware backoff so extract/convert backends do not starve
  the Nextcloud instance.
- **Scale limits**: a separate, larger admin-configurable cap for
  background batches (default 1000 files; synchronous path keeps its
  existing cap), sized for 25.000–55.000 docs/year and 50–200 users.

## Capabilities

### New Capabilities

- `redaction-at-scale`: the municipal-scale batch layer — chunked
  background processing, progress/cancel/resume, throughput + failure
  reporting, sampling QA, resource guards, background-scale limits.

### Modified Capabilities

- `batch-anonymization`: the batch anonymize step becomes a background
  operation above the synchronous threshold (the endpoint enqueues instead
  of looping in-request).
- `folder-batch-analysis`: folder batches gain optional recursive
  enumeration and chunked background extraction with cancellation checks
  (replacing the single-run-processes-all job behaviour).

## Impact

- **Backend**: new `BatchJobCoordinator` (cron `TimedJob`) + work-unit
  processing in `BatchAnonymizeService`/`BatchExtractionService`; new
  cancel/resume/list controller actions on `BatchAnonymizationController`;
  `BatchStateService` backed by OR objects (completing the REQ-BANON-00
  direction) with ICache retained only as a read-through cache;
  `BatchReportService` extended with throughput/failure aggregation; QA
  sampler.
- **Register JSON**: `batchAnonymizationJob` + `batchAnonymizationFile`
  schemas (declared by the in-flight REQ-BANON-00; this change depends on
  and completes them) gain progress/cancellation/QA fields.
- **Frontend**: batch progress view with cancel/resume actions and an
  operations list; QA-sample badge linking to the review workbench.
- **Admin settings**: `docudesk.batch.max_files_background`,
  `docudesk.batch.files_per_tick`, `docudesk.batch.seconds_per_tick`,
  `docudesk.batch.qa_sample_rate`.
- **Engines untouched**: extraction/detection/anonymisation remain
  OpenRegister services; LibreOffice serialization stays as-is and is
  respected, not bypassed.
