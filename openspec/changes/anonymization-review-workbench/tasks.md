# Tasks: anonymization-review-workbench

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 20.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `documentReview` schema and the optional `bases` array on `publicationProhibition` and `publicationConsent` to `lib/Settings/filinq_register.json`
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
- [ ] 2.4 Enforce the checked gate in `AnonymizationController::anonymize` and `BatchAnonymizationController::batchAnonymize` behind `IAppConfig` key `filinq.review.checked_gate` (`enforced` default | `advisory`) (REQ-DDARW-008, REQ-DDARW-011)
  - HTTP 409 with machine-readable reason + `uncheckedFiles`; advisory mode returns `checkedGate` verdict; runs in addition to the prohibition gate.

## 3. Frontend

- [ ] 3.1 Build `src/views/anonymization/ReviewWorkbench.vue`: original/anonymized split view reusing the existing viewers, `EntityReviewTable` as decision panel, anonymized pane resolved via `anonymizationLink` (REQ-DDARW-001, REQ-DDARW-002)
  - One shared entity state model; pending placeholder when no anonymized result; unsupported types degrade to the existing message.
  - Slice built 2026-10-10 for dossiq woo-delivered-set 4.2 (decision 156, REQ-DDARW-014): the split view itself, `src/components/compare/DocumentCompare.vue` over the existing viewers (Word, ODT and text viewers now also read from a `url`), exposed as `OCA.Filinq.mountCompare` from the self-contained `filinq-compare` entry; tests `tests/vitest/compareView.spec.js`, `src/components/compare/DocumentCompare.spec.js`. Still owed for 3.1: `ReviewWorkbench.vue` hosting it with `EntityReviewTable` as decision panel and the anonymized pane resolved via `anonymizationLink`.
- [ ] 3.2 Implement preview text-selection → pre-filled `AddManualEntityModal` (value, type picker, grondslag pre-fill from the proposal mapping), submitting to the existing OR manual-entities endpoint (REQ-DDARW-003)
  - No client-side offsets; new rows appear without reload; zero-match notice preserved.
- [ ] 3.3 Render prohibition/standing-consent badges with rule links and pre-applied include/exclude state; show proposed grondslag as pre-filled + marked, reviewer override wins (REQ-DDARW-004, REQ-DDARW-006)
- [ ] 3.4 Add checked-gate UI (mark reviewed / re-review prompt on invalidation) and wire nav entries for the workbench and the existing `ProhibitionIndex`/`StandingConsentIndex` views; add the `bases` picker to both policy form modals (REQ-DDARW-007, REQ-DDARW-009)
  - NcSelect fields carry `inputLabel`; modals stay in their own files (modal-isolation gate).

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for gate logic, `standingConsentMatch` attachment, policy `bases` CRUD and check-invalidation — minimum 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - End-to-end verify with OpenRegister on Postgres (8080): detection → pre-application → gate → commit.
- [ ] 4.2 Playwright spec `tests/e2e/spec-coverage/review-workbench.spec.ts` covering the `@e2e`-referenced scenarios
- [ ] 4.3 Vitest coverage for the workbench store wiring (shared entity model, selection pre-fill)
- [ ] 4.4 i18n: EN + NL translations for all new UI strings (keys in English) (ADR-005)
  - Test with the nldesign theme enabled for accessibility compliance.
- [ ] 4.5 Docs: `docs/features/review-workbench.md` with Playwright MCP screenshots of the split view, badges, grondslag pickers and the checked gate (ADR-010)
- [ ] 4.6 Validate: `openspec validate anonymization-review-workbench --strict` passes; hydra gates green on the branch

## 5. Amendment 2026-10-05: Woo rows 4.25 and 14.15

Build after sections 1 to 4, and after `openregister/anonymisation-discloses-itself` has merged
REQ-ADI-007. Every test named here fails on `development` today: no rendition service, no
`reviewRendition` on the link, no `certain_from` setting. Acceptance lines are plain bullets so the
checkbox cap of 20 holds.

- [ ] 5.1 The review rendition (REQ-DDARW-012). Files: `lib/Service/Review/ReviewRenditionService.php` (`prepare(File $source): array` with the six keys), `lib/Settings/filinq_register.json` (`anonymizationLink.reviewRendition`, `documentReview.renditionSha256`; register version bump), the detection entry point in `AnonymizationService` (detect on the rendition).
  - GIVEN a `.docx` WHEN prepared THEN kind `converted`, the backend from `convertToPdfReporting()`, `textLayer` `native`.
  - GIVEN a scan and OCR WHEN prepared THEN kind `ocr-text-layer`, `textLayer` `ocr`; without OCR THEN `textLayer` `none`.
  - GIVEN a PDF with text WHEN prepared THEN kind `native-pdf` and no conversion call.
  - Test: PHPUnit `ReviewRenditionServiceTest::testAWordFileIsConverted`, `::testAScanGetsAnOcrTextLayer`, `::testNoOcrMeansNoTextLayer`, `::testANativePdfIsUsedAsItIs`. Show `testAWordFileIsConverted` failing on `development` first; paste the line in the PR body.
- [ ] 5.2 The check holds for one rendition (REQ-DDARW-012). Files: `lib/Controller/DocumentReviewController.php`, `lib/Controller/AnonymizationController.php`, `lib/Controller/BatchAnonymizationController.php`.
  - GIVEN a check on sha256 A and a current rendition B WHEN the commit runs THEN refused, through the same gate as REQ-DDARW-008, on the single and the batch path.
  - GIVEN `textLayer` `none` WHEN checked without the by-eye confirmation THEN refused.
  - Test: PHPUnit `DocumentReviewControllerTest::testACheckOnAnotherRenditionDoesNotHold`, `::testNoTextLayerNeedsTheByEyeConfirmation`; `BatchAnonymizationControllerTest::testTheBatchPathHonoursTheRendition`. The batch test is the caller-side wiring proof: a gate on one path only is the defect this line prevents.
- [ ] 5.3 Certain and uncertain (REQ-DDARW-013). Files: `lib/Service/SettingsService.php` (`filinq.review.certain_from`, refuse below the floor), a reader of OpenRegister's floor (read the real accessor behind `GET /api/settings/files` on openregister `development` after REQ-ADI-007 merges, and name it in the PR body; fall back to 0.5 with the "not configurable" notice when the key is absent), `EntityReviewTable.vue` and the workbench (badge, filter, counter, the two numbers), `lib/Controller/DocumentReviewController.php` (refuse a check with an undecided uncertain finding).
  - GIVEN floor 0.6, `certain_from` 0.9, findings 0.65 and 0.95 WHEN opened THEN uncertain and certain as the spec says, and both numbers shown.
  - GIVEN an undecided uncertain finding WHEN checked THEN refused naming it.
  - Test: PHPUnit `SettingsServiceTest::testCertainFromBelowTheFloorIsRefused`, `DocumentReviewControllerTest::testAnUndecidedUncertainFindingBlocksTheCheck`; Vitest `EntityReviewTable.spec.js` for the badge and the default inclusion of an uncertain finding.
- [ ] 5.4 Strings, docs and e2e. Files: `l10n/en.json`, `l10n/nl.json`, `docs/features/review-workbench.md` (the rendition, the two thresholds and why there are two), `tests/e2e/spec-coverage/review-workbench.spec.ts` (a `.docx` reviewed as its rendition with the link shown; the threshold panel).
  - Test: the Playwright spec.

Verification for section 5 (plain bullets on purpose). The building agent follows `openspec/woo-build-rules.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate anonymization-review-workbench --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. Rows 4.25 and 14.15 are `production` only once a store release carries them.
