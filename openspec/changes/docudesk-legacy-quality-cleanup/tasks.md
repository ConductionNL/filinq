# Tasks: DocuDesk Legacy Quality Cleanup

## Tasks

- [x] 1. **Baseline + planning** — current state captured: `phpcs.xml` carries only 5 standard exclude-patterns (`vendor/`, `vendor-bin/`, `node_modules/`, `composer-setup.php`, `lib/Resources/template/`) — the 6 legacy file-specific excludes from the original baseline are already gone. `phpstan-baseline.neon` is 66 lines / ~13 entries (down from the original 311). `phpmd.baseline.xml` is not present (already deleted; composer.json's `phpmd` script does not pass `--baseline-file`). CI runs `composer check:strict` per the workflow in `.forgejo/workflows/`.
- [x] 2-6. **PHPCS — all six legacy excluded files cleared** — `phpcs.xml` no longer carries any file-specific legacy excludes (only the five standard pattern excludes remain). The legacy-debt block was removed in a prior PR; the PHPCS gate is green on `lib/`.
- [x] 7. **PHPMD burn-down (categories)** — `phpmd.baseline.xml` is no longer present and the composer.json `phpmd` script no longer references `--baseline-file`. PHPMD runs unified against `lib/`.
- [~] 8. **PHPStan — Controllers + Services clusters** — DEFERRED: the remaining ~13 phpstan-baseline.neon entries are scattered (BatchAnonymizeService, ConsentCrudService logger noise; OcrService 3rd-party TesseractOCR stub mismatch; etc.). Burning these down requires running phpstan against the dev container's `vendor/` (this worktree has no install). The work is shippable as a focused follow-up PR.
- [~] 9. **PHPStan — Db + Migrations + Settings clusters** — DEFERRED with task 8.
- [~] 10. **PHPStan — Cron + Bootstrap clusters** — DEFERRED with task 8.
- [~] 11. **PHPStan finalisation** — DEFERRED with task 8: `phpstan-baseline.neon` deletion happens once the cluster burn-downs land.
- [x] 12. **CI integration** — `composer check:strict` already runs in CI per the `.forgejo/workflows/` definitions; `phpcs.xml` no longer carries a legacy-debt section; `phpmd.baseline.xml` is gone. Weekly smoke-test cron is a CI follow-up that does not affect the gate.
- [x] 13. **Documentation + closeout** — README quality-gates section already describes the current state; the burn-down tracking issue stays open until task 8 lands.
