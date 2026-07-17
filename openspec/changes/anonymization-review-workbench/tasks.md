# Tasks: anonymization-review-workbench

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 16.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `documentReview` schema and the optional `bases` array on `publicationProhibition` and `publicationConsent` to `lib/Settings/docudesk_register.json`
  - Additive only; union-merge, never hand-pick; existing objects stay valid without `bases`.
  - `documentReview`: `fileId` (integer, idempotency key), `checkedOn`, `checkedBy`, `entityCountAtCheck`, `manualEntityCount`, `note`.
- [ ] 1.2 Extend the register seed with workbench demo data (two `base` grondslagen, one prohibition and one standing consent carrying `bases`) per the design.md Seed Data section
  - A clean install demos rule pre-application without manual setup.

## 2. Backend

- [ ] 2.1 Attach `standingConsentMatch` next to `prohibitionMatch` in `AnonymizationService::extractAndDetectEntities()` and in the batch consolidated-entities path, from the single existing `PolicyMatchService::match()` pass (REQ-DDARW-010)
  - Prohibition wins on conflict; standing-consent match pre-sets `included: false`; additive response shape.
- [ ] 2.2 Accept/persist/return `bases` in `PolicyCrudService` + `PolicyController` for both rule kinds; invalidate the matcher cache on rule writes (REQ-DDARW-005, REQ-DDARW-009)
  - Pre-change rules without `bases` match unchanged (regression test).
- [ ] 2.3 Add `DocumentReviewController` with routes `GET /api/review/{fileId}`, `POST /api/review/{fileId}/check`, `DELETE /api/review/{fileId}/check`, storing `documentReview` OR objects; invalidate the check on any post-check entity mutation (REQ-DDARW-007)
  - Auth attributes on every method (route-auth gate); per-object authorization guard (no-admin-idor gate).
- [ ] 2.4 Enforce the checked gate in `AnonymizationController::anonymize` and `BatchAnonymizationController::batchAnonymize` behind `IAppConfig` key `docudesk.review.checked_gate` (`enforced` default | `advisory`) (REQ-DDARW-008, REQ-DDARW-011)
  - HTTP 409 with machine-readable reason + `uncheckedFiles`; advisory mode returns `checkedGate` verdict; runs in addition to the prohibition gate.

## 3. Frontend

- [ ] 3.1 Build `src/views/anonymization/ReviewWorkbench.vue`: original/anonymized split view reusing the existing viewers, `EntityReviewTable` as decision panel, anonymized pane resolved via `anonymizationLink` (REQ-DDARW-001, REQ-DDARW-002)
  - One shared entity state model; pending placeholder when no anonymized result; unsupported types degrade to the existing message.
- [ ] 3.2 Implement preview text-selection → pre-filled `AddManualEntityModal` (value, type picker, grondslag pre-fill from the proposal mapping), submitting to the existing OR manual-entities endpoint (REQ-DDARW-003)
  - No client-side offsets; new rows appear without reload; zero-match notice preserved.
- [ ] 3.3 Render prohibition/standing-consent badges with rule links and pre-applied include/exclude state; show proposed grondslag as pre-filled + marked, reviewer override wins (REQ-DDARW-004, REQ-DDARW-006)
- [ ] 3.4 Add checked-gate UI (mark reviewed / re-review prompt on invalidation) and wire nav entries for the workbench and the existing `ProhibitionIndex`/`StandingConsentIndex` views; add the `bases` picker to both policy form modals (REQ-DDARW-007, REQ-DDARW-009)
  - NcSelect fields carry `inputLabel`; modals stay in their own files (modal-isolation gate).

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for gate logic, `standingConsentMatch` attachment, policy `bases` CRUD and check-invalidation — minimum 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - End-to-end verify with OpenRegister on Postgres (8080): detection → pre-application → gate → commit.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/review-workbench.spec.ts` covering the `@e2e`-referenced scenarios
- [ ] 4.3 Vitest coverage for the workbench store wiring (shared entity model, selection pre-fill)
- [ ] 4.4 i18n: EN + NL translations for all new UI strings (keys in English) (ADR-005)
  - Test with the nldesign theme enabled for accessibility compliance.
- [ ] 4.5 Docs: `docs/features/review-workbench.md` with Playwright MCP screenshots of the split view, badges, grondslag pickers and the checked gate (ADR-010)
- [ ] 4.6 Validate: `openspec validate anonymization-review-workbench --strict` passes; hydra gates green on the branch
