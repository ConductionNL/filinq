# Design — EML Viewer Preview

## Context

`eml-pdf-assembly` renders a redacted `AnonymisedEmlStructure` to a PDF/A-3b. The viewer needs to show the **original** message before/besides anonymisation, and EML must be uploadable in the first place.

## Key decisions

### D1 — Server render, not a browser EML parser

The preview is generated server-side and served as PDF. Rationale: reusability (any client hits one endpoint), and correctness (MIME decoding, transfer-encodings, `cid:` inline images and attachment routing already live in the backend). A JS EML parser would duplicate all of that and drift from the anonymised render.

### D2 — Reuse the assembly pipeline with an empty entity set

`renderOriginalPreview()` calls `anonymizeEmlStructured($node, [])`. With no entities, OpenRegister's replacement map is empty, so `redactText` is a no-op and attachments carry their original bytes — the returned structure is effectively the original message. Feeding it to `EmlPdfAssemblyService::assemble()` yields a faithful preview using the **same** renderer as the anonymised output. No second "render an email" path exists to maintain.

Consequence: unsupported/oversize attachments render as placeholder pages in the preview too, identical to the anonymised output. Acceptable — the preview mirrors what the pipeline can render.

Interaction with `eml-viewer-preview`'s sibling fix: the `markAsAnonymizedWithPlaceholders` call added to `anonymizeEmlStructured` is guarded on `empty($replacements) === false`, so the preview's empty-entity call never marks the source rows anonymised. The preview is side-effect-free.

### D3 — `PdfViewer` gains a `url` escape hatch

Rather than a bespoke `EmlViewer`, `PdfViewer` takes an optional `url`. When set it fetches bytes from that URL; otherwise it derives a WebDAV URL from `path` as before. This keeps one PDF-rendering component and makes it reusable for any server-rendered PDF. `FileViewerPage` routes `eml` → `PdfViewer` with `url = emlPreviewUrl(fileId)`.

### D4 — Failure surfaces as 422, never a raw leak

The controller catches everything from the service and returns a `422` JSON error; the service lets `ConversionFailedException` propagate and guards OpenRegister availability. No un-rendered or partial content is emitted.

## Out of scope

- Rendering un-anonymisable attachment content (placeholder pages only).
- Preview caching / thumbnails.
- EN envelope labels (follows `register-i18n`).
