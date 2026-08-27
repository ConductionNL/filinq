## 1. Widen the output-format enum

- [x] 1.1 In `lib/Controller/AnonymizationController.php`, change `VALID_OUTPUT_FORMATS` to `['pdf-only', 'pdf', 'preserve']`. Confirm the 400 body (`resolveOutputFormat()` invalid path) cites all three values.
- [x] 1.2 In `lib/Controller/BatchAnonymizationController.php`, change `VALID_OUTPUT_FORMATS` to `['pdf-only', 'pdf', 'preserve']`. Confirm the 400 body cites all three values.

## 2. Flip the default to pdf-only

- [x] 2.1 In `lib/Service/SettingsService.php` (~line 197–201), change the `filinq.anonymisation.default_output_format` fallback from `'pdf'` to `'pdf-only'`; update the adjacent comment to describe the three modes.
- [x] 2.2 In `lib/Service/AnonymizationService.php`, change the `anonymizeDocument()` default param from `$outputFormat='pdf'` to `$outputFormat='pdf-only'`; update its docblock.

## 3. Delete-after-convert in the conversion gate

- [x] 3.1 In `AnonymizationService::anonymizeDocument()` (~lines 245–286), make the conversion gate fire for both `pdf-only` and `pdf` (still guarded by `mime !== 'application/pdf'`); capture the native node into a local (e.g. `$nativeIntermediate`) BEFORE `$result` is reassigned by `convertToPdf()`.
- [x] 3.2 After a SUCCESSFUL `convertToPdf()`, when the resolved mode is `pdf-only`, best-effort `delete()` the captured native intermediate inside try/catch `Throwable`, logging a PII-free warning on failure (mirror the existing rollback at lines 270–281). Never re-throw; never alter the success response.
- [x] 3.3 Verify the already-a-PDF path is a no-op: when `mime === 'application/pdf'` the gate is skipped, so `pdf-only` creates no intermediate and deletes nothing.
- [x] 3.4 Verify the conversion-failure rollback branch is unchanged (it still deletes the converted/intermediate node and re-throws `ConversionFailedException`).

## 4. Tests

- [x] 4.1 In `tests/unit/Service/AnonymizationServiceTest.php`, add a `pdf-only` success case asserting: converted PDF is written, the native intermediate (`$nativeIntermediate`) is the deleted node, and the converted PDF is NOT deleted.
- [x] 4.2 Add a `pdf-only` cleanup-failure case: `delete()` throws → warning logged, run still returns success, relation unchanged.
- [x] 4.3 Add an already-a-PDF `pdf-only` case asserting no conversion and no delete occur (identical to `pdf`).
- [x] 4.4 Add a regression assertion that `pdf` (keep native) and `preserve` (no convert) paths are unchanged.
- [x] 4.5 In the controller tests, assert the widened enum: `pdf-only` is accepted, omitted `outputFormat` resolves to `pdf-only` via the tenant default, and the 400 body for an invalid value lists all three allowed values (cover both `AnonymizationController` and `BatchAnonymizationController`).

## 5. Documentation and verification

- [x] 5.1 CHANGELOG: add an "Added" entry for the `pdf-only` `outputFormat` value and a "Behavior changes" entry noting the default flip from `pdf` to `pdf-only` (callers needing the native file alongside the PDF must send `outputFormat: "pdf"`; native-only callers send `preserve`). Add the configuration-only rollback note (`default_output_format = pdf`).
- [x] 5.2 Update `docs/features/anonymization.md` (the `outputFormat` section) with the three-mode semantics table.
- [x] 5.3 Run `composer check:strict` — clean (fix any pre-existing issues only in touched files per the workflow rule).
- [x] 5.4 Run `openspec validate anonymise-pdf-only-output-mode` — clean.

## 6. Admin settings UI

- [x] 6.1 In `src/views/settings/Settings.vue`, replace the boolean PDF switch with a three-option radio group (`pdf-only` / `pdf` / `preserve`) bound to `filinq.anonymisation.default_output_format`; default the in-component value to `pdf-only` and load the persisted value with a `pdf-only` fallback.
- [x] 6.2 Fix the save mapping so the selected value is persisted as-is (validated against the three allowed values, defaulting to `pdf-only`) instead of being coerced to `pdf`/`preserve`; update labels/description i18n strings to describe the three modes.

## Acceptance criteria

- Resolved mode `pdf-only` with a non-PDF input writes a PDF/A-3b and leaves NO native anonymised intermediate in Nextcloud Files.
- `pdf` mode still keeps the native intermediate; `preserve` still skips conversion entirely.
- A native-intermediate delete failure is logged at warning level and never fails the anonymise run.
- An already-a-PDF result in `pdf-only` mode performs no conversion and no deletion (identical to `pdf`).
- The `anonymizationLink` relation points at the converted PDF (no schema/relation/seed-data change).
- Both controllers accept `pdf-only`, default to it when `outputFormat` is omitted, and reject unknown values with a 400 listing all three allowed values.

## Quality checklist

- Native-node reference is captured before `$result` is reassigned; the delete never targets the converted PDF.
- Deletion uses try/catch `Throwable` with a PII-free warning log; no PII (no document text) is ever logged.
- No OpenRegister schema, register, or seed-data (`_registers.json`) change is introduced.
- No DB migration is added — the default flip is config/behaviour only, covered by a release note.
- All changed code paths are covered by unit tests (repo AGENTS.md requirement).