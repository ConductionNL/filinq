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

## Technical Details

- Files stored in Nextcloud filesystem under user's `DocuDesk/` folder
- Entity detection via OpenRegister's TextExtractionService (Presidio/OpenAnonymiser)
- Anonymization via OpenRegister's FileService
- Duplicate file names handled with counter suffix (e.g., `report_1.pdf`)

## Source ↔ anonymised file link (anonymizationLink)

Every **successful** anonymisation run records a durable mapping between the
original (source) file and its anonymised counterpart as an `anonymizationLink`
object in the `document` register, so the relationship can be resolved in both
directions via OpenRegister's search API without re-running analysis.

- **Idempotent on `sourceFileId`**: one record per source file; re-anonymisation
  updates that record (preserving its `@self`) and increments `runCount`.
- **Success only**: failed runs are not recorded; `status` is always `anonymized`.
- **Best-effort**: a persistence failure is logged at `warning` and never aborts
  the anonymisation response. On success the response carries `anonymizationLinkId`.

`sourceFileId` and `anonymizedFileId` are `facetable`, enabling:

```
# Forward — anonymised file for a given source
GET /apps/openregister/api/objects/document/anonymizationLink?sourceFileId=<NC_FILE_ID>
# Reverse — source file for a given anonymised file
GET /apps/openregister/api/objects/document/anonymizationLink?anonymizedFileId=<NC_FILE_ID>
```

The file-viewer sidebar consumes the **reverse** direction: when a file is
opened it first resolves its `anonymizationLink` by `anonymizedFileId`. A hit
means the file is an anonymised output, so the sidebar shows the read-only
'removed items' review instead of restarting the un-anonymised detection flow.
The header subtitle names the source ('Anonymised version of <source>').

The per-entity list is resolved from the `[<TYPE>: <id>]` placeholders baked
into the file's text (each id is looked up read-only via
`GET /api/entities/{id}`). When the text carries no readable placeholders
(e.g. a flattened PDF), the sidebar shows a read-only summary from the link
object only (replacement count + source name); it does **not** re-extract,
because the extract endpoint mutates (appends) the entity store and opening a
file for viewing must stay read-only.

## Output format (PDF by default)

Since the `anonymise-output-as-pdf-by-default` change, the anonymise endpoints produce **PDF/A-3b** output by default. PDF flattens the redaction into a glyph stream and strips most metadata channels that would otherwise still name the original entities — making the anonymisation harder to revert by editing the file. Since the `anonymise-pdf-only-output-mode` change, the default additionally **deletes the native-format anonymised intermediate** so a re-editable copy of the redacted document is not left behind.

### Per-call override: `outputFormat`

The `POST /api/anonymization/anonymize/{fileId}` and `POST /api/anonymization/batch/{batchId}/anonymize` endpoints accept an optional top-level `outputFormat` field with three values:

| Value | Convert to PDF? | Keep native anonymised file? | Notes |
|---|---|---|---|
| `"pdf-only"` (default) | yes | no — deleted after a successful conversion | the privacy-correct default; only the PDF remains |
| `"pdf"` | yes | yes | converts but keeps the native intermediate alongside the PDF |
| `"preserve"` | no | yes (native is the only file) | native input format out (DOCX in → DOCX out, etc.). Legacy behaviour |

`pdf-only` is `pdf` plus a **best-effort** delete of the native intermediate on the success path: a cleanup-delete failure is logged at warning level and never fails the run. When the anonymised result is already a PDF, the conversion cascade is skipped, so no native intermediate is created and `pdf-only` behaves identically to `pdf`.

If `outputFormat` is supplied but is not `"pdf-only"`, `"pdf"`, or `"preserve"`, the endpoint returns `HTTP 400`.

### Tenant default

The tenant-wide default is configurable via the **Anonymisation → Always export anonymised documents as PDF** switch in the admin settings panel. The underlying `IAppConfig` key is `docudesk.anonymisation.default_output_format` (values: `"pdf-only"` | `"pdf"` | `"preserve"`, default `"pdf-only"`). A per-call `outputFormat` always overrides the tenant default. To restore the previous keep-both behaviour tenant-wide, set the key to `"pdf"`.

### Conversion cascade

When `outputFormat` is `"pdf-only"` or `"pdf"` and the anonymised file isn't already a PDF, the `PdfConversionService` walks an ordered list of backends. First success wins; total failure aggregates into an `HTTP 422` response with the per-backend attempt records.

1. **`OfficeAppBackend`** — uses Nextcloud's `OCP\Files\Conversion\IConversionManager` (NC 31+). Collabora, OnlyOffice, and Euro Office integrations register as conversion providers under this single API; the backend dispatches to whichever app is installed and configured. Highest fidelity for Word-family documents.
2. **`PhpWordBackend`** — in-process. Reads DOC (MsDoc), DOCX (Word2007), ODT (ODText), RTF, and HTML via `PhpOffice\PhpWord`; emits PDF/A-3b via the PdfWriter (mPDF-backed). Lower fidelity than a real Office engine but covers all Word-family formats without external dependencies.
3. **`MpdfBackend`** — in-process. Handles HTML and plain-text directly via mPDF, reusing the print-preview PDF/A-3b configuration. TXT inputs are wrapped in a minimal `<pre>` envelope to preserve whitespace.
4. **`EmlBackend`** — currently stubbed. Activates once OpenRegister adds `message/rfc822` text extraction; until then, EML inputs in `pdf` mode fall through to 422.

Spreadsheet (XLSX, ODS, CSV with layout) and presentation (PPTX, ODP) formats are **out of scope** for the in-process tiers. They rely on the `OfficeAppBackend` route only — without a configured Office app, those inputs return 422.

### `HTTP 422` body shape

When conversion fails for every backend, the response body documents which were tried and why:

```json
{
  "error": "Conversion to PDF failed; anonymisation rolled back.",
  "conversionAttempts": [
    {"name": "office_app", "available": false, "supports": false, "reason": "backend disabled or prerequisites not present"},
    {"name": "phpword",    "available": true,  "supports": false, "reason": "backend does not support MIME application/vnd.ms-excel / extension xls"},
    {"name": "mpdf",       "available": true,  "supports": false, "reason": "backend does not support MIME application/vnd.ms-excel / extension xls"},
    {"name": "eml",        "available": false, "supports": false, "reason": "backend disabled or prerequisites not present"}
  ],
  "outputFormat": "pdf",
  "fallback": "Set outputFormat to \"preserve\" to bypass conversion if you must keep the native format."
}
```

The un-converted anonymised intermediate is best-effort deleted before the 422 response is returned. The operator never sees a half-finished mixed-format result.

### Batch path

`batchAnonymize` accepts the same `outputFormat`. Per-file conversion failure is recorded as an `error` on that file's batch entry (with a `conversionAttempts` array) and the batch continues with the next file rather than aborting.

## Placeholder-numbering scope (`scope`)

Anonymisation replaces each detected entity with a numbered placeholder (e.g. `[PERSOON: 1]`, `[DATUM: 3]`). The `scope` field controls how those numbers are assigned — whether each file is numbered on its own, or a whole folder/dossier shares one consistent numbering so the **same** entity carries the **same** number across every file in the set. The flag is forwarded to OpenRegister, which owns the numbering.

| Value | Behaviour |
|---|---|
| `"document"` | Numbers are local to the single file being anonymised. `[PERSOON: 1]` in file A and `[PERSOON: 1]` in file B are unrelated. |
| `"dossier"` | Numbers are consistent across the dossier folder: a given entity gets the same number in every file of the dossier, so the redacted set reads as one unit. |

### Defaults differ per endpoint

The default matches the typical unit of work, so you usually don't pass `scope` at all:

| Endpoint | Default `scope` | Rationale |
|---|---|---|
| `POST /api/anonymization/anonymize/{fileId}` | `"document"` | A single-file call anonymises one file in isolation. |
| `POST /api/anonymization/batch/{batchId}/anonymize` | `"dossier"` | A batch **is** a folder/dossier, so its files share one numbering. |

Normalisation is lenient and never errors on an unrecognised value: on the single-file endpoint only `"dossier"` selects dossier scope (anything else, including omitted, is per-document); on the batch endpoint only `"document"` selects per-document scope (anything else, including omitted, is dossier).

### `dossierKey` (single-file endpoint)

On `POST /api/anonymization/anonymize/{fileId}`, an optional `dossierKey` names the folder the file belongs to when `scope: "dossier"`. It is the stable identifier of the dossier folder, so numbering stays consistent across separate single-file calls that target the same dossier. When omitted under `scope: "dossier"`, OpenRegister falls back to the file's parent folder. `dossierKey` has no effect under `scope: "document"`.

### Examples

Anonymise one file, numbered on its own (the default — `scope` may be omitted):

```json
POST /api/anonymization/anonymize/123
{ "entities": [ ... ], "scope": "document" }
```

Anonymise one file as part of a dossier, sharing the dossier's numbering:

```json
POST /api/anonymization/anonymize/123
{ "entities": [ ... ], "scope": "dossier", "dossierKey": "dossier-7f3a..." }
```

Anonymise a batch with per-file (independent) numbering, overriding the batch default:

```json
POST /api/anonymization/batch/abc123/anonymize
{ "entities": [ ... ], "scope": "document" }
```

## API Endpoints (extended)

| Method | URL | Description |
|--------|-----|-------------|
| POST | `/api/anonymization/anonymize/{fileId}` | Anonymise a single file. Body supports `entities`, `outputFormat`, `appendBasisSummary`, `excludeTypes`, `minConfidence`, `scope` (default `document`), `dossierKey`. |
| POST | `/api/anonymization/batch/{batchId}/anonymize` | Anonymise every file in a batch. Body supports `entities`, `outputFormat`, `appendBasisSummary`, `scope` (default `dossier`). |
