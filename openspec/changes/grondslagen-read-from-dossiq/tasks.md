# Tasks: grondslagen-read-from-dossiq

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 6.
     Acceptance criteria are plain bullets, not checkboxes. -->

Supporting change for Woo rows 12.29 and 13.28; decisions D3 and D12. Wave 2,
after `dossiq/woo-refusal-grounds-list` has merged and its task 1 has settled
the law. Every test named here fails on `development` today: no resolver,
snapshot, `GET /api/grounds`, mapping file or repair step exists, and the
pickers read `base` objects.

- [ ] 1 The resolver and the vendored snapshot (REQ-GRD-001). Files: `lib/Service/Grounds/RefusalGroundsResolver.php`, `lib/Settings/woo-refusal-grounds.snapshot.json` (copied verbatim from the dossiq release that ships it; record the dossiq version in the PR body).
  - GIVEN dossiq enabled and the class present WHEN `list()` runs THEN `source: dossiq`.
  - GIVEN dossiq absent, disabled, or throwing `WooRefusalGroundsUnavailable` WHEN `list()` runs THEN `source: snapshot` with its version.
  - GIVEN the snapshot unreadable WHEN `list()` runs THEN it throws; it never returns an empty list.
  - Test: PHPUnit `RefusalGroundsResolverTest::testWithDossiqTheListComesFromDossiq`, `::testWithoutDossiqTheSnapshotAnswers`, `::testAnUnavailableDossiqFallsBackToTheSnapshot`, `::testAnUnreadableSnapshotThrows`, `::testTheSnapshotHasTheListShape` (every ground carries the ten keys of design D-3). Read `OCA\Dossiq\Woo\WooRefusalGrounds` on dossiq `development` before writing the double; a dynamic call to a renamed method compiles, only the read catches it.
- [ ] 2 Every reader through the resolver (REQ-GRD-002). Files: `lib/Controller/GroundsController.php` (`GET /api/grounds`, `#[NoAdminRequired]`, read only), `appinfo/routes.php`, `src/services/bases.js` (call `/api/grounds`; keep `WOO_BASES` only as the display while loading, never as data), `lib/Service/BaseLabelResolver.php`, `lib/Service/LegalBasisCatalog.php`, `lib/Service/LegalBasisProposalService.php`, `lib/Service/DossierSummaryDataService.php`, `lib/Service/DossierEntityCollector.php`.
  - GIVEN a changed label in dossiq WHEN the summary renders THEN the new label prints.
  - GIVEN an unknown code WHEN rendered THEN "unknown ground" with the code.
  - Test: PHPUnit `BaseLabelResolverTest::testLabelsComeFromTheResolver`, `GroundsControllerTest::testTheEndpointAnswersTheResolver`, `GroundsReadersTest::testNoClassReadsWooGroundsFromTheBaseSchema` (a scan of `lib/` for `base` schema reads outside the resolver); Vitest for `src/services/bases.js`.
  - Wiring: `GroundsControllerTest` goes through the route name in `appinfo/routes.php`, and the Playwright spec opens the review table's picker, so the claim is proven from the screen a reviewer uses.
- [ ] 3 The mapping file and the repair (REQ-GRD-003). Files: `lib/Settings/woo-grounds-legacy-map.json` (filinq's 19 slugs, each to one dossiq code or `unmapped`, from dossiq's settled mapping in `woo-refusal-grounds-list/design.md`), `lib/Repair/RepointWooGrounds.php` (registered post-migration in `appinfo/info.xml`).
  - GIVEN `art-5-1-2-e` mapped to `5.1.2.e` WHEN the repair runs THEN every reference is rewritten and a second run changes nothing.
  - GIVEN an `unmapped` slug WHEN the repair runs THEN references stay and the report names each.
  - GIVEN the map WHEN tested THEN it holds exactly the 19 slugs of the current seed and every mapped code exists in the snapshot.
  - Test: PHPUnit `RepointWooGroundsTest::testAOneToOneSlugIsRewritten`, `::testAnUnmappedSlugIsReported`, `::testTheRepairIsIdempotent`; `WooGroundsLegacyMapTest::testTheMapCoversTheSeedAndTheSnapshot`. Show `testAOneToOneSlugIsRewritten` failing on `development` first; paste the line in the PR body.
- [ ] 4 Retire filinq's list (REQ-GRD-003). Files: `lib/Settings/filinq_register.json` (remove the 19 Woo `base` seed objects; the `base` schema description says Woo grounds come from dossiq; register version bump), the `base` save path (refuse a new Woo ground).
  - GIVEN a fresh install WHEN the seed runs THEN no Woo `base` object is created.
  - GIVEN an existing install WHEN upgraded THEN existing `base` objects stay readable.
  - Test: PHPUnit `RegisterSeedTest::testNoWooBaseIsSeeded`, `BaseSchemaGuardTest::testANewWooGroundIsRefused`.
- [ ] 5 The admin page (REQ-GRD-004). Files: the grondslagen settings section, `l10n/en.json`, `l10n/nl.json`.
  - GIVEN the snapshot WHEN the page opens THEN read-only, version shown, no add or edit action, and the line naming dossiq.
  - GIVEN dossiq WHEN the page opens THEN a link to dossiq's grounds page and no editor.
  - Test: Playwright `tests/e2e/spec-coverage/grondslagen-read-from-dossiq.spec.ts` (both states; the dossiq-absent state on an instance with dossiq disabled).
- [ ] 6 Docs. Files: `docs/features/grondslagen.md` (one list in dossiq, the snapshot for redaction only, the repair report).
  - Test: the docs build.

Verification (plain bullets on purpose). The building agent follows `openspec/woo-build-rules.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate grondslagen-read-from-dossiq --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green. Rows 12.29 and 13.28 move with dossiq's change.
