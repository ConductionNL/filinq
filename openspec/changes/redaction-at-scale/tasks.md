# Tasks: redaction-at-scale

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 17.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [x] 1.1 Re-verify against HEAD whether the REQ-BANON-00 OR-state migration (`docudesk-adopt-or-abstractions`) has landed; land or absorb the `batchAnonymizationJob` + `batchAnonymizationFile` schemas in `lib/Settings/filinq_register.json`
  - Spec-says-done is not proof — check the register JSON and `BatchStateService` on the apply branch.
- [x] 1.2 Extend the batch schemas with progress, cancellation and QA fields (`totalFiles`, succeeded/failed counters at read time, `qaSampleRate`, `qaSampleSeed`, `throttled`; per-file `qaSampled`, `qaOutcome`, `durationMs`, retry count) — additive, union-merge only
- [x] 1.3 Seed one demo batch (12 files, 1 error, 1 QA sample) per design.md Seed Data

## 2. Backend — durable state & work units

- [x] 2.1 Rebase `BatchStateService` onto OR objects as the source of truth with ICache as an invalidated read-through cache (REQ-DDRAS-001)
  - Wire statuses unchanged; cache entries carry batch version.
- [x] 2.2 Implement `BatchJobCoordinator` (cron `TimedJob`) with `files_per_tick`/`seconds_per_tick` budgets, sequential per-file processing, per-file state writes, throttled-flag computation (REQ-DDRAS-002, REQ-DDRAS-003)
  - One work unit per instance at a time; injected clock for tests.
- [x] 2.3 Route batches between sync and background paths by the two caps; server-side clamp `filinq.batch.max_files_background` (default 1000) (REQ-DDRAS-008, REQ-DDRAS-009)
  - Anonymize endpoint returns HTTP 202 + persisted entity list above the sync cap; pre-change contract below it.
- [x] 2.4 Implement cancel and resume endpoints + worker-side cooperative cancellation and `anonymizationLink`-keyed idempotent resume (REQ-DDRAS-004, REQ-DDRAS-005)
  - Auth attributes + owner/admin guards on every new route (route-auth, no-admin-idor gates).
  - Done: `BatchLifecycleService` + `BatchLifecycleController` (`POST .../cancel`, `POST .../resume`, 202/401/404/409, ownership through `BatchStateService::getBatch()`); `BatchAnonymizeService` and `BatchWorkUnit` check for a cancel between files. A resume stamps `resumedAt`; a file whose `anonymizationLink` (by `sourceFileId`) records a copy made after the batch started is taken from the link instead of anonymized again (`BatchBackgroundPath::existingOutput`).
- [x] 2.5 Treat soffice lock misses as bounded-retry per-file outcomes; migrate `FolderExtractionJob` processing into the coordinator work units; add `recursive` folder enumeration with relative paths (REQ-DDRAS-008, REQ-DDRAS-010, REQ-DDRAS-011)

## 3. Backend — reporting & QA

- [x] 3.1 Extend `BatchReportService` with read-time throughput, per-phase durations and failure grouping; keep entity values out of the report (REQ-DDRAS-006)
- [x] 3.2 Implement the seeded-PRNG QA sampler marking N% of succeeded files `qaSampled` and creating their review-queue routing via the `documentReview` gate; record outcomes on the report (REQ-DDRAS-007)
  - Depends on `anonymization-review-workbench` (documentReview schema); coordinate landing order.
  - Done: `BatchAnonymizeService` marks the sample on completion through `BatchQaSampler`, using `batch.qa_sample_rate` (default 5) and a stored or newly generated `qaSampleSeed`. A sample's outcome is read from its `documentReview` (keyed on the source `fileId`) by `BatchQaOutcomes` when the report is generated: none = `pending`, manual entities added = `corrected`, else `confirmed`; rate 0 reports `qaSampling,disabled`. The workbench queue view that lists unchecked files is `anonymization-review-workbench` task 3.1.

## 4. Frontend

- [x] 4.1 Batch operations view (`CnIndexPage`/`CnDataTable`): active/recent batches, phase, progress %, throughput, throttled indicator; owner-scoped with admin overview (REQ-DDRAS-003)
- [x] 4.2 Cancel/resume actions with confirmation dialogs (own modal files) and QA-sample badges linking to the review workbench (REQ-DDRAS-004, REQ-DDRAS-005, REQ-DDRAS-007)
  - Done: manifest `index` page `BatchOperations` over the `anonymizationBatch` objects (OR's cascade scopes it: owners their own, admins all; progress and documents per hour are written with each batch write by `BatchProgress`), row route to the custom `BatchOperationDetail` (polls status while running; cancel/resume through `src/dialogs/CancelBatchDialog.vue` / `ResumeBatchDialog.vue`; QA samples with their outcome badge). The badge links to the anonymization view with the file id until the workbench view (`anonymization-review-workbench` 3.1) exists.

## 5. Quality

- [ ] 5.1 PHPUnit for coordinator budgets, cancellation, resume idempotency, sampler determinism, report aggregation, cap routing — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify a 150+ file folder batch end-to-end on Postgres (8080) with OpenRegister: enqueue → ticks → cancel → resume → report.
- [x] 5.2 Playwright spec `tests/e2e/spec-coverage/redaction-at-scale.spec.ts` for the `@e2e`-referenced scenarios; Newman contracts for cancel/resume/report
  - Written: the Playwright spec (#2218) and the Newman contracts `tests/integration/batch-lifecycle.postman_collection.json` (a folder batch over WebDAV, then cancel, cancel again, report, resume, unknown batches 404, no auth 401, teardown), which CI's `tests/integration/*.postman_collection.json` glob picks up.
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): not run here, no live instance.
- [ ] 5.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [x] 5.4 Docs: `docs/features/redaction-at-scale.md` with Playwright screenshots (ops view, cancel/resume, QA badges) + sizing guidance (files_per_tick vs cron cadence vs docs/hour) (ADR-010)
  - Done live (25 Nov 2026, sandbox: Nextcloud 34 + MariaDB + Redis, OpenRegister main, filinq development): four screenshots in `docs/features/redaction-at-scale.md`, which already had the sizing section. The run found two app reds, both fixed in the same PR. (1) The seeded demo batch showed "0 succeeded, 0 failed, 0 pending" with empty progress and documents-per-hour columns. The seed bypasses `BatchProgress`, so it now carries the figures that `BatchProgress` would have written (11/1/0, 100%, 5.5 per hour). (2) The list's Started column showed unix seconds as a bare number ("1,784,189,400"). `BatchProgress` now also writes `startedOn` (ISO 8601), the schema declares it as `date-time` (anonymizationBatch 1.2.0, register 8.104.0), and the column uses it. The batches in review and cancelled in the screenshots were created on the sandbox only.
- [ ] 5.5 Validate: `openspec validate redaction-at-scale --strict` passes; hydra gates green
