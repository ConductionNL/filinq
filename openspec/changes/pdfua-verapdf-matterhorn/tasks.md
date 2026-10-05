# Tasks: pdfua-verapdf-matterhorn

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register & seed data

- [ ] 1.1 Add the `accessibilityConformanceReport` schema (`fileId`, `flavour`, `compliant`, `failedCheckCount`, `failedChecks` clause/test/checkpoint references, `validatorVersion`, `validatedAt`, `trigger`) to `lib/Settings/filinq_register.json` — additive, union-merge only; references only, never document content (REQ-DDPUM-002)
- [ ] 1.2 Seed two `tests/sample-documents/` fixtures (generated, no personal data): one genuinely PDF/UA-1-conformant tagged PDF; one that passes the wave-1 heuristics but fails Matterhorn checkpoints (untagged content + figure without alt) — the "heuristics ≠ conformance" fixture

## 2. Shared backend (verapdf-validation)

- [ ] 2.1 Extend `verapdf-validation`'s `VeraPdfService` with `validateUa(File): array` invoking the same probed binary with `--flavour ua1`, parsing the same JSON shape (flavour/compliant/failedChecks/validatorVersion), typed error on timeout/crash/unparseable — NO second binary, probe, config or admin row (REQ-DDPUM-001)
  - Hard build dependency on `verapdf-validation`; verify `VeraPdfService` exists at apply time — apply the two together (see design D1 / Open Questions).

## 3. Backend

- [ ] 3.1 Add `accessibility`-category validator checks to `DocumentValidationService`: `pdfua-conformance-failed`, `accessibility-validator-unavailable` — PDF-only, per-profile opt-in, default `off`, validator-presence-gated, references only; wave-1 heuristic checks untouched (REQ-DDPUM-005)
- [ ] 3.2 New accessibility conformance endpoint `POST /api/validation/accessibility/{fileId}` — auth attribute, IDOR-safe user-folder resolution (404, no existence disclosure), persists/updates the `accessibilityConformanceReport` distinct from the PDF/A `conformanceReport` (REQ-DDPUM-002)
- [ ] 3.3 Remediation guidance from failure shape (Filinq-generated → regenerate accessible; imported/mPDF → re-author from accessible source, no retag of imported pages), i18n-keyed; verdict never claims certification (REQ-DDPUM-003)
- [ ] 3.4 Surface the validator verdict as the authoritative accessibility conformance when available; heuristics remain the labelled floor when absent; heuristic-pass + validator-fail surfaces as fail (REQ-DDPUM-004)

## 4. Frontend

- [ ] 4.1 PDF/UA conformance card on document detail (flavour, verdict, failed checkpoints, guidance); validator findings rendered in the existing `accessibility` findings group, source-labelled (REQ-DDPUM-002, REQ-DDPUM-004)
- [ ] 4.2 Heuristic-vs-validator labelling in the accessibility panel; "not validated" state when the binary is absent (REQ-DDPUM-001, REQ-DDPUM-004)

## 5. Quality

- [ ] 5.1 PHPUnit for `validateUa()` parsing/degradation, the two checks, endpoint resolution + dual-report persistence, guidance classification, verdict-hierarchy — 75% coverage on new code (ADR-009)
  - Run in container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Live-verify on Postgres (8080) with veraPDF installed: validate the heuristic-pass/Matterhorn-fail fixture → non-conformant verdict, findings + card; remove the binary → honest heuristic-floor degradation.
- [ ] 5.2 Playwright spec `tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts` for the `@e2e`-referenced scenarios
- [ ] 5.3 i18n EN + NL for all new UI + guidance strings (keys in English); nldesign theme check; new UI itself WCAG 2.1 AA (ADR-005, ADR-003)
- [ ] 5.4 Docs: `docs/features/pdfua-verapdf-matterhorn.md` with Playwright screenshots (conformance card, source-labelled findings, degradation) and the heuristic→validator hierarchy explainer (ADR-010)
- [ ] 5.5 Validate: `openspec validate pdfua-verapdf-matterhorn --type change --strict` passes; hydra gates green

## Quality checklist

- GDPR: reports and findings carry checkpoint/clause references only — no document content or personal data (AVG Art. 5(1)(c)); processing stays 100% local.
- Shared integration: reuses `verapdf-validation`'s `VeraPdfService`, probe, `filinq.verapdf.*` config and admin status row — no second validator surface.
- `pdfua-accessible-output` (heuristics) and `verapdf-validation` (PDF/A backend + `archival` category) specs are referenced/consumed, not modified; the profiles-defaults requirement is not re-modified here.

## 6. Amendment 2026-10-05: Woo rows 15.3 and 15.7 (decision D5)

Build after sections 1 to 5. Every test named here fails on `development`
today: no attach listener, attach job, readiness reason or regeneration
service exists. Acceptance lines are plain bullets, so the checkbox cap holds.

- [ ] 6.1 Attach listener and job (REQ-DDPUM-006). Files: `lib/EventListener/PdfUaAttachListener.php`, `lib/BackgroundJob/PdfUaAttachCheckJob.php`, `lib/AppInfo/Application.php` (register on `NodeCreatedEvent` and `NodeWrittenEvent`), `lib/Service/VeraPdf/ConformanceService.php` (trigger `attach`, a `ua1` run).
  - GIVEN a PDF under `Open Registers/` WHEN the real `NodeCreatedEvent` is handled THEN one job is added and `ConformanceService` is not called.
  - GIVEN a PDF elsewhere, or a non-PDF WHEN handled THEN nothing is queued.
  - Test: PHPUnit `PdfUaAttachListenerTest::testTheListenerOnlyQueues`, `::testAFileOutsideOpenRegisterIsIgnored`, constructing the real `OCP\Files\Events\Node\NodeCreatedEvent` (constructor `(Node $node)`).
  - Wiring: a test that boots `Application::register()` with a registration context double and asserts the listener is registered for both events. A listener with a green suite and no registration is the defect this line prevents.
- [ ] 6.2 Verdict tags (REQ-DDPUM-006). Files: `lib/BackgroundJob/PdfUaAttachCheckJob.php`, `lib/Service/VeraPdf/PdfUaVerdictTagger.php` (through `ISystemTagManager` and `ISystemTagObjectMapper`, object type `files`).
  - GIVEN a compliant report WHEN the job runs THEN the file carries `pdfua-conform` and neither other tag.
  - GIVEN veraPDF absent, a timeout or unparseable output WHEN the job runs THEN `pdfua-niet-gecontroleerd` and no compliant report.
  - Test: PHPUnit `PdfUaAttachCheckJobTest::testACompliantFileIsTaggedConform`, `::testNoValidatorTagsNotChecked`, `::testTheTagsAreExclusive`. `testNoValidatorTagsNotChecked` must be shown failing on `development` first; paste the line in the PR body.
  - Cross-app: one Playwright step opens the publication in opencatalogi and reads the label in its file list, so the claim "the officer sees it" is tested through the app the officer uses, not through filinq.
- [ ] 6.3 Readiness reason (REQ-DDPUM-007). Files: `lib/Service/Publication/PublicationReadiness.php`.
  - GIVEN a record with a non-compliant or unchecked PDF WHEN evaluated THEN `readinessReasons` names the file; with severity `blocking` THEN `isReady()` is false.
  - Test: PHPUnit `PublicationReadinessTest::testAFailedPdfUaVerdictIsAReason`, `::testABlockingSeverityStopsTheHandOff`, `::testAMissingReportCountsAsNotPassed`.
- [ ] 6.4 Regenerate as PDF/UA, generated documents only (REQ-DDPUM-008, D5). Files: `lib/Service/VeraPdf/AccessibleRegenerationService.php`, `lib/Controller/ConformanceController.php` (`POST /api/validation/accessibility/{fileId}/regenerate`, IDOR-safe resolution, auth attribute), `appinfo/routes.php`, the conformance card.
  - GIVEN a generated PDF WHEN regenerated and the copy passes `ua1` THEN a new file version is stored and tagged `pdfua-conform`.
  - GIVEN the copy fails THEN the original and its version are unchanged.
  - GIVEN an imported PDF WHEN the route is called THEN 409 with `not-generated-by-filinq`, and the card offers no action.
  - Test: PHPUnit `AccessibleRegenerationServiceTest::testACompliantCopyReplacesTheOriginal`, `::testAFailingCopyIsDiscarded`, `::testAnImportedPdfIsRefused`; the route-reachability gate. One live run with the veraPDF binary, output recorded in the PR body.
- [ ] 6.5 Strings, docs and e2e. Files: `l10n/en.json`, `l10n/nl.json`, `docs/features/pdfua-verapdf-matterhorn.md` (the three tags, the gate, why imported PDFs are never converted), `tests/e2e/spec-coverage/pdfua-verapdf-matterhorn.spec.ts`.
  - Test: the Playwright spec covers the attach scenario and the imported-PDF scenario.

Verification for section 6 (plain bullets on purpose). The building agent follows `~/memcap-work/woo-build/LANE-RULES-BUILD.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check the `code-quality.yml` workflow requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate pdfua-verapdf-matterhorn --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. Rows 15.3 and 15.7 are `production` only once a store release carries them; 15.7 stays `partial` by decision D5.
