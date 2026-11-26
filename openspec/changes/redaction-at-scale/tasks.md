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

- [x] 5.1 PHPUnit for coordinator budgets, cancellation, resume idempotency, sampler determinism, report aggregation, cap routing — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify a 150+ file folder batch end-to-end on Postgres (8080) with OpenRegister: enqueue → ticks → cancel → resume → report.
  - Done live 26 Nov 2026 (sandbox: Nextcloud 34.0.6 + MariaDB + Redis, OpenRegister main 99ea152d with openregister#11261 applied locally, filinq development 8433ba3f; MariaDB stands in for Postgres). Batch over 160 letters in one folder, run in the background (`background: true`), ticks driven with `occ background-job:execute --force-execute` on BatchJobCoordinator, 25 files a tick:
    - Extract: about 30 s over 7 ticks. Cancel mid-run: `cancelling`, then `cancelled` at the next tick, and nothing more extracted (stopped at 51/160). Resume: continued from there to 160/160, then `review`.
    - Each file was marked the way the folder screen marks it (the redaction mark with the batch-wide selection, the review mark with uncertain findings decided). Anonymise: background. Cancel after the first tick (25 done), then resume: `requeued: 135`, only the unfinished files. 160/160 `anonymized`, 160 outputs in `anonymised/`, no duplicate.
    - Report: CSV with 160 rows; summary: 160 succeeded, 0 failed, 1195 documents/hour, extractMs 6548, anonymizeMs 168910. Four-eyes was switched off for the run, because one sandbox account both checked and anonymised (a batch runs with dossier scope, so it counts as a Woo run), and switched back to `woo` afterwards.
    - Found and fixed in the same PR: a second folder batch over the same folder took the 160 review renditions (`<name>.pdf`) as documents (320 files); FolderFileEnumerator now leaves out a PDF that a sibling's rendition record names. Newman batch-lifecycle 29/29 after fixing its no-auth requests (cookie jar + CSRF).
    - The PHPUnit half was already met (92-98% on the new classes, b-filinq 25 Nov).
- [x] 5.6 A batch whose files failed ends `completed` and cannot be resumed (`409 Only an errored or cancelled batch can be resumed`), so its failed files can only be redone by starting a new batch. Seen live: 160 files refused by the review gate. REQ-DDRAS-005's scenario "a batch that errored with 40 of 60 files succeeded" assumes otherwise, but nothing sets a batch to `error` for per-file failures. Recommendation: let resume accept a `completed` batch that has files in `error`. It re-queues only those files, and the report of the finished files stays readable. A completed batch with no failed files still answers 409. Adjust the scenario wording to match.
  - Built: `BatchLifecycleService::resume()` accepts a `completed` batch with files in `error` and re-queues only those (`prohibitionViolation` and succeeded files keep their outcome); a completed batch without failed files still answers 409. REQ-DDRAS-005 and its scenario reworded, and a scenario for the 409 added. Pinned by `BatchLifecycleServiceTest::testACompletedBatchWithFailedFilesRequeuesOnlyThoseFiles` and `::testACompletedBatchWithoutFailedFilesIsRefused`.
- [x] 5.2 Playwright spec `tests/e2e/spec-coverage/redaction-at-scale.spec.ts` for the `@e2e`-referenced scenarios; Newman contracts for cancel/resume/report
  - Written: the Playwright spec (#2218) and the Newman contracts `tests/integration/batch-lifecycle.postman_collection.json` (a folder batch over WebDAV, then cancel, cancel again, report, resume, unknown batches 404, no auth 401, teardown), which CI's `tests/integration/*.postman_collection.json` glob picks up.
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): not run here, no live instance.
- [ ] 5.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [x] 5.4 Docs: `docs/features/redaction-at-scale.md` with Playwright screenshots (ops view, cancel/resume, QA badges) + sizing guidance (files_per_tick vs cron cadence vs docs/hour) (ADR-010)
  - Done live (25 Nov 2026, sandbox: Nextcloud 34 + MariaDB + Redis, OpenRegister main, filinq development): four screenshots in `docs/features/redaction-at-scale.md`, which already had the sizing section. The run found two app reds, both fixed in the same PR. (1) The seeded demo batch showed "0 succeeded, 0 failed, 0 pending" with empty progress and documents-per-hour columns. The seed bypasses `BatchProgress`, so it now carries the figures that `BatchProgress` would have written (11/1/0, 100%, 5.5 per hour). (2) The list's Started column showed unix seconds as a bare number ("1,784,189,400"). `BatchProgress` now also writes `startedOn` (ISO 8601), the schema declares it as `date-time` (anonymizationBatch 1.2.0, register 8.104.0), and the column uses it. The batches in review and cancelled in the screenshots were created on the sandbox only.
- [ ] 5.5 Validate: `openspec validate redaction-at-scale --strict` passes; hydra gates green
