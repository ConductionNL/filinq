---
kind: code
depends_on:
  - odt-anonymisation-frontend
---

## Why

With ODT accepted for anonymisation (`odt-anonymisation-frontend`), a user who uploads an `.odt` gets **no document preview** in the anonymisation file viewer. `FileViewerPage.vue`'s `detectViewer()` maps `.odt` to no viewer, so the review pane shows the "unsupported" state — the operator cannot see the document, its detected entities highlighted, or select text to add entities. Every other supported format (pdf/docx/txt/eml) renders a preview; ODT must too.

There is no drop-in renderer: `WordViewer` uses mammoth, which is docx-only, and PhpWord's ODText reader drops tables/headers/footers. But `.odt` is a ZIP and **JSZip is already a dependency**, so the viewer can unzip the file and transform its `content.xml` to HTML in the browser — no backend round-trip, tables included.

## What Changes

- **NEW:** `src/services/odfToHtml.js` — pure, testable transform: `odfXmlToHtml(contentXml)` (ODF → a safe whitelist of structural HTML: headings, paragraphs, tables, lists, breaks — all text escaped, no attributes/scripts, so it is safe under `v-html`) and `odfXmlToText(xml)` (concatenated visible text for placeholder scanning).
- **NEW:** `src/components/viewers/OdtViewer.vue` — unzips the `.odt` with JSZip, renders `content.xml` via `odfXmlToHtml`, and wires the SAME entity-highlighting + text-selection UX as `WordViewer` (reuses `applyDomHighlights`/`clearDomHighlights`, `fileViewerStore` selection/add-mode).
- **MODIFIED:** `FileViewerPage.vue` — `detectViewer()` returns `'odt'` for `application/vnd.oasis.opendocument.text` / `.odt`; registers `OdtViewer`; maps `odt → OdtViewer`; gives ODT the Word-box icon.
- **MODIFIED:** `fileViewerService.js` — `extractDocumentText()` handles ODT via JSZip + `odfXmlToText` (previously it fetched the ZIP's transport bytes as text — garbage — so placeholder scanning of an anonymised `.odt` was broken).
- **NEW tests:** `src/services/odfToHtml.spec.js` — transform correctness (headings/paragraphs/tables/lists/breaks, span unwrap, entity escaping/XSS-safety, unparseable → empty) for both HTML and text.
- **NO backend change**, no new dependency (JSZip already present).

## Capabilities

### New Capabilities

- `document-preview` — in-app rendering of uploaded documents in the anonymisation viewer; this change adds ODT.

## Impact

- **Affected code:** `src/services/odfToHtml.js` (new), `src/services/odfToHtml.spec.js` (new), `src/components/viewers/OdtViewer.vue` (new), `src/views/fileViewer/FileViewerPage.vue`, `src/services/fileViewerService.js`.
- **Depends on** `odt-anonymisation-frontend` (which itself depends on `openregister:odt-anonymisation-writeback`).
- **Security:** `v-html` binds only the whitelist transform output (escaped text, no attributes/scripts).
- **Out of scope:** header/footer preview (styles.xml) and embedded-image rendering — the body (content.xml, incl. tables) is rendered; deferred as a nicety.
