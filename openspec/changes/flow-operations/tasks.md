# Tasks: flow-operations

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `flowOperationRun` schema (operation, fileId, fileName, ownerUserId, status, statusReason, triggerEvent, startedAt, finishedAt, resultSummary, producedFileId, errorMessage) to `lib/Settings/docudesk_register.json` with `x-openregister-lifecycle` (initial `queued`), `x-openregister-archival` (`P1Y` placeholder pending selectielijst sign-off) and the failure-notification rule in the verified `x-openregister-notifications` dialect (recipient `kind:field ownerUserId`) — additive, union-merge only; re-validate JSON after merge (REQ-DDFLO-008)
- [ ] 1.2 Seed the two `flowOperationRun` fixtures from design.md so the runs listing renders on a clean install

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
- [ ] 4.5 Validate: `openspec validate flow-operations --strict` passes; hydra gates green
