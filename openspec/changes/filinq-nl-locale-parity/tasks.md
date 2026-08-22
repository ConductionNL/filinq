# Tasks: filinq-nl-locale-parity

All tasks are `[filinq]`. Estimates: S = half-day, M = 1-2 days.

## [filinq] Gate: enforce en/nl key parity

### A-1. Extend the l10n check script (M)

- [ ] A-1.1 In `tests/l10n/check-l10n.js`, after the existing "every used key
  is present in en.json" check, add a parity pass: for each key in
  `l10n/en.json`, assert it also exists (non-empty) in `l10n/nl.json`.
- [ ] A-1.2 Exit non-zero and print the full list of missing/empty `nl.json`
  keys when the parity check fails (mirror the existing script's
  error-reporting style).
- [ ] A-1.3 Add a warn-only (non-failing) report of the same diff for
  `de.json` / `es.json` / `fr.json` / `it.json` so drift is visible without
  blocking CI on locales ADR-007 doesn't mandate.
  - **Acceptance:** running `npm run test:l10n` against the current
    (pre-backfill) repo state fails with a clear count/list of the 384
    missing `nl.json` keys.

## [filinq] Backfill nl.json

### B-1. Translate the 384 missing keys (M)

- [ ] B-1.1 Add a real Dutch translation for every `en.json` key currently
  absent from `nl.json` (full list obtainable by running the new A-1 check;
  do not copy the English string as a placeholder — ADR-007 requires actual
  translations).
- [ ] B-1.2 Regenerate `l10n/nl.js` from `l10n/nl.json` per the app's
  existing l10n build step (`.js`/`.json` pairs must stay in sync, matching
  the pattern already used for `en.js`/`en.json`).
- [ ] B-1.3 Re-run `npm run test:l10n` and confirm the new parity check
  (A-1) passes with zero missing keys.

## [filinq] Fix Dutch-as-key literals

### C-1. Rename the six Grondslagen/Grondslag call sites (S)

- [ ] C-1.1 `src/views/anonymization/AnonymizationPocWidget.vue:16` — change
  key to English (e.g. `'Bases catalogue could not be loaded — falling back
  to the seeded list.'`), add the existing Dutch text as the `nl.json`
  translation for the new key.
- [ ] C-1.2 `src/views/anonymization/AnonymizationPocWidget.vue:114` — change
  `'Grondslag (bases)'` to an English key (e.g. `'Legal basis (bases)'`).
- [ ] C-1.3 `src/views/anonymization/AnonymizationPocWidget.vue:139` — change
  `'Grondslagen'` input-label key to an English key (e.g. `'Legal bases'`).
- [ ] C-1.4 `src/views/anonymization/EntityReviewTable.vue:55` — same
  `'Grondslagen'` → English key rename.
- [ ] C-1.5 `src/views/anonymization/FolderAnonymizationView.vue:77` — same
  `'Grondslagen'` → English key rename.
- [ ] C-1.6 `src/components/DdEntityCard.vue:68` — same `'Grondslagen'` →
  English key rename.
  - **Acceptance:** `grep -rn "t('filinq', '[A-Za-z]*[Gg]rondslag" src/`
    returns zero matches; the new English keys are present in `en.json`
    (identity-mapped) with the original Dutch text preserved in `nl.json`.

## [filinq] Verification

### D-1. Full-repo sweep (S)

- [ ] D-1.1 Re-run the repo-wide scan for any other Dutch word used as a
  `t('filinq', ...)` literal key (spot-checked for "Grondslag" only in
  this pass — a broader Dutch-word sweep is worth one more pass before
  closing this change, since the six found here were located via a single
  keyword).
- [ ] D-1.2 Confirm `npm run test:l10n` is green end-to-end (en-completeness
  + new nl-parity check).
