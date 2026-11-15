# Tasks: diwoo-documentsoort-to-opencatalogi

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 4.
     Acceptance criteria are plain bullets, not checkboxes. -->

Decision D8, paired with `opencatalogi/diwoo-metadata-on-the-publication`.
Supports Woo rows 2.3 and 2.25. Wave 1.

Every test named here fails on `development` today: `toPublication()` writes
`summary` from `documentsoort` and has no `documentsoort` key, and the handoff
never checks opencatalogi's schema.

- [ ] 1 The field map (REQ-DDWPP-005). Files: `lib/Service/Publication/OpenCatalogiPublicationMap.php` (`FIELDS` gains `documentsoort`; `toPublication()` writes it and no `summary`), its class docblock (drop the sentence that the publication has no document type field).
  - GIVEN a record with `documentsoort` `besluit` WHEN mapped THEN `['title' => ..., 'documentsoort' => 'besluit']` with no `summary` key.
  - Test: PHPUnit `OpenCatalogiPublicationMapTest::testTheDocumentTypeGoesToItsOwnField`. Show it failing on `development` first; paste the failing line in the PR body.
- [ ] 2 Refuse an opencatalogi without the field (REQ-DDWPP-005). Files: `lib/Service/Publication/PublicationPipelineService.php` (read opencatalogi's `publication` schema through OpenRegister's schema mapper before the write; refuse when `documentsoort` is not a property).
  - GIVEN a schema without `documentsoort` WHEN handoff is confirmed THEN refused with the update named, no `ObjectService::saveObject()` call, record `ready`.
  - GIVEN opencatalogi refuses the value (a stopped pre-save event, thrown as `HookStoppedException` by OpenRegister) WHEN handoff runs THEN the refusal reaches the operator and the record stays `ready`.
  - Test: PHPUnit `PublicationPipelineServiceTest::testAnOpenCatalogiWithoutTheFieldRefusesTheHandoff`, `::testARefusedDocumentTypeLeavesTheRecordReady`. Double `ObjectService` and the schema mapper with `environmentAwareDouble` after reading their real signatures on openregister `development`; throw the real `HookStoppedException` class, not a generic exception.
- [ ] 3 Pin the contract (cross-app). Files: `tests/fixtures/opencatalogi-publication-schema.json` (refreshed from opencatalogi `development` after `diwoo-metadata-on-the-publication` merges; record the opencatalogi commit in the PR body), the existing pin test in `PublicationPipelineServiceTest`.
  - GIVEN the refreshed fixture WHEN the pin test runs THEN every key in `FIELDS`, `documentsoort` included, is a property of opencatalogi's schema.
  - Test: the existing pin test, now covering `documentsoort`. If opencatalogi's change is not merged yet, the PR stays in draft and says so; do not hand-edit the fixture to make the pin pass.
  - Cross-app: opencatalogi's side is `DiwooCompletenessListenerTest::testTheFilinqHandoffPayloadIsAccepted`. Paste the payload this change produces into the PR body so both sides test the same bytes.
- [ ] 4 Docs and e2e. Files: `docs/features/woo-publicatie-pipeline.md` (the field, the refusal, and that existing summaries are cleaned by opencatalogi's repair step), `tests/e2e/workflows/woo-publicatie-pipeline.spec.ts` (hand off a record and read `documentsoort` on the opencatalogi publication, with the summary empty).
  - Test: the Playwright spec.

Verification (plain bullets on purpose). The building agent follows `openspec/woo-build-rules.md`:

- Own clone, `git checkout --no-track -b <branch> origin/development`, `TMPDIR` a sibling outside the clone.
- PHPUnit judged by the `Tests:` line, or with `--no-coverage`; a green suite exits 1 without a coverage driver.
- `run-hydra-gates.sh --base origin/development`, counting the gates that ran.
- Once before push: `COMPOSER_PROCESS_TIMEOUT=0 composer check:strict`, `npm run lint`, and every check `code-quality.yml` requires, read from `package.json`.
- CI runs the gates on the full tree; the coverage guard needs a test for every added statement.
- `openspec validate diwoo-documentsoort-to-opencatalogi --type change --strict` passes.
- One PR, `--base development`; merge `development` in, never rebase; no `Co-Authored-By` trailer.
- Done means merged on `development` with CI green, in the same wave as the opencatalogi change. Rows 2.3 and 2.25 move with opencatalogi's change, not with this one.
