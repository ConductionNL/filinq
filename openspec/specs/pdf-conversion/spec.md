---
status: done
---

# pdf-conversion Specification

## Purpose
Converts Nextcloud files to PDF/A-3b through a `PdfConversionService` that walks a cascade of conversion backends in order, falling through to the next on failure. It accepts any Nextcloud file, leaves the source unchanged, and on total failure throws a typed exception carrying a per-backend report of availability, supported types, and the reason each backend did not apply. This gives DocuDesk a single, archival-grade PDF conversion entry point usable by anonymisation, comparison, and summary flows.
## Requirements
### Requirement: A `PdfConversionService` MUST exist that converts files to PDF/A-3b

The service MUST expose a single public method:

```php
public function convertToPdf(File $source, array $opts = []): File;
```

It MUST accept any Nextcloud `File` and return a converted PDF `File`. The output MUST be PDF/A-3b. On failure, the service MUST throw a typed `ConversionFailedException` whose payload includes a per-backend list of `{name, available, supports, reason}` so consumers can render structured error responses.

#### Scenario: Service converts a DOCX file to PDF/A-3b

- **GIVEN** a Nextcloud `File` containing a valid DOCX document
- **AND** at least one capable backend is available
- **WHEN** `convertToPdf($file)` is called
- **THEN** the returned `File` is a PDF
- **AND** the PDF declares PDF/A-3b conformance in its metadata
- **AND** the source file is unchanged

#### Scenario: Service throws on unsupported input

- **GIVEN** an input file whose MIME type no available backend can handle (e.g. an XLSX with no Office app and no LibreOffice installed)
- **WHEN** `convertToPdf($file)` is called
- **THEN** the service throws `ConversionFailedException`
- **AND** the exception's payload includes one entry per backend tried with the reason it didn't apply
- **AND** no output file is created

#### Scenario: Service throws on backend runtime error

- **GIVEN** a backend that `canHandle()` the input and `isAvailable()` returns true
- **WHEN** `convert()` throws (e.g. mPDF runs out of memory, soffice exits non-zero)
- **THEN** the cascade tries the next backend
- **AND** if all subsequent backends also fail or don't apply, `ConversionFailedException` is thrown with all attempted backends listed

### Requirement: The service MUST walk backends in cascade order

The default cascade order MUST be: (1) Office app (Collabora / OnlyOffice), (2) LibreOffice headless, (3) PhpWord + mPDF, (4) mPDF directly, (5) OR-EML-extractor + mPDF. The first backend that `isAvailable()` AND `canHandle(mime, ext)` AND `convert()` succeeds wins. The service MUST short-circuit on first success.

#### Scenario: Office app wins for DOCX when configured

- **GIVEN** Collabora is installed and configured
- **AND** a DOCX input
- **WHEN** the cascade runs
- **THEN** the Office app backend converts and returns; LibreOffice / PhpWord / mPDF backends are not invoked

#### Scenario: PhpWord wins for ODT on bare install

- **GIVEN** no Office app and no LibreOffice are available
- **AND** an ODT input
- **WHEN** the cascade runs
- **THEN** the PhpWord+mPDF backend handles the conversion

#### Scenario: mPDF handles HTML directly

- **GIVEN** an HTML input on a bare install (no Office app, no LibreOffice)
- **AND** PhpWord+mPDF is enabled (it can also handle HTML)
- **WHEN** the cascade runs
- **THEN** EITHER PhpWord+mPDF OR the direct mPDF backend handles the conversion (deterministic per cascade order — PhpWord+mPDF tried first since it appears earlier in the cascade for HTML)
- **AND** subsequent backends are not invoked

### Requirement: Each backend MUST implement `ConversionBackendInterface`

Backends MUST implement:

```php
interface ConversionBackendInterface {
    public function name(): string;
    public function isAvailable(): bool;
    public function canHandle(string $mimeType, string $extension): bool;
    public function convert(File $source): File;
}
```

`name()` returns a stable identifier (e.g. `"libreoffice_headless"`) used in logs and 422 response bodies. `isAvailable()` is a runtime + tenant-config check. `canHandle()` declares which inputs the backend supports. `convert()` performs the conversion or throws.

#### Scenario: Backend reports unavailable when disabled by tenant config

- **GIVEN** `docudesk.conversion.backends.libreoffice_enabled` is `false`
- **WHEN** the LibreOffice backend's `isAvailable()` is called
- **THEN** it returns false without attempting any runtime probe

#### Scenario: Backend reports unavailable when binary missing

- **GIVEN** the configured `docudesk.conversion.libreoffice_binary_path` does not resolve to an executable
- **WHEN** the LibreOffice backend's `isAvailable()` is called
- **THEN** it returns false
- **AND** the cascade proceeds to the next backend

#### Scenario: Backend cannot handle unsupported MIME

- **GIVEN** the PhpWord backend
- **AND** an XLSX input
- **WHEN** `canHandle('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx')` is called
- **THEN** it returns false

### Requirement: Output MUST be PDF/A-3b — backends that cannot guarantee this MUST fall through

A backend that can produce only plain PDF (not PDF/A-3b) MUST report `isAvailable()` as false for the conversion or throw from `convert()`, allowing the cascade to try the next backend. The service MUST NOT silently degrade to plain PDF.

When the EML backend is the active path, mPDF MUST be configured with the appropriate PDF/A-3 mode (`SetPDFAVersion('3-B')` or equivalent) before any content or embedded files are written. The resulting file MUST declare PDF/A-3b conformance in its metadata.

#### Scenario: EML assembly output declares PDF/A-3b

- **WHEN** the assembly produces an output file
- **THEN** the file's PDF metadata indicates PDF/A-3b conformance (verified via `pdfinfo` or `verapdf`)
- **AND** the embedded files are accessible via PDF/A-3-aware tools

### Requirement: Tenant configuration MUST control backend availability and order

The service MUST read tenant-level configuration to enable / disable backends and tune timeouts. The configuration keys MUST be:

| Key | Default | Purpose |
|---|---|---|
| `docudesk.conversion.backends.office_app_enabled` | `true` | Office app backend on/off |
| `docudesk.conversion.backends.libreoffice_enabled` | `true` | LibreOffice headless on/off |
| `docudesk.conversion.backends.phpword_enabled` | `true` | PhpWord + mPDF on/off |
| `docudesk.conversion.backends.mpdf_enabled` | `true` | mPDF direct on/off |
| `docudesk.conversion.backends.eml_enabled` | `true` | OR-EML-extractor backend on/off |
| `docudesk.conversion.libreoffice_binary_path` | `soffice` | Path to soffice binary |
| `docudesk.conversion.timeout_seconds` | `60` | Per-backend timeout |

The service MUST honour these at request time (no restart needed for config changes).

#### Scenario: Disabled backend is skipped

- **GIVEN** `docudesk.conversion.backends.office_app_enabled` is `false`
- **WHEN** a conversion runs with a DOCX input
- **THEN** the Office app backend is not consulted (no HTTP probe)
- **AND** the cascade starts at LibreOffice

#### Scenario: Backend timeout is enforced

- **GIVEN** `docudesk.conversion.timeout_seconds` is `10`
- **AND** a backend's `convert()` runs longer than 10 seconds
- **WHEN** the cascade is processing the input
- **THEN** the conversion attempt is terminated
- **AND** the backend is treated as failed
- **AND** the cascade proceeds to the next backend

### Requirement: LibreOffice headless invocations MUST be serialised

LibreOffice's `--headless` mode has known issues with concurrent invocations (user-profile lock contention). The LibreOffice backend MUST acquire a Nextcloud locking primitive (e.g. `ILockingProvider` keyed by `soffice:headless:convert`) before invoking `soffice`, and release it after. Failure to acquire the lock within a reasonable timeout MUST be treated as a backend failure (cascade proceeds to the next backend).

#### Scenario: Concurrent calls serialise

- **GIVEN** two simultaneous conversions both reach the LibreOffice backend
- **WHEN** both attempt to call `soffice` at the same time
- **THEN** one of them holds the lock and runs first
- **AND** the second waits up to the configured timeout
- **AND** within the lock, only one `soffice` process runs at a time

### Requirement: PhpWord backend MUST support the formats PhpWord can read

The PhpWord backend's `canHandle()` MUST return true for `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX), `application/vnd.oasis.opendocument.text` (ODT), `application/rtf` / `text/rtf` (RTF), and `text/html` (HTML). It MUST return false for everything else, including legacy DOC (`application/msword`) — PhpWord's `MsDoc` reader is too unreliable for production use.

#### Scenario: PhpWord supports DOCX, ODT, RTF, HTML

- **WHEN** `canHandle()` is called for each of those MIME types
- **THEN** it returns true

#### Scenario: PhpWord rejects legacy DOC

- **WHEN** `canHandle('application/msword', 'doc')` is called
- **THEN** it returns false (cascade continues to next backend, or 422 if none)

### Requirement: EML backend MUST depend on OpenRegister's text-extraction service

The EML backend's `isAvailable()` MUST check whether OpenRegister exposes EML extraction (via the `OCA\OpenRegister\Service\TextExtractionService::extractFile()` path or equivalent for `message/rfc822`). Until that capability lands in OpenRegister, the EML backend reports unavailable and EML inputs fall through to 422 in the default `pdf` mode.

#### Scenario: EML backend unavailable until OR change lands

- **GIVEN** OpenRegister's TextExtractionService does not yet support EML
- **WHEN** the EML backend's `isAvailable()` is called
- **THEN** it returns false

#### Scenario: EML backend converts via extracted text once OR supports EML

- **GIVEN** OpenRegister's TextExtractionService supports EML
- **AND** an EML input file
- **WHEN** the EML backend's `convert()` is called
- **THEN** the service extracts the EML body via OR
- **AND** wraps the extracted text in a minimal HTML wrapper
- **AND** renders to PDF/A-3b via mPDF
- **AND** returns the PDF file

### Requirement: `ConversionFailedException` MUST carry structured error data

When the cascade fails, the thrown exception MUST expose a method (e.g. `getAttempts(): array`) returning a list of `{name, available, supports, reason}` per backend. Consumers (e.g. the anonymise endpoint) use this to construct structured 422 response bodies.

#### Scenario: Exception payload lists every attempted backend

- **GIVEN** a conversion that fails because no backend handles the input
- **WHEN** `ConversionFailedException` is caught
- **THEN** `getAttempts()` returns an entry for each backend in cascade order
- **AND** each entry includes the backend name, availability, supports flag, and a human-readable reason

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

