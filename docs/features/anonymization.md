# Anonymization Pipeline

## Overview

DocuDesk provides a 4-step document anonymization pipeline for GDPR-compliant processing. Files are uploaded to a per-user DocuDesk folder, analyzed for personally identifiable information (PII), and anonymized by replacing detected entities with placeholders. All processing runs 100% locally.

## Pipeline Steps

1. **Upload**: Drag-and-drop or select a file to upload to your DocuDesk/ folder
2. **Analyze**: Extract text and detect entities (persons, organizations, locations, etc.)
3. **Anonymize**: Review detected entities and anonymize the document
4. **Done**: Download the anonymized document

## Screenshot

![Anonymization Pipeline](/screenshots/anonymization.png)

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/anonymization/files` | List processed files with entity counts |
| POST | `/api/anonymization/upload` | Upload file (multipart form data) |
| POST | `/api/anonymization/extract/{fileId}` | Extract text and detect entities |
| POST | `/api/anonymization/anonymize/{fileId}` | Anonymize document |

## Per-Entity Legal Bases (grondslagen)

The anonymize endpoint accepts an optional `bases[]` field per entity in the request payload. When present, it is forwarded verbatim to OpenRegister's anonymise endpoint, which persists it on the `EntityRelation` row (paired `entity-relation-grondslagen` change in OpenRegister). DocuDesk does not persist bases locally.

```json
{
  "entities": [
    {
      "text": "Jan Janssen",
      "entityType": "PERSON",
      "bases": ["uuid-of-woo-art5-grondslag"]
    }
  ]
}
```

- `bases[]` is optional per entity; absent/null entries are forwarded without the field.
- An empty array (`bases: []`) is forwarded as `[]`, not omitted.
- DocuDesk does not validate that UUID values resolve; OpenRegister also does not.

## Prohibition Match on Extract Response

The extract endpoint now includes a `prohibitionMatch` field per detected entity. This allows the frontend to render prohibition state without re-running the matcher client-side.

```json
{
  "entities": [
    {
      "type": "PERSON",
      "value": "Jan Janssen",
      "confidence": 0.96,
      "prohibitionMatch": {
        "ruleId": "rule-uuid",
        "ruleName": "Beschermde Getuige A",
        "highConfidence": true
      }
    }
  ]
}
```

- `prohibitionMatch` is `null` when no publication-prohibition rule matches the entity.
- `highConfidence` is `true` when `confidence >= threshold` (inclusive); threshold defaults to 0.85 and is configurable via `docudesk.prohibition.high_confidence_threshold`.
- Requires `PolicyMatchService` from `anonymisation-prohibition-gate`; returns `null` for all entities when that service is not yet installed.

## CHANGELOG

### Added

- Per-entity `bases[]` field accepted on the anonymise request payload and forwarded verbatim to OpenRegister (stored on `EntityRelation`).
- `prohibitionMatch` field on each entity in the extract endpoint response: `null` when no rule matches, or `{ ruleId, ruleName, highConfidence }` when matched.

## appendBasisSummary flag

An optional per-call boolean flag `appendBasisSummary` (default `false`) can be
set on the per-document and batch anonymize endpoints.

| Mode | Effect |
|------|--------|
| `outputFormat: "pdf"` (default) | The grondslagen summary is appended as an extra page to the anonymised PDF. |
| `outputFormat: "preserve"` | A separate `<original-base>_anonymized_grondslagen.pdf` is written alongside the anonymised native-format file. The response gains `summaryFileId` and `summaryFilePath` fields. |

### Opt-in mechanics

Send `appendBasisSummary: true` in the request body. The flag applies to every
file when used on the batch endpoint. Files with no `EntityRelation.bases` data
still receive a summary page with `⟨geen grondslag vastgelegd⟩` placeholders.

### Warning shape on failure

If the summary rendering fails (e.g. the rendering service is unavailable), the
anonymised file is **always preserved** and HTTP 200 is returned. The response
body gains a `warning` field:

```json
{
  "warning": {
    "code": "SUMMARY_APPEND_FAILED",
    "message": "Basis summary could not be appended. The anonymised file is preserved."
  }
}
```

Pre-change clients that do not send `appendBasisSummary` see no behaviour change
and no new fields.

See also: `docs/features/grondslagen-summary.md` (rendering details).

## PDF Output by Default (anonymise-output-format-flag)

The anonymise endpoint now produces **PDF/A-3b output by default**. After OpenRegister
returns the anonymised file in its native format, DocuDesk invokes `PdfConversionService`
(from the `pdf-conversion-service` capability) and atomically replaces the native-format
file in Nextcloud Files with the converted PDF.

### `outputFormat` parameter

Both the single-file and batch anonymise endpoints accept an optional top-level
`outputFormat` field:

| Value      | Behaviour                                          |
|------------|----------------------------------------------------|
| `"pdf"`    | Convert to PDF/A-3b after anonymization (default)  |
| `"preserve"` | Keep native format — identical to pre-change behaviour |

Any other value returns HTTP 400.

The effective format is resolved as: **per-call value → tenant default → `"pdf"`**.

### Tenant default

Administrators can change the default for all requests by setting the IAppConfig key
`docudesk.anonymisation.default_output_format` to `"preserve"` (admin UI follow-up
planned). This lets tenants keep native-format output for existing integrations without
requiring per-call changes.

### Conversion failure — HTTP 422

When `outputFormat` resolves to `"pdf"` and `PdfConversionService` cannot convert the
file (because no backend supports the input type), the endpoint returns **HTTP 422**
with a structured body. The native-format intermediate is deleted so callers never see
a partially-anonymised mixed-format result.

```json
{
  "error": "PDF conversion failed: no backend could handle the file.",
  "conversionAttempts": [
    {"backend": "office_app", "available": false, "supports": true, "reason": "Not installed"},
    {"backend": "libreoffice_headless", "available": false, "supports": true, "reason": "Binary not found"}
  ],
  "outputFormat": "pdf",
  "fallback": "To keep the native format, send outputFormat: 'preserve'."
}
```

### Batch endpoint — HTTP 207 / HTTP 422 / HTTP 200

The batch endpoint (`POST /api/anonymization/batch/{batchId}/anonymize`) honours the
same `outputFormat` field. Per-file conversion failures are tracked separately:

- **HTTP 200** — all files anonymised and converted successfully.
- **HTTP 207 Multi-Status** — some files converted, some failed. Successfully converted
  files remain in NC as PDFs; failed files are not written to NC in any format.
- **HTTP 422** — all files failed conversion.

### Cross-link

See `docs/features/pdf-conversion.md` for the conversion cascade and per-backend
configuration (Office app → LibreOffice headless → PhpWord → mPDF → EML).

## Technical Details

- Files stored in Nextcloud filesystem under user's `DocuDesk/` folder
- Entity detection via OpenRegister's TextExtractionService (Presidio/OpenAnonymiser)
- Anonymization via OpenRegister's FileService
- Duplicate file names handled with counter suffix (e.g., `report_1.pdf`)
- PDF conversion delegated to `PdfConversionService` (pdf-conversion-service capability)
