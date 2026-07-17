# redaction-at-scale Specification (delta)

---
status: proposed
---

## Purpose

The municipal-scale batch layer over the existing batch-anonymization and
folder-batch capabilities: durable OR-backed batch state processed in
bounded background work units under NC cron, with per-batch progress,
cooperative cancellation, idempotent resume, throughput + failure
reporting, sampling QA routed to human review, and resource guards so the
extract/convert backends (LibreOffice, OR text extraction) never starve
the instance. Sized for 25.000–55.000 documents/year and 50–200 users
(Arnhem 407824, Groningen 421583). All processing stays local.

## ADDED Requirements

### Requirement: Durable batch state as OR objects (REQ-DDRAS-001)

Batch and per-file processing state MUST be persisted as OpenRegister
objects (`batchAnonymizationJob` with `batchAnonymizationFile` child
objects, completing the direction set by REQ-BANON-00) and MUST survive a
PHP restart, cache flush or cron interruption. `ICache` MAY be used only
as a read-through cache for status polls and MUST be invalidated on every
state write; the OR objects are authoritative. Existing per-file wire
statuses (`pending`, `processing`, `success`, `error`) MUST be preserved.

#### Scenario: Batch survives a cache flush

- GIVEN a background batch in status `anonymizing` with 200 of 480 files succeeded
- WHEN the distributed cache is flushed and the batch status endpoint is called
- THEN the response reports the batch and per-file state unchanged, read from OR objects
- @e2e exclude infrastructure resilience contract (cache flush) not drivable from UI; covered by PHPUnit (tests/unit/Service/BatchStateServiceTest.php)

#### Scenario: State writes invalidate the read-through cache

- GIVEN a cached batch status entry
- WHEN a per-file child object transitions to `success`
- THEN the next status poll reflects the transition, not the cached value
- @e2e exclude cache-coherence contract; covered by PHPUnit (tests/unit/Service/BatchStateServiceTest.php)

### Requirement: Chunked background processing under cron (REQ-DDRAS-002)

Batches on the background path MUST be processed by a cron-scheduled
coordinator job in bounded work units: at most
`docudesk.batch.files_per_tick` files (default 25) AND at most
`docudesk.batch.seconds_per_tick` wall seconds (default 120) per run,
whichever limit is reached first, after which the coordinator MUST yield
and continue in a later run. Per-file state MUST be written after each
file so the work-unit boundary is the crash-recovery boundary. Both the
extraction phase and the anonymize phase MUST run through this work-unit
mechanic. Files within a work unit MUST be processed sequentially (v1
concurrency = 1).

#### Scenario: Large batch is processed across multiple ticks

- GIVEN a background batch of 60 files and `files_per_tick = 25`
- WHEN the coordinator runs three times
- THEN the first two runs process 25 files each and the third processes the remaining 10
- AND after each run the batch progress reflects the files completed so far
- @e2e exclude cron work-unit scheduling not drivable from UI; covered by PHPUnit (tests/unit/BackgroundJob/BatchJobCoordinatorTest.php)

#### Scenario: Time budget ends a tick early

- GIVEN a tick whose processed files consume the `seconds_per_tick` budget after 8 of 25 files
- WHEN the budget is exhausted
- THEN the coordinator finishes the current file, persists progress, and yields
- AND the remaining files are processed in subsequent ticks
- @e2e exclude time-budget behaviour requires clock control; covered by PHPUnit with injected clock

### Requirement: Per-batch progress reporting (REQ-DDRAS-003)

The batch status endpoint MUST report, for background batches: current
phase, `totalFiles`, files succeeded/failed/pending, percentage complete,
`startedAt`, an updating throughput figure (documents per hour over the
batch so far), and a `throttled` flag (set when a tick exhausts its time
budget on fewer than 20% of its file budget). The frontend batch
operations view MUST render this progress and MUST list active and recent
batches of the current user (admins see all batches).

#### Scenario: Operator watches batch progress

- GIVEN a running background batch
- WHEN the operator opens the batch operations view
- THEN the batch row shows phase, progress percentage, throughput and any throttled indicator
- AND the view updates as polling continues
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Throttling is visible, not silent

- GIVEN a tick that processed 4 of 25 budgeted files before its time budget expired
- WHEN the batch status is next read
- THEN `throttled: true` is reported on the batch progress
- @e2e exclude throttle-flag computation covered by PHPUnit (tests/unit/BackgroundJob/BatchJobCoordinatorTest.php)

### Requirement: Cooperative cancellation (REQ-DDRAS-004)

The system MUST provide `POST /api/anonymization/batch/{batchId}/cancel`
for background batches: the batch transitions to `cancelling`; the worker
MUST check the flag between files and MUST complete (never truncate) the
file in progress before transitioning the batch to `cancelled`. Outputs
already produced MUST be kept and reported. Cancelling a batch that is
`completed`, `error` or already `cancelled` MUST return HTTP 409. Only the
batch owner or an admin MAY cancel.

#### Scenario: Cancel takes effect between files

- GIVEN a background batch processing file 12 of 60
- WHEN the owner calls the cancel endpoint
- THEN file 12 finishes normally and no further file is started
- AND the batch ends in status `cancelled` with 12 files succeeded
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Cancel of a completed batch is refused

- GIVEN a batch in status `completed`
- WHEN the cancel endpoint is called
- THEN the system returns HTTP 409
- @e2e exclude API error contract; covered by PHPUnit + Newman batch contracts

### Requirement: Idempotent resume (REQ-DDRAS-005)

The system MUST provide `POST /api/anonymization/batch/{batchId}/resume`
for batches in status `error` or `cancelled`: only files not in `success`
are re-queued. Per-file processing MUST be idempotent — the anonymize step
MUST key on the existing `anonymizationLink.sourceFileId` linkage so a
file that already produced its output is skipped rather than re-processed,
and a resumed run MUST NOT duplicate anonymized output files. Resume of a
running or completed batch MUST return HTTP 409.

#### Scenario: Resume after a mid-batch failure

- GIVEN a batch that errored with 40 of 60 files succeeded
- WHEN the owner calls the resume endpoint
- THEN only the 20 non-succeeded files are re-queued
- AND the batch completes with 60 succeeded files and no duplicate output files
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: File that succeeded out-of-band is skipped

- GIVEN a resumed batch containing a file whose `anonymizationLink` already records a successful output
- WHEN the worker reaches that file
- THEN the file is marked `success` without re-anonymizing
- @e2e exclude idempotency-key behaviour; covered by PHPUnit (tests/unit/Service/BatchAnonymizeServiceTest.php)

### Requirement: Throughput and failure reporting (REQ-DDRAS-006)

The batch report (`GET /api/anonymization/batch/{batchId}/report`) MUST be
extended with: overall documents/hour, per-phase durations
(extract/anonymize), and failures grouped by reason (exception class or
mapped error category) with per-file detail. Aggregations MUST be computed
at read time from the per-file child objects (no stored counters that can
drift). Entity values MUST remain excluded from the report (GDPR data
minimisation, Recital 26 — existing behaviour).

#### Scenario: Report groups failures by reason

- GIVEN a completed batch with 3 files failed by conversion lock timeouts and 1 by extraction error
- WHEN the report is downloaded
- THEN the failure summary shows the two reason groups with counts 3 and 1
- AND per-file rows carry their individual reason
- @e2e exclude CSV report content contract; covered by PHPUnit (tests/unit/Service/BatchReportServiceTest.php) and Newman

#### Scenario: Throughput appears on the report

- GIVEN a completed 480-file batch that ran for 6 hours
- WHEN the report is downloaded
- THEN it states the overall documents/hour for the batch
- @e2e exclude report aggregation covered by PHPUnit (tests/unit/Service/BatchReportServiceTest.php)

### Requirement: Sampling QA routed to human review (REQ-DDRAS-007)

On completion of a background batch's anonymize phase, the system MUST
select a uniform random sample of `docudesk.batch.qa_sample_rate` percent
(default 5; minimum 1 file when the rate > 0 and the batch has successful
files; rate 0 disables sampling) of successfully auto-anonymized files,
mark them `qaSampled: true`, and route them to human review via the
review-workbench checked gate (`documentReview`). Sample selection MUST be
reproducible: the PRNG seed MUST be stored on the batch object. The batch
report MUST record each sample's outcome (`pending`, `confirmed`,
`corrected`). Sampling MUST NOT block batch completion.

#### Scenario: Five percent of a batch is sampled

- GIVEN a completed background batch with 200 succeeded files and a 5% sample rate
- WHEN sampling runs
- THEN 10 files are marked `qaSampled: true` and appear in the review queue
- AND the batch report lists 10 samples with outcome `pending`
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Sample is reproducible from the stored seed

- GIVEN a batch with a stored `qaSampleSeed`
- WHEN the sample selection is recomputed from the seed for audit
- THEN the same file set is selected
- @e2e exclude deterministic-sampling audit property; covered by PHPUnit (tests/unit/Service/BatchQaSamplerTest.php)

#### Scenario: Rate zero disables sampling

- GIVEN `docudesk.batch.qa_sample_rate = 0`
- WHEN a batch completes
- THEN no file is marked `qaSampled` and the report shows sampling disabled
- @e2e exclude admin-config branch; covered by PHPUnit (tests/unit/Service/BatchQaSamplerTest.php)

### Requirement: Resource guards on extract/convert backends (REQ-DDRAS-008)

Background processing MUST respect the existing LibreOffice serialization
lock (`soffice:headless:convert`): a fail-fast lock miss MUST be treated
as a retryable per-file outcome (retried in a later tick, bounded retry
count) rather than a file error. The coordinator MUST never process more
than one batch work unit concurrently per instance. Background batch size
MUST be clamped server-side to `docudesk.batch.max_files_background`
(default 1000); the synchronous path keeps its existing
`docudesk.batch.max_files_per_run` cap (default 100), and batches between
the two caps MUST be routed to the background path instead of rejected.

#### Scenario: Lock miss is retried, not failed

- GIVEN a file whose PDF conversion hits a held soffice lock
- WHEN the work unit processes it
- THEN the file returns to `pending` with a retry count incremented
- AND it is retried in a later tick, becoming `error` only after the bounded retries are exhausted
- @e2e exclude lock-contention behaviour requires concurrency control; covered by PHPUnit (tests/unit/Service/BatchAnonymizeServiceTest.php)

#### Scenario: Oversized batch routes to background instead of 400

- GIVEN a folder batch of 480 files, a sync cap of 100 and a background cap of 1000
- WHEN the batch is created
- THEN it is accepted and marked for background processing
- @e2e tests/e2e/spec-coverage/redaction-at-scale.spec.ts

#### Scenario: Batch above the background cap is refused

- GIVEN a folder containing 1200 files and a background cap of 1000
- WHEN batch creation is attempted
- THEN the system returns HTTP 400 naming the configured maximum
- @e2e exclude API limit contract; covered by PHPUnit + Newman batch contracts
