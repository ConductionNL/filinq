## Context

DocuDesk's anonymization pipeline currently processes one file at a time through a linear wizard. Government organizations handling WOO disclosure requests routinely need to anonymize 10-100+ documents per dossier. The current single-file approach forces users to repeat the upload-extract-anonymize cycle for each document, with no way to apply consistent entity decisions across files.

The existing architecture delegates all heavy lifting to OpenRegister services (TextExtractionService for extraction, FileService for anonymization). This design preserves that delegation pattern while adding batch orchestration and entity review on the DocuDesk side.

### Current State
- `AnonymizationService` handles single-file extract + anonymize
- `AnonymizationController` exposes 4 endpoints (files, upload, extract, anonymize)
- `EntityDetectionService` normalizes entities and maps them for anonymization
- Frontend Pinia store has a file queue but processes sequentially with no review step
- No batch state management — each API call is stateless

### Constraints
- All processing MUST remain 100% local (no external cloud APIs)
- OpenRegister services are the sole providers of text extraction and anonymization
- No own database tables — use Nextcloud ICache for batch state
- Vue 2.7 frontend with Pinia stores

## Goals / Non-Goals

**Goals:**
- Enable multi-file upload and batch processing in a single workflow
- Provide entity review step where users can include/exclude entities before anonymization
- Support WOO-aligned entity category profiles (auto-anonymize PERSON/BSN, optionally keep ORGANIZATION)
- Generate audit-trail reports (CSV) after batch completion
- Maintain backward compatibility with existing single-file endpoints

**Non-Goals:**
- Real-time collaboration on entity review (single-user workflow)
- Persistent batch history (batches are ephemeral, tied to user session)
- Custom NER model training or entity type configuration
- Integration with OpenCatalogi's WOO publication workflow (that remains in OpenCatalogi)
- Async background job processing (batches run synchronously within the request/session)

## Decisions

### 1. Batch State in Nextcloud ICache (not database, not session)

**Decision**: Store batch state (batch ID, file list, extracted entities, status) in Nextcloud's `ICache` (distributed cache via APCu/Redis) with a TTL of 2 hours.

**Rationale**: Batch state is ephemeral — it only needs to survive between API calls during a single user session. Using ICache avoids adding database tables (which would violate the "no own tables" constraint) and is more reliable than PHP session storage in Nextcloud's multi-process environment.

**Alternatives considered**:
- PHP sessions: Unreliable in Nextcloud due to session locking and OCS API statelessness
- OpenRegister ObjectService: Overhead of persisting temporary batch data as register objects
- Client-side state only: Would require sending all entity data back and forth on every call, impractical for large batches

### 2. Sequential Extraction with Batch Anonymization

**Decision**: File extraction runs sequentially (one file at a time) via OpenRegister's TextExtractionService. Anonymization runs as a batch after entity review.

**Rationale**: TextExtractionService + Presidio/OpenAnonymiser is CPU-intensive. Running extractions in parallel would overload the server. Sequential extraction with progress reporting gives users feedback without resource contention. Anonymization (text replacement) is lightweight and can process the full batch quickly.

**Alternatives considered**:
- Parallel extraction: Risk of OOM/timeout on modest hardware; OpenRegister services are not designed for concurrent calls
- Background jobs (Nextcloud cron): Adds complexity; WOO workflows need interactive feedback, not async polling

### 3. Entity Review as Intermediate API Step

**Decision**: After extraction completes for all files, the batch enters a "review" state. The frontend fetches consolidated entities from `GET /api/anonymization/batch/{batchId}/entities` and submits the reviewed entity list to `POST /api/anonymization/batch/{batchId}/anonymize`.

**Rationale**: Separating extraction from anonymization with an explicit review step gives users control over which entities are anonymized. This is critical for WOO compliance where some entity types (e.g., organization names of government bodies) should remain visible.

### 4. WOO Category Profiles as App Config

**Decision**: Store WOO entity category profiles as JSON in Nextcloud `IAppConfig` under key `docudesk_woo_entity_profiles`. Default profile: anonymize PERSON, BSN, PHONE, EMAIL, IBAN; keep ORGANIZATION, LOCATION.

**Rationale**: Admin-configurable profiles via IAppConfig integrate with Nextcloud's settings system. No database table needed. Profiles are simple JSON maps of entity_type -> include/exclude.

### 5. CSV Report Generation in PHP

**Decision**: Generate batch completion reports as CSV using PHP's `fputcsv()`, served as a download from `GET /api/anonymization/batch/{batchId}/report`.

**Rationale**: CSV is universally readable, requires no additional dependencies, and serves the audit trail purpose. PDF reports would add complexity without significant benefit for this use case.

## Risks / Trade-offs

- **[ICache eviction]** Batch state may be evicted under memory pressure before the user completes review. Mitigation: Use a 2-hour TTL, warn users if batch state is lost, allow re-extraction.
- **[Large batch timeout]** Extracting 100+ files sequentially may take 10+ minutes. Mitigation: Frontend shows per-file progress; extraction endpoint processes one file per call so the connection doesn't timeout.
- **[Entity deduplication across files]** Same entity (e.g., "Jan de Vries") may appear in multiple files with different confidence scores. Mitigation: Deduplicate by value in the consolidated review, show highest confidence score, apply decision to all files.
- **[Backward compatibility]** Existing single-file endpoints must continue working. Mitigation: Batch endpoints use `/batch/` prefix; existing endpoints unchanged.

## Open Questions

- Should batch reports include the original entity values (privacy risk if report is shared) or only anonymized placeholders and counts?
- What is a reasonable maximum batch size? 100 files? 500? Should this be admin-configurable?
