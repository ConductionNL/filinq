---
status: in-progress
---

# pdf-conversion Specification

**Status**: in-progress
**OpenSpec changes**:
- [multi-format-output](../../changes/multi-format-output/) _(active)_ — adds non-throwing capability introspection: `PdfConversionService::getCapabilities()` reports per-backend `{name, available, supports}` in cascade order, reusing the `ConversionFailedException` report shape (REQ-DDMFO-005) (kind: code)

## Purpose
Converts Nextcloud files to PDF/A-3b through a `PdfConversionService` that walks a cascade of conversion backends in order, falling through to the next on failure. It accepts any Nextcloud file, leaves the source unchanged, and on total failure throws a typed exception carrying a per-backend report of availability, supported types, and the reason each backend did not apply. This gives Filinq a single, archival-grade PDF conversion entry point usable by anonymisation, comparison, and summary flows.

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

- **GIVEN** `filinq.conversion.backends.libreoffice_enabled` is `false`
- **WHEN** the LibreOffice backend's `isAvailable()` is called
- **THEN** it returns false without attempting any runtime probe

#### Scenario: Backend reports unavailable when binary missing

- **GIVEN** the configured `filinq.conversion.libreoffice_binary_path` does not resolve to an executable
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

When the EML backend is the active path, mPDF MUST be configured with the appropriate PDF/A-3 mode (`SetPDFAVersion('3-B')` or equivalent) before any content is written. The resulting file MUST declare PDF/A-3b conformance in its metadata. (No original attachment bytes are embedded, so PDF/A-3 file-embedding conformance is not exercised by this change.)

#### Scenario: EML assembly output declares PDF/A-3b

- **WHEN** the assembly produces an output file
- **THEN** the file's PDF metadata indicates PDF/A-3b conformance (verified via `pdfinfo` or `verapdf`)
- **AND** the PDF contains no embedded original attachment files

### Requirement: Tenant configuration MUST control backend availability and order

The service MUST read tenant-level configuration to enable / disable backends and tune timeouts. The configuration keys MUST be:

| Key | Default | Purpose |
|---|---|---|
| `filinq.conversion.backends.office_app_enabled` | `true` | Office app backend on/off |
| `filinq.conversion.backends.libreoffice_enabled` | `true` | LibreOffice headless on/off |
| `filinq.conversion.backends.phpword_enabled` | `true` | PhpWord + mPDF on/off |
| `filinq.conversion.backends.mpdf_enabled` | `true` | mPDF direct on/off |
| `filinq.conversion.backends.eml_enabled` | `true` | OR-EML-extractor backend on/off |
| `filinq.conversion.libreoffice_binary_path` | `soffice` | Path to soffice binary |
| `filinq.conversion.timeout_seconds` | `60` | Per-backend timeout |

The service MUST honour these at request time (no restart needed for config changes).

#### Scenario: Disabled backend is skipped

- **GIVEN** `filinq.conversion.backends.office_app_enabled` is `false`
- **WHEN** a conversion runs with a DOCX input
- **THEN** the Office app backend is not consulted (no HTTP probe)
- **AND** the cascade starts at LibreOffice

#### Scenario: Backend timeout is enforced

- **GIVEN** `filinq.conversion.timeout_seconds` is `10`
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

### Requirement: The cascade exposes non-throwing capability introspection (REQ-DDMFO-005)

`PdfConversionService` MUST expose a public `getCapabilities(): array` that
returns, without performing or failing any conversion, one entry per
configured backend in cascade order with at least `name` (the backend's
`name()`), `available` (the backend's live `isAvailable()` result), and
`supports` (whether the backend takes HTML, the generation intermediate),
plus `inputs` (which of html/docx/odt it handles) — reusing the
exact report shape already defined for the `ConversionFailedException`
payload, so consumers and test fixtures share one structure. The method MUST
respect the tenant configuration for backend availability and order (a
disabled backend appears as unavailable with its reason, or per the same
visibility rules as the exception report), MUST NOT mutate any state, and
MUST NOT throw when a backend probe fails — a probe failure is reported as
`available: false` with a reason.

#### Scenario: Capability report matches the exception report shape

- GIVEN a configured cascade with at least one available and one unavailable backend
- WHEN `getCapabilities()` is called
- THEN it returns entries in cascade order with `name`, `available`, and `supports` per backend
- AND the entry structure equals the per-backend structure of a `ConversionFailedException` payload
- @e2e exclude pure backend introspection with no UI surface — covered by PHPUnit (tests/unit/Service/PdfConversionCapabilitiesTest.php::testGetCapabilitiesShape)

#### Scenario: A failing backend probe degrades to unavailable, not an exception

- GIVEN a backend whose availability probe throws
- WHEN `getCapabilities()` is called
- THEN the method returns normally
- AND that backend is reported `available: false` with a reason
- @e2e exclude fault-injection on a backend probe; covered by PHPUnit (tests/unit/Service/PdfConversionCapabilitiesTest.php::testProbeFailureDegrades)
