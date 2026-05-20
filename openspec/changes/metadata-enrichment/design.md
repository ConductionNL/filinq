## Context

DocuDesk documents stored in OpenRegister lack standardized metadata: language, keywords, topic, canonical document type, and normalized dates are all missing on ingest. Downstream features (full-text search weighting, faceted filtering, DCAT-AP/OWMS compliance exports, consent-scope tagging) depend on these fields being present and consistent. Manual maintenance is infeasible at scale.

This change implements a local enrichment pipeline — no external NLP services — that runs automatically on object lifecycle events and is also callable on demand via a REST endpoint. All enrichment is idempotent: pre-populated fields are skipped so manual overrides are preserved.

## Goals / Non-Goals

**Goals:**

- Detect document language (Dutch / English) using word-frequency heuristics; skip if already set; skip if text too short.
- Extract top 10 keywords by frequency after stop-word removal (Dutch + English stop words); skip if already set.
- Classify documents into one of four topic buckets (legal, financial, medical, technical) using vocabulary keyword scoring; skip if already set.
- Standardize `documentType` strings to canonical values (pdf, word, spreadsheet, presentation, text, html, image); pass through unknowns unchanged.
- Normalize standard date fields (created, modified, date, creationDate, modificationDate) to ISO 8601; skip unparseable values gracefully.
- Run automatically on `ObjectCreatedEvent`; re-run on `ObjectUpdatedEvent` only when content fields (content, text, description, title) changed.
- Provide `POST /api/metadata/enrich` for on-demand enrichment with optional direct data pass-through.
- Respect per-feature admin toggles; if all disabled, no processing runs.

**Non-Goals:**

- External NLP services, cloud-based language models, or semantic embeddings — out of scope; heuristic-only.
- Language support beyond Dutch and English in v1.
- Per-document toggle (all-or-nothing per feature at the instance level in v1).
- Enrichment of objects outside OpenRegister (e.g. raw NC Files without an object record).

## Decisions

### D1. Heuristic-only, no external services

Word frequency analysis is fast, deterministic, and runs on-prem. Accuracy is acceptable for the supported use-cases (routing, filtering, compliance tagging). External NLP would add latency, network dependencies, and data-residency concerns incompatible with Dutch government deployments.

### D2. Skip if field already populated (idempotency)

Operators may manually set `language`, `keywords`, or `topic` before enrichment runs. Overwriting manual overrides would erode trust. Skipping pre-populated fields makes enrichment safe to run repeatedly.

**Trade-off:** enriched values can go stale if the document text changes but the field was set manually. Accepted for v1; a `force` flag on the API endpoint is a natural follow-up.

### D3. Lazy service resolution in event listener

`DocuDeskEventListener` has an empty constructor and resolves `MetadataService` / `LoggerInterface` via `\OC::$server->get()` at handle-time. This avoids circular dependency failures during Nextcloud app registration.

**Trade-off:** `\OC::$server` is a service-locator anti-pattern that reduces unit testability (META-074). The correct long-term fix is constructor DI with `IEventListener` registration deferred to the event-dispatcher, but that requires a Nextcloud DI wiring change beyond this change's scope.

### D4. Content-change detection on ObjectUpdatedEvent

Re-enriching every update would waste cycles and overwrite in-flight manual edits. The listener checks whether any of the content fields (content, text, description, title) changed between the old and new object state. If none changed, enrichment is skipped and a debug log records the skip.

### D5. Duplicated getObjectService() pattern — document but defer consolidation

`MetadataService` carries a private `getObjectService()` that duplicates the pattern in `SettingsService` (and two other services). Consolidation to `SettingsService::getObjectService()` is the correct fix but touches multiple services outside this change's boundary. The duplication is documented here (META-070/071) and tracked as a follow-up refactor.

### D6. Text extraction priority order

Text content is extracted from object fields in the priority order: `content` → `text` → `description`. The first non-empty field wins. If none is present, text-based enrichment (language, keywords, topic) is skipped entirely — date normalization and type standardization still run.

## Architecture

### Service Layer

```
MetadataController (thin — POST /api/metadata/enrich)
    └── MetadataService (orchestration + save-back to OpenRegister)
            ├── DocumentTextExtractor  (text extraction, date normalization)
            ├── LanguageClassifier     (language detection, topic classification)
            └── TextAnalysisService    (keyword extraction, type standardization)
```

### Event-Driven Path

```
OpenRegister event bus
    └── DocuDeskEventListener (lazy-resolves services via \OC::$server)
            └── DocuDeskEventHandler (dispatches by event type)
                    └── EnrichmentRunner (content-change detection + calls MetadataService)
```

### Enrichment Pipeline (per object)

1. Extract text content from object fields (priority: content → text → description).
2. If text found:
   a. Language detection (`LanguageClassifier::detectLanguage()`) — skip if `language` set.
   b. Keyword extraction (`TextAnalysisService::extractKeywords()`) — skip if `keywords` set.
   c. Topic classification (`LanguageClassifier::classifyTopic()`) — skip if `topic` set.
3. Document type standardization (`TextAnalysisService::standardizeDocumentType()`).
4. Date normalization (`DocumentTextExtractor::normalizeDates()`) for all standard date fields.
5. Save enriched fields back to OpenRegister via `ObjectService::saveObject()`.

## Reuse Analysis

| Existing capability | How it is used |
|---|---|
| `openregister/ObjectService` | `findObject()` to fetch objects by id/register/schema; `saveObject()` to write enriched fields back |
| `openregister/ObjectCreatedEvent`, `ObjectUpdatedEvent`, `ObjectDeletedEvent` | Trigger automatic enrichment; consumed by `DocuDeskEventListener` |
| `docudesk/SettingsService` | `getAppValue()` to check per-feature toggle flags; **`getObjectService()` duplicated** — see D5 |
| `openregister/TextExtractionService` | Not used — enrichment text comes from object fields (already-ingested), not raw files |

No new OpenRegister services are required. No custom CRUD endpoints — list/detail views of enriched objects use standard OpenRegister ObjectService + `CnDetailPage`.

## Seed Data

Not applicable. This change adds no new OpenRegister schemas or registers. Enrichment writes into properties of existing document objects. Seed data for the document objects themselves is the responsibility of the `document-register` change.

## Risks / Trade-offs

- **`\OC::$server` anti-pattern** (META-074): event listener is difficult to unit-test without a full Nextcloud bootstrap. Mitigated by testing the services (`LanguageClassifierTest`, `TextAnalysisServiceTest`) independently.
- **Silent error swallowing in nested try/catch**: if logger resolution itself fails inside the error handler, the original exception is silently dropped (META-077). Low probability but hard to diagnose. Follow-up: use a fallback no-op logger instead of re-resolving from the container.
- **Heuristic accuracy**: Dutch/English word-frequency classification can misfire on short or mixed-language documents. The threshold (>5 matches) reduces false positives at the cost of more `null` returns. Acceptable for v1.

## Migration Plan

None required. Enrichment populates existing object properties. No database migrations, no schema changes, no breaking API changes.

**Rollback:** disable all three feature toggles in admin settings. Enrichment stops immediately; existing enriched values remain on objects but cause no harm.

## Open Questions

- Should the API endpoint support a `force: true` parameter to overwrite already-populated fields? (Not in v1 — deferred to follow-up.)
- Should consolidation of `getObjectService()` to `SettingsService` be its own change or bundled into a broader service-layer cleanup? (Recommendation: dedicated refactor change targeting all three duplicates simultaneously.)
- Should `appendBasisSummary`-style warning shapes be standardized across the API for partial enrichment failures? (Deferred to API standards ADR.)
