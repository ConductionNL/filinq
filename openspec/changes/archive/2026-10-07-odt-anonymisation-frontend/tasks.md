# Tasks — odt-anonymisation-frontend

> Frontend-only, Filinq-only. Backend ODT redaction is the paired `openregister:odt-anonymisation-writeback` change — do NOT duplicate it here.
> No OpenRegister schema/register/object, no DB migration, no API change.

## 1. Extract the upload allow-list into a testable module

- [x] 1.1 Create `src/services/anonymizationUpload.js` exporting `ALLOWED_EXTENSIONS`, `ALLOWED_MIMES`, `ACCEPT_ATTR`, and `partitionFiles()` — with `odt` / `application/vnd.oasis.opendocument.text` included.
- [x] 1.2 Import `partitionFiles` into `AnonymizationWidget.vue` and remove the inline allow-list/definition.

## 2. Accept ODT in the widget

- [x] 2.1 Add `.odt` + `application/vnd.oasis.opendocument.text` to the file input's `accept` attribute.
- [x] 2.2 Update the two format-enumerating copy strings to include ODT (drop-subtitle + skipped-files error).

## 3. i18n

- [x] 3.1 Add NL + EN entries for the two new msgids in `l10n/{en,nl}.{js,json}`.

## 4. Tests

- [x] 4.1 Add `src/services/anonymizationUpload.spec.js`: ODT accepted by MIME and by extension; docx/txt/pdf/eml still accepted; unsupported (xlsx/png) rejected; allow-list/accept include ODT.

## Acceptance criteria

- An `.odt` file can be selected/dropped in the anonymisation widget and is accepted (by MIME and by extension).
- The supported-formats copy mentions ODT and is translated (NL + EN).
- Previously-supported formats still pass; unsupported formats are still rejected and reported.
- No backend, API, store, or schema change.

## Quality / test / i18n reminders

- `openspec validate "odt-anonymisation-frontend"` passes.
- New JS files carry the EUPL-1.2 SPDX header (Conduction convention).
- Jest spec passes; ESLint clean on the changed files.
- Ship after `openregister:odt-anonymisation-writeback` so uploads actually redact.
