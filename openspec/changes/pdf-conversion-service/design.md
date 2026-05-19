## Context

DocuDesk lacks a reusable file-to-PDF conversion path. Today the anonymise endpoint emits whatever OpenAnonymiser returns — DOCX in, DOCX out — and downstream consumers (e.g. summary-page rendering) can't append to a non-PDF output. PDF rendering also flattens the document (glyph stream, shed metadata, reduced edit affordances), which is the right privacy default for a redacted artifact.

This change is the new capability + service + backends. The anonymise endpoint's payload flag and orchestration live in `anonymise-output-format-flag`.

## Goals / Non-Goals

**Goals:**

- `PdfConversionService::convertToPdf(File $source, array $opts = []): File` — one entry point for all callers.
- Backend cascade with `isAvailable()` + `canHandle()` + `convert()` semantics.
- PDF/A-3b output by default (archival compliance).
- Per-backend tenant config (enable/disable, paths, timeouts).
- `ConversionFailedException::getAttempts()` for diagnostic 422 bodies.

**Non-Goals:**

- Anonymise endpoint surface — covered by `anonymise-output-format-flag`.
- XLSX/ODS support.
- Plain (non-PDF/A) output mode.
- Custom rendering (logos, watermarks).

## Decisions

### D1. Cascade order

Office app → LibreOffice headless → PhpWord+mPDF → mPDF direct → EML (when OR supports). First-success short-circuits. Tenants can disable individual backends via `*_enabled` config.

### D2. `ConversionBackendInterface` is minimal

`canHandle(string $mime): bool`, `convert(File $source): File`, `isAvailable(): bool`, `name(): string`. Each backend reports its own availability (checks binary path, app installation, OR feature flag).

### D3. PDF/A-3b output across all backends

mPDF backends configure `'PDFA' => true`. PhpWord uses `Settings::PDF_RENDERER_MPDF`. LibreOffice headless passes `pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2`. Office app converters request the PDF/A-3b variant where the underlying API exposes it.

### D4. Shell-out backends acquire NC locks + enforce timeouts

`LibreOfficeHeadlessBackend` acquires `ILockingProvider` keyed `soffice:headless:convert` before invocation and releases after. Timeout enforced via `proc_open` + select loop. Default `timeout_seconds: 60`. In-process backends wrap in a deadline check.

### D5. EML backend gated on OR capability

`EmlBackend::isAvailable()` probes OR's `TextExtractionService` for `message/rfc822` support (reflection or OR feature flag). Until OR ships, returns false. The richer assembly variant lives in `eml-pdf-assembly`.

### D6. PhpSpreadsheet not added

XLSX/ODS rely on Office app or LibreOffice fallback only. If neither is present, `canHandle` returns false everywhere and the cascade ends in 422. Adding PhpSpreadsheet is deferred (large dep, narrow benefit).

### D7. Atomic file replacement is the consumer's responsibility

The service returns a converted file; callers (e.g. the anonymise orchestration in `anonymise-output-format-flag`) are responsible for writing the PDF, deleting the source, and updating the extension atomically. Keeps the service's contract narrow.

## Risks / Trade-offs

- **LibreOffice headless lock contention** → single global lock per host serialises conversions. Acceptable for typical batch sizes; documented.
- **PhpWord-emitted PDF/A conformance** → not all PhpWord-supported inputs preserve full PDF/A-3b conformance under all conditions. Acceptable for v1; spike during apply if conformance check fails on common inputs.
- **Office app installation matrix** → Collabora and OnlyOffice have different convert APIs; `OfficeAppBackend` wraps both with detection. Bug-surface risk; documented as the most-tested path in CI.
- **Conversion latency on batch flows** → compounds across files. Office app and PhpWord paths are fast; LibreOffice headless is slowest. Tenants with large batches favour Office app first.

## Migration Plan

1. Add `phpoffice/phpword` to composer; lock-update.
2. Land the service + interface + exception.
3. Land each backend (Office app, LibreOffice, PhpWord, mPDF, EML).
4. Register backends in DI in the documented order.
5. Surface config keys in admin settings.

**Rollback:** Disable all backends (`*_enabled: false`); the service short-circuits to `ConversionFailedException`. Consumers that need to keep working pass `outputFormat: "preserve"` (per the sibling flag-change).

## Seed Data

Not applicable.

## Open Questions

- Should the OR-EML-backend probe be a hard service-resolution (throws if OR isn't loaded) or a soft check (returns false silently)? Provisional: soft, to match how `isAvailable()` is used elsewhere.
