# Tasks: redaction-guarantees-from-the-engine

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 6.
     Acceptance criteria are plain bullets, not checkboxes. -->

Supporting change, decision D2. Supports Woo rows 4.5, 4.27, 18.1, 18.2 and
18.3. Wave 3, after `openregister/redaction-release-safeguards` and
`openregister/redaction-policy-as-data` are merged and released, and after
`filinq/anonymization-review-workbench`. Every test named here fails on
`development` today: filinq verifies and gates itself and never reads
OpenRegister's `verification`.

Precondition, stop if unmet: read both OpenRegister changes on OpenRegister
`development`. Name in the PR body the release that carries them, the shape of
`verification`, and the term list and profile methods. If either change has not
merged, stop and say so; do not write calls to methods that do not exist.
Also confirm `redaction-and-what-leaves-the-building` has been archived, so
`openspec/specs/redaction-output-guarantee/spec.md` exists for this delta to
modify; if not, say so in the PR body.

- [ ] 1 Require the engine (REQ-RWB-01). Files: `lib/Service/OpenRegisterAvailabilityService.php` (a capability check for `verification` on the installed OpenRegister), the anonymise entry points in `AnonymizationService` and `BatchAnonymizationController`.
  - GIVEN an OpenRegister without `verification` WHEN a commit runs THEN nothing is written and the message names the release.
  - Test: PHPUnit `EngineCapabilityTest::testWithoutVerificationNothingIsWritten`, `::testWithVerificationTheCommitProceeds`. Show the first failing on `development`; paste the line in the PR body.
- [ ] 2 Store the engine's verdict and delete filinq's verifier (REQ-RWB-01). Files: `lib/Service/Redaction/RedactionVerdictRecorder.php` (record from OpenRegister's `verification`), delete `RedactionIrreversibilityVerifier.php`, `RedactionLeakRoute.php` and the verifier half of `RedactionOutputModes.php`, `lib/Service/Publication/PublicationReadiness.php` (only `clean` passes).
  - GIVEN `leaking` from the engine WHEN stored THEN the link reads `leaking` and readiness fails; GIVEN no `verification` THEN `unverifiable`.
  - Test: PHPUnit `EngineVerdictRecorderTest::testTheEngineVerdictIsStored`, `::testAMissingVerificationIsUnverifiable`; `EngineOnlyTest::testFilinqHasNoVerifierOfItsOwn`. Build the doubles from OpenRegister's real response shape, read on its `development`.
- [ ] 3 Review goes to the engine and filinq's gate is deleted (REQ-RWB-02). Files: `lib/Service/Review/WorkbenchEngineBridge.php` (decision PATCH and review-mark POST), the workbench actions, delete `RedactionReviewGate.php`, `RedactionOutputGuard.php` and the gate use of `RedactionReviewMarkRepository` and `DetectionRunIdentity`, `lib/Service/SettingsService.php` (`filinq.review.checked_gate` shows OpenRegister's `anonymisation.requireReview`).
  - GIVEN a decided document WHEN marked reviewed THEN OpenRegister holds the mark; GIVEN OpenRegister's 409 on the batch path THEN filinq shows the message per document and continues with the next.
  - Test: PHPUnit `WorkbenchEngineBridgeTest::testMarkReviewedPostsTheEngineMark`, `::testAnIncludeWritesRedactAndASkipWritesRelease`; `BatchAnonymizationControllerTest::testTheEngineRefusalIsShownPerDocument`. The batch test is the caller-side wiring proof.
- [ ] 4 Move term lists and the Woo profile (REQ-RWB-05). Files: `lib/Repair/MovePoliciesToTheEngine.php` (post-migration in `appinfo/info.xml`), `lib/Service/PolicyMatchService.php`, `lib/Service/WooProfileService.php`.
  - GIVEN an organisation prohibition term WHEN the repair runs THEN it is in OpenRegister's always-redact list; a second run moves nothing.
  - GIVEN a rule the engine cannot express WHEN the repair runs THEN it stays and is reported.
  - GIVEN the stored Woo profile WHEN the repair runs THEN an OpenRegister named profile exists and `WooProfileService::shouldAnonymize()` reads it.
  - Test: PHPUnit `MovePoliciesToTheEngineTest::testAnOrganisationTermIsMoved`, `::testAnInexpressibleRuleStays`, `::testTheRepairIsIdempotent`, `::testTheWooProfileBecomesAnEngineProfile`.
- [ ] 5 Settings, strings and docs. Files: the admin settings (where the guarantees now live, with links to OpenRegister's settings), `l10n/en.json`, `l10n/nl.json`, `docs/features/redaction-and-what-leaves-the-building.md` (the engine is the guarantee; filinq is the workbench).
  - Test: the docs build.
- [ ] 6 End to end. Files: `tests/e2e/spec-coverage/redaction-guarantees-from-the-engine.spec.ts` (with `anonymisation.requireReview` on: a batch with one undecided document shows the engine's refusal for it; decide, mark reviewed, run again, read `clean` on the link).
  - Test: the Playwright spec.

Verification (plain bullets on purpose). The building agent follows `~/memcap-work/woo-build/LANE-RULES-BUILD.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver. This change deletes central classes, so run the full suite, not a filter.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate redaction-guarantees-from-the-engine --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. The supported rows move with OpenRegister's changes, not with this one.
