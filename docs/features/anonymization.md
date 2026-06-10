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
