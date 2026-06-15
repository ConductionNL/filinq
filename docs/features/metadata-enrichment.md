# Metadata Enrichment

## Overview

DocuDesk automatically enriches document metadata when documents are created or updated in OpenRegister. Enrichment includes language detection, keyword extraction, topic classification, document type standardization, and date normalization. All processing runs locally using heuristic algorithms.

## Enrichment Pipeline

| Step | Description | Feature Toggle |
|------|-------------|----------------|
| Language Detection | Detect nl/en via word frequency analysis | `enable_language_detection` |
| Keyword Extraction | Extract top 10 non-stop-word keywords | `enable_keyword_extraction` |
| Topic Classification | Classify as legal, financial, medical, or technical | `enable_topic_classification` |
| Document Type | Standardize types (doc->word, xlsx->spreadsheet) | Always on |
| Date Normalization | Normalize date fields to ISO 8601 | Always on |

## API

- `POST /apps/docudesk/api/metadata/enrich` - Trigger enrichment for a document object

### Request Body

```json
{
  "objectId": "uuid-123",
  "register": "register-id",
  "schema": "schema-id",
  "objectData": { "text": "Document content..." }
}
```

## Event-Driven Processing

Enrichment runs automatically via the `DocuDeskEventListener` when OpenRegister fires `ObjectCreatedEvent` or `ObjectUpdatedEvent`. Feature toggles in admin settings control which enrichments are active.
