# Design: redaction-at-scale

## Context

Verified at HEAD:

- `BatchStateService` stores batch state in `ICache` (TTL 7200s, prefix
  `filinq_batch_`, `DEFAULT_MAX_FILES = 100`); TTL is refreshed on writes
  and status polls.
- `BatchAnonymizationController::batchExtract` extracts one file per HTTP
  call; `::batchAnonymize` → `BatchAnonymizeService::anonymizeBatch` loops
  over ALL files synchronously in one request.
- `FolderExtractionJob extends QueuedJob` processes every file of a folder
  batch sequentially in a single run; no cancellation, no per-run budget,
  no resume.
- `LibreOfficeHeadlessBackend` serializes soffice via a global
  `ILockingProvider` lock (`soffice:headless:convert`) and fails fast when
  the lock is held — the only existing resource guard.
- The canonical `batch-anonymization` spec already mandates (REQ-BANON-00,
  status implementing, change `docudesk-adopt-or-abstractions`): batch
  state as `batchAnonymizationJob` OR objects with per-file child objects
  and lifecycle annotations, scheduled via OR Background Jobs, with the
  cap promoted to `IAppConfig filinq.batch.max_files_per_run`. Those
  schemas are NOT yet present in `lib/Settings/filinq_register.json` —
  the migration is in flight.
- Sibling change `anonymization-review-workbench` introduces the
  per-document `documentReview` checked gate this change's sampling QA
  routes into.

## Goals / Non-Goals

**Goals:**

- Process dossier/folder batches sized for 25.000–55.000 docs/year and
  50–200 users without HTTP timeouts, lost state or instance starvation.
- Operator control: progress, cancel, resume, throughput/failure insight.
- Statistical trust: sampling QA over auto-redacted output.

**Non-Goals:**

- No distributed workers / external queue (Redis queues, Horizon-style) —
  NC cron + OR background jobs are the platform primitives (ADR-001/022).
- No change to detection quality or anonymisation semantics (OR-owned).
- No review-UI work beyond routing QA samples into the existing workbench.
- No multi-tenant scheduling fairness (single-org assumption for v1).

## Decisions

### D1 — Complete and extend REQ-BANON-00, don't fork it

Durable state is a precondition for scale, and the canonical spec already
mandates OR-object batch state. **Decision:** this change depends on and
completes that migration — `batchAnonymizationJob` (+ per-file
`batchAnonymizationFile` children) become the source of truth, ICache is
demoted to an optional read-through cache for hot status polls. The
existing wire statuses stay unchanged (`pending/processing/success/error`
per file; batch adds `cancelling/cancelled` — see D3). Rejected: a second,
parallel "large batch" store — two state machines for one domain object is
the orphaned-capability pattern this fleet keeps paying for.

### D2 — Chunked work units under a cron coordinator

**Decision:** a `BatchJobCoordinator` cron job (NC `TimedJob`, interval ≤ 5
min) claims pending batches and processes bounded **work units**: at most
`filinq.batch.files_per_tick` files (default 25) AND at most
`filinq.batch.seconds_per_tick` wall seconds (default 120) per run,
whichever ends first, then yields. Progress is written per file, so the
unit boundary is also the crash-recovery boundary — resume is "by
construction" (D4). Both extract and anonymize phases run through the same
work-unit mechanic. Rejected alternatives: one QueuedJob per file (job-table
bloat at 5k-file dossiers, no ordering); keeping the single-run
QueuedJob (crash strands the batch; a 5.000-file run in one cron slot
starves other NC jobs — memory: NC34 double bg-jobs incidents).

### D3 — Cooperative cancellation between files

**Decision:** `POST /api/anonymization/batch/{batchId}/cancel` sets the
batch to `cancelling`; the worker checks the flag between files and
transitions to `cancelled`, never killing mid-file (a half-written
anonymized file is worse than one extra file). Already-produced outputs are
kept and reported. Cancel of a completed/errored batch is a 409.

### D4 — Resume = re-enqueue only non-succeeded files, idempotently

**Decision:** `POST /api/anonymization/batch/{batchId}/resume` on an
`error` or `cancelled` batch re-queues only files not in `success`.
Idempotency per file: the anonymize step keys on the existing
`anonymizationLink.sourceFileId` (unique per source; `runCount` increments
on re-anonymisation) so a file that succeeded between crash and resume is
skipped, not double-processed. Extraction idempotency is OR-side
(`TextExtractionService::extractFile` re-extract semantics).

### D5 — Sampling QA routes into the workbench checked gate

**Decision:** at batch anonymize completion, a uniform random sample of
`filinq.batch.qa_sample_rate` percent (default 5, min 1 file when the
batch is non-empty and the rate > 0) of successfully auto-anonymized files
is marked `qaSampled: true` on their per-file objects. Sampled files
REQUIRE human review: they surface in the review workbench queue, and the
batch report records per-sample outcome (`pending/confirmed/corrected`).
Selection uses a seeded PRNG with the seed stored on the batch so the
sample is reproducible for audit (algoritmeregister expectation).
Rejected: confidence-weighted sampling — better statistically but
unexplainable to auditors in v1; noted as future work.

### D6 — Resource guards compose, they don't replace

**Decision:** (a) per-tick file and time budgets (D2); (b) extraction
concurrency: the coordinator processes files sequentially within a tick
(concurrency 1 — matching today's serialized reality) and respects the
existing soffice `ILockingProvider` lock by treating a fail-fast lock miss
as a retryable per-file outcome, not an error; (c) load-aware backoff: if
a tick exhausts its time budget on fewer than 20% of its file budget, the
coordinator marks the batch `throttled` in progress metadata and continues
next tick — visible, not silent. No PHP `sys_getloadavg()` gating in v1
(container-dependent, flaky signal).

### D7 — Two caps: synchronous stays 100, background gets 1000

**Decision:** the existing synchronous upload/extract path keeps
`filinq.batch.max_files_per_run` (default 100). Batches above it are not
rejected anymore but require the background path
(`filinq.batch.max_files_background`, default 1000; hard server-side
clamp). Folder batches route by the same thresholds. Arnhem-scale yearly
volume (55.000 docs ≈ 220/working day) fits comfortably; single mega-dossiers
beyond 1000 files must be split (explicit product decision — an unbounded
cap invites an unbounded outage).

### D8 — Declarative vs imperative (ADR-031)

- Batch/per-file state, lifecycle transitions
  (`pending → extracting → review → anonymizing → completed | error` +
  `cancelling/cancelled`), progress fields, QA flags: **declarative** —
  schemas + `x-openregister-lifecycle` in `lib/Settings/filinq_register.json`
  (extending the REQ-BANON-00 schemas).
- The coordinator job, work-unit loop, cancellation checks, sampler and
  throughput aggregation: **imperative** — scheduled bulk work is an
  explicit ADR-031 exception category.
- Report aggregation (docs/hour, failure grouping) is computed in
  `BatchReportService` at read time, not stored — avoids denormalised
  counters drifting from child-object truth.

### D9 — OpenRegister services (ADR-001) and frontend (ADR-012)

All state via OR ObjectService (batch job + file child objects,
`anonymizationLink` reads, `documentReview` for QA routing); no custom
tables; extraction/detection/anonymisation via the existing container-
resolved OR services (`TextExtractionService`, `EntityRelationMapper`,
`FileService::anonymizeDocument`). Frontend: batch operations view built
on `CnIndexPage`/`CnDataTable` with the existing store pattern; progress
via status polling (no websockets in v1). ADR-011: no new
validation/formatting utilities — reuse OR formats.

## Seed Data

```json
// batchAnonymizationJob — a folder batch mid-flight (municipal dossier)
{ "name": "Woo-dossier Subsidietoekenningen 2025",
  "sourceType": "folder",
  "folderId": 731001,
  "folderPath": "/Dossiers/Woo-2025-014",
  "status": "anonymizing",
  "totalFiles": 480,
  "filesSucceeded": 213,
  "filesFailed": 2,
  "qaSampleRate": 5,
  "qaSampleSeed": "00000000-0000-0000-0000-000000000000",
  "requestedBy": "w.devries",
  "startedAt": "2026-07-16T08:10:00Z",
  "throttled": false }

// batchAnonymizationFile — child object, one per file
{ "batchId": "00000000-0000-0000-0000-000000000001",
  "fileId": 731245,
  "fileName": "besluit-subsidie-0042.pdf",
  "status": "success",
  "entityCount": 31,
  "replacementCount": 29,
  "qaSampled": true,
  "qaOutcome": "pending",
  "durationMs": 5400,
  "error": null }
```

Seed task: one demo batch (12 files, 1 error, 1 QA sample) so the
operations view and report render meaningfully on a clean install.

## Risks / Trade-offs

- [REQ-BANON-00 migration is in flight in another change] → this change
  declares the dependency explicitly and lands after (or absorbs) the
  OR-state migration; tasks include a HEAD re-verify before apply
  (memory: spec-says-done ≠ feature runs).
- [Cron cadence caps throughput (~25 files/5 min/instance by default)] →
  budgets are admin-tunable; the report exposes docs/hour so operators see
  the ceiling; documented sizing guidance in docs task.
- [Sequential-within-tick underuses big hosts] → deliberate v1 safety
  choice (soffice serialization makes >1 extraction concurrency mostly
  false parallelism anyway); revisit with real pilot numbers (CB #148).
- [Sampling QA can be perceived as blocking throughput] → only sampled
  files require review; the batch completes regardless, report shows QA
  debt.
- [ICache read-through can serve stale status] → OR objects are
  authoritative; cache entries carry the batch version and are invalidated
  on every write.

## Migration Plan

1. Land/absorb the REQ-BANON-00 OR-state schemas; add progress,
   cancellation and QA fields (additive).
2. Ship coordinator + work units behind config `filinq.batch.engine`
   (`background` default | `legacy-sync` kill switch, one release).
3. Ship cancel/resume/report/ops-view + QA sampler.
4. Rollback: `legacy-sync` restores in-request behaviour; OR batch objects
   remain readable (no data loss either direction).

## Open Questions

- Should QA sample outcomes feed back into detection-confidence tuning
  (OR-side)? Deferred — cross-app contract with OR entity detection.
- Should the ops view be admin-only or visible to all batch owners?
  Provisional: owners see their batches, admins see all.
