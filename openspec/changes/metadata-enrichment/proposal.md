## Why

DocuDesk stores documents in OpenRegister but ships them bare — no language tag, no keywords, no topic, no standardized type, no normalized dates. Downstream search, filtering, and compliance workflows depend on these fields being populated. Rather than requiring operators to maintain metadata by hand (error-prone, inconsistent), the app needs automatic enrichment that fires whenever a document is created or updated and can also be triggered on-demand via the API.

All processing runs locally using heuristic algorithms. No external NLP services, no cloud round-trips, no data leaving the instance.

## What Changes

### Added Capabilities

- **`metadata-enrichment`** — new capability covering the full enrichment pipeline: language detection (nl/en), keyword extraction (top 10), topic classification (legal/financial/medical/technical), document type standardization, and ISO 8601 date normalization. Enrichment is event-driven (ObjectCreated/Updated events) and available on-demand via `POST /api/metadata/enrich`. Feature toggles in admin settings control each enrichment independently. All text-based enrichment (language, keywords, topic) falls back gracefully when no text content is present.

## Capabilities

### New Capabilities

- `metadata-enrichment`

## Cross-app Dependencies

- **Hard** — `openregister` — `ObjectService` is used to fetch and save enriched objects; `ObjectCreatedEvent`, `ObjectUpdatedEvent`, `ObjectDeletedEvent` drive automatic enrichment.
- **Soft** — `docudesk:admin-settings` — `SettingsService` provides feature toggle values (`enable_language_detection`, `enable_keyword_extraction`, `enable_topic_classification`).

## Impact

- **Code (docudesk):**
  - `lib/Service/MetadataService.php` — NEW: orchestrates the enrichment pipeline, resolves ObjectService, saves enriched data back to OpenRegister.
  - `lib/Service/TextAnalysisService.php` — NEW: keyword extraction (stop-word-filtered, frequency-ranked, max 10) and document type standardization (canonical type mapping).
  - `lib/Service/LanguageClassifier.php` — NEW: word-frequency language detection (nl/en, threshold >5 matches) and topic classification (keyword vocabulary scoring).
  - `lib/Service/DocumentTextExtractor.php` — NEW: text extraction from object fields (priority: content → text → description) and ISO 8601 date normalization for standard date fields.
  - `lib/Controller/MetadataController.php` — NEW: `POST /api/metadata/enrich` REST endpoint (thin controller, delegates to MetadataService).
  - `lib/EventListener/DocuDeskEventListener.php` — NEW: listens to OpenRegister object lifecycle events; resolves services lazily via `\OC::$server->get()` to avoid circular DI at registration time.
  - `lib/EventListener/DocuDeskEventHandler.php` — NEW: dispatches event payloads to enrichment logic.
  - `lib/EventListener/EnrichmentRunner.php` — NEW: executes enrichment on event-driven triggers, including content-change detection on updates.
- **API contract:** new endpoint `POST /apps/docudesk/api/metadata/enrich` (requires `objectId`, `register`, `schema`; optional `objectData`; returns enriched field names and updated object data).
- **Admin settings:** three new toggle keys read from `IAppConfig` (`enable_language_detection`, `enable_keyword_extraction`, `enable_topic_classification`).
- **Known technical debt:** `DocuDeskEventListener` uses `\OC::$server->get()` at handle-time (anti-pattern, reduces testability — META-074). Consolidation of the duplicated `getObjectService()` pattern across MetadataService, ConsentService, and ObjectionDeadlineChecker to `SettingsService::getObjectService()` is a recommended follow-up (META-070/071).
- **Migration:** None — enrichment writes into existing object properties; no schema changes.
