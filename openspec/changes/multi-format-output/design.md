# Design: multi-format-output

## Context

Verified current state (HEAD of this worktree):

- `DocumentService`: `VALID_FORMATS = ['pdf', 'odf', 'html']`, one
  `options.format`; `produceOutput()` switches html (passthrough) / odf
  (`convertToOdf()`, LibreOffice headless HTML→ODT via `shell_exec which
  soffice`, 503 when missing) / pdf (`PdfService::renderPdf()` → mPDF).
- `CorrespondenceService`: `VALID_FORMATS = ['pdf', 'docx', 'html', 'email']`;
  its `produceOutput()` has a **private** LibreOffice `--convert-to docx`
  path — DOCX capability exists in the codebase but is unreachable from
  `api/documents/generate`.
- Conversion cascade (`lib/Service/Conversion/`): `ConversionBackendInterface`
  with `name()`, `isAvailable()`, `canHandle(mime, ext)`, `convert(File)`;
  backends OfficeApp → LibreOfficeHeadless → PhpWord → Mpdf → Eml. The
  per-backend `{name, available, supports, reason}` report exists only inside
  `ConversionFailedException` (pdf-conversion spec) — there is no way to ask
  "what can this instance produce?" without failing a conversion.
- `generatedDocument` schema (document register 2.2.0): `format` enum
  `['pdf', 'odf', 'html']`, single value, required.
- `CorrespondenceIndex.vue` hardcodes its `formats` array client-side.
- Wave-1 `office-template-authoring` REQ-DDOTA-003: office templates fill via
  PhpWord `TemplateProcessor` and already define `docx` passthrough output on
  their path.

Constraint (openspec/config.yaml): all conversion local (LibreOffice/mPDF —
no external APIs); ADR-011: reuse the existing DOCX conversion rather than
writing a second one.

## Goals / Non-Goals

**Goals:**

- One render, N formats — outputs provably from the same render of the same
  data.
- Editable DOCX from the main generation path (inter-municipal exchange).
- Capability honesty: flows ask what is producible instead of failing.
- Zero behaviour change for existing single-format callers.

**Non-Goals:**

- No XLSX/PPTX/ODS; no email-format changes; no merged-PDF changes.
- No DOCX re-import round-trip.
- No new conversion engines — LibreOffice headless + mPDF remain the backbone.
- No PDF-conformance changes (PDF/A-3b and PDF/UA stay owned by
  `pdf-conversion` / `pdfua-accessible-output`).

## Decisions

### D1 — `options.formats` (array) beside `options.format`; render once, convert N

Request contract on `POST /api/documents/generate`:

- `options.format` (string, existing) → today's behaviour, binary
  `DataDownloadResponse`, untouched. Back-compat is absolute.
- `options.formats` (array of ≥1 valid formats, deduplicated) → multi-format
  job. Supplying both is a 400.

Pipeline: resolve data once, render once to the **canonical intermediate** —
rendered HTML for `twig` templates, the filled DOCX for `office` templates
(REQ-DDOTA-003's fill step) — then produce each requested format from that
intermediate:

| Target | From HTML (twig) | From filled DOCX (office) |
|---|---|---|
| `html` | passthrough | not offered (no faithful DOCX→HTML path in the cascade) |
| `pdf` | `PdfService::renderPdf()` (mPDF) | `PdfConversionService::convertToPdf()` (cascade) |
| `odf` | LO headless HTML→ODT (existing `convertToOdf()`) | LO headless DOCX→ODT |
| `docx` | shared `HtmlToDocxConverter` (LO headless, D3) | the filled DOCX itself (true editable passthrough) |

Per-format conversion failures do not abort the job: each output entry
carries `status: generated|failed` + error, mirroring the partial-failure
philosophy of DCS-043. The render step failing aborts everything (nothing to
convert).

### D2 — Multi-format delivery: NC files + JSON manifest (no ZIP)

A multi-format response is a JSON manifest, not a binary: each produced
output is written to the generated-documents output folder (the app-managed
folder pattern; `OutputLayoutResolver` conventions for naming/destination)
and reported as `{format, fileId, fileName, downloadUrl, size, status,
error?}` plus the shared generation `warnings`/metadata.

**Rejected:** ZIP streaming (opaque to the Files app, breaks per-file
sharing — the DOCX must be shareable to the buurgemeente directly from
Nextcloud, which is the point) and multipart responses (poor client
support). A caller wanting one file uses `options.format`.

### D3 — Promote `CorrespondenceService`'s DOCX conversion to a shared converter (ADR-011)

Extract the existing private HTML→DOCX LibreOffice invocation
(`CorrespondenceService::produceOutput()` `docx` case, verified: `soffice
--headless --convert-to docx`) into `lib/Service/Conversion/
HtmlToDocxConverter` (same serialization lock discipline as the cascade's LO
backend — pdf-conversion's "LibreOffice headless invocations MUST be
serialised" applies to every soffice caller). `CorrespondenceService` and
`DocumentService` both consume it; correspondence behaviour is unchanged
(pinned by its existing tests). No second DOCX implementation is written.

### D4 — Capability introspection on the cascade + a `FormatMatrixService`

`PdfConversionService` gains a public, non-throwing `getCapabilities()`
returning the same per-backend structure the `ConversionFailedException`
report already defines (`{name, available, supports}` — reusing the shape, so
consumers and tests share fixtures). On top of it, `FormatMatrixService`
computes:

- **instance matrix** (`GET /api/documents/formats`): for each output format,
  `{available: bool, reason?: string}` — `pdf`/`html` always true (mPDF is a
  composer dep), `odf`/`docx` true iff an LO-capable backend reports
  available.
- **template matrix** (`GET /api/templates/{id}/formats`): instance matrix
  intersected with the template's `templateType` row of the D1 table (e.g.
  `html` absent for office templates).

The matrix is computed live per request (availability can change when
LibreOffice is installed/removed); responses carry `Cache-Control: no-store`.
Forced generation of an unavailable format keeps failing 503 with the same
reason string the matrix reports — matrix and failure never disagree.

### D5 — Audit: `outputs` on `generatedDocument`

`generatedDocument` gains optional `outputs` (array of `{format, fileId,
status, error?}`) and its `format` enum gains `docx`. For multi-format jobs
one `generatedDocument` object is written per render (not per format) with
`format` set to the first requested format (back-compat for consumers that
read the scalar) and `outputs` carrying the full truth. Single-format
generations stay exactly as today (no `outputs`). Register bump is additive;
this wave also bumps the document register in `guided-document-wizard` —
whichever change lands second rebases its version number (both additive, no
conflict; noted in tasks).

### D6 — Flows read the matrix

`CorrespondenceIndex.vue` replaces its hardcoded `formats` array with the
template matrix (disabled option + reason tooltip for unavailable formats);
the generate/wizard review step (see `guided-document-wizard` D7 — the runner
already reads "whatever the generate API advertises") consumes the same
endpoint. **Canonical-spec touch discipline:** the
`letter-correspondence-generation` canonical spec is not assigned to this
change, so the correspondence-flow requirement is modelled inside the new
`multi-format-output` capability (REQ-DDMFO-004) and this paragraph documents
the relationship; the correspondence capability's own requirements (formats,
defaults, register logging) are not modified — the UI change is purely how
the existing choice list is populated.

### Declarative vs imperative (ADR-031)

Format conversion is an explicitly valid imperative exception
(external-binary invocation — soffice/mPDF), identical in kind to the
existing generation/conversion services. The data side is declarative:
`generatedDocument` schema extension is a pure `docudesk_register.json` edit;
no lifecycle/aggregation/notification annotations are added or needed.

## OpenRegister usage (ADR-001)

| Operation | OR service |
|---|---|
| Generation audit (`generatedDocument` + `outputs`) | `ObjectService` via the existing `DocumentService` logging path |
| Output files | NC app-managed output folder (file ids referenced from the audit object — same pattern as `anonymizationLink.sourceFileId`) |
| Template lookup for the matrix | existing `TemplateService` read path |

No custom database tables; register import via
`ConfigurationService::importFromApp()` on boot.

## Seed Data

No new schemas — no new seed objects. Test fixtures (unit + e2e) reuse the
Wave-1 seed templates: the Twig demo template and office template
`00000000-0000-0000-0000-000000000101` ("Beschikking parkeervergunning",
Demostad flavour) exercise both rows of the D1 intermediate table. A
multi-format e2e run generates `["pdf", "docx"]` against the seeded Demostad
dossier data and asserts both files land in the output folder.

## Security Considerations

- **No new data exposure**: outputs land in the same access-controlled
  output folder as today's generated files; download URLs are standard NC
  file access (no signed public links).
- **Format forcing**: requesting an unavailable format fails 503 with the
  matrix reason — never a silent downgrade to another format (an "editable"
  file that is secretly a PDF rename would be a trust failure).
- **soffice invocation**: the shared converter inherits the cascade's
  serialization lock and temp-dir hygiene (0700 dirs, unlink after use, no
  shell-interpolated user input — filenames generated server-side), as the
  existing correspondence implementation already does.
- **Matrix endpoints** are read-only, authenticated (`#[NoAdminRequired]` +
  user guard), and leak only backend availability booleans + reason strings
  (no paths, no versions).
- GDPR: no new personal-data category; more formats of the same rendered
  content, all local.

## Risks / Trade-offs

- [DOCX from HTML (twig path) is layout-approximate — LO's HTML import is not
  print-perfect] → accepted and documented: the editable deliverable is for
  *editing*, the PDF remains the presentation artifact; office templates get
  native-fidelity DOCX (the filled source). The docs state which path gives
  which fidelity.
- [Multi-format jobs multiply soffice work under the serialization lock] →
  formats convert sequentially per job; `odf`+`docx` from one HTML means two
  LO calls — bounded by the formats list length (≤4 valid formats), and bulk
  jobs already queue.
- [Two changes bump the document register this wave] → additive properties,
  explicit rebase note in tasks; register import is idempotent.
- [Manifest response is a new shape for `api/documents/generate`] → gated
  entirely behind `options.formats`; existing clients cannot receive it
  accidentally.
- [Matrix says available but conversion still fails (race, corrupt input)] →
  per-output `status: failed` + error in manifest and audit object; matrix is
  advisory, the job report is truth.
