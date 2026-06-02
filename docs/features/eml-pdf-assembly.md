# EML PDF Assembly

DocuDesk assembles a rich **PDF/A-3b** from `.eml` (email) inputs when the conversion cascade processes an EML file (e.g. via the anonymise endpoint with `outputFormat: "pdf"`).

## What the PDF contains

| Section | Content |
|---------|---------|
| **Header block** | Van / Aan / Cc / Onderwerp / Datum (NL labels) |
| **Body** | HTML body preferred (preserves layout and inline images); plain-text fallback in `<pre>`; placeholder when both are absent |
| **Embedded files** | Every attachment as a PDF/A-3 embedded file (extractable by any PDF/A-3 viewer) |
| **Appended pages** | Renderable attachments (PDF, images, plain-text, nested EML, Word docs) as appended pages prefixed by a divider page |

## Renderable attachment types

| MIME / Extension | Rendered as pages? |
|------------------|--------------------|
| `application/pdf` | Yes — pages imported via mPDF |
| `image/png`, `image/jpeg`, `image/gif`, `image/webp` | Yes — single page per image |
| `text/plain`, `text/csv`, `text/markdown` | Yes — `<pre>`-wrapped page |
| `message/rfc822` (.eml) | Yes — recursive assembly (depth ≤ 3) |
| DOCX / ODT / RTF / HTML (PhpWord-readable) | Yes — rendered via PhpWord → mPDF |
| All other MIMEs (zip, xlsx, ics, …) | No — embedded only; divider page explains |

## Configuration

All keys are `IAppConfig` values (app: `docudesk`):

| Key | Default | Description |
|-----|---------|-------------|
| `docudesk.conversion.eml.append_attachment_pages` | `true` | Set to `false` to embed attachments only without rendering pages |
| `docudesk.conversion.eml.max_attachment_render_size_bytes` | `26214400` (25 MB) | Attachments larger than this are embedded but not rendered |
| `docudesk.conversion.eml.divider_template` | `eml/divider.twig` | Override the per-attachment divider page template |
| `docudesk.conversion.backends.eml_enabled` | `true` | Set to `false` to disable the EML backend entirely |

## Dependencies

| Dependency | Status | Effect when absent |
|------------|--------|-------------------|
| OpenRegister `text-extraction-eml` — provides `TextExtractionService::parseEmlStructured()` | Required | `EmlBackend::isAvailable()` returns `false`; EML inputs return 422 |
| DocuDesk `anonymise-output-as-pdf-by-default` — provides the conversion cascade | Required | No cascade to route EML inputs through |

## Graceful degradation

| Failure | Behaviour |
|---------|-----------|
| `parseEmlStructured` throws | Falls back to `extractEml` flat text; single plain-text page, no attachments |
| Twig template render fails | Minimal HTML envelope with available headers + "(template rendering failed)" notice |
| One attachment fails to render | Skip rendered pages; keep the embedded file; divider says "kon niet worden weergegeven" |
| Attachment embedding fails | Log; continue; footer notice lists failed embeds |
| All steps fail | Throw `ConversionFailedException` → cascade 422s |

## Limitations (v1)

- **Header labels are Dutch-only** — EN labels follow `register-i18n`.
- **HTML rendering fidelity** — mPDF handles common email HTML well; complex CSS (flexbox, modern grid) may render imperfectly. This is acceptable for archival rendering.
- **Nested EML depth cap** — OR's `parseEmlStructured` caps nesting at depth 3; deeper chains are embedded but not rendered.
- **Encrypted bodies** — rendered as an opaque notice; decryption is a separate change.
