# Tasks — odt-viewer-preview

> Frontend-only, Filinq-only. Client-side render; no backend, no new dependency (JSZip already present).
> Depends on `odt-anonymisation-frontend` (ODT accepted for upload) → `openregister:odt-anonymisation-writeback`.

## 1. ODF transform service

- [x] 1.1 Add `src/services/odfToHtml.js` — `odfXmlToHtml(contentXml)` (whitelist ODF→HTML: headings/paragraphs/tables/lists/breaks, text escaped, no attributes/scripts) and `odfXmlToText(xml)` (concatenated paragraph text).

## 2. ODT viewer component

- [x] 2.1 Add `src/components/viewers/OdtViewer.vue`: fetch ArrayBuffer, unzip with JSZip, read `content.xml`, render via `odfXmlToHtml` under `v-html`.
- [x] 2.2 Reuse WordViewer's UX: entity highlighting (`applyDomHighlights`/`clearDomHighlights` on `$refs.content`), add-mode selection capture, and the "highlight after loading" ordering.
- [x] 2.3 Style the rendered HTML with `:deep()` inside the scoped block (no unscoped `<style>`).

## 3. Wire the viewer selection

- [x] 3.1 `FileViewerPage.vue`: `detectViewer()` returns `'odt'` for the ODT MIME/extension; register `OdtViewer`; map `odt → OdtViewer`; ODT uses the Word-box icon.

## 4. Fix ODT placeholder extraction

- [x] 4.1 `fileViewerService.js`: `extractDocumentText()` handles ODT via JSZip + `odfXmlToText` (content.xml + styles.xml) instead of the raw-bytes text fetch.

## 5. Tests

- [x] 5.1 Add `src/services/odfToHtml.spec.js`: headings/paragraphs/tables/lists/breaks render; span unwrap; entity escaping / XSS-safety; unparseable → empty; `odfXmlToText` returns concatenated text incl. table cells.

## Acceptance criteria

- Opening an `.odt` in the anonymisation viewer shows a rendered preview (paragraphs, headings, tables, lists), not the unsupported state.
- Detected entities are highlighted in the ODT preview and text selection for "add entity" works, as in the docx preview.
- Document text can never inject executable markup via `v-html`.
- `extractDocumentText()` returns real ODT text (not ZIP bytes), so placeholder scanning works.
- No backend change, no new dependency.

## Quality / test / i18n reminders

- `openspec validate "odt-viewer-preview"` passes.
- New JS files carry the EUPL-1.2 SPDX header (Conduction convention).
- Jest specs pass; ESLint clean on the changed files.
- No new user-facing strings beyond existing viewer copy (loading/error already translated); no new i18n keys required.
