## Why

Anonymisation is a privacy operation. Today it produces output in the same format as the input — DOCX in, DOCX out — which carries two real privacy risks:

1. **Easy un-redaction.** A DOCX is trivially editable: an operator (or recipient) can delete `[PERSON_1]` and re-type the original text. The redaction acts as a recommendation rather than a control.
2. **Metadata leakage.** Word documents retain track-changes history, embedded comments, author metadata, and edit timelines. Even when the visible text is anonymised, these channels can still expose the original entities. Same problem applies to EML attachments, ODT properties, etc.

PDF rendering flattens the document: text becomes glyph stream, metadata is largely shed (especially when targeting PDF/A), edit affordances are reduced. It's not a perfect lock — PDFs can still be edited with specialist tools — but it raises the bar from "trivially re-editable" to "deliberately re-editable", which is the right default for a redacted artifact.

This change makes PDF the **default** output format for the anonymise endpoint, with an explicit opt-out (`outputFormat: "preserve"`) for callers that have a documented reason to keep the native format. It introduces a small in-app conversion service that cascades through Office app integrations → LibreOffice headless → PhpWord (with mPDF as the PDF backend) before failing with a clear 422.

This is the prerequisite to the `anonymisation-grondslagen-summary` change, which wants to append a basis-summary page to the anonymised output — once the output is reliably PDF, that append is straightforward.

## What Changes

- **NEW:** `PdfConversionService` in DocuDesk that converts arbitrary supported input files to PDF (or PDF/A-3b — see Decisions). The service tries backends in order: Nextcloud Office (Collabora) / OnlyOffice via the configured doc-converter API → LibreOffice headless on the host → PhpWord-via-mPDF for documents PhpWord can read (DOCX, ODT, RTF, HTML) → mPDF directly for HTML/TXT → OR's text-extractor for EML (when that capability lands in OpenRegister).
- **NEW dep:** `phpoffice/phpword` added to DocuDesk's `composer.json`. Uses the existing `mpdf/mpdf` (already a dep for `pdf-generation`) as the PdfWriter backend. `phpoffice/phpspreadsheet` is **not** added — XLSX/ODS support relies on Office app or LibreOffice fallback only, with 422 if neither is available.
- **MODIFIED:** The anonymise endpoint accepts an optional top-level `outputFormat: "pdf" | "preserve"` field, default `"pdf"`. When `"pdf"`, after OpenRegister returns the anonymised file in its native format, DocuDesk converts it to PDF and replaces the file in Nextcloud Files. When `"preserve"`, the existing pre-change behaviour applies (native format kept).
- **MODIFIED:** The anonymise endpoint surfaces conversion failures as **HTTP 422** with a structured body — input format unsupported, no backend available, conversion library threw. The 422 path NEVER produces a partially-anonymised mixed-format result; the anonymised native file is rolled back / removed and the operator is notified.
- **NO breaking change for callers that don't set `outputFormat`:** they get the new PDF default. Callers that need the old behaviour explicitly send `outputFormat: "preserve"`.
- **NEW config:** Admin setting `docudesk.anonymisation.default_output_format` (`pdf` | `preserve`, default `pdf`) to let tenants flip the default at the install level. The per-call flag overrides the tenant default.

### Conversion cascade (default order)

1. **Nextcloud Office (Collabora)** if installed and configured.
2. **OnlyOffice DocConverter** if installed and configured.
3. **LibreOffice headless** if available on the host (`soffice --headless --convert-to pdf`).
4. **PhpWord + mPDF** for inputs PhpWord can read (`.docx`, `.odt`, `.rtf`, `.html`).
5. **mPDF directly** for inputs that don't need a Word/Office processor: `.html`, `.txt`. (HTML preferred over PhpWord for plain HTML.)
6. **OR text-extractor + mPDF** for `.eml` (depends on a future OpenRegister change that adds EML text extraction; until that lands, EML inputs fall through to 422).
7. **None of the above:** 422 with a clear error explaining which backends were tried and why each was unavailable.

The cascade is described in the order tried; tenants can disable individual backends via config (e.g. an Office-app-first install can disable LibreOffice / PhpWord).

### Out of scope

- **PhpSpreadsheet support** for XLSX/ODS conversion — not added in this change. Spreadsheet inputs rely on Office app / LibreOffice or fail with 422.
- **Input normalisation** before sending to OpenAnonymiser — out of scope. This change only converts the *output* of anonymisation; the input is whatever the existing pipeline already accepts.
- **Format-specific layout for EML** beyond plain extracted text — the EML branch produces a `<pre>`-wrapped plaintext PDF until a richer template is designed (tracked as a follow-up).
- **PDF/A vs plain PDF as a per-call choice** — defaults to PDF/A-3b for archival compliance (matching `print-preview`); plain PDF is not exposed as an option in this change. If a tenant needs plain PDF, follow-up.
- **Custom rendering for the resulting PDF** (logo, header, footer, watermark) — out of scope; the conversion is content-faithful.
- **Migration / re-conversion of past anonymised files** — out of scope; this change only affects new anonymisation calls.

## Capabilities

### New Capabilities

- `pdf-conversion`: a file-to-PDF conversion service with the documented backend cascade, tenant-configurable backend order, PDF/A-3b output, and 422 error semantics.

### Modified Capabilities

- `anonymization`: the anonymise endpoint accepts an optional top-level `outputFormat: "pdf" | "preserve"` (default `pdf`); when `pdf`, the result is converted via the new `pdf-conversion` capability before being written to Nextcloud Files.

## Cross-app Dependencies

- **Soft** — `openregister:text-extraction-eml` (future) — provides EML text extraction. Until it lands, EML inputs fail with 422 in default `pdf` mode; operators bypass via `outputFormat: "preserve"`. The hard dep on the same OR change is owned by the consumer change `docudesk:eml-pdf-assembly`.

Track as a `Depends on` link from this change's GitHub issue once the OR-side tracking issue exists.

## Impact

- **Code (docudesk):**
  - `lib/Service/PdfConversionService.php` — NEW. Implements the cascade. One method `convertToPdf(File $source, array $opts = []): File` returning the converted file or throwing a typed exception.
  - `lib/Service/Conversion/` — NEW directory. One backend per file: `OfficeAppBackend.php` (Collabora/OnlyOffice via NC's converter abstraction), `LibreOfficeHeadlessBackend.php`, `PhpWordBackend.php`, `MpdfBackend.php`, `EmlBackend.php`. Each implements a small `ConversionBackendInterface { canHandle(string $mime): bool; convert(File $source): File }`.
  - `lib/Controller/AnonymizationController.php` and `BatchAnonymizationController.php` — accept `outputFormat`; on `pdf`, invoke `PdfConversionService` after OR's anonymise returns; on conversion failure, return 422 + structured body.
  - `lib/Service/AnonymizationService.php` and `BatchAnonymizeService.php` — orchestrate the conversion call, error rollback (delete the un-converted intermediate if conversion fails).
  - `composer.json` — add `phpoffice/phpword` (^1.2 to match OR's existing version).
  - `lib/Settings/admin/SettingsController.php` (or equivalent admin settings surface) — expose the `default_output_format` setting.
  - Test fixtures: small DOCX, ODT, RTF, TXT, EML inputs.
- **API contract:** Anonymise endpoint payload gains a top-level optional `outputFormat: "pdf" | "preserve"` (default `pdf`). Response on success is unchanged in shape (file metadata, replacement count) — the file written to NC is now a PDF when default is in effect. New possible 422 response with `conversionError` body shape.
- **Cross-app:**
  - Soft dependency on a future OpenRegister change adding EML text extraction. Until that lands, EML inputs fail with 422 in the default `pdf` mode (operator can use `outputFormat: "preserve"` to bypass).
  - No change to the `entity-relation-grondslagen` change (OR-side bases-strip happens before this conversion runs).
  - Paired with the upcoming `anonymisation-grondslagen-summary` change — that change appends a summary page to the converted PDF. This change is its prerequisite.
- **Privacy / compliance:** Strengthens the "redacted artifact is hard to un-redact" guarantee. PDF/A-3b is archival-compliant (Wet open overheid retention friendly). Default behaviour change is a privacy-positive default.
- **Performance:** Conversion adds latency per file. Office-app backends are typically fast (sub-second for small docs); LibreOffice headless is the slowest path (process spawn + render, often 1-3 seconds for non-trivial docs); PhpWord+mPDF is in between. For batch flows this can compound — call out in design.md as a known performance consideration.
- **Migration:** None — additive default. Operators that need the old behaviour add `outputFormat: "preserve"` to their calls.
- **Tests:** Unit tests per backend (canHandle + convert); integration tests for the cascade (which backend wins under different install configurations); end-to-end tests for the anonymise endpoint with default + opt-out + 422 paths.
