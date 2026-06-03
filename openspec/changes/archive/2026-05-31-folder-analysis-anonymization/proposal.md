## Why

WOO (Wet open overheid) requests and GDPR audits typically involve entire folders of documents, not individual files. Currently, users must either upload files one by one or drag-drop a batch manually. There is no way to point at an existing Nextcloud folder and analyze/anonymize all documents in it as a coherent set — with entities recognized consistently across files. This change adds a folder-level analysis and anonymization pipeline that runs extraction as a background job and provides progressive results as each file completes.

## What Changes

- New API endpoint to initiate folder-level batch analysis from an existing Nextcloud folder path (no file upload needed — files are already in Nextcloud)
- Background extraction job (`QueuedJob`) that processes files sequentially and updates batch state after each file, enabling progressive result polling
- Entity consolidation endpoint relaxed to return partial results during extraction (not just after all files are extracted)
- Anonymized output files saved in the same source folder with `_anonymized` suffix (already supported by OpenRegister's `DocumentProcessingHandler`)
- Batch state TTL extended via keep-alive pattern (reset on poll) to support human-in-the-loop review for large folders
- Only flat folder scanning (direct children); recursive scanning is out of scope

## Capabilities

### New Capabilities
- `folder-batch-analysis`: Enumerate an existing Nextcloud folder, create a batch from its file IDs, queue background extraction, and provide progressive entity consolidation results during processing

### Modified Capabilities
- `batch-anonymization`: Relax entity polling to allow partial results during `extracting` status; add keep-alive TTL reset on batch state reads

## Impact

- **New files**: `FolderBatchService` (service), `FolderExtractionJob` (background job)
- **Modified files**: `BatchAnonymizationController` (new endpoint + relaxed entity gate), `BatchStateService` (TTL keep-alive), `BatchAnonymizeService` (output in source folder)
- **API**: New `POST /api/anonymization/batch/folder` endpoint; modified `GET /api/anonymization/batch/{batchId}/entities` response shape (adds `complete` flag and `filesProcessed`)
- **Dependencies**: No new external dependencies — reuses existing OpenRegister services (TextExtractionService, EntityRelationMapper, FileService)
- **Routes**: New route registration in `appinfo/routes.php`
