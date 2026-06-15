---
id: print-preview
title: Print Preview
sidebar_label: Print Preview
sidebar_position: 7
description: Browser-based print preview and PDF/A-3b archival download from Twig templates
keywords:
  - print
  - preview
  - PDF/A
  - archival
  - mPDF
  - Twig
---

# Print Preview

## Status: Reviewed (implemented)

Print Preview provides browser-based print preview and PDF/A-3b archival document download from Twig/HTML templates. All rendering is server-side using mPDF with bundled DejaVu Sans fonts.

## Overview

Two workflows are supported:

1. **Print preview** — Renders the template to HTML with print-optimized CSS (`@page` rules, `page-break-inside: avoid`, navigation hiding). The rendered HTML is returned as JSON and injected into an iframe so the browser's native print dialog can produce a pixel-perfect page.
2. **PDF/A-3b download** — Generates an archival-quality PDF with embedded fonts, XMP metadata, and PDF/A-3b compliance enforced by mPDF. The `pdfa: true` flag is always forced regardless of request body options.

Templates can be referenced by stored UUID (via Template Management) or passed inline as a Twig string.

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/print/preview` | Render template to HTML with print CSS; returns `{ html, title }` |
| `POST` | `/api/print/pdf-a` | Render template to PDF/A-3b; returns binary PDF download |

### Request Body (both endpoints)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `templateId` | string (UUID) | One of | Load stored template via TemplateService |
| `template` | string | One of | Inline Twig template content |
| `data` | object | No | Template context data |
| `filename` | string | No | Output filename (default: `document.pdf`, PDF/A endpoint only) |

Returns HTTP 400 if neither `templateId` nor `template` is provided.

## Vue Component

A `PrintPreview` Vue component is provided that:
- Renders the preview in an iframe
- Provides a print button that triggers the browser print dialog
- Provides a download button that calls `/api/print/pdf-a`

## Standards

- **PDF/A-3b (ISO 19005-3)** — Long-term archival compliance enforced by mPDF
- **PDF/UA (ISO 14289-1)** — Accessible PDF output
- **Forum Standaardisatie: PDF (NEN-ISO)** — Dutch government open standard for document portability
- **GEMMA** Outputmanagementcomponent
- **TEC-DMS-1** (Content Authoring)

## Related Features

- [Print Functionality](./print-functionality.md) — Broader print pipeline including PDF/A generation
- [PDF Generation](./pdf-generation.md) — Shared PdfService used by this feature
- [Template Management](./template-management.md) — Stored templates referenced by `templateId`
