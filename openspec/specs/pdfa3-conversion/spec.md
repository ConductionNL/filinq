---
status: in-progress
---

# pdfa3-conversion Specification

**Status**: in-progress
**OpenSpec changes**:
- [verapdf-validation](../../changes/verapdf-validation/) _(active)_ — adds veraPDF output verification after conversion: `X-Docudesk-Pdfa3-Verified` header, persisted `conformanceReport` (`trigger: "conversion"`), report mode by default and a `filinq.pdfa3.strict_verify` fail-loud mode (REQ-DDVPV-006) (kind: code)

## Purpose
Converts HTML and existing PDFs into genuine PDF/A-3b archival documents via the vendored mPDF/FPDI stack: real XMP `pdfaid` identification, embedded fonts and ICC output intent for rendered content, caller-supplied embedded attachments plus an auto-generated MDTO metadata sidecar, and MDTO/archival metadata mapped into a `filinq:` XMP namespace. Conversion is guarded (size caps, wall-clock budget, output validation) and fails loud with typed `Pdfa3ConversionException` reasons rather than silently returning non-compliant bytes; the capability is reachable via `POST /api/pdfa3/convert` (IDOR-safe) and from Filinq's own PDF/A generation endpoint.
## Requirements
### Requirement: Converting HTML MUST produce a genuine PDF/A-3b document

`Pdfa3ConversionService::convertHtml()` MUST render the given HTML through
mPDF configured with `PDFA`, `PDFAauto`, and `PDFAversion = '3-B'`, and MUST
return output whose XMP packet identifies conformance level 3-B.

@e2e exclude PDF/A-3 XMP identification and metadata mapping — pure PHP
service logic exercising the real mPDF/FPDI libraries. Covered by PHPUnit
(Pdfa3ConversionServiceTest).

#### Scenario: Rendered HTML carries PDF/A-3b XMP identification

- **GIVEN** an HTML document body and no metadata
- **WHEN** `convertHtml()` is called
- **THEN** the returned content starts with the `%PDF-` header
- **AND** the XMP packet contains `<pdfaid:part>3</pdfaid:part>` and
  `<pdfaid:conformance>B</pdfaid:conformance>`
- **AND** the returned `conformance` field equals `"3-B"`

### Requirement: Converting an existing PDF MUST re-emit it as PDF/A-3b

`Pdfa3ConversionService::convertExistingPdf()` MUST import every page of the
source PDF (via mPDF's native `setSourceFile`/`importPage`/`useTemplate`)
into a PDF/A-3-configured document, preserving page count.

#### Scenario: An existing non-archival PDF is converted to PDF/A-3

- **GIVEN** an existing PDF produced without PDF/A compliance
- **WHEN** `convertExistingPdf()` is called with that source
- **THEN** the returned content carries the same PDF/A-3b XMP identification
  as the `convertHtml()` path
- **AND** the returned `pages` count matches the source page count

### Requirement: Callers MUST be able to embed file attachments

The service MUST support embedding one or more caller-supplied files via
mPDF's `SetAssociatedFiles()`, and MUST auto-generate an MDTO metadata XML
sidecar attachment when metadata is supplied and the caller did not already
provide an XML attachment.

#### Scenario: A caller-supplied attachment is embedded

- **GIVEN** an attachment `{name: "source-record.xml", mime: "text/xml",
  content: "...", AFRelationship: "Source"}`
- **WHEN** conversion runs
- **THEN** the output contains `/Type /Filespec` and `/Type /EmbeddedFile`
  objects
- **AND** the output contains the attachment's name and its
  `/AFRelationship /Source` marker

#### Scenario: MDTO metadata without an explicit attachment gets an auto-generated sidecar

- **GIVEN** metadata `{identifier: "ZAAK-2026-001"}` and no attachments
- **WHEN** conversion runs
- **THEN** the output contains an embedded `mdto-metadata.xml` attachment

### Requirement: MDTO/archival metadata MUST be mapped into the XMP packet

Standard fields (`title`, `author`, `creator`, `subject`, `keywords`) MUST
use mPDF's dedicated setters. Every other metadata key MUST be folded into a
`filinq:`-namespaced custom XMP RDF block via `SetAdditionalXmpRdf()`
rather than being dropped or silently merged into `/Keywords`.

#### Scenario: Archival fields appear in the custom XMP namespace

- **GIVEN** metadata `{title: "Beschikking", identifier: "ZAAK-1",
  caseReference: "BEK-42"}`
- **WHEN** conversion runs
- **THEN** the output's XMP contains `<filinq:identifier>ZAAK-1</filinq:identifier>`
  and `<filinq:caseReference>BEK-42</filinq:caseReference>`

### Requirement: Conversion MUST fail loud rather than pass through a non-compliant file

The service MUST never return bytes as a successful conversion result unless
those bytes carry the `%PDF-` header and the XMP
`pdfaid:part`/`pdfaid:conformance` markers. Any guardrail violation or
converter failure MUST raise a typed `Pdfa3ConversionException` carrying a
machine-readable `reason` and an admin-actionable hint — never a silent
fallback to a plain, non-PDF/A file.

#### Scenario: Output missing the PDF/A XMP markers is rejected, not returned

- **GIVEN** assembled bytes that carry a `%PDF-` header but no
  `pdfaid:part`/`pdfaid:conformance` markers
- **WHEN** the output-validation guard runs
- **THEN** it raises `Pdfa3ConversionException` with reason
  `output_validation_failed`
- **AND** no bytes are returned to the caller

#### Scenario: A disabled converter fails gracefully with an admin hint

- **GIVEN** `filinq.pdfa3.enabled` is set to `false`
- **WHEN** any conversion is attempted
- **THEN** it raises `Pdfa3ConversionException` with reason
  `converter_unavailable`, HTTP-style code 503, and a non-empty admin hint

### Requirement: Source and attachment size MUST be capped

The service MUST reject a source PDF larger than
`filinq.pdfa3.max_input_bytes` (default 50 MiB) before reading its content,
and MUST reject any single attachment larger than
`filinq.pdfa3.max_attachment_bytes` (default 20 MiB).

#### Scenario: An oversized source is rejected before its content is read

- **GIVEN** `filinq.pdfa3.max_input_bytes` set below the source file's size
- **WHEN** `convertExistingPdf()` is called
- **THEN** it raises `Pdfa3ConversionException` with reason
  `source_too_large` and HTTP-style code 413
- **AND** the source file's content is never read

### Requirement: Conversion MUST be bounded by a wall-clock time budget

The service MUST check elapsed time against `filinq.pdfa3.max_seconds`
(default 60) before rendering begins, before finalising output, and — for
`convertExistingPdf()` — between every imported page.

#### Scenario: An exceeded time budget aborts the conversion

- **GIVEN** the configured time budget has already elapsed by the first
  in-loop check
- **WHEN** conversion is running
- **THEN** it raises `Pdfa3ConversionException` with reason
  `time_limit_exceeded` and HTTP-style code 504

### Requirement: The conversion capability MUST be reachable via REST and wired to a real caller

`POST /api/pdfa3/convert` MUST resolve the source file through the
requesting user's folder (IDOR-safe — a file the user cannot read returns
404, not 403), and filinq's own PDF/A generation endpoint
(`PdfController::renderPdfA`) MUST delegate to this service when the request
carries `metadata` or `attachments`, so the capability has a real in-app
caller in addition to the standalone endpoint.

@e2e exclude Route wiring and IDOR-safe resolution — covered by PHPUnit
controller tests (Pdfa3ConversionControllerTest, PdfControllerTest) mirroring
the existing ValidationController pattern; no live-NC e2e harness exists yet
for this app's file-conversion endpoints.

#### Scenario: A file the requesting user cannot read returns 404, not disclosure

- **GIVEN** a `fileId` that does not resolve within the requesting user's
  folder
- **WHEN** `POST /api/pdfa3/convert` is called with that `fileId`
- **THEN** the response is 404 with a generic "File not found" body

#### Scenario: A successful conversion returns composition metadata as headers

- **GIVEN** a valid `fileId` the requesting user can read
- **WHEN** `POST /api/pdfa3/convert` succeeds
- **THEN** the response is a PDF download carrying
  `X-Docudesk-Pdfa3-Checksum-Sha256`, `X-Docudesk-Pdfa3-Pages`, and
  `X-Docudesk-Pdfa3-Conformance` headers

#### Scenario: renderPdfA delegates to the archival service when metadata or attachments are present

- **GIVEN** a `pdf#renderPdfA` request carrying non-empty `metadata` or
  `attachments`
- **WHEN** the request is handled
- **THEN** `Pdfa3ConversionService::convertHtml()` is invoked and its
  composition-metadata headers are present on the response

#### Scenario: renderPdfA stays on the plain PdfService path without archival params

- **GIVEN** a `pdf#renderPdfA` request with no `metadata` and no
  `attachments`
- **WHEN** the request is handled
- **THEN** `Pdfa3ConversionService::convertHtml()` is never invoked

