---
status: in-progress
---

# Document Preview

<!-- OpenSpec changes: odt-viewer-preview (client-side ODT preview in the anonymisation viewer) -->

## Purpose

In-app rendering of uploaded documents in the Filinq anonymisation file viewer, so an operator can review a document, see detected entities highlighted, and select text to add entities. Each supported format is rendered by a dedicated viewer component (PDF via pdfjs, Word/.docx via mammoth, plain text verbatim, EML via a server-rendered PDF preview, and ODT via a client-side ODF→HTML transform).

The normative requirements for the ODT preview are defined in the active change `odt-viewer-preview` until it is archived.

## Requirements

### Requirement: Format-specific in-app document preview (REQ-DDPRV-001)

The anonymisation file viewer MUST render an uploaded document in-app through a
viewer component selected by the document's format, so an operator can review the
content and its highlighted entities without downloading the file.

Renderers by format: PDF via pdfjs, Word/`.docx` via mammoth, plain text verbatim,
EML via a server-rendered PDF preview, and ODT via a client-side ODF→HTML transform
(the ODT path's normative detail lives in the active `odt-viewer-preview` change
until it is archived).

#### Scenario: A supported document is previewed in the viewer

- GIVEN an operator opens a PDF, `.docx`, plain-text, EML or ODT document in the anonymisation file viewer
- WHEN the viewer resolves the document's format
- THEN the matching viewer component renders the document in-app, with detected entities highlighted for review
- @e2e exclude legacy retrofit spec — viewer components are covered by their own change specs

#### Scenario: An unsupported format does not render a viewer

- GIVEN a document whose format has no dedicated viewer component
- WHEN the operator opens it in the anonymisation file viewer
- THEN no preview is rendered and the operator is told the format cannot be previewed in-app
- @e2e exclude legacy retrofit spec — negative path asserted at component level
