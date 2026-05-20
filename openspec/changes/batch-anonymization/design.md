## Context

The existing anonymisation pipeline (`archive/2026-03-21-anonymization`) handles one document at a time through three API calls: `POST /api/anonymization/upload`, `POST /api/anonymization/extract/{fileId}`, and `POST /api/anonymization/anonymize/{fileId}`. For WOO operators processing 30–100 documents in a single publication batch, this requires manual repetition of every step per file and gives no consolidated progress view.

Batch state cannot be persisted in OpenRegister entities — no schema exists for transient pipeline state, and creating one would require a migration and cleanup logic. ICache is the correct store: fast, TTL-enforced, no DB migration, automatically cleaned up. The processing model is sequential-per-call (not background job) to remain within Nextcloud's request lifecycle and to give the frontend fine-grained progress feedback without WebSocket complexity.

The WOO entity profiles feature reduces per-batch cognitive load: WOO operators always anonymise the same entity categories. A pre-configured profile eliminates manual selection on every batch while remaining fully overridable by admins.

## Goals / Non-Goals

**Goals:**

- Accept up to 100 files (admin-configurable) in one upload; persist state in ICache with 2-hour TTL; return a batch ID.
- Sequential extraction: one file per API call; per-file status updated in ICache after each call; progress percentage returned in response.
- Batch status endpoint returning per-file status, overall batchStatus, entity count, and progress percentage.
- Batch anonymisation: entity list supplied at call time; consistent UUID pseudonymisation across all files in the batch (same entity value → same UUID); error files skipped with reason reported.
- CSV audit report: fileName, originalFileId, anonymizedFileId, entityCount, replacementCount, status, timestamp; entity values excluded.
- WOO entity category profiles: default ships with app; admin can override via PUT; entity review pre-selects from active profile.
- Non-admin users receive HTTP 403 on PUT profiles.

**Non-Goals:**

- Background/async processing via Nextcloud job queue (v1 uses sequential API calls).
- Parallel file extraction or anonymisation.
- Multi-batch management (list of active batches, per-user batch history).
- Per-file entity override outside the shared entity list provided at anonymise time.
- Automatic cleanup of ICache entries before TTL expiry.
- Batch download as ZIP (report only; anonymised files remain in Nextcloud Files).
- Multiple named profiles with a profile-selector UI (v1 supports a single active WOO profile).

## Decisions

### D1. ICache for batch state

Batch state is transient — it exists only during an active operator session and has no business value after completion (the CSV report is the durable artefact). ICache with a 2-hour TTL fits exactly. An OpenRegister entity or database row would require a migration, a mapper, a schema, and background cleanup. The TTL is extended on each successful step call so the window runs from last activity, not batch creation.

### D2. Sequential extraction via repeated API calls

Background job extraction would give no natural progress ticks and would require a polling endpoint or WebSocket. Per-call sequential extraction is simpler: one call extracts the next `uploaded` file, updates ICache, and returns progress. The frontend calls this in a loop until `batchStatus === 'review'`. This pattern fits Nextcloud's PHP lifecycle and requires no job infrastructure.

### D3. Consistent UUID pseudonymisation across batch files

Per GDPR Art. 4(5), pseudonymisation requires the same real-world value to map to the same pseudonym within a processing context. The entity list is resolved once at the `POST /api/anonymization/batch/{batchId}/anonymize` call; UUIDs are assigned at that point from the deduplicated entity list; the resulting map is applied to every file in the batch. This ensures cross-file consistency (e.g. "Petra de Vries" → the same UUID pseudonym in all 30 documents).

### D4. Entity profiles in IAppConfig

Profiles are admin-only configuration, not user data. IAppConfig is the correct store (ADR-003). The default WOO profile is hardcoded as a fallback in `WooProfileService::getDefaultProfile()`; admins can override via PUT. Storing in OpenRegister would create a schema dependency that does not belong to this app's data model.

### D5. CSV report excludes entity values

The audit report allows a data controller to verify that anonymisation ran and which files were processed. It does not need the actual PII values — inclusion would create an unnecessary PII record of the very data being protected. Counts and file identifiers are sufficient (GDPR Recital 26).

### D6. Admin check via `#[AuthorizedAdminSetting]` on PUT profiles

The `PUT /api/anonymization/profiles` endpoint is annotated `#[AuthorizedAdminSetting(Application::APP_ID)]`. This follows ADR-005: admin checks belong in the routing table (declarative, grep-able), not duplicated in the controller body.

### D7. Error files skipped during anonymisation

Files that failed extraction (status `error`) cannot be anonymised — they have no extracted entities. They are skipped silently during the anonymise step; their skipped status and reason are included in the `skippedFiles` array of the response. The batch can still complete as `completed` if at least one file is anonymised successfully.

## Risks / Trade-offs

- **ICache size**: A 100-file batch with entity metadata can grow large. Mitigated by storing only status/counts in ICache after extraction (not raw entity arrays); raw entity data lives in OpenRegister.
- **TTL expiry mid-session**: If an operator takes more than 2 hours between steps the batch expires; `GET /status` returns HTTP 404. Mitigation: TTL extended on each successful step call (D1). Documented in UX.
- **Sequential extraction latency**: 100 files on slow hardware could require many sequential calls. The progress percentage and per-file feedback mitigate perceived latency; operators see steady progress.
- **Profile misconfiguration**: An admin setting an empty `anonymize` list in the WOO profile results in zero pre-selected entities. The system continues — operators can still select manually. Validation warns but does not block save.

## Migration Plan

1. Deploy new controller + four new services + route entries.
2. Wire admin settings for `docudesk_batch_max_files` (default 100) and the WOO profile editor.
3. Default WOO profile is a hardcoded fallback in `WooProfileService` — no IAppConfig seed required.

**Rollback:** Remove the new route registrations from `appinfo/routes.php`. ICache entries expire within 2 hours and leave no persistent state. No DB changes to roll back.

## Seed Data

### Batch state object (ICache key: `docudesk_batch_{batchId}`)

```json
{
  "batchId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "batchStatus": "review",
  "createdBy": "j.dejong",
  "createdAt": "2026-05-20T09:15:00Z",
  "ttlExtendedAt": "2026-05-20T09:38:44Z",
  "files": [
    {
      "fileId": 1042,
      "fileName": "besluit-2026-0041.pdf",
      "status": "extracted",
      "entityCount": 7,
      "error": null
    },
    {
      "fileId": 1043,
      "fileName": "bijlage-a-overeenkomst.pdf",
      "status": "extracted",
      "entityCount": 3,
      "error": null
    },
    {
      "fileId": 1044,
      "fileName": "subsidie-aanvraag-visser.pdf",
      "status": "error",
      "entityCount": 0,
      "error": "TextExtractionService: unsupported MIME type image/tiff"
    }
  ]
}
```

### Batch upload response

```json
{
  "batchId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "fileCount": 3,
  "files": [
    { "fileId": 1042, "fileName": "besluit-2026-0041.pdf", "status": "uploaded" },
    { "fileId": 1043, "fileName": "bijlage-a-overeenkomst.pdf", "status": "uploaded" },
    { "fileId": 1044, "fileName": "subsidie-aanvraag-visser.pdf", "status": "uploaded" }
  ]
}
```

### Batch anonymise completed response

```json
{
  "batchId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "batchStatus": "completed",
  "files": [
    {
      "fileId": 1042,
      "fileName": "besluit-2026-0041.pdf",
      "status": "anonymized",
      "replacementCount": 12,
      "anonymizedFilePath": "/j.dejong/files/DocuDesk/anonymised/besluit-2026-0041.pdf"
    },
    {
      "fileId": 1043,
      "fileName": "bijlage-a-overeenkomst.pdf",
      "status": "anonymized",
      "replacementCount": 5,
      "anonymizedFilePath": "/j.dejong/files/DocuDesk/anonymised/bijlage-a-overeenkomst.pdf"
    }
  ],
  "skippedFiles": [
    {
      "fileId": 1044,
      "fileName": "subsidie-aanvraag-visser.pdf",
      "reason": "extraction error: TextExtractionService: unsupported MIME type image/tiff"
    }
  ]
}
```

### WOO entity profile (IAppConfig key: `docudesk_woo_entity_profiles`)

```json
{
  "woo-default": {
    "name": "WOO Standaard",
    "anonymize": ["PERSON", "BSN", "PHONE", "EMAIL", "IBAN", "ADDRESS"],
    "keep": ["ORGANIZATION", "LOCATION", "DATE"]
  }
}
```

### CSV audit report rows

```
fileName,originalFileId,anonymizedFileId,entityCount,replacementCount,status,timestamp
besluit-2026-0041.pdf,1042,1098,7,12,anonymized,2026-05-20T09:42:11Z
bijlage-a-overeenkomst.pdf,1043,1099,3,5,anonymized,2026-05-20T09:43:02Z
subsidie-aanvraag-visser.pdf,1044,,0,0,error,2026-05-20T09:41:55Z
```

## Open Questions

- Should `BatchAnonymizeService` extend the ICache TTL on every successful step call, or keep a fixed 2-hour window from creation? Provisional: extend on each step so the window runs from last activity.
- Should the profile store support multiple named presets (e.g. "WOO Standaard", "WOO Strikt") or a single active profile? Provisional: store as a keyed map now to allow future multi-profile UI without a schema change.
- Should `GET /api/anonymization/batch/{batchId}/report` be available while the batch is still `extracting` (partial report)? Provisional: no — HTTP 409 until `completed`.
