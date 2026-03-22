## Context

DocuDesk currently generates PDF 1.4 documents via `PdfService` using mPDF. The service is stateless: callers provide a Twig template string and data, and receive PDF binary output. Templates are managed separately via `TemplateService` and stored as OpenRegister objects.

Government tenders require PDF/A (ISO 19005) for archival-compliant output. The existing mPDF dependency already supports PDF/A natively via its `PDFA` configuration flag, so no new dependencies are needed. The gap is: (1) PDF/A mode is not exposed, (2) fonts are not explicitly embedded, and (3) there is no browser-based print preview workflow.

## Goals / Non-Goals

**Goals:**
- Enable PDF/A-3b compliant output from PdfService with a single option toggle
- Embed DejaVu Sans fonts for consistent cross-system rendering
- Provide a print-preview endpoint returning rendered HTML for browser printing
- Add a Vue component for print preview with PDF/A download action
- Maintain backward compatibility: existing `renderPdf()` callers unaffected unless they opt in to PDF/A

**Non-Goals:**
- PDF/A-1 or PDF/A-2 conformance levels (PDF/A-3b is sufficient for Dutch government archival)
- Digital signature embedding (covered by separate `document-signing` spec)
- Print queue or batch printing
- PDF form fields or interactive elements
- Custom font upload (only system/bundled fonts)

## Decisions

### 1. PDF/A mode via mPDF native support
**Decision**: Use mPDF's built-in `PDFA: true` config flag rather than post-processing with external tools.
**Rationale**: mPDF already supports PDF/A-3b. No new dependency. Post-processing tools (Ghostscript, verapdf) add complexity and external dependencies, violating the "100% local, no external services" constraint.
**Alternative considered**: Ghostscript `ps2pdf` with PDF/A profile -- rejected because it requires a system-level binary not guaranteed in Nextcloud container environments.

### 2. Font embedding with DejaVu Sans
**Decision**: Bundle DejaVu Sans (regular, bold, italic, bold-italic) in `lib/Fonts/` and configure mPDF's `fontDir` and `fontdata` to use them.
**Rationale**: DejaVu Sans is LGPL-licensed, covers Latin/Cyrillic/Greek, and is the standard fallback font for mPDF. Bundling guarantees availability regardless of host system fonts. Font files total approximately 2.5MB.
**Alternative considered**: Relying on system fonts -- rejected because Docker containers may not have consistent font sets, leading to rendering inconsistencies.

### 3. Print preview as separate controller
**Decision**: Create `PrintController` with a `preview` endpoint returning rendered HTML (not PDF). The frontend uses `window.print()` for browser-native print dialog.
**Rationale**: Separating print preview from PDF generation allows the browser to handle print settings (printer selection, copies, page range) while DocuDesk handles content rendering. The preview endpoint reuses `TemplateRenderer` for HTML rendering.
**Alternative considered**: Generating a PDF and embedding it in an iframe -- rejected because it prevents browser print customization and adds unnecessary PDF generation overhead for preview.

### 4. Extend existing PdfService rather than new service
**Decision**: Add `pdfa` and `fonts` options to the existing `PdfService::renderPdf()` method and `buildMpdfConfig()`.
**Rationale**: The change is additive -- new options with backward-compatible defaults. Creating a separate `PdfAService` would duplicate logic and fragment the API.

### 5. Print-optimized CSS injection
**Decision**: `PdfService` injects `@media print` CSS rules automatically when `pdfa` mode is enabled, including page break hints and margin normalization.
**Rationale**: Government documents need predictable print layout. Auto-injection avoids requiring every template author to add print CSS manually.

## Risks / Trade-offs

- **[Risk] mPDF PDF/A output may not pass strict validation** -- Mitigation: Test output with veraPDF validator during development. mPDF's PDF/A support is well-established for PDF/A-3b.
- **[Risk] Font bundle increases app size by ~2.5MB** -- Mitigation: Acceptable trade-off for guaranteed rendering consistency. Fonts are loaded only when PDF/A mode is active.
- **[Risk] Print preview HTML may render differently from PDF output** -- Mitigation: Use the same CSS for both HTML preview and PDF generation. Document known browser rendering differences.
- **[Trade-off] PDF/A-3b chosen over PDF/A-1b** -- PDF/A-3b is the most permissive archival format and allows embedded attachments. PDF/A-1b is stricter but mPDF's support for it is less reliable.

## Migration Plan

1. **No breaking changes** -- existing `renderPdf()` calls continue to produce PDF 1.4 output
2. **Opt-in** -- callers add `'pdfa' => true` to options array for PDF/A output
3. **Font files** -- bundled in `lib/Fonts/`, no installation step needed
4. **Routes** -- new endpoints added to `routes.php`, no existing routes modified
5. **Rollback** -- remove new controller, revert PdfService changes; no data migration involved

## Open Questions

- Should a global admin setting control "default to PDF/A" for all PDF generation?
- Should the print preview endpoint support unauthenticated access for public document scenarios?
