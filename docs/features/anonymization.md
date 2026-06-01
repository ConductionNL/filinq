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

## Batch Output Folder Layout

### File layout (batch / folder flows)

Batch and folder-based anonymisation routes write redacted outputs to a
**subfolder** inside the source folder, with clean filenames:

```
Before (legacy):              After (current):
<dossier>/                    <dossier>/
  report.pdf                    report.pdf
  report_anonymized.pdf         anonymised/
  letter.docx                     report.pdf
  letter_anonymized.docx          letter.pdf
```

The subfolder name defaults to `anonymised` and is tenant-configurable.

### Config key

| Key | Default | Allowed values |
|-----|---------|---------------|
| `docudesk.anonymisation.output_subfolder_name` | `anonymised` | Non-empty, `[a-z0-9_-]+` |

Set via the admin settings panel. Invalid values are rejected at save time with
a message identifying the disallowed characters.

### Behavior details

- **Subfolder created on first run** — DocuDesk creates `<source>/anonymised/`
  automatically if it does not exist.
- **Second run overwrites** — A re-run reuses the existing subfolder and
  overwrites files by destination filename. Files not in the current run's
  source set are left untouched (no auto-cleanup).
- **Legacy `_anonymized` suffix stripped** — A file ending in `_anonymized`
  before the extension has its suffix removed in the destination:
  `foo_anonymized.pdf` → `<subfolder>/foo.pdf`.
- **Single-file flow unchanged** — The per-document anonymise endpoint
  continues to write `<file>_anonymized.<ext>` in the same folder as the
  source. The subfolder layout applies only to batch / folder flows.
- **Move failure is non-fatal** — If the post-process move fails (permissions,
  quota), the file is preserved at OR's legacy output path. The response
  includes a `warning` with `code: "MOVE_FAILED"` and the `anonymizedFilePath`
  field points to the legacy location.
- **`anonymizedFilePath` always reflects the actual location** — Clients that
  read this field from API responses work without code changes; the path simply
  changes to the subfolder location.

### Source-discovery filter

When creating a batch from an existing folder, files whose base name (without
extension) ends with `_anonymized` are **excluded** from the source set. These
are legacy outputs from a previous batch run that should not be silently
re-anonymised.

> **Edge case:** A genuine source file named `foo_anonymized.pdf` (rare) is
> also excluded by this filter. Rename it first, or use the per-file anonymise
> endpoint which does not apply the filter.

## Technical Details

- Files stored in Nextcloud filesystem under user's `DocuDesk/` folder
- Entity detection via OpenRegister's TextExtractionService (Presidio/OpenAnonymiser)
- Anonymization via OpenRegister's FileService
- Duplicate file names handled with counter suffix (e.g., `report_1.pdf`)
- Batch output subfolder name configurable via `docudesk.anonymisation.output_subfolder_name`
