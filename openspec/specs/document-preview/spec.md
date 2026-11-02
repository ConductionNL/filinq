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

### Requirement: The anonymisation viewer MUST render an ODT preview

When the current file in the anonymisation file viewer is an `.odt` (MIME `application/vnd.oasis.opendocument.text`), the viewer MUST render a document preview rather than the unsupported state. The preview MUST be produced client-side by unzipping the `.odt` and transforming its `content.xml` to HTML (no backend round-trip), MUST render at least paragraphs, headings, tables, and lists, and MUST support the same entity-highlighting and text-selection behaviour as the Word (.docx) preview.

#### Scenario: An uploaded ODT is previewed

- **GIVEN** the viewer's current file is an `.odt`
- **WHEN** the viewer resolves the component for the file
- **THEN** the ODT viewer component is selected (not the unsupported state)

#### Scenario: Body text and tables are rendered

- **GIVEN** an ODT whose content.xml has a paragraph and a table cell
- **WHEN** its content.xml is transformed for preview
- **THEN** the paragraph renders as `<p>` and the table renders as `<table><tr><td>`

#### Scenario: Document text cannot inject markup

- **GIVEN** an ODT paragraph whose text is the literal string `<script>alert(1)</script>`
- **WHEN** its content.xml is transformed for preview
- **THEN** the output contains no executable `<script>` element
- **AND** the text is HTML-escaped

### Requirement: ODT placeholder text extraction MUST unzip the container

`extractDocumentText()` MUST extract an `.odt`'s visible text by unzipping the container and reading `content.xml` (and `styles.xml`), NOT by fetching the ZIP's transport bytes as text. This is what lets an anonymised `.odt` be scanned for `[<TYPE>: <id>]` placeholders.

#### Scenario: ODT text extraction returns readable content

- **GIVEN** an `.odt` whose content.xml contains the text `Jan Jansen` and `123456789`
- **WHEN** `extractDocumentText()` is called for the file
- **THEN** the returned text contains `Jan Jansen` and `123456789`
- **AND** it is not the raw ZIP transport bytes
