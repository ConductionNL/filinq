---
status: draft
---

# PDF Conversion — Delta for EML Rich-Rendering Assembly

This delta extends the existing `pdf-conversion` capability so the `EmlBackend` (introduced by `anonymise-output-as-pdf-by-default`) consumes OpenRegister's structured EML parse and assembles a rich PDF/A-3b combining headers, body (HTML when present, plain-text fallback), and attachments (always embedded as PDF/A-3 file attachments; renderable types additionally appended as pages with dividers).

## ADDED Requirements

### Requirement: The `EmlBackend` MUST use `TextExtractionService::parseEmlStructured` when available

The `EmlBackend.isAvailable()` MUST return true if and only if BOTH conditions hold:

1. OpenRegister's `TextExtractionService` exposes `parseEmlStructured()` (the paired `text-extraction-eml` change has been applied).
2. DocuDesk's `EmlPdfAssemblyService` is registered in the DI container (this change is applied).

When `convert()` runs, the backend MUST call `TextExtractionService::parseEmlStructured($file)` and pass the resulting `EmlStructure` to `EmlPdfAssemblyService::assemble()`. The backend MUST NOT fall back to the original placeholder "extract flat text + render plaintext" path when the structured method is available.

#### Scenario: Both changes applied — backend is available

- **GIVEN** an OR install with `text-extraction-eml` applied (parseEmlStructured exists)
- **AND** a DocuDesk install with this change applied (EmlPdfAssemblyService registered)
- **WHEN** `EmlBackend::isAvailable()` is called
- **THEN** it returns true

#### Scenario: OR change not yet applied — backend reports unavailable

- **GIVEN** an OR install where `text-extraction-eml` has not yet landed (parseEmlStructured does not exist)
- **WHEN** `EmlBackend::isAvailable()` is called
- **THEN** it returns false
- **AND** the conversion cascade proceeds to the next backend (or 422s if no backend handles EML)

### Requirement: The assembled PDF MUST include a header block with From / To / Cc / Subject / Date

The Twig envelope template (`lib/Resources/templates/eml/email_envelope.twig`) MUST render a header block at the top of the assembled PDF containing the EML's headers in fixed order: From, To (comma-joined recipient list), Cc (only when non-empty), Subject, Date (formatted as YYYY-MM-DD HH:MM in the tenant's timezone). Labels MUST be in Dutch in v1 (`Van:`, `Aan:`, `Cc:`, `Onderwerp:`, `Datum:`).

#### Scenario: Standard EML produces full header block

- **GIVEN** an EML with full standard headers
- **WHEN** the assembly runs
- **THEN** the resulting PDF's first page begins with the header block in the documented order
- **AND** each line uses the Dutch label prefix
- **AND** the date is formatted as `YYYY-MM-DD HH:MM`

#### Scenario: Cc header omitted from rendering when empty

- **GIVEN** an EML with no Cc recipients
- **WHEN** the assembly runs
- **THEN** the header block contains no `Cc:` line

#### Scenario: Malformed Date results in fallback rendering

- **GIVEN** an EML where the Date header is malformed (parses to null per OR's structured parse)
- **WHEN** the assembly runs
- **THEN** the `Datum:` line shows `(onbekend)` (or the tenant-localised "unknown" placeholder)

### Requirement: The body MUST be rendered preferring HTML, falling back to plain-text

The body section of the assembled PDF MUST use:

1. `EmlStructure.body.html` if non-null and non-empty — rendered directly (with `cid:` inline-image references resolved per the next requirement).
2. `EmlStructure.body.plainText` wrapped in a `<pre>` block if HTML is null/empty but plain-text is present.
3. A localised "(empty body — only attachments)" notice if both are null/empty.

#### Scenario: HTML body rendered when present

- **GIVEN** an EML with both `text/plain` and `text/html` body parts
- **WHEN** the assembly runs
- **THEN** the rendered body uses the HTML part
- **AND** layout / formatting from the HTML is preserved

#### Scenario: Plain-text fallback when only plain-text body exists

- **GIVEN** an EML with only `text/plain` body
- **WHEN** the assembly runs
- **THEN** the body section contains a `<pre>`-wrapped block with the plain-text content
- **AND** whitespace and linebreaks are preserved

#### Scenario: Empty-body EML shows a placeholder

- **GIVEN** an EML with both body parts null/empty
- **WHEN** the assembly runs
- **THEN** the body section contains the localised "(Bericht zonder body — alleen bijlagen)" notice (or equivalent)

### Requirement: Inline images referenced by `cid:` URLs MUST be resolved from the attachments list

When the rendered HTML body contains `<img src="cid:<contentId>">` (or similar), the assembly MUST resolve the reference by looking up `EmlStructure.attachments[].contentId` and substitute a `data:` URL with the matching attachment's bytes. If no matching attachment exists, the broken reference is left as-is and a debug log entry is emitted.

#### Scenario: Inline image is resolved and embedded as data URL

- **GIVEN** an EML's HTML body with `<img src="cid:logo@example">`
- **AND** an attachment with `contentId: "logo@example"` and `mimeType: "image/png"`
- **WHEN** the assembly runs
- **THEN** the rendered HTML's `src` is replaced with `data:image/png;base64,<encoded>`
- **AND** the rendered PDF page shows the image inline

#### Scenario: Broken cid reference renders mPDF placeholder

- **GIVEN** HTML body referencing `cid:missing@example` with no matching attachment
- **WHEN** the assembly runs
- **THEN** the rendered page shows mPDF's missing-image placeholder
- **AND** the broken cid reference is logged at debug level

### Requirement: Every attachment MUST be embedded as a PDF/A-3 file attachment

For each entry in `EmlStructure.attachments[]`, the assembly MUST embed the raw bytes as a PDF/A-3 file attachment in the resulting PDF. The embedded file MUST preserve the attachment's original filename and MIME type. Embedding MUST happen regardless of the `append_attachment_pages` setting.

#### Scenario: PDF attachment is embedded as a file inside the resulting PDF/A-3b

- **GIVEN** an EML with a PDF attachment named `bijlage.pdf`
- **WHEN** the assembly runs
- **THEN** the resulting PDF/A-3b contains an embedded file with name `bijlage.pdf` and MIME `application/pdf`
- **AND** extracting the embedded file via a PDF/A-3 viewer produces the byte-identical original PDF

#### Scenario: Non-renderable attachment is embedded only

- **GIVEN** an EML with a `.zip` attachment (non-renderable)
- **AND** `append_attachment_pages` is the default (true)
- **WHEN** the assembly runs
- **THEN** the resulting PDF contains the embedded `.zip` file
- **AND** no rendered page exists for the `.zip`
- **AND** a divider page references it: "Bijlage <N>: <filename> — niet weergegeven; zie ingebed bestand"

### Requirement: Renderable attachments MUST be appended as pages with dividers (when enabled)

When `docudesk.conversion.eml.append_attachment_pages` is true (default), the assembly MUST append rendered pages for each renderable attachment. Each set of attachment pages MUST be preceded by a divider page identifying the attachment by index, filename, MIME type, and size. The renderable set is: `application/pdf`, `image/png`, `image/jpeg`, `image/gif`, `image/webp`, plain-text MIMEs, `message/rfc822` (recursive nested EML), and the Word MIMEs supported by Change A's PhpWord backend (DOCX/ODT/RTF/HTML).

Attachments larger than `docudesk.conversion.eml.max_attachment_render_size_bytes` (default 26214400 = 25 MB) MUST be embedded but NOT rendered as pages; the divider page indicates "te groot om weer te geven".

#### Scenario: PDF attachment renders as appended pages

- **GIVEN** an EML with a 5-page PDF attachment named `bijlage-1.pdf`
- **AND** `append_attachment_pages: true`
- **WHEN** the assembly runs
- **THEN** the resulting PDF contains a divider page identifying "Bijlage 1: bijlage-1.pdf"
- **AND** the 5 pages of the source PDF follow the divider
- **AND** the PDF/A-3b file embedding for the attachment is also present

#### Scenario: Image attachment renders as a single page

- **GIVEN** an EML with an image attachment
- **WHEN** the assembly runs (default config)
- **THEN** a divider page identifies the image
- **AND** a single page following the divider shows the image (sized to fit the page)

#### Scenario: Oversized attachment is embedded but not rendered

- **GIVEN** an attachment larger than the configured size limit (e.g. 30 MB PDF with 25 MB limit)
- **WHEN** the assembly runs
- **THEN** the divider page shows "Bijlage <N>: <filename> — te groot om weer te geven; zie ingebed bestand"
- **AND** no further pages from the attachment are rendered
- **AND** the PDF/A-3b embedded file is still present

#### Scenario: append_attachment_pages: false — attachments embedded only

- **GIVEN** the tenant config sets `append_attachment_pages: false`
- **WHEN** the assembly runs against an EML with renderable attachments
- **THEN** no divider pages or rendered attachment pages appear
- **AND** all attachments are still embedded as PDF/A-3 files
- **AND** the resulting PDF contains only the email envelope page(s)

### Requirement: Nested EML attachments MUST be recursively assembled within the depth budget

When an attachment has `mimeType: "message/rfc822"` and a non-null `nestedEml` (per OR's depth-3 cap), the assembly MUST recursively render its envelope + its own attachments using the same template and rules. The nested rendering produces its own divider, header block, body, and (recursively) attachments. Beyond the depth-3 limit, the nested EML is embedded but rendered as a "(genest e-mail, niet weergegeven — diepte-limiet)" notice.

#### Scenario: Depth-2 nested EML is recursively assembled

- **GIVEN** an EML containing a nested EML attachment (depth-1 nesting)
- **WHEN** the assembly runs
- **THEN** the outer envelope renders normally
- **AND** a divider page identifies the nested EML
- **AND** the nested EML's own envelope + body + attachments are rendered as appended pages

#### Scenario: Depth-4 nested EML is embedded only

- **GIVEN** an EML chain at depth 4 (where OR's depth-3 cap applied during parse, leaving the depth-3-level EML's attachment with `nestedEml: null`)
- **WHEN** the assembly runs at the depth-3 level
- **THEN** the depth-4 EML is embedded as a `.eml` PDF/A-3 file attachment
- **AND** the divider page shows "Bijlage <N>: <filename> (genest e-mail, niet weergegeven — diepte-limiet)"
- **AND** no recursive rendering attempt is made beyond the limit

### Requirement: Assembly failure modes MUST degrade gracefully

The assembly MUST NOT abandon the entire conversion on partial failures. Per the design's error-handling table:

- OR `parseEmlStructured` throws → fall back to `extractEml` flat text; render as a single plain-text page; no attachments visible. Log the parse failure.
- Twig template render throws → render minimal envelope with available headers + a notice "(template rendering failed)".
- Renderable attachment fails to render → skip the page; embed bytes only; divider with "kon niet worden weergegeven".
- File embedding fails → log; embed as much as possible; footer notice listing failed embeds.
- All steps fail catastrophically → throw `ConversionFailedException` per Change A's contract; cascade falls through (which 422s for EML, since no other backend handles EML in the cascade).

#### Scenario: parseEmlStructured throws — flat-text fallback renders

- **GIVEN** an EML where `parseEmlStructured` throws `EmlParseException` but `extractEml` returns flat text
- **WHEN** the assembly runs
- **THEN** the resulting PDF is a single page containing the flat text
- **AND** an error is logged identifying the structured-parse failure
- **AND** no attachments are visible (no structure available)

#### Scenario: One attachment fails to render — others still render

- **GIVEN** an EML with three renderable attachments where one fails (e.g. corrupted PDF)
- **WHEN** the assembly runs
- **THEN** two attachments produce dividers + rendered pages
- **AND** the failing attachment produces a divider + "kon niet worden weergegeven" notice
- **AND** all three attachments are still embedded as PDF/A-3 files
- **AND** the conversion succeeds (returns the assembled PDF)

## MODIFIED Requirements

### Requirement: Output MUST be PDF/A-3b — backends that cannot guarantee this MUST fall through

A backend that can produce only plain PDF (not PDF/A-3b) MUST report `isAvailable()` as false for the conversion or throw from `convert()`, allowing the cascade to try the next backend. The service MUST NOT silently degrade to plain PDF.

When the EML backend is the active path, mPDF MUST be configured with the appropriate PDF/A-3 mode (`SetPDFAVersion('3-B')` or equivalent) before any content or embedded files are written. The resulting file MUST declare PDF/A-3b conformance in its metadata.

#### Scenario: EML assembly output declares PDF/A-3b

- **WHEN** the assembly produces an output file
- **THEN** the file's PDF metadata indicates PDF/A-3b conformance (verified via `pdfinfo` or `verapdf`)
- **AND** the embedded files are accessible via PDF/A-3-aware tools
