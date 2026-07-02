---
kind: code
depends_on:
  - eml-pdf-assembly
  - openregister:anonymise-eml-structured
---

## Why

`eml-pdf-assembly` made EML inputs produce an anonymised PDF/A-3b, but two frontend gaps remained before an operator can actually work with email in the anonymisation UI:

1. **EML could not be uploaded.** The anonymisation upload widget hard-restricts accepted formats to `docx` / `txt` / `pdf` (the formats OpenRegister redacts in place). `message/rfc822` was rejected at the widget, so the whole EML pipeline was unreachable from the UI.
2. **The original EML could not be previewed.** The in-app file viewer maps only `pdf` / `docx` / `text` to a viewer component; an opened `.eml` fell through to "This file type cannot be previewed." An operator reviewing correspondence before anonymising had no way to see the source message, and the viewer's *Show original / Show anonymised* toggle had no "original" side for EML.

The natural fix for (2) is a **server-side render**, not a browser-side EML parser. Rendering on the server means any client — the DocuDesk viewer, a direct link, a future mobile client, another app — consumes one endpoint instead of re-implementing MIME parsing, encoding handling and inline-image resolution in JavaScript. It also lets us reuse the exact rendering that `eml-pdf-assembly` already ships: the original preview is simply the anonymise-assembly pipeline run with an **empty entity set** (nothing to redact), so there is no second "render an email" code path to maintain or keep consistent.

## What Changes

- **NEW capability `eml-preview`.**
- **MODIFIED (frontend gate):** the anonymisation upload widget (`AnonymizationWidget.vue`) accepts `message/rfc822` / `.eml` in both its `accept` attribute and its `partitionFiles` allow-list, so EML files can be uploaded into the anonymisation flow.
- **NEW backend service `EmlPreviewService`:** `renderOriginalPreview(int $fileId): string` resolves OpenRegister's `FileService`, calls `anonymizeEmlStructured($node, [])` (empty entity set → no redaction), and feeds the resulting structure to `eml-pdf-assembly`'s `EmlPdfAssemblyService::assemble()`, returning PDF/A-3b bytes. No file is written; no new render path is introduced.
- **NEW backend endpoint:** `GET /api/anonymization/eml-preview/{fileId}` (`EmlPreviewController::preview`, `#[NoAdminRequired]`, `#[NoCSRFRequired]`) streams the preview PDF as a `DataDownloadResponse`, or a `422` JSON error when rendering fails.
- **MODIFIED (viewer):** `PdfViewer.vue` gains an optional `url` prop — when set, it fetches the PDF bytes from that URL instead of deriving a WebDAV URL from `path`. This makes `PdfViewer` reusable for any server-rendered PDF.
- **MODIFIED (viewer routing):** `FileViewerPage.vue`'s `detectViewer()` maps `message/rfc822` / `.eml` to a new `eml` kind that renders through `PdfViewer`, bound (via a `viewerProps` computed) to the preview endpoint keyed by `currentFile.fileId`. The *Show original / Show anonymised* toggle now has a working original side for EML.
- **NEW frontend service helpers:** `emlPreviewUrl(fileId)` and `fetchUrlAsArrayBuffer(url)` in `fileViewerService.js`.

Out of scope: rendering un-anonymisable attachment content (they remain placeholder pages, consistent with `eml-pdf-assembly`); EN translations of envelope labels (follows `register-i18n`); any preview caching.