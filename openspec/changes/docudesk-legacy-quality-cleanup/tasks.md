# Tasks: DocuDesk Legacy Quality Cleanup

## Tasks

- [ ] 1. **Baseline + planning** — run `composer phpcs`, `composer phpmd`, and `composer phpstan`; capture current counts (PHPCS: 6 exclude-patterns; PHPMD: first unified-gate violations + categories; PHPStan: 311-line baseline); group PHPStan errors by directory cluster; decide PHPMD strategy (fix-outright vs capture-baseline); confirm CI runs `composer check:strict` on every PR before burn-down work begins.
- [ ] 2. **PHPCS — file 1** — fix sniffs on the first excluded file, drop its `<exclude-pattern>` entry from `phpcs.xml`, verify the gate stays green.
- [ ] 3. **PHPCS — file 2** — fix sniffs on the second excluded file, drop its `<exclude-pattern>`, verify gate green.
- [ ] 4. **PHPCS — file 3** — fix sniffs on the third excluded file, drop its `<exclude-pattern>`, verify gate green.
- [ ] 5. **PHPCS — file 4** — fix sniffs on the fourth excluded file, drop its `<exclude-pattern>`, verify gate green.
- [ ] 6. **PHPCS — files 5–6 + legacy block removal** — fix sniffs on the fifth and sixth excluded files, drop their `<exclude-pattern>` entries, then remove the legacy-debt block from `phpcs.xml` entirely.
- [ ] 7. **PHPMD burn-down (categories)** — burn down ElseExpression (early-return reshape), CyclomaticComplexity/NPathComplexity (method extraction), MissingImport (add `use`), ExcessiveMethodLength (extract helpers), StaticAccess (replace with DI), and variable-naming sniffs (Long/Short/Undefined/UnusedFormalParameter); once the baseline reaches zero, delete `phpmd.baseline.xml` and drop `--baseline-file` from composer.json's `phpmd` script.
- [ ] 8. **PHPStan — Controllers + Services clusters** — burn down `lib/Controller/` and `lib/Service/` errors; common patterns: missing return/param types, mixed types (specify generic/union), possibly-null dereferences, strict-comparison (`==` → `===`); regenerate baseline between PRs.
- [ ] 9. **PHPStan — Db + Migrations + Settings clusters** — burn down `lib/Db/` (mappers + entities), `lib/Migration/`, and `lib/Settings/`; same common patterns; regenerate baseline.
- [ ] 10. **PHPStan — Cron + Bootstrap clusters** — burn down cron/background-jobs and bootstrap/appinfo/util; same common patterns; regenerate baseline.
- [ ] 11. **PHPStan finalisation** — once baseline reaches zero lines, delete `phpstan-baseline.neon`.
- [ ] 12. **CI integration** — verify `composer check:strict` runs in CI on every PR; once all baselines are empty, delete `phpmd.baseline.xml` (if created) and `phpstan-baseline.neon`, and drop the legacy-debt section from `phpcs.xml`; add a smoke-test cron running `composer check:strict` weekly against `development`.
- [ ] 13. **Documentation + closeout** — update README quality-gates section; note in `app-config.json` that legacy quality cleanup is done; close the burn-down tracking issue once the last baseline line is removed.
