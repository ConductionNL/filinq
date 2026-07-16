## Context

Legal/archival requirement: documents entering case systems must be
convertible to PDF/A-3 for MDTO compliance ("automatic format conversion is
needed"). Two concrete fleet consumers already exist and are blocked on this:

- procest's `openspec/changes/archive/2026-06-13-beschikking-generatie/tasks.md`
  task T26 ("Docudesk: Ensure template-engine supports PDF/A-3 output... and
  return of checksumSha256 and paginas count") is explicitly marked
  `Deferred 2026-06-13 (cross-app, docudesk)` — procest ships a
  `TemplateEngineAdapterInterface` + `MockTemplateEngineAdapter` and is
  waiting for the real docudesk deliverable.
- OpenRegister's TMLO/MDTO e-depot transfer (SIP builder) needs
  archival-grade PDF/A-3 output for the documents it packages.

## Converter decision

**Chosen: mPDF (already vendored, `mpdf/mpdf` ^8.3.1) + its bundled FPDI
import support. No new composer dependency, no external binary.**

Investigation of the vendored library turned up more capability than
initially assumed:

- `mpdf/mpdf`'s own `composer.json` hard-requires `setasign/fpdi` (^2.1) —
  it is not an optional/dev dependency, it ships in every install that
  already has mPDF (which docudesk already depends on for
  `PdfService`/`Service\Conversion\MpdfBackend`).
- `Mpdf\Mpdf` mixes in `Mpdf\FpdiTrait` (`vendor/mpdf/mpdf/src/FpdiTrait.php`),
  which itself uses `\setasign\Fpdi\FpdiTrait` — so `$mpdf->setSourceFile()`,
  `$mpdf->importPage()`, and `$mpdf->useTemplate()` are public, native
  methods on the same `Mpdf` object already used for HTML rendering. No
  separate `Fpdi`-family class or bridge is needed; `setSourceFile()` also
  accepts `setasign\Fpdi\PdfParser\StreamReader::createByString()`, so an
  existing PDF can be imported from an in-memory string with no temp file.
- `Mpdf::SetAssociatedFiles(array $files)` is mPDF's own PDF/A-3
  embedded-file API (`vendor/mpdf/mpdf/src/Mpdf.php:1861`) — its docblock
  literally documents the `AFRelationship` key as "PDF/A-3 AFRelationship".
  This is the mechanism that makes PDF/A-3 (vs A-1/A-2) real.
- When `PDFA => true`, mPDF's `MetadataWriter::writeOutputIntent()`
  (`vendor/mpdf/mpdf/src/Writer/MetadataWriter.php:186`) always writes an
  `/OutputIntent` referencing an ICC profile — the bundled
  `data/iccprofiles/sRGB_IEC61966-2-1.icc` when no custom profile is
  configured. Requirement (ii) (embedded ICC output intent) is therefore
  automatic, not new code this change had to write.
- `MetadataWriter::writeMetadata()` writes the XMP `pdfaid:part` /
  `pdfaid:conformance` block from `$mpdf->PDFAversion` (parsed as
  `"<part>-<conformance>"`, e.g. `"3-B"`) — **but `PdfService` never set
  this key**, so it silently defaulted to mPDF's own default,
  `ConfigVariables.php`'s `'PDFAversion' => '1-B'`. Every existing PDF/A
  caller in this app has been emitting PDF/A-1B while claiming PDF/A-3b.
  Fixed as part of this change (`PdfService::buildMpdfConfig()` now pins
  `PDFAversion = '3-B'`).

### Alternatives considered

- **(b) Guarded shell-out to a system Ghostscript binary
  (`gs -dPDFA=3 ...`)**, mirroring the fleet's LibreSign/IAppManager
  fallback pattern (`Service/CorrespondenceService.php`'s
  `shell_exec('which soffice')` feature-detection is the closest existing
  precedent in this app). Rejected as the primary path: mPDF/FPDI already
  cover every documented requirement (XMP, ICC, fonts, attachments) without
  a new external-process dependency, and Ghostscript would need its own
  attachment-embedding step regardless (its `pdfwrite` device does not
  natively expose PDF/A-3 `AFRelationship` embedding the way mPDF's own API
  does). Not implemented in this change; if a future need arises for
  byte-for-byte re-distillation of arbitrary existing PDFs (see "Validation
  scope" below), it remains available as an additional, optional backend
  behind the same guarded-detection pattern.
- **(c) A new composer dependency** (e.g. a dedicated PDF/A library). Not
  needed — see above — and therefore not pursued. Per the fleet's dependency
  guardrail (a vendored `sabre/xml` once shadowed Nextcloud core CalDAV
  instance-wide), adding a new autoloaded package into the whole Nextcloud
  instance is treated as a last resort; this change adds zero new packages.

### Shadow-risk assessment

No new vendored package is introduced. `mpdf/mpdf` and `setasign/fpdi` are
already present in `composer.lock` and already autoloaded into the instance
by docudesk's existing `PdfService`/`Service\Conversion\MpdfBackend`/
`LibreOfficeHeadlessBackend` code paths — this change adds zero new surface
to the fleet's shadow-risk inventory. `composer show --tree` was not run
against a *new* dependency because none was added; the existing `mpdf/mpdf`
tree was already audited by prior docudesk changes that introduced it.

## Validation scope

This service asserts, and this change tests, the **container-level**
PDF/A-3 markers it is directly responsible for producing:

- `%PDF-` magic header present.
- XMP `pdfaid:part` / `pdfaid:conformance` identification present and equal
  to `3` / `B`.
- The embedded-attachment mechanism (`/Type /Filespec`, `/Type
  /EmbeddedFile`, `/AFRelationship`) is present when attachments are
  supplied.

It does **not** perform full veraPDF-grade structural/content validation
(font subsetting correctness, colour-space conformance of every content
stream, tagged-PDF structure tree, etc.). That is explicitly out of scope —
follow-up work, if ever needed for a regulator-facing conformance claim,
would integrate veraPDF (a separate Java tool, out of this change's budget)
as a post-conversion checker.

Two paths have materially different real-world guarantees, and callers
should choose deliberately:

- **`convertHtml()`** (docudesk's own generation path — templates,
  correspondence, beschikkingen): mPDF renders every element itself, so font
  embedding and colour-space handling are fully within mPDF's own PDF/A
  auto-correction (`PDFAauto`). This is the strongest-guarantee path.
- **`convertExistingPdf()`** (an already-existing PDF, e.g. an upload or a
  document produced outside docudesk): page content is imported via FPDI as
  opaque XObjects — copied byte-for-byte from the source, not re-rendered.
  This service wraps that content in a genuinely compliant PDF/A-3
  *container* (XMP + ICC output intent + attachments), but it cannot
  retroactively embed fonts that were missing in the source PDF itself, nor
  verify the source's internal colour-space usage. A source PDF that was
  never PDF/A-compliant internally will not become fully content-compliant
  merely by being wrapped — this mirrors a well-known limitation of
  FPDI-based "PDF/A wrapping" approaches generally (as opposed to
  Ghostscript-specific pdfwrite-style re-distillation). Callers with a strong
  compliance requirement on documents docudesk did not itself generate
  should prefer generating the archival document natively through
  `convertHtml()`/the template pipeline where possible.

## Guardrails

- **Size caps** (`docudesk.pdfa3.max_input_bytes`, default 50 MiB;
  `docudesk.pdfa3.max_attachment_bytes`, default 20 MiB): rejected before
  the (potentially large) content is even read into memory for the source
  cap, and per-attachment for the attachment cap.
- **Time budget** (`docudesk.pdfa3.max_seconds`, default 60): checked
  before the page-builder runs and again before final `Output()`; for
  `convertExistingPdf()`, also checked between every imported page, so a
  pathologically large multi-page source cannot hang a request
  indefinitely. This is a best-effort, page-granularity check — PHP has no
  preemptive interrupt for a single synchronous mPDF call, so a single
  extremely large *page* cannot itself be interrupted mid-render. The
  service's `now()` clock hook is isolated behind a protected method so
  tests can simulate an exceeded budget deterministically without real
  sleeping.
- **Converter availability** (`docudesk.pdfa3.enabled`, default true, plus a
  defensive `class_exists(\Mpdf\Mpdf::class)` check): a disabled or broken
  converter fails with a typed `Pdfa3ConversionException`
  (`REASON_CONVERTER_UNAVAILABLE`) carrying an admin hint, never a silent
  fallback to a non-compliant PDF.
- **No silent passthrough:** `validateOutput()` asserts the `%PDF` header
  and the XMP `pdfaid:part`/`pdfaid:conformance` markers on every successful
  conversion before returning bytes to the caller — defence-in-depth against
  a future regression (e.g. someone flips off `PDFA` in
  `instantiateMpdf()`) that would otherwise silently return a
  non-compliant file labelled as PDF/A-3. This is asserted directly (via
  reflection, since the guard itself has no natural external failure
  trigger through the public API) in
  `Pdfa3ConversionServiceTest::testValidateOutputRejectsMissingPdfHeader()`
  and `::testValidateOutputRejectsPdfWithoutPdfaMarkers()`.

## MDTO metadata mapping

`title`/`author`/`creator`/`subject`/`keywords` map to mPDF's dedicated PDF
`/Info` + XMP `dc:` setters (`SetTitle`/`SetAuthor`/`SetCreator`/
`SetSubject`/`SetKeywords`). Every other caller-supplied metadata key
(`identifier`, `caseReference`, `archiefvormer`, `aggregatieniveau`, and any
other MDTO/archival field) is:

1. Folded into a `docudesk:`-namespaced custom XMP RDF block via
   `SetAdditionalXmpRdf()`, so the archival metadata is genuinely part of the
   PDF/A-3's XMP packet, not merely crammed into `/Keywords`.
2. Serialised into an auto-generated `mdto-metadata.xml` attachment (unless
   the caller already supplied their own `.xml` attachment) — a concrete,
   literal realisation of PDF/A-3's defining "embed the source/metadata
   alongside" capability.

## Consumer path (for reference, not implemented in this change)

- **procest**: `TemplateEngineAdapterInterface::render()` expects
  `array{format: string, bestandId: string, checksumSha256: string, paginas:
  int}`. The new REST endpoint's response headers
  (`X-Docudesk-Pdfa3-Checksum-Sha256`, `X-Docudesk-Pdfa3-Pages`) and the
  service's return shape (`checksumSha256`, `pages`) are deliberately named
  to make wiring a real adapter a thin HTTP-client + rename exercise, not a
  contract-design exercise.
- **OpenRegister**: the TMLO/MDTO SIP builder can call
  `POST /api/pdfa3/convert` with `fileId` + MDTO metadata for any file the
  requesting Nextcloud user can read, or depend on `Pdfa3ConversionService`
  directly if run in-process.
