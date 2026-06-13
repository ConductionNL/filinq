## Context

DocuDesk has a working batch anonymization pipeline: users upload files via drag-and-drop, the frontend drives extraction one file at a time via repeated API calls, entities are consolidated across files, the user reviews and selects entities to anonymize, and all files are anonymized. This works well for small batches where the user is actively watching.

For WOO requests and GDPR audits, documents already exist in a Nextcloud folder. The user should be able to point at a folder and get the same pipeline — but with background processing (no browser tab required) and progressive results (entities visible as soon as the first file is analyzed).

**Key constraint**: All data operations go through OpenRegister (ADR-001). Entity data is already stored persistently in `openregister_entity_relations` and `openregister_entities` tables. Only the batch metadata (which files, their status) uses ICache.

**Existing pattern**: `BatchCorrespondenceJob` already implements the exact QueuedJob pattern needed — sequential processing with per-item status updates.

## Goals / Non-Goals

**Goals:**
- API endpoint to initiate folder-level analysis from a Nextcloud folder path
- Background extraction that doesn't require an active browser session
- Progressive entity results available as soon as the first file is extracted
- Anonymized output in the same folder as source files with `_anonymized` suffix
- Keep-alive pattern on batch state to prevent TTL expiration during human review

**Non-Goals:**
- Recursive folder scanning (future enhancement)
- Fuzzy cross-document entity resolution (future enhancement — current exact match is sufficient)
- Frontend UI for folder selection (API-first; UI comes later)
- Changing how single-file or upload-based batch anonymization works

## Decisions

### 1. Reuse existing batch state infrastructure (ICache) with keep-alive

**Decision**: Keep batch metadata in ICache (distributed cache) but reset TTL on every read.

**Alternatives considered**:
- **Database storage**: More durable but violates ADR-001 (no custom tables) and adds migration complexity. OpenRegister doesn't have a suitable "batch job" schema.
- **Extend TTL to 24h**: Simpler but wasteful — abandoned batches linger.

**Rationale**: The keep-alive pattern gives durability proportional to attention. A batch stays alive as long as someone polls it (even once every 2 hours). Abandoned batches auto-expire. Implementation is a one-line change in `BatchStateService::getBatch()`.

### 2. New service (FolderBatchService) for folder enumeration + batch creation

**Decision**: Create a dedicated `FolderBatchService` rather than extending `BatchUploadService`.

**Rationale**: The upload service handles multipart file data (tmp files, upload errors). The folder service resolves Nextcloud paths and enumerates existing files. Different input, different concerns, but both produce the same batch state structure. Following ADR-008 (Controller → Service → Mapper layering).

**Components**:
- `FolderBatchService`: Resolves folder path → enumerates files → creates batch via `BatchStateService` → queues `FolderExtractionJob`
- Uses `IRootFolder` to resolve the user's folder and `Folder::getDirectoryListing()` to enumerate children

### 3. QueuedJob for background extraction

**Decision**: Use `OCP\BackgroundJob\QueuedJob` (same as `BatchCorrespondenceJob`).

**Alternatives considered**:
- **TimedJob**: Wrong semantics — this is a one-shot task, not recurring
- **Frontend-driven loop**: Current approach for upload batches. Requires active browser session, breaks for large folders.

**Rationale**: QueuedJob is picked up by Nextcloud's cron runner. It processes files one at a time, updating batch state after each. The cron interval (typically 5 minutes or less in production) means the first file starts processing promptly.

### 4. Relax entity endpoint gate from `review`-only to `extracting` + `review`

**Decision**: Modify `BatchAnonymizationController::batchEntities()` to accept both statuses, adding `complete` and `filesProcessed` to the response.

**Rationale**: `EntityConsolidationService::consolidateEntities()` already only processes files with status "extracted" — it naturally returns partial results. The only change needed is removing the status check gate and enriching the response.

### 5. No changes to anonymization output path

**Decision**: No changes needed. OpenRegister's `DocumentProcessingHandler::anonymizeDocument()` already saves `{basename}_anonymized.{ext}` in the same parent folder as the source file.

**Rationale**: For upload-based batches, files live in the DocuDesk folder, so anonymized copies go there. For folder-based batches, files stay in their original folder, so anonymized copies go next to the originals. Same code, different source location, correct behavior in both cases.

### 6. Add `folderPath` and `sourceType` to batch state

**Decision**: Store `folderPath` (the source folder) and `sourceType` ("folder" vs "upload") in the batch metadata.

**Rationale**: The report and status endpoints can show whether this was a folder analysis or an upload batch. The `folderPath` is useful for UI display and could support a "re-analyze folder" action in the future.

## Risks / Trade-offs

**[Risk] Cron delay**: Nextcloud's background job runner depends on cron configuration. If cron runs every 15 minutes, there's up to 15 minutes before extraction starts.
→ **Mitigation**: Document that `crontab` cron (not AJAX cron) is recommended for production. The status endpoint shows "queued" so the user knows processing hasn't started yet.

**[Risk] Large folders**: A folder with hundreds of files could mean a background job running for a long time. If the PHP process is killed (timeout), the batch is left in "extracting" state.
→ **Mitigation**: The job updates state after each file, so progress is preserved even if interrupted. A re-queued job could resume from where it left off (future enhancement). For now, partially extracted batches can still be reviewed and anonymized for the files that did complete.

**[Risk] Cache eviction**: Even with keep-alive, if no one polls for 2 hours, the batch state is lost. Entity data in OpenRegister is permanent, but the batch tracking (which files, their status) is gone.
→ **Mitigation**: Acceptable for now. The entity data persists. A future enhancement could reconstruct batch state from OpenRegister entity relations if needed.

**[Risk] Concurrent folder modification**: If files are added/removed from the folder while extraction is running, the batch won't reflect those changes.
→ **Mitigation**: The batch is a snapshot at initiation time. This is expected behavior and matches how upload-based batches work.

## Open Questions

None — decisions are informed by the existing codebase patterns and the exploration session.
