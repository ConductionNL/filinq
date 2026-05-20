## Context

DocuDesk is a Nextcloud app for managing sensitive government documents. The anonymization pipeline fills a critical gap: civil servants handling WOO requests, GDPR data-subject access requests, or archival preparation need a local, GDPR-safe tool to strip personally identifiable information from documents before release. The pipeline must run entirely within the Nextcloud instance — no external HTTP calls — and must degrade gracefully when OpenRegister is not installed or temporarily unavailable.

## Goals / Non-Goals

**Goals:**

- Store uploaded documents in a user-scoped `DocuDesk/` folder in Nextcloud Files.
- Detect PII entities (PERSON, ORGANIZATION, EMAIL, PHONE) via OpenRegister's NER pipeline.
- Replace detected entities with UUID v4 placeholder tokens, producing an anonymized copy of the document.
- List processed files with entity counts, anonymization status, and risk level.
- Provide a drag-and-drop 4-step wizard UI with sequential queue processing.
- Degrade gracefully when OpenRegister is unavailable (lazy service resolution, caught exceptions).

**Non-Goals:**

- Custom NER model training — entity detection delegates entirely to OpenRegister.
- Batch anonymization (separate change scope).
- Prohibition gate for protected persons (separate `anonymisation-prohibition-gate` change).
- Grondslagen (legal basis) passthrough (separate `anonymisation-bases-passthrough` change).
- Persistent anonymization jobs / async queue — pipeline runs synchronously per request.

## Decisions

### D1. Controller → Service → OpenRegister layering (ADR-003)

`AnonymizationController` is thin: validates input, calls a service, returns a `JSONResponse`. All business logic lives in the service layer. OpenRegister is called only from services, never from the controller. This keeps the controller testable without OpenRegister and keeps business logic out of HTTP handling.

### D2. Lazy OpenRegister service resolution

OpenRegister services are resolved via `ContainerInterface::get()` at call time, not injected at constructor time. Before resolution, `IAppManager::getInstalledApps()` checks that OpenRegister is present. If absent, a `\RuntimeException` is thrown. `listProcessedFiles()` catches this exception and returns default values (`entityCount: 0`, `riskLevel: "unknown"`) so the file list remains functional without OpenRegister.

**Trade-off:** each call re-resolves the service. Acceptable overhead for the request rates DocuDesk sees; avoids constructor injection that would make OpenRegister a hard dependency at app boot.

### D3. UUID v4 keys for entity replacement tokens

Each entity mapped for anonymization receives a unique UUID v4 string (`random_bytes(16)` + RFC 4122 version/variant bits). The placeholder format is `[TYPE: uuid]` (e.g., `[PERSON: a1b2c3d4-...]`). UUIDs are not linked back to the original entity text outside the anonymization mapping returned in the API response.

**Rationale:** UUID v4 with 122 bits of entropy is collision-resistant and cryptographically unpredictable, preventing reverse-engineering of redacted values from the output document.

### D4. Entity filtering before anonymization

Before building the UUID mapping, `EntityDetectionService` filters the entity list:
1. Skip values shorter than 3 characters (avoids replacing common abbreviations like "de", "AB").
2. Skip purely numeric values (PHP array key type coercion; numeric strings become integer keys).
3. Deduplicate by value using a seen-set (avoids double-replacement and mapping bloat).

### D5. Two distinct EntityRelationMapper methods

- **`findEntitiesForFile()`**: used after extraction to retrieve rich entity data (type, value, confidence). Returns entity objects.
- **`findByFileId()`**: used in file listing to count relations. Each relation exposes `getAnonymized()` for per-entity tracking. Returns relation records.

Using the wrong method in either context would return incompatible shapes; this distinction is enforced in the spec and tested.

### D6. Pinia store with sequential file queue

The frontend store (`src/store/modules/anonymization.js`) manages a file array where each entry transitions through statuses: `queued → uploading → extracting → anonymizing → completed` (or `error`). Files are processed one at a time. Getters — `hasFiles`, `hasCompleted`, `allDone`, `isProcessing` — drive UI visibility. Errors set the file to `error` status and advance the queue.

## Architecture

### Backend

```
AnonymizationController
  ├── upload()           → FileUploadService::uploadFile()
  ├── files()            → FileListingService::listProcessedFiles()
  ├── extract()          → AnonymizationService::extractAndDetectEntities()
  └── anonymize()        → AnonymizationService::anonymizeDocument()

AnonymizationService
  ├── extractAndDetectEntities()
  │     ├── OpenRegister::TextExtractionService::extractFile()
  │     ├── OpenRegister::EntityRelationMapper::findEntitiesForFile()
  │     └── EntityDetectionService::normalizeEntities()
  └── anonymizeDocument()
        ├── EntityDetectionService::buildAnonymizationMapping()
        └── OpenRegister::FileService::anonymizeDocument()

FileListingService::listProcessedFiles()
  ├── IRootFolder (user DocuDesk/ folder scan)
  ├── OpenRegister::EntityRelationMapper::findByFileId()
  └── OpenRegister::RiskLevelService::getRiskLevel()

FileUploadService::uploadFile()
  └── IRootFolder (getOrCreate DocuDesk/, deduplicate name, put file)
```

### Frontend

```
AnonymizationWidget.vue
  └── anonymization (Pinia store)
        ├── uploadFile()
        ├── extractEntities()
        ├── anonymizeDocument()
        └── processQueue()   ← sequential, one file at a time
```

### Data Flow

1. **Upload**: user drops file → `POST /api/anonymization/upload` → `FileUploadService` creates/finds `DocuDesk/` folder, writes file, returns `{fileId, fileName, filePath, fileSize}`.
2. **Extract**: `POST /api/anonymization/extract/{fileId}` → `AnonymizationService` calls `TextExtractionService::extractFile()` then `EntityRelationMapper::findEntitiesForFile()`, normalizes result, returns `{entities[], entityCount}`.
3. **Anonymize**: `POST /api/anonymization/anonymize/{fileId}` with `{entities[]}` body → `EntityDetectionService` filters + maps to UUID keys → `FileService::anonymizeDocument()` produces redacted copy → returns `{anonymizedFileId, anonymizedFileName, anonymizedFilePath, replacementCount}`.
4. **List**: `GET /api/anonymization/files` → `FileListingService` iterates `DocuDesk/` nodes, enriches each with `findByFileId()` counts + `RiskLevelService` assessment, returns sorted array.

## API Endpoints

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/api/anonymization/files` | Session (401 if missing) | List DocuDesk files with metadata |
| `POST` | `/api/anonymization/upload` | Session (401 if missing) | Upload file (multipart/form-data) |
| `POST` | `/api/anonymization/extract/{fileId}` | Controller-level only | Extract text + detect entities |
| `POST` | `/api/anonymization/anonymize/{fileId}` | Controller-level only | Anonymize document with entity mapping |

## Seed Data

### Example File Entries (GET /api/anonymization/files response)

```json
[
  {
    "fileId": 1042,
    "fileName": "verzoek-woo-2026-001.pdf",
    "filePath": "/DocuDesk/verzoek-woo-2026-001.pdf",
    "fileSize": 204800,
    "mimeType": "application/pdf",
    "entityCount": 7,
    "anonymizedCount": 7,
    "status": "anonymized",
    "riskLevel": "high",
    "modified": 1747699200
  },
  {
    "fileId": 1043,
    "fileName": "besluit-bezwaar-bakker.pdf",
    "filePath": "/DocuDesk/besluit-bezwaar-bakker.pdf",
    "fileSize": 98304,
    "mimeType": "application/pdf",
    "entityCount": 3,
    "anonymizedCount": 0,
    "status": "extracted",
    "riskLevel": "medium",
    "modified": 1747612800
  },
  {
    "fileId": 1044,
    "fileName": "correspondentie-gemeente.pdf",
    "filePath": "/DocuDesk/correspondentie-gemeente.pdf",
    "fileSize": 51200,
    "mimeType": "application/pdf",
    "entityCount": 0,
    "anonymizedCount": 0,
    "status": "uploaded",
    "riskLevel": "unknown",
    "modified": 1747526400
  }
]
```

### Example Detected Entities (POST /api/anonymization/extract response)

```json
{
  "entities": [
    { "type": "PERSON",       "value": "Ruben van der Linde",  "confidence": 0.97 },
    { "type": "ORGANIZATION", "value": "Gemeente Amsterdam",    "confidence": 0.92 },
    { "type": "EMAIL",        "value": "r.linde@amsterdam.nl", "confidence": 0.99 },
    { "type": "PERSON",       "value": "Maria de Groot",        "confidence": 0.88 },
    { "type": "PHONE",        "value": "020-1234567",           "confidence": 0.84 }
  ],
  "entityCount": 5
}
```

### Example Anonymization Mapping (POST /api/anonymization/anonymize response)

```json
{
  "anonymizedFileId": 1045,
  "anonymizedFileName": "verzoek-woo-2026-001_anonymized.pdf",
  "anonymizedFilePath": "/DocuDesk/verzoek-woo-2026-001_anonymized.pdf",
  "replacementCount": 4,
  "mapping": {
    "Ruben van der Linde":  "[PERSON: a3f1e2d4-5b6c-4789-8abc-def012345678]",
    "Gemeente Amsterdam":   "[ORGANIZATION: b7c8d9e0-1f2a-4b3c-9d4e-5f6a7b8c9d0e]",
    "r.linde@amsterdam.nl": "[EMAIL: c1d2e3f4-a5b6-47c8-9d0e-1f2a3b4c5d6e]",
    "Maria de Groot":       "[PERSON: d4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f8a]"
  }
}
```

## ADR Compliance

- **ADR-002**: API paths follow `/api/{resource}` pattern; GET=read, POST=create; errors return `message` field.
- **ADR-003**: Controller → Service → OpenRegister; no mappers called from controllers; strict 3-layer; `@spec` PHPDoc on all files.
- **ADR-007**: All user-visible strings via `$this->l10n->t()` in PHP and `this.t()` in Vue; both `en.json` and `nl.json` updated.
- **ADR-014**: EUPL-1.2 `@license` PHPDoc on every PHP file; SPDX header on every Vue/JS file.
- **ADR-015**: `@nextcloud/axios` for all frontend API calls; no raw `fetch()`; components imported from `@conduction/nextcloud-vue`.
