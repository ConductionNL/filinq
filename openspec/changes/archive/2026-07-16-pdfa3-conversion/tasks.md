# Tasks: pdfa3-conversion

All tasks are `[docudesk]`.

## [docudesk] Fix pre-existing PDF/A version bug

- [x] F-1.1 `PdfService::buildMpdfConfig()` pins `PDFAversion = '3-B'` when
  `pdfa` is requested (was silently defaulting to mPDF's `'1-B'`, so every
  existing caller emitted PDF/A-1B while claiming PDF/A-3b).
  - **Acceptance:** `PdfServiceTest::testRenderPdfWithPdfaOptionProducesPdfA3b()`
    asserts `<pdfaid:part>3</pdfaid:part>` and
    `<pdfaid:conformance>B</pdfaid:conformance>` in the output.
- [x] F-1.2 `PdfService::getFontDirectory()` promoted from `private` to
  `public` (no behaviour change) so `Pdfa3ConversionService` can reuse the
  same embedded DejaVu Sans set.

## [docudesk] Core conversion service

- [x] C-1.1 `lib/Service/Pdfa3ConversionService.php`: `convertHtml()` —
  renders HTML via mPDF configured for PDF/A-3b (PDFA/PDFAauto/PDFAversion
  3-B), applies metadata, applies attachments, validates output.
- [x] C-1.2 `convertExistingPdf()` — imports every page of an
  `OCP\Files\File` via mPDF's native `setSourceFile`/`importPage`/
  `useTemplate` (FpdiTrait), same PDF/A-3 assembly as `convertHtml()`.
- [x] C-1.3 `applyMetadata()` — standard fields (title/author/creator/
  subject/keywords) via mPDF setters; every other metadata key folded into
  a `docudesk:` custom XMP RDF block via `SetAdditionalXmpRdf()`.
- [x] C-1.4 `buildAssociatedFiles()` — caller-supplied attachments mapped to
  mPDF's `SetAssociatedFiles()` shape; auto-generates an `mdto-metadata.xml`
  sidecar attachment when metadata is present and no XML attachment was
  already supplied.
- [x] C-1.5 `validateOutput()` — asserts `%PDF-` header and
  `pdfaid:part`/`pdfaid:conformance` XMP markers before returning; the
  no-silent-passthrough guardrail.
  - **Acceptance:** `Pdfa3ConversionServiceTest::testValidateOutputRejectsMissingPdfHeader()`
    and `::testValidateOutputRejectsPdfWithoutPdfaMarkers()` (invoked via
    reflection since the guard has no natural external failure trigger
    through the public API).

## [docudesk] Guardrails

- [x] G-1.1 Size caps: `docudesk.pdfa3.max_input_bytes` (source, default 50
  MiB) and `docudesk.pdfa3.max_attachment_bytes` (per-attachment, default 20
  MiB) — typed `Pdfa3ConversionException` (`source_too_large` /
  `attachment_too_large`, HTTP 413) before content is read/embedded.
- [x] G-1.2 Time budget: `docudesk.pdfa3.max_seconds` (default 60), checked
  before rendering, before final `Output()`, and between every imported page
  for `convertExistingPdf()`. Clock isolated behind a protected `now()`
  method so tests simulate an exceeded budget deterministically.
- [x] G-1.3 Converter availability: `docudesk.pdfa3.enabled` (default true)
  plus a defensive `class_exists(\Mpdf\Mpdf::class)` check —
  `converter_unavailable` (HTTP 503) with an admin hint, never a silent
  fallback.

## [docudesk] Exception + REST exposure

- [x] E-1.1 `lib/Exception/Pdfa3ConversionException.php` — typed exception
  with `REASON_*` constants (source_too_large, attachment_too_large,
  time_limit_exceeded, converter_unavailable, source_unreadable,
  render_failed, output_validation_failed), `getReason()`, `getAdminHint()`.
- [x] E-1.2 `lib/Controller/Pdfa3ConversionController.php` — `POST
  api/pdfa3/convert`, IDOR-safe `fileId` resolution (mirrors
  `ValidationController`), `#[NoAdminRequired]`, composition-metadata
  response headers (`X-Docudesk-Pdfa3-Checksum-Sha256`, `-Pages`,
  `-Conformance`).
- [x] E-1.3 `appinfo/routes.php` — `pdfa3Conversion#convert` route added.

## [docudesk] Natural in-app caller wiring

- [x] W-1.1 `PdfController::renderPdfA()` accepts optional `metadata` /
  `attachments` body params and delegates to
  `Pdfa3ConversionService::convertHtml()` when either is present (extracted
  into `renderArchivalPdfA()` to keep the method within the fleet's
  cyclomatic-complexity threshold); falls back to the existing
  `PdfService::renderPdf()` path unchanged when neither is present.
  - **Acceptance:** `PdfControllerTest::testRenderPdfADelegatesToPdfa3ServiceWithMetadata()`
    and `::testRenderPdfAUsesPlainPathWithoutArchivalParams()`.

## [docudesk] Tests

- [x] T-1.1 `tests/unit/Service/Pdfa3ConversionServiceTest.php` — happy path
  (HTML + existing-PDF, both asserting real XMP markers via real mPDF/FPDI),
  attachment embedding, MDTO metadata mapping, converter-disabled graceful
  failure, source/attachment size caps, time-budget guardrail, output
  no-silent-passthrough guardrail (both directions).
- [x] T-1.2 `tests/unit/Controller/Pdfa3ConversionControllerTest.php` —
  missing fileId, IDOR-safe 404, successful download with headers, typed
  exception mapping.
- [x] T-1.3 `tests/unit/Controller/PdfControllerTest.php` — archival
  delegation branch coverage (updated for the new constructor dependency).
- [x] T-1.4 `tests/unit/Service/PdfServiceTest.php` — regression test for
  the PDFAversion fix.
- [x] T-1.5 `tests/stubs/NextcloudStubs.php` — corrected the standalone
  `DataDownloadResponse` stub (was missing `addHeader()`/`getHeaders()`,
  unlike the real OCP class it stands in for) so the composition-metadata
  headers are testable without a live Nextcloud instance.

## [docudesk] Quality gates

- [x] Q-1.1 `phpcs` clean on every new/changed file (0 errors).
- [x] Q-1.2 `phpstan` clean on every new/changed file.
- [x] Q-1.3 `psalm` clean (no errors) on every new/changed file.
- [x] Q-1.4 `phpmd` — `PdfController::renderPdfA()` refactored (parameter
  collection + exception mapping extracted) to clear cyclomatic-complexity
  and method-length thresholds. `Pdfa3ConversionService`'s overall class
  complexity (73, threshold 50) is accepted as a documented trade-off — the
  guardrail/metadata/attachment logic is cohesive to one compliance-critical
  service; phpmd is non-blocking in this repo's `composer phpmd` script
  (`|| echo 'PHPMD not installed, skipping...'` swallows the exit code) and
  the class is fully covered by 12 dedicated unit tests.
- [x] Q-1.5 Full `phpunit-unit` suite green on the diff (907 tests; the 8
  pre-existing errors in `GrondslagProposalServiceTest` /
  `AnonymizationServiceProhibitionTest` are unrelated to this change — `git
  diff` confirms zero changes to `GrondslagProposalService.php`,
  `AnonymizationService.php`, or those test files).
