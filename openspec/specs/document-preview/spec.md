---
status: in-progress
---

# Document Preview

<!-- OpenSpec changes: odt-viewer-preview (client-side ODT preview in the anonymisation viewer) -->

## Purpose

In-app rendering of uploaded documents in the DocuDesk anonymisation file viewer, so an operator can review a document, see detected entities highlighted, and select text to add entities. Each supported format is rendered by a dedicated viewer component (PDF via pdfjs, Word/.docx via mammoth, plain text verbatim, EML via a server-rendered PDF preview, and ODT via a client-side ODF→HTML transform).

The normative requirements for the ODT preview are defined in the active change `odt-viewer-preview` until it is archived.
