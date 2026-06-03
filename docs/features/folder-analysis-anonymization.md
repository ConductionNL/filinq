# Folder Analysis and Anonymization

Analyze and anonymize all documents in a Nextcloud folder as a single batch. Entities detected across multiple files are consolidated, so an entity recognized in one file is treated as the same entity in all other files.

## API Endpoint

### Start folder analysis

```
POST /api/anonymization/batch/folder
```

**Request body — exactly one of `folderId` or `folderPath` is required:**

Providing neither, or providing both, results in HTTP 400.

By **folder path** (human-readable, existing usage):

```json
{
  "folderPath": "/Documents/WOB-2024"
}
```

By **folder ID** (rename-proof, ideal for integrations that already hold a Nextcloud node ID — e.g. the FilePicker from `@nextcloud/dialogs`, Files-app context actions, or other Conduction apps):

```json
{
  "folderId": 12345
}
```

When `folderId` resolves to multiple mounts within the user's tree (the same file ID surfacing through personal storage + a share + a group folder), a mount with write permission is preferred so anonymized copies can be written back into the source folder. If no writable mount exists, the first readable node is used — extraction-only flows still work, but the subsequent anonymization step will fail to write back to a read-only location.

**Response — always includes both identifiers regardless of which input was used:**

```json
{
  "batchId": "a1b2c3d4-...",
  "folderId": 12345,
  "folderPath": "/Documents/WOB-2024",
  "fileCount": 5,
  "files": [
    { "fileId": 101, "fileName": "report.pdf", "status": "uploaded" },
    { "fileId": 102, "fileName": "letter.docx", "status": "uploaded" }
  ]
}
```

The endpoint creates a batch from all files in the specified folder (flat scan, direct children only — subdirectories are skipped). A background extraction job is queued automatically. Path-based callers receive a free upgrade path: capture `folderId` from the response and use it on reruns to stay rename-proof.

### Example: start analysis from a Nextcloud FilePicker result

The Nextcloud `@nextcloud/dialogs` `FilePicker` returns `Node` objects with a native `fileid`. Pass that directly — no path derivation required:

```js
import { getFilePickerBuilder, FilePickerType } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const picker = getFilePickerBuilder(t('docudesk', 'Select folder to analyze'))
  .setMultiSelect(false)
  .setType(FilePickerType.Choose)
  .allowDirectories(true)
  .build()

const [folder] = await picker.pick()

const { data } = await axios.post(
  generateUrl('/apps/docudesk/api/anonymization/batch/folder'),
  { folderId: folder.fileid }
)

console.log(data.batchId, data.folderPath, data.fileCount)
```

### Error responses

| Status | Condition |
|--------|-----------|
| 400 | Neither `folderId` nor `folderPath` provided, both provided, path/ID is not a folder, folder is empty, folder exceeds max batch size |
| 401 | Not authenticated |
| 404 | Folder not found (ID not accessible by the current user, or path does not exist) |

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

Anonymized files are saved in a **dedicated subfolder** under the source folder with their original filename (no `_anonymized` suffix):

```
/Documents/WOB-2024/
  report.pdf              (original, unchanged)
  letter.docx             (original, unchanged)
  anonymised/
    report.pdf            (anonymized copy)
    letter.docx           (anonymized copy)
```

The subfolder name defaults to `anonymised` and is tenant-configurable via the admin settings (config key `docudesk.anonymisation.output_subfolder_name`). See the [batch anonymization docs](batch-anonymization.md) for shared configuration details.

Original files are never modified.

### Source-discovery filter

Files whose base name ends with `_anonymized` (legacy outputs from pre-layout runs) are **excluded** from folder source discovery. This prevents re-anonymising legacy redacted files in a re-run. Legacy files are left as-is; operators may delete or rename them manually.

### Move-failure fallback

When the post-anonymisation move to the subfolder fails (permissions, disk error), the anonymised file is preserved at its legacy path (`<source>/<base>_anonymized.<ext>`) and the per-file response includes a `warning` field with `code: "MOVE_FAILED"`. The anonymisation itself is still counted as successful.

### Background job behaviour

The background `FolderExtractionJob` applies the same source-discovery filter: `_anonymized`-suffixed files in the source folder are skipped during the extraction phase. Per-job retry is idempotent — if a file is already extracted, the retry skips it without re-processing.

## Batch State

Batch state is stored in Nextcloud's distributed cache with a 2-hour TTL. The TTL resets on every status or entity poll (keep-alive pattern), so the batch remains active as long as it is being used.

## Configuration

| Setting | Key | Default |
|---------|-----|---------|
| Maximum files per batch | `docudesk_batch_max_files` | 100 |

Configurable by admins via IAppConfig.
