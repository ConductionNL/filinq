# Folder Analysis and Anonymization

Analyze and anonymize all documents in a Nextcloud folder as a single batch. Entities detected across multiple files are consolidated, so an entity recognized in one file is treated as the same entity in all other files.

## API Endpoint

### Start folder analysis

```
POST /api/anonymization/batch/folder
```

**Request body:**

```json
{
  "folderPath": "/Documents/WOB-2024"
}
```

**Response:**

```json
{
  "batchId": "a1b2c3d4-...",
  "fileCount": 5,
  "files": [
    { "fileId": 101, "fileName": "report.pdf", "status": "uploaded" },
    { "fileId": 102, "fileName": "letter.docx", "status": "uploaded" }
  ]
}
```

The endpoint creates a batch from all files in the specified folder (flat scan, direct children only — subdirectories are skipped). A background extraction job is queued automatically.

### Error responses

| Status | Condition |
|--------|-----------|
| 400 | Path is not a folder, folder is empty, folder exceeds max batch size, no path provided |
| 401 | Not authenticated |
| 404 | Folder not found |

## Progressive Polling

Extraction runs as a background job. Poll for progress:

### Batch status

```
GET /api/anonymization/batch/{batchId}/status
```

Returns overall progress, per-file status, and entity count.

### Entity consolidation (progressive)

```
GET /api/anonymization/batch/{batchId}/entities
```

**Available during extraction** (not only after completion). Response includes:

```json
{
  "entities": [...],
  "entityCount": 12,
  "complete": false,
  "filesProcessed": 3
}
```

- `complete: false` — extraction still in progress, partial results
- `complete: true` — all files extracted, full entity list
- `filesProcessed` — number of files analyzed so far

Entities are deduplicated across files using exact case-insensitive matching. The `fileCount` field shows how many files contain each entity.

## Review and Anonymize

After extraction completes (`batchStatus: "review"`), review the consolidated entity list and anonymize:

```
POST /api/anonymization/batch/{batchId}/anonymize
```

**Request body:**

```json
{
  "entities": [
    { "type": "PERSON", "value": "Jan Jansen" },
    { "type": "EMAIL", "value": "jan@example.com" }
  ]
}
```

## Anonymized Output

Anonymized files are saved **in the same folder** as the originals with the `_anonymized` suffix:

```
/Documents/WOB-2024/
  report.pdf              (original)
  report_anonymized.pdf   (anonymized copy)
  letter.docx             (original)
  letter_anonymized.docx  (anonymized copy)
```

Original files are never modified.

## Batch State

Batch state is stored in Nextcloud's distributed cache with a 2-hour TTL. The TTL resets on every status or entity poll (keep-alive pattern), so the batch remains active as long as it is being used.

## Configuration

| Setting | Key | Default |
|---------|-----|---------|
| Maximum files per batch | `docudesk_batch_max_files` | 100 |

Configurable by admins via IAppConfig.
