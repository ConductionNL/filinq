## Context

`AnonymizationWidget.vue` gates uploads three ways: the file input's `accept` attribute, a module-scoped `ALLOWED_EXTENSIONS`/`ALLOWED_MIMES` allow-list consumed by `partitionFiles()`, and user-facing copy enumerating the supported formats. All three listed `docx`/`txt`/`pdf`/`eml` only. The allow-list carried a comment explaining ODT was excluded because OpenRegister's redaction no-opped/corrupted it — a limitation now removed by `openregister:odt-anonymisation-writeback`.

## Goals / Non-Goals

**Goals:** accept `.odt` in the upload widget (by MIME and by extension), update the copy + NL/EN translations, and make the allow-list unit-testable.

**Non-Goals:** any backend/redaction change (owned by OpenRegister); ODT preview in the file viewer (`fileViewerService.js` renders only docx via mammoth — a separate, optional nicety); changes to the anonymise store/API calls (format-agnostic already).

## Decisions

### D1 — Extract the allow-list into `src/services/anonymizationUpload.js`

The allow-list + `partitionFiles()` were private to the SFC and untestable. Extract them into a pure ES module exporting `ALLOWED_EXTENSIONS`, `ALLOWED_MIMES`, `ACCEPT_ATTR`, and `partitionFiles()`, and import `partitionFiles` into the widget. This matches the repo's `src/services/*.js` + co-located `.spec.js` convention (e.g. `highlightText.spec.js`) and lets the ODT behaviour be asserted directly without mounting the component.

*Alternative considered:* keep the logic inline and test via `@vue/test-utils` mount — rejected: mounting the full widget needs many `@nextcloud/*` mocks for a trivial allow-list assertion; the pure module is the proportionate, idiomatic choice.

### D2 — Add ODT by both extension and MIME

Add `odt` to `ALLOWED_EXTENSIONS`, `application/vnd.oasis.opendocument.text` to `ALLOWED_MIMES`, and both to the input `accept` attribute. `partitionFiles()` already accepts on MIME **or** extension, so drag-and-drop (which sometimes omits MIME) and file-picker both work.

### D3 — Copy + i18n

Update the two format-enumerating strings to include ODT. New msgids get NL translations (`Alleen Word (.docx)-, ODT-, PDF- …`) and EN identity entries in `l10n/{en,nl}.{js,json}`. Transifex will re-sync later; the manual entries make the strings translated on ship.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| Frontend enabled before the backend redaction ships → users hit the old bug | Cross-app `depends_on`; ship after `openregister:odt-anonymisation-writeback`. |
| Manual l10n edits overwritten by the next Transifex sync | Source strings live in the `t()` calls; Transifex re-extracts them. Manual NL/EN entries only bridge until then. |

## Migration Plan

Pure frontend; no migration. Rollback is reverting the widget + service + l10n edits.

## Open Questions

- ODT preview in the in-viewer file viewer (mammoth is docx-only) — out of scope; track separately if wanted.
