---
id: print-functionality
title: Print Functionality
sidebar_label: Print Functionality
sidebar_position: 8
description: Generate print previews and PDF/A-3b archival documents from Twig templates
keywords:
  - print
  - PDF
  - PDF/A
  - archival
  - mPDF
---

# Print Functionality

DocuDesk provides a dedicated print pipeline for generating browser-ready print previews and
PDF/A-3b compliant archival documents from Twig/HTML templates. All rendering happens
server-side using mPDF with bundled DejaVu Sans fonts.

## Overview

The print feature supports two workflows:

1. **Print preview** — Renders the template to HTML and injects print-optimized CSS so the
   browser can print a pixel-perfect page.
2. **PDF/A-3b download** — Generates an archival-quality PDF with embedded fonts, XMP metadata,
   and PDF/A-3b compliance enforced by mPDF.

Templates can be referenced by a stored template UUID (via the Template Management feature) or
provided as inline Twig/HTML content in the request body.

## API Endpoints

### Print Preview

```
POST /apps/docudesk/api/print/preview
```

Returns rendered HTML with print-optimized CSS injected. Intended for use in a preview iframe
before the user commits to a download.

**Request body (JSON):**

| Field        | Type   | Required | Description                                         |
|-------------|--------|----------|-----------------------------------------------------|
| `templateId` | string | *        | UUID of a stored template (see Template Management) |
| `template`   | string | *        | Inline Twig/HTML template content                   |
| `data`       | object |          | Data context passed to Twig for rendering           |
| `options`    | object |          | PDF options: `format`, `orientation`                |

*One of `templateId` or `template` is required.*

**Successful response (200):**

```json
{
  "html":  "<style>...</style><h1>Hello World</h1>",
  "title": "My Document"
}
```

**Error response:**

```json
{ "error": "Either templateId or template content is required" }
```

---

### PDF/A Download

```
POST /apps/docudesk/api/print/pdf-a
```

Returns a PDF/A-3b binary as a file download. PDF/A mode is always enabled for this endpoint.

**Request body (JSON):**

| Field        | Type   | Required | Description                                         |
|-------------|--------|----------|-----------------------------------------------------|
| `templateId` | string | *        | UUID of a stored template                           |
| `template`   | string | *        | Inline Twig/HTML template content                   |
| `data`       | object |          | Data context passed to Twig                         |
| `filename`   | string |          | Suggested download filename (default: `document.pdf`) |

*One of `templateId` or `template` is required.*

**Successful response (200):**
- `Content-Type: application/pdf`
- `Content-Disposition: attachment; filename="<filename>"`
- Body: PDF/A-3b binary

---

## Configuration Options

The `options` object (used in the print preview endpoint and stored template records) accepts:

| Key           | Type    | Default | Description                                          |
|--------------|---------|---------|------------------------------------------------------|
| `format`     | string  | `A4`    | Page size: `A4`, `A3`, `Letter`, `Legal`             |
| `orientation`| string  | `P`     | `P` (portrait) or `L` (landscape)                   |
| `margin`     | object  | 15mm    | Override margins: `{ top, right, bottom, left }` (mm) |
| `title`      | string  | `""`    | PDF metadata title field                             |
| `pdfa`       | boolean | `false` | Force PDF/A-3b mode (always `true` for `/pdf-a`)    |

## Template Resolution

When `templateId` is supplied, the format and orientation are read from the stored template
record fields (`format`, `orientation`). Inline request `options` are ignored for stored
templates.

When `template` (inline content) is supplied, `options` from the request body are used directly.

## PDF/A-3b Compliance

The PDF/A-3b output includes:

- **Embedded fonts** — DejaVu Sans (Regular, Bold, Italic, Bold Italic) bundled with the app
- **XMP metadata** — Author (`DocuDesk`), creator (`DocuDesk PDF/A Generator`), and title
- **Print CSS** — Automatic `@page` size, margin normalization, and page-break rules
- **PDFAauto** — mPDF auto-corrects non-compliant elements where possible

## Dependencies

| Dependency         | Purpose                          |
|-------------------|----------------------------------|
| `mpdf/mpdf`        | HTML-to-PDF conversion           |
| `TemplateRenderer` | Twig sandboxed template engine   |
| `TemplateService`  | Stored template retrieval (optional) |

## Services

### `PdfService`

Handles all PDF generation logic.

| Method                  | Description                                                 |
|------------------------|-------------------------------------------------------------|
| `renderPdf()`           | Render Twig template to PDF binary                         |
| `renderHtmlPreview()`   | Render Twig template to HTML with print CSS                |
| `buildPrintCss()`       | Generate `@media print` style block for given format/orientation |

### `PrintController`

REST controller for print endpoints. Resolves template content from request parameters and
delegates to `PdfService`.

| Method           | Route                        | Verb |
|-----------------|------------------------------|------|
| `preview()`      | `/api/print/preview`         | POST |
| `downloadPdfA()` | `/api/print/pdf-a`           | POST |
