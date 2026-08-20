## Why

Long-term document preservation requires PDF/A-3 format: documents entering
case systems must be converted to PDF/A-3 for MDTO compliance, and automatic
format conversion is a researched legal requirement. No fleet capability
produces PDF/A-3 today. Per fleet policy, document capabilities belong in
docudesk, not in the apps that consume them — and two concrete consumers are
already waiting: procest's beschikking/archival pipeline defines
`TemplateEngineAdapterInterface` with task T26 explicitly deferred ("PDF/A-3
template-engine capability lives in Docudesk... the engine upgrade is a
Docudesk deliverable"), and OpenRegister's TMLO/MDTO e-depot SIP builder needs
an archival-grade PDF/A-3 for its transfer packages.

Investigating the existing code turned up two things this change also fixes:

1. **A pre-existing correctness bug.** `PdfService::buildMpdfConfig()` has
   always claimed "PDF/A-3b archival compliance" in its docblock, and sets
   mPDF's `PDFA`/`PDFAauto` flags — but never sets `PDFAversion`. mPDF
   defaults that to `'1-B'`, so every existing PDF/A caller
   (`pdf#renderPdfA`, `print#downloadPdfA`, `DocumentService`'s
   `pdfOptions.pdfa`) has been silently emitting PDF/A-**1B**, not PDF/A-3b.
2. **The tools are already vendored.** `mpdf/mpdf` (^8.3.1, already a
   dependency) hard-requires `setasign/fpdi` (^2.1) and ships its own
   `Mpdf\FpdiTrait`, giving `Mpdf\Mpdf` native `setSourceFile()` /
   `importPage()` / `useTemplate()` methods plus `SetAssociatedFiles()` (the
   PDF/A-3 embedded-attachment mechanism) and automatic ICC output-intent
   embedding when `PDFA` is enabled. No new composer dependency and no
   external binary (Ghostscript) are needed.

## What Changes

- **Fix:** `PdfService::buildMpdfConfig()` now pins `PDFAversion = '3-B'`
  when `pdfa` is requested, so every existing PDF/A caller genuinely emits
  PDF/A-3b (was silently PDF/A-1B).
- **NEW service `lib/Service/Pdfa3ConversionService.php`:** converts either
  an existing PDF (`convertExistingPdf()`, imports pages via mPDF's native
  FPDI support) or freshly rendered HTML (`convertHtml()`, mPDF's own
  strongest-guarantee rendering path) into a genuine PDF/A-3b file with XMP
  conformance identification, an embedded ICC output intent, embedded fonts,
  and embedded attachments (including an auto-generated MDTO metadata XML
  sidecar) — the feature that actually distinguishes PDF/A-3 from PDF/A-1/A-2.
- **NEW typed exception `lib/Exception/Pdfa3ConversionException.php`:**
  carries a machine-readable `reason` and an admin-actionable hint for every
  guardrail (oversized source/attachment, time budget exceeded, converter
  disabled, unreadable source, render failure, output-validation failure).
- **NEW REST endpoint `POST /api/pdfa3/convert`
  (`Pdfa3ConversionController`):** IDOR-safe `fileId`-based conversion of an
  existing PDF the requesting user can read, for procest/OpenRegister and any
  external integrator to call. Returns the PDF/A-3 binary with
  `X-Docudesk-Pdfa3-Checksum-Sha256` / `-Pages` / `-Conformance` headers —
  shaped to match procest's already-declared
  `TemplateEngineAdapterInterface::render()` contract
  (`checksumSha256`, `paginas`).
- **Wired natural in-app caller:** `PdfController::renderPdfA()` (docudesk's
  own generation endpoint) now delegates to `Pdfa3ConversionService` when the
  request carries `metadata` or `attachments`, so newly generated archival
  documents (beschikkingen, correspondence) can be emitted as full PDF/A-3 on
  request without a second round trip through the new endpoint.
- **Guardrails:** configurable size caps (source PDF, per-attachment),
  wall-clock time budget, and a converter-availability check — all typed
  failures with admin hints, never a silent non-compliant passthrough. Output
  is validated (`%PDF` header + `pdfaid:part`/`pdfaid:conformance` XMP
  markers) before being returned.

### Out of scope

- Full veraPDF-grade structural/content PDF/A validation — this change
  asserts the container-level markers it produces (XMP identification, ICC
  output intent presence, attachment mechanism), not deep content
  conformance of arbitrary imported page streams. See design.md "Validation
  scope".
- Retroactively re-embedding fonts inside an *existing* source PDF's page
  content when that source never embedded them — `convertExistingPdf()`
  wraps the source pages in a compliant PDF/A-3 container but does not
  re-distill their content streams (that would require Ghostscript-class
  tooling). `convertHtml()` (docudesk's own generation path) has no such gap
  — mPDF controls all font embedding there.
- procest's or OpenRegister's actual consumption of the new endpoint (wiring
  procest's `TemplateEngineAdapterInterface` real adapter, or OR's SIP
  builder call site) — those are deliverables in their own repos; this
  change ships the docudesk-side capability their interfaces already declare
  as deferred.

## Capabilities

### New Capabilities

- `pdfa3-conversion`

## Cross-app Dependencies

- **Soft** — procest's `TemplateEngineAdapterInterface` (task T26, deferred
  2026-06-13) names this exact capability as its real-adapter dependency;
  this change ships the docudesk side, procest wiring is a separate,
  procest-repo deliverable.
- **Soft** — OpenRegister's TMLO/MDTO e-depot SIP builder is a documented
  consumer of archival-grade PDF/A-3; no OR-side code change is required by
  this change (REST endpoint + service are consumable as-is).

## Impact

- **Code (docudesk):** `lib/Service/Pdfa3ConversionService.php` (NEW),
  `lib/Exception/Pdfa3ConversionException.php` (NEW),
  `lib/Controller/Pdfa3ConversionController.php` (NEW),
  `appinfo/routes.php` (new route), `lib/Service/PdfService.php` (bugfix +
  small public-method additions for reuse), `lib/Controller/PdfController.php`
  (archival-path wiring).
- **Tests:** `tests/unit/Service/Pdfa3ConversionServiceTest.php` (NEW),
  `tests/unit/Controller/Pdfa3ConversionControllerTest.php` (NEW),
  `tests/unit/Service/PdfServiceTest.php` (regression test for the
  PDFAversion fix), `tests/unit/Controller/PdfControllerTest.php` (archival
  delegation coverage), `tests/stubs/NextcloudStubs.php` (the standalone
  `DataDownloadResponse` stub was missing `addHeader()` — corrected to match
  the real OCP inheritance chain).
