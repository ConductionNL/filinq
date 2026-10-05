# Tasks: woo-request-workflow

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 6.
     Acceptance criteria are plain bullets, not checkboxes. -->

Re-scoped 2026-10-05 by decision D1. The original sixteen tasks (schemas,
intake and deadline, collection, dedupe, assessment, package, lifecycle, the
Woo-verzoeken UI and the entity-search hand-over) were never started and are
withdrawn; `proposal.md` says where each one now lives. Woo row 7.9. Wave 1.

Every test named here fails on `development` today: no Woo template is seeded,
no `woo-decision` contract exists, and `DocumentGenerationRequestService`
renders any data it is given.

## 1. Templates

- [ ] 1.1 Seed `woo-besluit` and `woo-inventarislijst` (REQ-DDWRW-006). Files: `lib/Settings/filinq_register.json` (two `template` seed objects, slug, `dataContract: woo-decision`, placeholder body; register version bump), the seed path that imports templates.
  - GIVEN a fresh install WHEN the seed runs THEN both slugs exist.
  - GIVEN an edited `woo-besluit` WHEN the app is upgraded THEN the edit survives.
  - Test: PHPUnit `WooTemplateSeedTest::testBothTemplatesAreSeeded`, `::testAnEditedTemplateSurvivesAnUpgrade`. Add the seed rows by hand; do not run a generator over the register file.

## 2. The contract

- [ ] 2.1 `WooDecisionContextBuilder` and its hook in the generator (REQ-DDWRW-010, design R1). Files: `lib/Service/Woo/WooDecisionContextBuilder.php`, `lib/Exception/WooDecisionContractException.php`, `lib/Service/DocumentGenerationRequestService.php` (call the builder only for `dataContract: woo-decision`).
  - GIVEN a withheld document without a ground WHEN dispatched THEN refused naming it, and no file.
  - GIVEN a missing `decisionDate` and a duplicate inventory number WHEN dispatched THEN both named in one error.
  - GIVEN a template without the contract WHEN generated THEN behaviour is as today.
  - Test: PHPUnit `WooDecisionContextBuilderTest::testAWithheldDocumentNeedsAGround`, `::testAllProblemsAreReported`, `::testOtherTemplatesAreUntouched`. `testAWithheldDocumentNeedsAGround` must be shown failing on `development` first; paste the line in the PR body.
- [ ] 2.2 Grounds by label (REQ-DDWRW-011). Files: `lib/Service/Woo/WooDecisionContextBuilder.php`, the grounds resolver class shared with `grondslagen-read-from-dossiq` (`lib/Service/Grounds/RefusalGroundsResolver.php`; whichever change lands first creates it, the other reuses it).
  - GIVEN code `5.1.2.e` WHEN resolved THEN `{code, article, label}` reaches the template.
  - GIVEN an unknown code, or a retired one without `allowRetiredGrounds` WHEN dispatched THEN refused naming it.
  - Test: PHPUnit `WooDecisionContextBuilderTest::testGroundsAreResolvedToLabels`, `::testAnUnknownGroundIsRefused`, `::testARetiredGroundNeedsTheFlag`. Double the resolver from the real return shape of `OCA\Dossiq\Woo\WooRefusalGrounds::byCode()` (keys `id, code, article, paragraph, letter, label, description, parent, status, legalSource`, as `dossiq/woo-refusal-grounds-list` design D-3 fixes it). Read the class on dossiq `development` before writing the double; if it is not merged yet, say so in the PR body.

## 3. The two documents

- [ ] 3.1 Template bodies and agreement (REQ-DDWRW-007). Files: the two template bodies in the seed, `tests/fixtures/woo-decision.json` (three documents: disclose, partially disclose, withhold).
  - GIVEN the fixture WHEN both templates render THEN the letter cites documents 2 and 3 with grounds in words, the inventory lists 1 to 3 with the same numbers and assessments, and document 2 is never shown as disclosed.
  - Test: PHPUnit `WooDecisionTemplatesTest::testTheLetterAndTheInventoryAgree` renders the real seeded bodies through the real generator with only storage doubled.
- [ ] 3.2 Through the command, as dossiq calls it (wiring). Files: `tests/Unit/EventListener/DocumentGenerationRequestedListenerWooTest.php`, `docs/features/woo-decision-templates.md` (the `wooDecision` keys, the refusals, the result keys, and that dossiq or decidiq is the caller).
  - GIVEN the real `DocumentGenerationRequestedEvent` with `templateSlug: woo-besluit` and the fixture, dispatched through a real `IEventDispatcher` wired by `Application::register()` THEN `isHandled()` is true and `getResult()['fileId']` is set.
  - GIVEN the same with a withheld document without a ground THEN `getError()` is set and `isHandled()` is false.
  - Test: the listener test above.
  - Cross-app: record in the PR body that dossiq's `FilinqTemplateEngineAdapter` (or decidiq's, if the besluit raise moves) must send `templateSlug` and `data.wooDecision` in this shape, with a test on that side using the same event class.

## 4. Delivery

- [ ] 4.1 Strings, docs and end-to-end. Files: `l10n/en.json`, `l10n/nl.json`, `docs/features/woo-decision-templates.md`, `tests/e2e/workflows/woo-request-workflow.spec.ts` (edit `woo-besluit` in the template editor, generate through the API with the fixture, open the stored PDF).
  - Test: the Playwright spec.

Verification (plain bullets on purpose). The building agent follows `~/memcap-work/woo-build/LANE-RULES-BUILD.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate woo-request-workflow --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. Row 7.9 is `production` only once a store release of filinq carries the templates and a dossiq release calls them.
