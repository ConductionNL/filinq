# PDF Conversion

Filinq provides a reusable file-to-PDF conversion service (`PdfConversionService`) that converts any supported input file to PDF/A-3b using a cascade of configurable backends.

## Architecture

The service implements a **cascade pattern**: backends are tried in priority order. The first backend that is available, claims the input format, and successfully converts the file wins. If every backend fails or declines, `ConversionFailedException` is thrown with structured per-backend attempt records suitable for a 422 response body.

### Cascade Order

| Priority | Backend | Identifier | Handles |
|---|---|---|---|
| 1 | `OfficeAppBackend` | `office_app` | Any format supported by Collabora / OnlyOffice / Euro Office (via NC `IConversionManager`, NC 31+) |
| 2 | `LibreOfficeHeadlessBackend` | `libreoffice_headless` | DOC, DOCX, XLS, XLSX, PPT, PPTX, ODT, ODS, ODP, RTF, HTML, TXT, PNG, JPG |
| 3 | `PhpWordBackend` | `phpword` | DOCX, ODT, RTF, HTML, DOC (via `phpoffice/phpword` + mPDF) |
| 4 | `MpdfBackend` | `mpdf` | HTML, XHTML, TXT, Markdown (direct mPDF rendering) |
| 5 | `EmlBackend` | `eml` | EML (stubbed — activates when OpenRegister ships EML extraction) |

### Backend Interface

Every backend implements `ConversionBackendInterface`:

```php
interface ConversionBackendInterface {
    public function name(): string;           // stable identifier
    public function isAvailable(): bool;      // runtime + tenant check
    public function canHandle(string $mimeType, string $extension): bool;
    public function convert(File $source): File;
}
```

## PDF/A-3b Output

All backends are configured to emit PDF/A-3b (archival-grade PDF) where technically possible:

- **mPDF**: `'PDFA' => true` in the mPDF config.
- **LibreOffice headless**: `pdf:writer_pdf_Export:UseTaggedPDF=true,SelectPdfVersion=2` filter.
- **Office app**: uses the provider's PDF/A-3b export variant where the underlying API supports it.

## Tenant Configuration

Configuration is read at request time from `IAppConfig` (no restart required):

| Key | Default | Description |
|---|---|---|
| `filinq.conversion.backends.office_app_enabled` | `true` | Enable/disable Office app backend |
| `filinq.conversion.backends.libreoffice_enabled` | `true` | Enable/disable LibreOffice headless backend |
| `filinq.conversion.backends.phpword_enabled` | `true` | Enable/disable PhpWord backend |
| `filinq.conversion.backends.mpdf_enabled` | `true` | Enable/disable mPDF backend |
| `filinq.conversion.backends.eml_enabled` | `true` | Enable/disable EML backend (no-op until OR EML lands) |
| `filinq.conversion.libreoffice_binary_path` | `soffice` | Path to the `soffice` binary |
| `filinq.conversion.timeout_seconds` | `60` | Per-backend conversion timeout in seconds |

To disable a backend and force the cascade to skip it:

```bash
php occ config:app:set filinq filinq.conversion.backends.libreoffice_enabled --value=false
```

## LibreOffice Headless Serialisation

LibreOffice's `--headless` mode does not handle concurrent invocations safely (user-profile lock contention). The `LibreOfficeHeadlessBackend` acquires a Nextcloud `ILockingProvider` lock keyed `soffice:headless:convert` before invoking `soffice` and releases it when the process exits (success or failure). If the lock cannot be acquired, the backend reports failure and the cascade falls through to the next tier.

## Error Handling

When the cascade exhausts all backends, `ConversionFailedException` is thrown. It exposes:

```php
public function getAttempts(): array;
// Returns: [{name, available, supports, reason}, ...]
```

Consumers (e.g. the anonymise endpoint) use this to construct a structured HTTP 422 body identifying exactly which backends were tried and why each one failed.

## Dependencies

- `mpdf/mpdf ^8.2` — already required for print-preview.
- `phpoffice/phpword ^1.2` — added as part of this change for in-process Word-family conversion.
- LibreOffice (optional) — system-level package; not bundled.
- A Nextcloud Office app (optional) — Collabora, OnlyOffice, or Euro Office.

## Cross-App Dependencies

The `EmlBackend` has a **soft dependency** on OpenRegister's forthcoming `TextExtractionService::extractFile()` support for `message/rfc822`. Until that capability is shipped in OpenRegister, `EmlBackend::isAvailable()` always returns `false` and EML inputs reach a `ConversionFailedException`. Track progress in the `eml-pdf-assembly` OpenSpec change.
