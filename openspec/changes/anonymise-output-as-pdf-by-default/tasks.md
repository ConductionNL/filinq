## 1. Composer dependency

- [ ] 1.1 Add `phpoffice/phpword` (`^1.2`, matching OpenRegister's pin) to `composer.json` under `require`. Run `composer update phpoffice/phpword` and commit `composer.lock`.
- [ ] 1.2 Confirm `mpdf/mpdf` is already available (it is — used by `pdf-generation`). No action needed; document the dependency relationship in design.md if not already.
- [ ] 1.3 Run `composer install` in CI / build pipeline; confirm no conflict resolutions are needed.

## 2. PdfConversionService skeleton

- [ ] 2.1 Create `lib/Service/PdfConversionService.php`. Constructor injects an ordered list of `ConversionBackendInterface` implementations + the logger + `IAppConfig` for tenant config.
- [ ] 2.2 Implement `convertToPdf(File $source, array $opts = []): File` with the cascade walk: for each backend, check `isAvailable()` then `canHandle($mime, $ext)`, then call `convert()`. On first success, return. On all failure, throw `ConversionFailedException`.
- [ ] 2.3 Define `lib/Exception/ConversionFailedException.php` carrying the `getAttempts(): array` method that returns `[{name, available, supports, reason}, ...]`.
- [ ] 2.4 Define `lib/Service/Conversion/ConversionBackendInterface.php` per design D3.

## 3. Backend implementations

- [ ] 3.1 `lib/Service/Conversion/MpdfBackend.php` — handles HTML and TXT directly via mPDF. Wraps TXT in a `<pre>` HTML envelope. Configures mPDF for PDF/A-3b output (`'PDFA' => true`). `name()`: `mpdf`.
- [ ] 3.2 `lib/Service/Conversion/PhpWordBackend.php` — uses `\PhpOffice\PhpWord\IOFactory::load()` to read DOCX/ODT/RTF/HTML; configures `Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF)` + `Settings::setPdfRendererPath()`; uses `IOFactory::createWriter($phpWord, 'PDF')` to emit PDF/A-3b. `canHandle()` returns true for those four MIME types. `name()`: `phpword`.
- [ ] 3.3 `lib/Service/Conversion/LibreOfficeHeadlessBackend.php` — invokes `soffice --headless --convert-to "pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2" --outdir <tmp> <input>`. Acquires `ILockingProvider` lock keyed `soffice:headless:convert` before invocation; releases after. `isAvailable()` checks the configured binary path resolves and is executable. `name()`: `libreoffice_headless`.
- [ ] 3.4 `lib/Service/Conversion/OfficeAppBackend.php` — wraps Collabora and OnlyOffice convert APIs. Detect which (if either) is installed and configured via Nextcloud's app-manager + each app's settings. Use the appropriate API to request PDF/A-3b output. `name()`: `office_app`.
- [ ] 3.5 `lib/Service/Conversion/EmlBackend.php` — `isAvailable()` returns true only when OpenRegister's `TextExtractionService` supports `message/rfc822` (probe via reflection or a feature flag from OR). `convert()` extracts the body, wraps in HTML, and delegates to the mPDF backend. Until OR's EML support lands, `isAvailable()` returns false. `name()`: `eml`.
- [ ] 3.6 Register all backends in `Application::register()` (or the equivalent DI registration point). Order: `OfficeAppBackend`, `LibreOfficeHeadlessBackend`, `PhpWordBackend`, `MpdfBackend`, `EmlBackend`.

## 4. Tenant configuration surface

- [ ] 4.1 Define the configuration keys per design.md D10 in admin settings UI (or document them as undocumented IAppConfig keys in v1, with admin UI in a follow-up). Keys: `default_output_format`, per-backend `*_enabled`, `libreoffice_binary_path`, `timeout_seconds`.
- [ ] 4.2 Wire each backend's `isAvailable()` to read its corresponding `*_enabled` flag from `IAppConfig`. Default true if unset.
- [ ] 4.3 Wire `LibreOfficeHeadlessBackend` to read `libreoffice_binary_path` (default `soffice`).
- [ ] 4.4 Wire all backends' `convert()` to enforce `timeout_seconds` (default 60). For shell-out backends use `proc_open` with select-loop timeout; for in-process backends wrap in a deadline check.

## 5. Anonymise endpoint integration

- [ ] 5.1 Update `lib/Controller/AnonymizationController::anonymize` to accept top-level optional `outputFormat: "pdf" | "preserve"`. Validate; reject other values with HTTP 400.
- [ ] 5.2 Resolve the effective `outputFormat`: per-call value if present, else tenant default from `IAppConfig` (`docudesk.anonymisation.default_output_format`), else `"pdf"`.
- [ ] 5.3 In `lib/Service/AnonymizationService::anonymizeDocument`, after OR returns the anonymised file: if `outputFormat === "pdf"`, invoke `PdfConversionService::convertToPdf()`. On success, replace the native-format file in NC with the PDF (atomic — write the PDF first, then delete the native file, with proper file-name extension update).
- [ ] 5.4 On `ConversionFailedException`, roll back: delete the un-converted anonymised intermediate from NC. Convert the exception into HTTP 422 with the documented body shape (see specs/anonymization/spec.md).
- [ ] 5.5 Update `lib/Service/BatchAnonymizeService` similarly. Per-file conversion; per-file outcomes returned.
- [ ] 5.6 Update `lib/Controller/BatchAnonymizationController::batchAnonymize` to accept `outputFormat` and surface per-file outcomes (HTTP 207 multi-status if some files succeeded and some failed; HTTP 422 if none succeeded).

## 6. Unit tests

- [ ] 6.1 `tests/unit/Service/Conversion/MpdfBackendTest.php` — handles HTML, TXT; produces PDF/A-3b; rejects non-supported MIMEs.
- [ ] 6.2 `tests/unit/Service/Conversion/PhpWordBackendTest.php` — handles DOCX, ODT, RTF, HTML; rejects DOC and other formats; PDF output is PDF/A-3b.
- [ ] 6.3 `tests/unit/Service/Conversion/LibreOfficeHeadlessBackendTest.php` — `isAvailable()` honours config + binary check; lock acquired; timeout enforced; non-zero exit treated as failure.
- [ ] 6.4 `tests/unit/Service/Conversion/OfficeAppBackendTest.php` — Collabora and OnlyOffice detection paths; convert API mock.
- [ ] 6.5 `tests/unit/Service/Conversion/EmlBackendTest.php` — `isAvailable()` returns false when OR has no EML support; returns true and delegates to mPDF when OR supports EML (mock the OR service).
- [ ] 6.6 `tests/unit/Service/PdfConversionServiceTest.php` — cascade walks in order; first-success short-circuit; `ConversionFailedException` aggregates attempts; tenant config disables backends.
- [ ] 6.7 `tests/unit/Service/AnonymizationServiceTest.php` — when `outputFormat: "pdf"`, conversion is invoked; rollback on conversion failure; native file replaced atomically; `outputFormat: "preserve"` path unchanged.
- [ ] 6.8 `tests/unit/Controller/AnonymizationControllerTest.php` — 400 for invalid `outputFormat`; 422 with structured body on conversion failure; 200 with PDF metadata on success.

## 7. Integration tests

- [ ] 7.1 Newman / Postman: anonymise call without `outputFormat` returns PDF (when LibreOffice or PhpWord is available in the test stack).
- [ ] 7.2 Newman: anonymise call with `outputFormat: "preserve"` returns native format.
- [ ] 7.3 Newman: anonymise call with an unsupported input + no backends returns 422 with structured body.
- [ ] 7.4 Newman: batch anonymise with mixed-format inputs returns appropriate per-file outcomes.
- [ ] 7.5 Manually verify on a stack with Collabora installed: the Office app backend wins over LibreOffice / PhpWord. (Document the test rather than automating Collabora setup in CI.)

## 8. Documentation

- [ ] 8.1 Add a new section to `docs/features/anonymization.md` (create if missing) describing the PDF-by-default behaviour, the `outputFormat` parameter, the conversion cascade, and the 422 error body shape.
- [ ] 8.2 CHANGELOG entry under "Added": new `PdfConversionService` with cascade backends; new `outputFormat` parameter on anonymise endpoints; tenant configuration for backend control.
- [ ] 8.3 CHANGELOG entry under "Behavior changes": anonymise endpoint now produces PDF/A-3b output by default. Callers needing native-format output must send `outputFormat: "preserve"`. Conversion failures return HTTP 422 — operators that previously got native-format output for unsupported types may need to install LibreOffice / an Office app or send `outputFormat: "preserve"`.
- [ ] 8.4 Document the new `composer require phpoffice/phpword` dependency in installation instructions.
- [ ] 8.5 Note the soft cross-app dependency on a future OpenRegister change for EML extraction. Until that lands, EML inputs in the default `pdf` mode return 422.

## 9. Quality and verification

- [ ] 9.1 Run `composer check:strict` — clean. Fix any pre-existing issues in touched files per the workflow rule.
- [ ] 9.2 Manual smoke against a live stack: anonymise a DOCX, ODT, RTF, TXT, HTML with each backend tier active in turn (start with Office app + LibreOffice + PhpWord, then disable each in sequence to verify the cascade falls through correctly). Verify the resulting file declares PDF/A-3b conformance via `pdfinfo` or similar.
- [ ] 9.3 Run `openspec validate anonymise-output-as-pdf-by-default` — clean.
- [ ] 9.4 Run `openspec validate` on the canonical `anonymization` capability (it picks up the delta) — clean.
