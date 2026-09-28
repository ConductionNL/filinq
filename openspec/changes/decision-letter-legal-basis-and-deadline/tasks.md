# Tasks: decision-letter-legal-basis-and-deadline

- [ ] 1.1 `ObjectionTermCalculator`: decision date plus the configured weeks, weekend moved to Monday; PHPUnit for a Tuesday, a deadline on Saturday, and a missing date
- [ ] 1.2 Generation adds `bezwaar` to the context and a warning when no decision date is found; PHPUnit through `DocumentService` with the real resolver
- [ ] 1.3 `MergeFieldDialog.vue` offers `grondslag.name`, `grondslag.article`, `bezwaar.uiterlijk` and `bezwaar.termijnWeken`; vitest on the offered list; en and nl strings
- [ ] 1.4 Docs in `docs/features/document-creatie-sjablonen.md`: the fields, the setting, the holiday limit
