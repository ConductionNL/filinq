---
status: draft
---

# PDF Conversion — Delta for EML Redacted-Component Assembly

This delta extends the existing `pdf-conversion` capability so the `EmlBackend` (introduced as a stub by `anonymise-output-as-pdf-by-default`) consumes OpenRegister's **anonymise-EML API** (an `AnonymisedEmlStructure` from the paired `anonymise-eml-structured` change) and assembles a PDF/A-3b purely from already-redacted components: a redacted header block, the redacted body (HTML when present, plain-text fallback), and the redacted attachments (renderable types rendered as appended pages with dividers; un-anonymisable types dropped to a placeholder page). DocuDesk performs no redaction itself, embeds no original bytes, and always produces a PDF for EML inputs.

## ADDED Requirements

### Requirement: The `EmlBackend` MUST consume OpenRegister's anonymise-EML API and assemble redacted components

The `EmlBackend.isAvailable()` MUST return true if and only if BOTH conditions hold:

1. OpenRegister exposes its anonymise-EML API (the paired `anonymise-eml-structured` change has been applied), returning an `AnonymisedEmlStructure`.
2. DocuDesk's `EmlPdfAssemblyService` is registered in the DI container (this change is applied).

When `convert()` runs, the backend MUST call OpenRegister's anonymise-EML API for the source file and pass the resulting **redacted** result to `EmlPdfAssemblyService::assemble()`. The backend MUST NOT call the raw `parseEmlStructured()`, MUST NOT redact anything itself, and MUST NOT fall back to a raw/unredacted parse on failure.

#### Scenario: Both changes applied — backend is available

- **GIVEN** an OR install with `anonymise-eml-structured` applied (the anonymise-EML API exists)
- **AND** a DocuDesk install with this change applied (EmlPdfAssemblyService registered)
- **WHEN** `EmlBackend::isAvailable()` is called
- **THEN** it returns true

#### Scenario: OR anonymise-EML API not yet applied — backend reports unavailable

- **GIVEN** an OR install where `anonymise-eml-structured` has not yet landed (the anonymise-EML API does not exist)
- **WHEN** `EmlBackend::isAvailable()` is called
- **THEN** it returns false
- **AND** the conversion cascade proceeds to the next backend (or 422s if no backend handles EML)

#### Scenario: convert consumes the redacted result, not the raw parse

- **GIVEN** an EML input and an available `EmlBackend`
- **WHEN** `convert()` runs
- **THEN** it calls OpenRegister's anonymise-EML API (not `parseEmlStructured`)
- **AND** passes the redacted result to `EmlPdfAssemblyService::assemble()`
- **AND** performs no redaction of headers, body, or attachments itself

### Requirement: The assembled PDF MUST render the redacted header block (From / Reply-To / To / Cc / Subject / Date)

The Twig envelope template (`lib/Resources/templates/eml/email_envelope.twig`) MUST render a header block at the top of the assembled PDF containing the **redacted** headers from OR's result in fixed order: From, Reply-To (only when non-empty), To (comma-joined), Cc (only when non-empty), Subject, Date (formatted `YYYY-MM-DD HH:MM` in the tenant timezone). Labels MUST be Dutch in v1 (`Van:`, `Antwoord aan:`, `Aan:`, `Cc:`, `Onderwerp:`, `Datum:`). The header values rendered are exactly what OR redacted — DocuDesk does not alter them.

#### Scenario: Standard EML produces full redacted header block

- **GIVEN** OR's anonymise-EML result with redacted From/Reply-To/To/Subject/Date
- **WHEN** the assembly runs
- **THEN** the resulting PDF's first page begins with the header block in the documented order
- **AND** each line uses the Dutch label prefix
- **AND** the rendered values are OR's redacted values (no DocuDesk-side redaction)
- **AND** the date is formatted as `YYYY-MM-DD HH:MM`

#### Scenario: Reply-To and Cc omitted from rendering when empty

- **GIVEN** OR's result has no Reply-To and no Cc recipients
- **WHEN** the assembly runs
- **THEN** the header block contains no `Antwoord aan:` line and no `Cc:` line

### Requirement: The body MUST be rendered from OR's redacted body, preferring HTML

The body section MUST use OR's **redacted** body:

1. redacted `html` if non-null/non-empty — rendered directly (with `cid:` inline images resolved per the next requirement).
2. redacted `plain` wrapped in a `<pre>` block if `html` is null/empty but `plain` is present.
3. A localised "(empty body — only attachments)" notice if both are null/empty.

#### Scenario: Redacted HTML body rendered when present

- **GIVEN** OR's result with both redacted `plain` and redacted `html`
- **WHEN** the assembly runs
- **THEN** the rendered body uses the redacted HTML
- **AND** layout / formatting from the HTML is preserved

#### Scenario: Redacted plain-text fallback when only plain exists

- **GIVEN** OR's result with only redacted `plain` (html null)
- **WHEN** the assembly runs
- **THEN** the body section contains a `<pre>`-wrapped block with the redacted plain text
- **AND** whitespace and linebreaks are preserved

#### Scenario: Empty-body result shows a placeholder

- **GIVEN** OR's result with both body parts null/empty
- **WHEN** the assembly runs
- **THEN** the body section contains the localised "(Bericht zonder body — alleen bijlagen)" notice

### Requirement: Inline images MUST be resolved from OR's redacted inline-image map

When the redacted HTML body contains `<img src="cid:<contentId>">`, the assembly MUST resolve the reference against OR's inline-image map (`contentId → redacted bytes`) and substitute a `data:` URL with the matching **redacted** bytes. If no matching entry exists, the broken reference is left as-is and a debug log entry is emitted. DocuDesk substitutes only OR's redacted bytes — never original inline-image content.

#### Scenario: Inline image resolved from the redacted map

- **GIVEN** redacted HTML with `<img src="cid:logo@example">`
- **AND** OR's inline-image map has `logo@example → <redacted png bytes>` with mimeType `image/png`
- **WHEN** the assembly runs
- **THEN** the rendered HTML's `src` is replaced with `data:image/png;base64,<redacted-encoded>`
- **AND** the rendered PDF page shows the redacted image inline

#### Scenario: Broken cid reference renders mPDF placeholder

- **GIVEN** redacted HTML referencing `cid:missing@example` with no map entry
- **WHEN** the assembly runs
- **THEN** the rendered page shows mPDF's missing-image placeholder
- **AND** the broken cid reference is logged at debug level

### Requirement: Redactable attachments MUST be rendered as pages from their redacted bytes; originals MUST NOT be embedded

For each `attachments[]` entry that carries `redactedContent`, has a renderable MIME, and is within the size cap, the assembly MUST (when `docudesk.conversion.eml.append_attachment_pages` is true, the default) append rendered pages produced from the **redacted bytes** via the existing cascade backends, preceded by a divider page identifying the attachment by index, filename, MIME, and size. The renderable set is `application/pdf`, `image/png`, `image/jpeg`, `image/gif`, `image/webp`, plain-text MIMEs, `message/rfc822` (recursive), and the Word MIMEs supported by Change A's PhpWord backend.

The assembly MUST NOT embed any attachment's original bytes as a PDF/A-3 file attachment. (This change drops the prior draft's verbatim embedding because original bytes are un-redacted and would leak PII.)

A `redactedContent` attachment whose MIME is NOT renderable MUST get a placeholder/divider page ("geredigeerd maar niet weer te geven") and no embedded bytes. An attachment larger than `docudesk.conversion.eml.max_attachment_render_size_bytes` (default 26214400 = 25 MB) MUST get a placeholder page ("te groot om weer te geven") and no embedded bytes.

#### Scenario: Redacted PDF attachment renders as appended pages, no verbatim embedding

- **GIVEN** OR's result with a renderable PDF attachment `bijlage-1.pdf` carrying `redactedContent`
- **AND** `append_attachment_pages: true`
- **WHEN** the assembly runs
- **THEN** a divider page identifies "Bijlage 1: bijlage-1.pdf"
- **AND** the redacted PDF's pages follow the divider
- **AND** the resulting PDF/A-3b contains NO embedded file attachment for the original bytes

#### Scenario: Redacted image attachment renders as a single page

- **GIVEN** OR's result with a renderable image attachment carrying `redactedContent`
- **WHEN** the assembly runs (default config)
- **THEN** a divider page identifies the image
- **AND** a single page following the divider shows the redacted image (sized to fit)

#### Scenario: Redacted but non-renderable attachment gets a placeholder

- **GIVEN** OR's result with a `.xlsx` attachment carrying `redactedContent` (non-renderable MIME)
- **WHEN** the assembly runs
- **THEN** a divider/placeholder page references it ("Bijlage <N>: <filename> — geredigeerd maar niet weer te geven")
- **AND** no rendered pages and no embedded bytes exist for the attachment

#### Scenario: Oversized redacted attachment gets a placeholder

- **GIVEN** a redacted attachment larger than the configured size limit
- **WHEN** the assembly runs
- **THEN** the placeholder page shows "Bijlage <N>: <filename> — te groot om weer te geven"
- **AND** no rendered pages and no embedded bytes exist for the attachment

#### Scenario: append_attachment_pages false — only the redacted envelope renders

- **GIVEN** the tenant config sets `append_attachment_pages: false`
- **WHEN** the assembly runs against a result with renderable attachments
- **THEN** no divider pages or rendered attachment pages appear for renderable attachments
- **AND** the resulting PDF contains only the redacted email envelope page(s)
- **AND** no original or redacted attachment bytes are embedded

### Requirement: Un-anonymisable attachments MUST be dropped to a placeholder page

For each `attachments[]` entry OR marks `unsupported: true` (no anonymiser available for that format), the assembly MUST append a placeholder/divider page and MUST NOT embed or render the attachment's bytes in any form. The placeholder MUST name the attachment and state the omission reason.

#### Scenario: Unsupported attachment produces placeholder, no content

- **GIVEN** OR's result with an attachment entry `{filename: "verslag.bin", mimeType: "application/octet-stream", unsupported: true}`
- **WHEN** the assembly runs
- **THEN** a placeholder page reads "Bijlage <N>: verslag.bin (application/octet-stream) weggelaten — geen anonimiseerder beschikbaar"
- **AND** the attachment's bytes are neither embedded nor rendered anywhere in the PDF

### Requirement: Nested EML attachments MUST be recursively assembled from OR's redacted nested result within the depth budget

When an attachment is `message/rfc822` and OR's result carries a non-null **redacted** nested `AnonymisedEmlStructure` (within OR's depth-3 cap), the assembly MUST recursively render its redacted envelope + its own redacted attachments using the same template and rules. Beyond depth 3, OR returns the nested EML as an `unsupported`/placeholder entry and the assembly renders the placeholder page only (no bytes).

#### Scenario: Depth-1 nested EML is recursively assembled from redacted result

- **GIVEN** OR's result with a nested EML attachment carrying a redacted nested result
- **WHEN** the assembly runs
- **THEN** the outer redacted envelope renders normally
- **AND** a divider page identifies the nested EML
- **AND** the nested EML's redacted envelope + body + attachments render as appended pages

#### Scenario: Beyond-depth nested EML gets a placeholder only

- **GIVEN** OR returned the depth-3-level nested EML attachment as `unsupported` (depth cap reached)
- **WHEN** the assembly runs at that level
- **THEN** a placeholder page identifies the nested EML as omitted at the depth limit
- **AND** no recursive rendering and no byte embedding occurs

### Requirement: EML inputs MUST always produce a PDF; `outputFormat: "preserve"` MUST be overridden to PDF for EML

Because OR redacts EML components rather than producing a reliably-redacted native `.eml`, EML inputs MUST always resolve to a PDF output. When an anonymise request targets an EML input with `outputFormat: "preserve"`, the format MUST be silently overridden to the PDF cascade — the request MUST NOT be rejected and MUST NOT return an error; the caller receives the assembled PDF. For `outputFormat: "pdf-only"`, `"pdf"`, and the overridden `"preserve"`, the EML cascade runs and produces the assembled PDF; since there is no native EML intermediate, all three behave identically for EML.

#### Scenario: preserve for an EML input is overridden to PDF

- **GIVEN** an anonymise request for an EML input with `outputFormat: "preserve"`
- **WHEN** the request is processed
- **THEN** the format is overridden to the PDF cascade and the assembled PDF/A-3b is returned
- **AND** no error is returned
- **AND** no native EML output is written

#### Scenario: pdf-only and pdf both produce the assembled PDF for EML

- **GIVEN** an EML input anonymised with `outputFormat: "pdf-only"` (or `"pdf"`)
- **WHEN** the EML backend runs
- **THEN** the result is the assembled PDF/A-3b
- **AND** there is no native EML intermediate to keep or delete

### Requirement: Assembly failure modes MUST degrade gracefully and MUST NEVER emit un-redacted content

The assembly MUST NOT abandon the entire conversion on partial failures, and MUST NOT fall back to any path that emits un-redacted content:

- OR's anonymise-EML API throws → throw `ConversionFailedException` (cascade falls through → 422 for EML). The assembly MUST NOT fall back to a raw/unredacted parse.
- Twig render throws → render a minimal envelope with the redacted headers + "(template rendering failed)" notice.
- A renderable redacted attachment fails to render → skip the page; divider says "kon niet worden weergegeven"; no bytes embedded.
- All steps fail catastrophically → throw `ConversionFailedException` per Change A's contract.

#### Scenario: OR anonymise-EML failure does NOT leak a raw parse

- **GIVEN** an EML where OR's anonymise-EML API throws
- **WHEN** the assembly runs
- **THEN** a `ConversionFailedException` is raised and the cascade falls through (422 for EML)
- **AND** no raw/unredacted email content is rendered or written

#### Scenario: One redacted attachment fails to render — others still render

- **GIVEN** OR's result with three renderable redacted attachments where one fails to render (e.g. corrupted redacted PDF)
- **WHEN** the assembly runs
- **THEN** two attachments produce dividers + rendered pages
- **AND** the failing attachment produces a divider + "kon niet worden weergegeven" notice
- **AND** no attachment bytes are embedded
- **AND** the conversion succeeds (returns the assembled PDF)

## MODIFIED Requirements

### Requirement: Output MUST be PDF/A-3b — backends that cannot guarantee this MUST fall through

A backend that can produce only plain PDF (not PDF/A-3b) MUST report `isAvailable()` as false for the conversion or throw from `convert()`, allowing the cascade to try the next backend. The service MUST NOT silently degrade to plain PDF.

When the EML backend is the active path, mPDF MUST be configured with the appropriate PDF/A-3 mode (`SetPDFAVersion('3-B')` or equivalent) before any content is written. The resulting file MUST declare PDF/A-3b conformance in its metadata. (No original attachment bytes are embedded, so PDF/A-3 file-embedding conformance is not exercised by this change.)

#### Scenario: EML assembly output declares PDF/A-3b

- **WHEN** the assembly produces an output file
- **THEN** the file's PDF metadata indicates PDF/A-3b conformance (verified via `pdfinfo` or `verapdf`)
- **AND** the PDF contains no embedded original attachment files