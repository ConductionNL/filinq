---
kind: code
---

# Proposal: multi-format-output

## Why

A Dutch municipal document rarely lives in one format: the citizen gets a
PDF, the collaborating buurgemeente or ODR asks for an **editable DOCX**, and
the zaaksysteem archives HTML or PDF. Today DocuDesk forces one format per
render (verified at HEAD):

- `DocumentService::generateDocument()` — `VALID_FORMATS = ['pdf', 'odf',
  'html']`, exactly one `options.format` per request; no DOCX at all on the
  main generation path.
- `CorrespondenceService` — `VALID_FORMATS = ['pdf', 'docx', 'html',
  'email']`, still one per request, DOCX via a private LibreOffice
  `--convert-to docx` call the document path cannot reach.
- The correspondence UI hardcodes its format list client-side
  (`CorrespondenceIndex.vue`), so whether `soffice` is even installed is
  discovered by failing, not by asking.

Competitors treat simultaneous output as table stakes: **Docmosis** sells
"multi-format single render — simultaneous PDF + DOCX output from one render
call"; **Carbone** renders "JSON to PDF/DOCX/XLSX/PPTX/ODT/ODS/HTML"; Fluent
likewise (spectr `competitor_features`, competitor theme #5). Municipalities
that exchange concept-besluiten inter-municipally still do so as editable
DOCX. Getting PDF + DOCX from one request also guarantees the two artifacts
came from the *same* render of the *same* data — generating twice does not.

Priority **could-have**.

## What Changes

- **Multi-format render requests**: `POST /api/documents/generate` accepts
  `options.formats` (array). The template is rendered/filled **once**; each
  requested format is produced from that single intermediate (HTML for Twig
  templates, filled DOCX for office templates), stored as Nextcloud files in
  the generated-documents output folder, and returned as a JSON manifest with
  per-format file references and download URLs. Single-`format` requests keep
  today's binary `DataDownloadResponse` byte-for-byte (back-compat).
- **`docx` as a first-class document-generation format**: added to the
  document path's valid formats — via LibreOffice headless HTML→DOCX for Twig
  templates (same mechanism `CorrespondenceService` already uses, promoted
  out of it) and as the filled source DOCX (true editable passthrough) for
  office templates (Wave-1 REQ-DDOTA-003). This is the editable-DOCX delivery
  for inter-municipal exchange.
- **Office-template HTML output (full format parity)**: office (DOCX/ODT)
  templates can now also produce `html`, via LibreOffice headless DOCX→HTML
  (new `DocxToHtmlConverter`, `soffice --convert-to html`, sharing the
  cascade's soffice serialization lock and temp-dir hygiene). Verified at
  HEAD: the conversion cascade (`lib/Service/Conversion/`) hard-codes a PDF
  target (`LibreOfficeHeadlessBackend` → `--convert-to pdf:writer_pdf_Export…`)
  and offers **no** DOCX→HTML path today — office templates previously had no
  HTML output at all. With this converter every output format
  (`pdf`/`odf`/`docx`/`html`) is reachable for both `twig` and `office`
  templates, subject to live backend availability.
- **Format-capability matrix**: `GET /api/documents/formats` (instance-level)
  and `GET /api/templates/{id}/formats` (per template) report which output
  formats are currently producible and why not (e.g. `odf`/`docx` unavailable
  when LibreOffice is missing) — computed from the template's `templateType`
  plus live conversion-backend availability (`lib/Service/Conversion/`
  cascade backends already implement `isAvailable()`/`canHandle()`).
- **Capabilities surfaced in the flows**: the correspondence view and the
  wizard/generate review step drive their format choices from the matrix
  instead of hardcoded lists; unavailable formats are disabled with the
  reported reason.
- **Audit**: `generatedDocument` logs every produced format of a multi-format
  job (`outputs` array with per-format file references and status), format
  enum extended with `docx`.

GDPR note (config rule): no new personal-data category — multi-format output
stores the same rendered document content in more formats, in the same
Nextcloud output folder with the same access control; processing stays fully
local (LibreOffice headless, mPDF — no external API calls).

## Capabilities

### New Capabilities

- `multi-format-output`: one render request producing multiple output formats
  from a single render pass, a per-template/per-instance format-capability
  matrix computed from live conversion-backend availability, and
  capability-driven format selection in the generation and correspondence
  flows.

### Modified Capabilities

- `document-creatie-sjablonen`: output-format support (REQ-DCS-03 family) is
  extended — `docx` becomes a valid generation format (editable delivery),
  `html` becomes a valid output format for `office` templates too (LibreOffice
  DOCX→HTML — full format parity), `options.formats` produces multiple outputs
  from one render, and the `generatedDocument` audit object records every
  produced output.
- `pdf-conversion`: the conversion cascade gains a non-throwing capability
  introspection surface (per-backend availability + supported conversions)
  that powers the format matrix — today that report only exists inside
  `ConversionFailedException`.

## Impact

- **Backend**: `DocumentService` — `formats` handling, render-once/convert-N
  pipeline, DOCX production (shared `HtmlToDocxConverter` extracted from
  `CorrespondenceService`'s private implementation — ADR-011 reuse instead of
  a second copy) and office HTML production (new `DocxToHtmlConverter`,
  LibreOffice headless DOCX→HTML); `PdfConversionService` — public
  `getCapabilities()`; new `FormatMatrixService`; `DocumentController` manifest
  response; `CorrespondenceService` delegates its DOCX conversion to the shared
  converter (behaviour unchanged).
- **Register**: `generatedDocument` schema — `format` enum gains `docx`,
  new optional `outputs` array (document register bump `2.3.0` → `2.4.0`,
  additive). Apply order is fixed: `guided-document-wizard` applies **first**
  and bumps the document register `2.2.0` → `2.3.0`; this change applies
  **after** it and bumps `2.3.0` → `2.4.0` (both additive; no
  rebase-on-whichever-lands-second — the order is pinned in tasks/design).
- **Routes**: `GET api/documents/formats`, `GET api/templates/{id}/formats`
  (`appinfo/routes.php`).
- **Frontend**: format checkboxes/disabled states driven by the matrix in
  `CorrespondenceIndex.vue` and the generate/wizard review step (ADR-012
  components, NL Design tokens).
- **Dependencies**: none new — LibreOffice headless and mPDF are already the
  conversion backbone.
- **Sibling boundaries**: no OpenRegister/OpenConnector changes. The
  `letter-correspondence-generation` capability's canonical spec is NOT
  modified by this change (not assigned): the correspondence-flow surfacing
  requirement lives in the new `multi-format-output` capability and the
  relationship is documented in design.md.

## Out of Scope

- XLSX/PPTX/ODS output (Carbone parity beyond document formats) — no
  spreadsheet/presentation templates exist in DocuDesk.
- Editable-DOCX round-trip *import* (re-ingesting an edited DOCX as a new
  version) — exchange is outbound this wave.
- Changing the email format or correspondence batch semantics.
- Merged-PDF bulk output changes (DCS-042 unchanged).
- PDF/A / PDF/UA conformance levels (owned by `pdf-conversion` /
  `pdfua-accessible-output`).

## Success Criteria

- `openspec validate multi-format-output --strict` exits 0.
- One `POST /api/documents/generate` with `options.formats: ["pdf", "docx"]`
  returns a manifest referencing a PDF and an editable DOCX produced from one
  render pass; the DOCX opens editable in Word/LibreOffice.
- An `office` template generates `html` output via LibreOffice DOCX→HTML, and
  its per-template matrix offers `html` when a LibreOffice backend is
  available (full format parity with `twig` templates).
- Requests with the existing single `options.format` are byte-identical to
  today's behaviour.
- With LibreOffice unavailable, the matrix reports `docx`/`odf` unavailable
  with a reason, the UI disables them, and a forced request fails 503 with
  the same reason — no silent PDF fallback.
- `generatedDocument` records every produced output of a multi-format job.
- `composer check:strict` and the unit suite pass with zero new violations.
