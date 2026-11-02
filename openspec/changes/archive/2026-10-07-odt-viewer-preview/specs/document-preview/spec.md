## ADDED Requirements

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
