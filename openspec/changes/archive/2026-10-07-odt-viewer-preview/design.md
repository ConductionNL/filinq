## Context

The anonymisation file viewer (`FileViewerPage.vue`) selects a viewer component per format via `detectViewer()` → `PdfViewer` / `WordViewer` / `TextViewer` (EML routes through a server-rendered PDF). `WordViewer` renders docx by converting it to HTML with mammoth and binding it with `v-html`, then applies entity highlights to `$refs.content` via `applyDomHighlights` and captures text selection into `fileViewerStore` (add-mode). `.odt` maps to no viewer, so it shows the unsupported state.

## Goals / Non-Goals

**Goals:** render an ODT preview in the viewer with the same highlight + selection UX as docx; fix ODT placeholder text extraction; keep it fully client-side.

**Non-Goals:** header/footer (styles.xml) preview; embedded image rendering; pixel-fidelity layout; any backend change.

## Decisions

### D1 — Client-side ODF→HTML, no backend, no new dependency

`.odt` is a ZIP and JSZip is already a dependency. `OdtViewer` fetches the file as an ArrayBuffer (reusing `fetchFileAsArrayBuffer`), unzips it, reads `content.xml`, and transforms it to HTML. This avoids a server round-trip and, unlike PhpWord's ODText reader (which drops tables/headers/footers), preserves table text. mammoth is not an option — it is docx-only.

*Alternatives considered:* server-side ODT→PDF preview via the conversion cascade + `PdfViewer` (like EML) — higher visual fidelity only when Collabora is installed, lossy on the PhpWord fallback, and needs a new backend endpoint; rejected for v1. Plain-text-only preview — loses table structure and diverges from the docx preview UX; rejected.

### D2 — A pure, whitelisted, XSS-safe transform in its own module

`src/services/odfToHtml.js` is a pure function (no viewer coupling) so it is unit-testable. It emits a fixed whitelist of structural tags (`h1`–`h6`, `p`, `table/tr/td`, `ul/li`, `br`) with all text escaped and NO attributes, so binding the result with `v-html` cannot inject scripts, styles, or external references. Unknown/wrapper elements contribute their children only; embedded objects render nothing.

### D3 — Reuse WordViewer's highlight + selection UX

`OdtViewer` mirrors `WordViewer`: same `highlightEntities`/`pendingValue`/`isAddMode` computeds, the same `applyDomHighlights`/`clearDomHighlights` calls on `$refs.content`, the same `captureSelection`, and the same "highlight only after loading is false" ordering. So detected entities are highlighted and text selection for "add entity" works identically in the ODT preview.

### D4 — Fix ODT placeholder extraction in `extractDocumentText`

`extractDocumentText()` previously routed ODT to a raw WebDAV text fetch — which returns the ZIP's transport bytes, not text — so scanning an anonymised `.odt` for `[<TYPE>: <id>]` placeholders was broken. It now unzips via JSZip and concatenates `content.xml` + `styles.xml` text via `odfXmlToText`.

### D5 — Styling via `:deep()`, not an unscoped block

`WordViewer` styles its `v-html` output with an unscoped `<style>` block (which trips `vue/enforce-style-attribute`). `OdtViewer` instead uses `:deep()` selectors inside the scoped block, keeping styles scoped and lint-clean.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| `v-html` injection from document content | Whitelist transform escapes all text and emits no attributes/scripts (D2); unit test asserts `<script>` in document text is neutralised. |
| ODF structures not in the whitelist render as plain text | Wrappers recurse to their children so text is never lost; structural fidelity (headings/tables/lists) covers the common government-document cases; richer structures are a future enhancement. |
| Header/footer text not shown (styles.xml not rendered) | Documented non-goal; the body (incl. tables) is the review surface; placeholder extraction DOES include styles.xml text (D4). |

## Migration Plan

Pure frontend; no migration. Rollback removes `OdtViewer`/`odfToHtml` and reverts the `FileViewerPage`/`fileViewerService` edits — `.odt` falls back to the unsupported state.

## Open Questions

- Whether to later add header/footer (styles.xml) rendering and embedded-image support to the preview.
