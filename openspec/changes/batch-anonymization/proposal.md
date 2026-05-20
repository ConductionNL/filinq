## Why

DocuDesk's anonymisation pipeline today handles one document at a time. WOO operators regularly receive batches of 10–100 documents that must all be anonymised before publication. Without a batch surface they must trigger upload → extract → anonymise → download individually for each file — a process that scales linearly in manual effort and creates a window for operator error (e.g. missing a file, or forwarding an unredacted copy to the wrong recipient).

This change introduces the complete batch anonymisation surface: a stateful multi-file pipeline backed by ICache that processes documents sequentially, tracks per-file status across multiple API calls, supports a consolidated entity review step, and produces a GDPR-compliant CSV audit report on completion. A companion feature (WOO entity category profiles) lets operators apply the correct anonymisation preset (PERSON/BSN/PHONE/EMAIL/IBAN/ADDRESS) with a single click rather than selecting entity types manually on every batch.

## What Changes

- **NEW capability:** `batch-anonymization` — five new API endpoints: batch upload, sequential extraction, batch status poll, batch anonymisation, and completion report download.
- **NEW capability:** `woo-entity-profiles` — `GET /api/anonymization/profiles` and `PUT /api/anonymization/profiles` backed by `IAppConfig` key `docudesk_woo_entity_profiles`; the entity review step pre-selects entities from the active profile; default WOO profile ships with the app.
- **NEW controller:** `lib/Controller/BatchAnonymizationController.php` — handles all five batch endpoints and the two profiles endpoints.
- **NEW service:** `lib/Service/BatchAnonymizeService.php` — orchestrates the stateful pipeline; reads/writes batch state to `ICache` with 2-hour TTL (extended on each successful step call); max batch size admin-configurable via `IAppConfig` key `docudesk_batch_max_files` (default: 100).
- **NEW service:** `lib/Service/BatchExtractionService.php` — processes one file per call using OpenRegister `TextExtractionService::extractFile()` + `EntityRelationMapper::findEntitiesForFile()`; updates per-file status in batch state.
- **NEW service:** `lib/Service/BatchReportService.php` — produces the CSV audit report; entity values excluded per GDPR Recital 26 (data minimisation); Content-Disposition header set for download.
- **NEW service:** `lib/Service/WooProfileService.php` — reads/writes WOO entity category profiles from `IAppConfig`; default profile hardcoded as fallback; admin-only write.
- **MODIFIED:** `appinfo/routes.php` — seven new route registrations.
- **MODIFIED:** Admin settings — surface `docudesk_batch_max_files` and the WOO profile editor (admin-only UI).

## Capabilities

### New Capabilities

- `batch-anonymization`
- `woo-entity-profiles`

## Cross-app Dependencies

- **Hard** — `docudesk:anonymization` (archive/2026-03-21-anonymization) — batch pipeline delegates to the same OpenRegister `TextExtractionService`, `EntityRelationMapper`, and `FileService::anonymizeDocument()` used by the single-file flow. Single-file flow must be present and operational before batch can be applied.
- **Soft** — `docudesk:anonymisation-batch-output-folder-layout` — if active, per-file anonymisation inside `BatchAnonymizeService` routes outputs to `<source>/anonymised/<filename>`; if not active, the legacy `_anonymized`-suffix path is used. The batch pipeline reads `anonymizedFilePath` from OR's response and is layout-agnostic.
- **Soft** — `docudesk:anonymisation-prohibition-gate` — if active, each per-file anonymisation step inside `BatchAnonymizeService` passes through the prohibition gate; gate-blocked files are recorded as `error` in batch state and included in the `skippedFiles` array of the anonymise response.

## Impact

- **Code (docudesk):** One new controller, four new services, admin settings extension, seven new route entries.
- **API contract:** Five new batch endpoints + two profiles endpoints; pure additions — no existing endpoint signatures changed.
- **UX impact:** Operators select up to 100 files in one upload and step through extraction and review without manual per-file repetition. Entity review pre-selects WOO-relevant categories from the active profile, reducing selection effort and the chance of missed categories.
- **Privacy / compliance:** Batch UUID pseudonymisation (GDPR Art. 4(5)) assigns consistent pseudonyms per entity value across all files in the batch. CSV report excludes entity values (Recital 26 data minimisation). `PUT /api/anonymization/profiles` is admin-only (`#[AuthorizedAdminSetting]`), enforcing least privilege.
- **Migration:** None. No database schema changes. ICache entries expire after 2 hours and leave no persistent state. Existing single-file anonymisation flow is unchanged.
