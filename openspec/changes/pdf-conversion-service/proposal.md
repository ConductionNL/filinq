## Why

DocuDesk needs a reusable file-to-PDF conversion service. The first concrete consumer is the anonymise endpoint (sibling change `anonymise-output-format-flag`) which wants PDF output by default for privacy reasons (flattened glyph stream, shed metadata, harder to silently un-redact). Downstream consumers include `anonymisation-grondslagen-summary-rendering` (appends summary pages to the converted PDF) and `eml-pdf-assembly` (richer EML rendering once OR's `text-extraction-eml` ships).

The conversion must be PDF/A-3b for archival compliance (matches `print-preview`) and must cascade through multiple backends so installs without a particular tool still produce output where possible. A 422 with a structured body explains failures when every backend is unavailable or returns an error.

This change is the new capability + service + backends + cascade. The `anonymization` capability delta (`outputFormat: "pdf" | "preserve"` payload field + endpoint orchestration) lives in `anonymise-output-format-flag`.

## What Changes

- **NEW capability:** `pdf-conversion`. A file-to-PDF conversion service with PDF/A-3b output and a documented backend cascade.
- **NEW service** `lib/Service/PdfConversionService.php` with `convertToPdf(File $source, array $opts = []): File`. Walks the registered backends in order: for each, check `isAvailable()` + `canHandle()` then `convert()`; first-success short-circuits; all-failure throws `ConversionFailedException` carrying `getAttempts()`.
- **NEW backends** under `lib/Service/Conversion/` implementing `ConversionBackendInterface { canHandle(string $mime): bool; convert(File $source): File }`: `OfficeAppBackend` (Collabora / OnlyOffice), `LibreOfficeHeadlessBackend`, `PhpWordBackend`, `MpdfBackend`, `EmlBackend` (placeholder until OR ships EML text extraction).
- **NEW dep:** `phpoffice/phpword` (`^1.2`, matching OR's pin) in `composer.json`. Reuses existing `mpdf/mpdf` (already a dep of `pdf-generation`). NOT adding `phpoffice/phpspreadsheet`.
- **NEW config:** per-backend `*_enabled` flags + `libreoffice_binary_path` + `timeout_seconds`; tenant-configurable.
- **NEW exception** `lib/Exception/ConversionFailedException.php` with `getAttempts(): array` returning `[{name, available, supports, reason}, ...]` for diagnostics.

### Conversion cascade (default order)

1. **Office app (Collabora / OnlyOffice)** when installed + configured.
2. **LibreOffice headless** when `soffice --headless` is available.
3. **PhpWord + mPDF** for inputs PhpWord reads (DOCX, ODT, RTF, HTML).
4. **mPDF** directly for HTML / TXT.
5. **OR text-extractor + mPDF** for EML (gated on a future OR capability).
6. Else: 422 with `ConversionFailedException::getAttempts()` payload.

### Out of scope

- The `anonymization` capability delta — `anonymise-output-format-flag`.
- PhpSpreadsheet (XLSX/ODS) support.
- Input normalisation before sending to OpenAnonymiser.
- PDF/A vs plain PDF as a per-call choice (PDF/A-3b is the only output).
- Logo / header / footer / watermark.
- Migration of past anonymised files.

## Capabilities

### New Capabilities

- `pdf-conversion`

## Cross-app Dependencies

- **Soft** — `openregister:text-extraction-eml` (future) — needed for the EML backend to be functional. Until it lands, `EmlBackend::isAvailable()` returns false.

## Impact

- **Code (docudesk):** new service + backend directory + exception; `composer.json` adds `phpoffice/phpword`; admin settings expose the new config keys.
- **API contract:** no new endpoints in this change; the service is consumed by the sibling `anonymise-output-format-flag` and downstream changes.
- **Performance:** conversion adds latency per file. Office-app backends typically sub-second; LibreOffice headless 1–3s; PhpWord+mPDF in between. Batch flows compound — documented in design.
- **Migration:** None — additive.
