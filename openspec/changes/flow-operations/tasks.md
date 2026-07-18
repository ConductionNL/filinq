# Tasks: flow-operations

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `flowOperationRun` schema (operation, fileId, fileName, ownerUserId, status, statusReason, triggerEvent, startedAt, finishedAt, resultSummary, producedFileId, errorMessage) to `lib/Settings/docudesk_register.json` with `x-openregister-lifecycle` (initial `queued`), `x-openregister-archival` TTL retention in the OR-validated object shape (`{"retention": {"default": "<ISO-8601>"}}` — `P1Y` placeholder for authoring, see the apply-blocker task 1.3) and the failure-notification rule in the verified `x-openregister-notifications` dialect (recipient `kind:field ownerUserId`) — additive, union-merge only; re-validate JSON after merge (REQ-DDFLO-008)
- [ ] 1.2 Seed the two `flowOperationRun` fixtures from design.md so the runs listing renders on a clean install
- [ ] 1.3 APPLY-BLOCKER — replace the `P1Y` retention placeholder on `flowOperationRun` with a real selectielijst-manager-approved retention value before apply/done (REQ-DDFLO-010)
  - Obtain the approved processing-log retention from the responsible selectielijst-manager (records-appraisal decision — the runs carry `ownerUserId`, file names and per-type entity counts); express it in the validated object shape; add a PHPUnit register-lint that FAILS while `P1Y` remains so the gate enforces it (same production-enablement posture as archiefwet B4 / REQ-DDARE-009). Note only: `entitySearchLog` and `classificationResult` carry the same obligation in `entity-search` and `inbound-auto-classification` — out of scope here.

## 2. Backend

- [ ] 2.1 Add `lib/Flow/RegisterFlowOperationsListener.php` + registration in `Application::register()` for `RegisterOperationsEvent`; register all four operations (REQ-DDFLO-001)
- [ ] 2.2 Add the four `ISpecificOperation` classes under `lib/Flow/Operation/` (file entity, SCOPE_ADMIN + SCOPE_USER, translated name/description/icon, `validateOperation()` rejecting malformed config) (REQ-DDFLO-001)
- [ ] 2.3 Add `lib/Flow/FlowOperationJob.php` (QueuedJob): `onEvent()` matches via `IRuleMatcher`, creates the run in `queued`, enqueues; job executes in the owner's context and updates the run through its lifecycle (REQ-DDFLO-002)
  - Dedupe on `{operation, fileId}` with a run `queued`/`running`; ignore folders/non-file entities.
- [ ] 2.4 Implement the loop guard: skip candidates matching a recorded `producedFileId` with run status `skipped`, reason `own_output` (REQ-DDFLO-003)
- [ ] 2.5 Wire the four operation bodies to the existing services: anonymise intake via `extractAndDetectEntities()` honouring checked + prohibition gates (counts-only resultSummary); OCR via `OcrService::processFile()` with `ocrResult.triggeredBy: "flow"`; PDF/A via `Pdfa3ConversionService`/`PdfConversionService` with additive output + `producedFileId`; validation via `DocumentValidationService::validate()` with severity aggregation (REQ-DDFLO-004..007)
  - Coordinate with wave-1 `ocr-trigger-surface`: `triggeredBy` enum gains `flow` (additive).

## 3. Frontend

- [ ] 3.1 Flow operator-registration script (loaded via `LoadSettingsScriptsEvent`) rendering the four operations in the Flow rule builder, with the `targetFolder` (PDF/A) and `documentType` (validation) options (REQ-DDFLO-001, REQ-DDFLO-005..007)
- [ ] 3.2 Flow-runs listing (operation, file, status, timing, reason) using standard Cn/Nc components; NL Design System tokens only (REQ-DDFLO-008, ADR-012, ADR-003)
- [ ] 3.3 Add `<category>workflow</category>` to `appinfo/info.xml` (REQ-DDFLO-009)

## 4. Quality

- [ ] 4.1 PHPUnit for operation validation, enqueue/dedupe, loop guard, gate pass-through (prohibition + checked gate), OCR/settings degradation, PDF/A additive output, validation severity aggregation, run persistence — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with OpenRegister: build a real Flow rule, drop a scan in the folder, run cron, see the run + notification.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/flow-operations.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 4.3 i18n EN + NL for all new UI strings (keys in English); nldesign theme check (ADR-005, ADR-003)
- [ ] 4.4 Docs: `docs/features/flow-operations.md` with Playwright screenshots (rule builder with a DocuDesk operation, runs listing, failure notification) (ADR-010)
- [ ] 4.5 Validate: `openspec validate flow-operations --strict` passes; the REQ-DDFLO-010 register-lint passes (no `P1Y` placeholder remains); hydra gates green
