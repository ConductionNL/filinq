# Tasks: intake-paper-archive-digitisation

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [ ] 1.1 Add `digitisationProject` and `paperOriginal` with the D1 properties, lifecycles and the per-box aggregation to `lib/Settings/filinq_register.json`, seed and version bump (REQ-PAD-001). Verify: `occ maintenance:repair` imports them and the seed project shows its counts.

## 2. Separators and splitting

- [ ] 2.1 Add the v2 separator per original to `SeparatorSheetService` with shelf mark and description in print (D2) (REQ-PAD-002). Verify: PHPUnit decodes the QR payload from the rendered page.
- [ ] 2.2 File a segment that names an original on the project target with the original's metadata and set it `scanned`; keep v1 segments going to the inbox (D3) (REQ-PAD-003). Verify: PHPUnit on a fixture batch with one v2 and one v1 separator.

## 3. Project service

- [ ] 3.1 Add `lib/Service/DigitisationProjectService.php` with the sampling draw (D4) (REQ-PAD-004). Verify: PHPUnit with a seeded random source for 0, 10 and 100 percent.
- [ ] 3.2 CSV box list import through openregister's object import with a failure list (D5) (REQ-PAD-001). Verify: PHPUnit imports a fixture CSV with one bad row.

## 4. Screens

- [ ] 4.1 Add a project index and detail with per-box counts and a "Print separators for this box" action, in `src/manifest.json` (REQ-PAD-001, REQ-PAD-002). Verify: Playwright prints the separators of the seed box.
- [ ] 4.2 Add the checking queue: scan beside the original's metadata, pass or reject with a note (REQ-PAD-004). Verify: Playwright rejects one seeded scan and sees the original back in `registered`.

## 5. Quality

- [ ] 5.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
- [ ] 5.2 PHPUnit at 75% on new code inside the container (ADR-009). Verify: coverage report for the new service and the split branch.
