# Tasks: DocuDesk Legacy Quality Cleanup

## Phase 1 — Inventory + planning

- [ ] Run `composer phpcs` and capture current baseline error count
      (target: starting from 6 exclude-patterns in phpcs.xml)
- [ ] Run `composer phpmd` for the first time as a unified gate
      and capture violation count + categories
- [ ] Run `composer phpstan` and capture current error count
      (target: starting from 311-line phpstan-baseline.neon)
- [ ] Group PHPStan errors by directory cluster
- [ ] Decide PHPMD strategy: fix-outright or capture baseline
- [ ] Confirm CI runs `composer check:strict` on every PR before
      starting burn-down work

## Phase 2 — PHPCS burn-down (per excluded file)

For each file: fix errors, remove the phpcs.xml `<exclude-pattern>`
entry, verify gate stays green.

- [ ] Excluded file 1 — fix sniffs + drop exclude
- [ ] Excluded file 2 — fix sniffs + drop exclude
- [ ] Excluded file 3 — fix sniffs + drop exclude
- [ ] Excluded file 4 — fix sniffs + drop exclude
- [ ] Excluded file 5 — fix sniffs + drop exclude
- [ ] Excluded file 6 — fix sniffs + drop exclude
- [ ] Once all excludes are gone, drop the legacy-debt block from
      phpcs.xml entirely

## Phase 3 — PHPMD burn-down

Contingent on Phase 1's first-run output.

- [ ] If baseline captured: ElseExpression — re-shape `if/else` to
      early-return
- [ ] If baseline captured: CyclomaticComplexity / NPathComplexity —
      extract methods
- [ ] If baseline captured: MissingImport — add `use` statements
- [ ] If baseline captured: ExcessiveMethodLength — extract helpers
- [ ] If baseline captured: StaticAccess — replace with DI
- [ ] If baseline captured: variable-naming sniffs (Long/Short/
      Undefined/UnusedFormalParameter)
- [ ] Once baseline reaches 0 lines: delete phpmd.baseline.xml and
      drop `--baseline-file` from composer.json's phpmd script

## Phase 4 — PHPStan burn-down (311 lines)

Group errors by directory cluster (per Phase 1 inventory) and
work cluster-by-cluster. Regenerate baseline between PRs.

- [ ] Cluster 1: Controllers (`lib/Controller/`)
- [ ] Cluster 2: Services (`lib/Service/`)
- [ ] Cluster 3: Db mappers + entities (`lib/Db/`)
- [ ] Cluster 4: Migrations (`lib/Migration/`)
- [ ] Cluster 5: Settings (`lib/Settings/`)
- [ ] Cluster 6: Cron / background jobs
- [ ] Cluster 7: Bootstrap / appinfo / util
- [ ] Common patterns to fix:
  - [ ] Missing return-type / param-type declarations
  - [ ] Mixed types (specify generic / union)
  - [ ] Possibly-null dereferences
  - [ ] Strict-comparison nudges (`==` to `===`)
- [ ] Once baseline reaches 0 lines: delete phpstan-baseline.neon

## Phase 5 — CI integration

- [ ] Verify `composer check:strict` runs in CI on every PR
- [ ] Once all baselines are empty:
  - [ ] Delete `phpmd.baseline.xml` (if it was created)
  - [ ] Delete `phpstan-baseline.neon`
  - [ ] Drop the legacy-debt section from `phpcs.xml`
- [ ] Add a smoke-test cron that runs `composer check:strict`
      weekly on `development`

## Phase 6 — Documentation

- [ ] Update README quality-gates section
- [ ] Note in `app-config.json` that legacy quality cleanup is done
- [ ] Close the burn-down tracking issue once the last baseline
      line is removed
