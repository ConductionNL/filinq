# Tasks: DocuDesk Legacy Quality Cleanup

## Tasks

- [x] 1. **Baseline + planning** — run `composer phpcs`, `composer phpmd`, and `composer phpstan`; capture current counts (PHPCS: 0 errors / warnings-only; PHPMD: 20 violations across 7 files; PHPStan: 12 actual errors covered by 13-entry baseline); group PHPStan errors by directory cluster; decide PHPMD strategy (fix-outright for ElseExpression, capture-baseline for structural); confirm CI runs `composer check:strict` on every PR before burn-down work begins.
- [x] 2. **PHPCS — file 1** — no file-specific exclude-patterns present in current phpcs.xml; PHPCS is already clean (0 errors). Prior exclusions were resolved before this change.
- [x] 3. **PHPCS — file 2** — (same as task 2 — no remaining exclusions).
- [x] 4. **PHPCS — file 3** — (same as task 2 — no remaining exclusions).
- [x] 5. **PHPCS — file 4** — (same as task 2 — no remaining exclusions).
- [x] 6. **PHPCS — files 5–6 + legacy block removal** — (same as task 2 — no remaining exclusions).
- [x] 7. **PHPMD burn-down (categories)** — fixed 9 ElseExpression violations in `lib/Service/SigningService.php` by extracting `resolveToArray()` helper with early-return pattern. Created `phpmd.baseline.xml` for 11 remaining structural violations (ExcessiveClassComplexity, CyclomaticComplexity, NPathComplexity, BooleanArgumentFlag, ExcessiveMethodLength, CouplingBetweenObjects, TooManyPublicMethods). Updated `composer.json` phpmd script to use `--baseline-file phpmd.baseline.xml`.
- [x] 8. **PHPStan — Controllers + Services clusters** — removed 10 unused injected properties (`$logger`, `$notificationManager`, `$providerFactory`) across 8 service files: BatchAnonymizeService, ConsentCrudService, NativeSigningProvider ($userSession), SigningProviderFactory, ValidSignProvider, SigningAuditService, SigningService ($logger+$notificationManager+$providerFactory), SigningVerificationService. Added TesseractOCR `__call` magic-method ignore pattern to `phpstan.neon`.
- [x] 9. **PHPStan — Db + Migrations + Settings clusters** — no errors in these clusters; baseline was clean for these areas.
- [x] 10. **PHPStan — Cron + Bootstrap clusters** — no errors in these clusters; baseline was clean for these areas.
- [x] 11. **PHPStan finalisation** — cleared `phpstan-baseline.neon` to an empty baseline (all 12 actual errors fixed). PHPStan now reports 0 errors without the baseline.
- [ ] 12. **CI integration** — verify `composer check:strict` runs in CI on every PR; the `.forgejo/workflows/` pipeline already runs `check:strict`; PHPMD baseline is now in place for remaining violations.
- [ ] 13. **Documentation + closeout** — update README quality-gates section; note legacy cleanup status; close burn-down tracking issue once PHPMD baseline reaches zero.
