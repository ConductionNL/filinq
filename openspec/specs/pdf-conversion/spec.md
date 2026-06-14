# pdf-conversion Specification

## Purpose
TBD - created by archiving change pdf-conversion-service. Update Purpose after archive.
## Requirements
### Requirement: A `PdfConversionService` MUST exist that converts files to PDF/A-3b

The service MUST expose a single public method:

```php
public function convertToPdf(File $source, array $opts = []): File;
```

It MUST accept any Nextcloud `File` and return a converted PDF `File`. The output MUST be PDF/A-3b. On failure, the service MUST throw a typed `ConversionFailedException` whose payload includes a per-backend list of `{name, available, supports, reason}` so consumers can render structured error responses.

#### Scenario: Service converts a DOCX file to PDF/A-3b

- **GIVEN** a Nextcloud `File` containing a valid DOCX document
- **AND** at least one capable backend is available
- **WHEN** `convertToPdf($file)` is called
- **THEN** the returned `File` is a PDF
- **AND** the PDF declares PDF/A-3b conformance in its metadata
- **AND** the source file is unchanged

#### Scenario: Service throws on unsupported input

- **GIVEN** an input file whose MIME type no available backend can handle (e.g. an XLSX with no Office app and no LibreOffice installed)
- **WHEN** `convertToPdf($file)` is called
- **THEN** the service throws `ConversionFailedException`
- **AND** the exception's payload includes one entry per backend tried with the reason it didn't apply
- **AND** no output file is created

#### Scenario: Service throws on backend runtime error

- **GIVEN** a backend that `canHandle()` the input and `isAvailable()` returns true
- **WHEN** `convert()` throws (e.g. mPDF runs out of memory, soffice exits non-zero)
- **THEN** the cascade tries the next backend
- **AND** if all subsequent backends also fail or don't apply, `ConversionFailedException` is thrown with all attempted backends listed

### Requirement: The service MUST walk backends in cascade order

The default cascade order MUST be: (1) Office app (Collabora / OnlyOffice), (2) LibreOffice headless, (3) PhpWord + mPDF, (4) mPDF directly, (5) OR-EML-extractor + mPDF. The first backend that `isAvailable()` AND `canHandle(mime, ext)` AND `convert()` succeeds wins. The service MUST short-circuit on first success.

#### Scenario: Office app wins for DOCX when configured

- **GIVEN** Collabora is installed and configured
- **AND** a DOCX input
- **WHEN** the cascade runs
- **THEN** the Office app backend converts and returns; LibreOffice / PhpWord / mPDF backends are not invoked

#### Scenario: PhpWord wins for ODT on bare install

- **GIVEN** no Office app and no LibreOffice are available
- **AND** an ODT input
- **WHEN** the cascade runs
- **THEN** the PhpWord+mPDF backend handles the conversion

#### Scenario: mPDF handles HTML directly

- **GIVEN** an HTML input on a bare install (no Office app, no LibreOffice)
- **AND** PhpWord+mPDF is enabled (it can also handle HTML)
- **WHEN** the cascade runs
- **THEN** EITHER PhpWord+mPDF OR the direct mPDF backend handles the conversion (deterministic per cascade order — PhpWord+mPDF tried first since it appears earlier in the cascade for HTML)
- **AND** subsequent backends are not invoked

### Requirement: Each backend MUST implement `ConversionBackendInterface`

Backends MUST implement:

```php
interface ConversionBackendInterface {
    public function name(): string;
    public function isAvailable(): bool;
    public function canHandle(string $mimeType, string $extension): bool;
    public function convert(File $source): File;
}
```

`name()` returns a stable identifier (e.g. `"libreoffice_headless"`) used in logs and 422 response bodies. `isAvailable()` is a runtime + tenant-config check. `canHandle()` declares which inputs the backend supports. `convert()` performs the conversion or throws.

#### Scenario: Backend reports unavailable when disabled by tenant config

- **GIVEN** `docudesk.conversion.backends.libreoffice_enabled` is `false`
- **WHEN** the LibreOffice backend's `isAvailable()` is called
- **THEN** it returns false without attempting any runtime probe

#### Scenario: Backend reports unavailable when binary missing

- **GIVEN** the configured `docudesk.conversion.libreoffice_binary_path` does not resolve to an executable
- **WHEN** the LibreOffice backend's `isAvailable()` is called
- **THEN** it returns false
- **AND** the cascade proceeds to the next backend

#### Scenario: Backend cannot handle unsupported MIME

- **GIVEN** the PhpWord backend
- **AND** an XLSX input
- **WHEN** `canHandle('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx')` is called
- **THEN** it returns false

### Requirement: Output MUST be PDF/A-3b — backends that cannot guarantee this MUST fall through

A backend that can produce only plain PDF (not PDF/A-3b) MUST report `isAvailable()` as false for the conversion or throw from `convert()`, allowing the cascade to try the next backend. The service MUST NOT silently degrade to plain PDF.

#### Scenario: A backend that emits plain PDF is skipped

- **GIVEN** a hypothetical backend that produces plain PDF only
- **WHEN** the cascade evaluates it for a conversion
- **THEN** it MUST behave as if unavailable (or throw on convert)
- **AND** the cascade tries the next backend
- **AND** if all backends fail to produce PDF/A-3b, `ConversionFailedException` is thrown

### Requirement: Tenant configuration MUST control backend availability and order

The service MUST read tenant-level configuration to enable / disable backends and tune timeouts. The configuration keys MUST be:

| Key | Default | Purpose |
|---|---|---|
| `docudesk.conversion.backends.office_app_enabled` | `true` | Office app backend on/off |
| `docudesk.conversion.backends.libreoffice_enabled` | `true` | LibreOffice headless on/off |
| `docudesk.conversion.backends.phpword_enabled` | `true` | PhpWord + mPDF on/off |
| `docudesk.conversion.backends.mpdf_enabled` | `true` | mPDF direct on/off |
| `docudesk.conversion.backends.eml_enabled` | `true` | OR-EML-extractor backend on/off |
| `docudesk.conversion.libreoffice_binary_path` | `soffice` | Path to soffice binary |
| `docudesk.conversion.timeout_seconds` | `60` | Per-backend timeout |

The service MUST honour these at request time (no restart needed for config changes).

#### Scenario: Disabled backend is skipped

- **GIVEN** `docudesk.conversion.backends.office_app_enabled` is `false`
- **WHEN** a conversion runs with a DOCX input
- **THEN** the Office app backend is not consulted (no HTTP probe)
- **AND** the cascade starts at LibreOffice

#### Scenario: Backend timeout is enforced

- **GIVEN** `docudesk.conversion.timeout_seconds` is `10`
- **AND** a backend's `convert()` runs longer than 10 seconds
- **WHEN** the cascade is processing the input
- **THEN** the conversion attempt is terminated
- **AND** the backend is treated as failed
- **AND** the cascade proceeds to the next backend

### Requirement: LibreOffice headless invocations MUST be serialised

LibreOffice's `--headless` mode has known issues with concurrent invocations (user-profile lock contention). The LibreOffice backend MUST acquire a Nextcloud locking primitive (e.g. `ILockingProvider` keyed by `soffice:headless:convert`) before invoking `soffice`, and release it after. Failure to acquire the lock within a reasonable timeout MUST be treated as a backend failure (cascade proceeds to the next backend).

#### Scenario: Concurrent calls serialise

- **GIVEN** two simultaneous conversions both reach the LibreOffice backend
- **WHEN** both attempt to call `soffice` at the same time
- **THEN** one of them holds the lock and runs first
- **AND** the second waits up to the configured timeout
- **AND** within the lock, only one `soffice` process runs at a time

### Requirement: PhpWord backend MUST support the formats PhpWord can read

The PhpWord backend's `canHandle()` MUST return true for `application/vnd.openxmlformats-officedocument.wordprocessingml.document` (DOCX), `application/vnd.oasis.opendocument.text` (ODT), `application/rtf` / `text/rtf` (RTF), and `text/html` (HTML). It MUST return false for everything else, including legacy DOC (`application/msword`) — PhpWord's `MsDoc` reader is too unreliable for production use.

#### Scenario: PhpWord supports DOCX, ODT, RTF, HTML

- **WHEN** `canHandle()` is called for each of those MIME types
- **THEN** it returns true

#### Scenario: PhpWord rejects legacy DOC

- **WHEN** `canHandle('application/msword', 'doc')` is called
- **THEN** it returns false (cascade continues to next backend, or 422 if none)

### Requirement: EML backend MUST depend on OpenRegister's text-extraction service

The EML backend's `isAvailable()` MUST check whether OpenRegister exposes EML extraction (via the `OCA\OpenRegister\Service\TextExtractionService::extractFile()` path or equivalent for `message/rfc822`). Until that capability lands in OpenRegister, the EML backend reports unavailable and EML inputs fall through to 422 in the default `pdf` mode.

#### Scenario: EML backend unavailable until OR change lands

- **GIVEN** OpenRegister's TextExtractionService does not yet support EML
- **WHEN** the EML backend's `isAvailable()` is called
- **THEN** it returns false

#### Scenario: EML backend converts via extracted text once OR supports EML

- **GIVEN** OpenRegister's TextExtractionService supports EML
- **AND** an EML input file
- **WHEN** the EML backend's `convert()` is called
- **THEN** the service extracts the EML body via OR
- **AND** wraps the extracted text in a minimal HTML wrapper
- **AND** renders to PDF/A-3b via mPDF
- **AND** returns the PDF file

### Requirement: `ConversionFailedException` MUST carry structured error data

When the cascade fails, the thrown exception MUST expose a method (e.g. `getAttempts(): array`) returning a list of `{name, available, supports, reason}` per backend. Consumers (e.g. the anonymise endpoint) use this to construct structured 422 response bodies.

#### Scenario: Exception payload lists every attempted backend

- **GIVEN** a conversion that fails because no backend handles the input
- **WHEN** `ConversionFailedException` is caught
- **THEN** `getAttempts()` returns an entry for each backend in cascade order
- **AND** each entry includes the backend name, availability, supports flag, and a human-readable reason

