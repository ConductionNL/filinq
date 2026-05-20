## Context

The existing anonymisation pipeline (canonical `anonymization` capability) exposes three endpoints: upload, extract, and anonymise. After extraction, DocuDesk forwards all detected entities to OpenAnonymiser without a human review step. For batch workflows covering multiple files, there is no single surface where an operator can see the full entity landscape, spot false-positives, apply a confidence floor, or bulk-manage inclusion before committing.

This change adds a review step between extract and anonymise:

1. The backend endpoint `GET /api/anonymization/batch/{batchId}/entities` rolls up all entity data produced by the extract step across every file in the batch, deduplicates by value (case-insensitive), computes `highestConfidence` and `fileCount` aggregates, and applies WOO-profile defaults to the `included` flag.
2. The frontend adds a Review step to the existing wizard (`AnonymizationIndex.vue`) rendering an `EntityReviewTable` component. Operators interact with the table — toggle, filter, search, bulk-select — and then trigger the existing anonymise endpoint with only the `included=true` entries.

## Goals / Non-Goals

**Goals:**

- Expose a consolidated, deduplicated entity list per batch via a single backend endpoint.
- Apply WOO-profile-derived `included` defaults server-side so the review table starts in a sensible state.
- Support operator confidence threshold (`minConfidence`) to suppress low-confidence entities from the active set.
- Provide frontend search (value substring), type filter, bulk-select, column sorting, and a summary bar.
- Enforce entity review as a prerequisite for the anonymise call — the frontend drives ordering.

**Non-Goals:**

- Persist individual entity toggle decisions server-side (the final approved set is sent in the anonymise request; no intermediate state is stored).
- Modify the anonymise endpoint or its payload schema (the existing `POST /api/anonymization/batch/{batchId}/anonymize` accepts `entities[]` unchanged).
- Build a new anonymise pipeline — this change only inserts a review step into the existing flow.
- Add `prohibitionMatch` or `suggestedBases` to the consolidated-entities response (those live in the sibling changes `anonymisation-entity-review-prohibition-hints` and `anonymisation-bases-passthrough`).

## Decisions

### D1. Deduplication by value, case-insensitive

Entities are grouped by `lower(value)`. The `highestConfidence` is `MAX(score)` across all occurrences in all files; `fileCount` is the count of distinct files containing the entity. Entity `type` is taken from the highest-confidence occurrence (ties broken by first seen). This yields one canonical row per real-world entity regardless of casing variation across files.

**Rationale:** The same name ("gemeente Utrecht" vs "Gemeente Utrecht") detected by different extractors should not appear as two rows — duplicate rows inflate the review burden and confuse operators.

### D2. `included` default is WOO-profile driven

The active WOO anonymise profile determines whether a given entity type is included by default. Entity types listed in the profile's anonymise list → `included=true`. Entity types listed in the profile's keep list → `included=false`. Unknown types → `included=true` (fail-safe: over-redact rather than leak).

### D3. `minConfidence` filter is a soft exclusion

Entities below `minConfidence` have `included=false` regardless of WOO profile, and are visually flagged in the frontend as "low confidence". They are still returned in the response so the operator can see them and manually re-include if needed.

**Rationale:** The operator retains override authority. The threshold implements GDPR Art. 5(1)(d) accuracy by not blindly anonymising low-confidence matches, but does not hide them.

### D4. HTTP 409 for not-yet-complete batches

If the batch is not yet in "review" status (e.g. still extracting), the endpoint returns HTTP 409 with `{ "error": "Batch extraction is not yet complete" }`. This prevents stale or partial entity reads.

### D5. Frontend-only toggle state

Toggle decisions (included=true/false per entity) are held in frontend Pinia store state only. On anonymise trigger, the store filters to `included=true` entries and builds the anonymise request payload. No PATCH/PUT endpoint for individual entity state is added.

**Rationale:** Simpler backend contract; the final approved list is the only state that matters. Session-level undo/redo is handled by the frontend store.

### D6. Bulk-select operates on the filtered set only

"Select All Visible" / "Deselect All Visible" toggle `included` for entities currently matching the active search + type filter. Entities hidden by the filter are not affected. This is consistent with standard table bulk-action UX and prevents accidental mass-exclusion of entities the operator hasn't reviewed.

### D7. Sorting is client-side

The response returns the full entity list sorted by `highestConfidence DESC` by default. Column re-sorting (click column header) is handled client-side in the Vue component without additional API calls.

## Architecture

### Backend

- `BatchAnonymizationController::entities(batchId, minConfidence)` — new action; validates batch status (409 if not "review"), calls `EntityConsolidationService`, returns JSON array.
- `EntityConsolidationService` — new service:
  - Calls `EntityRelationMapper::findEntitiesForBatch(batchId)` to retrieve all per-file entity records.
  - Groups by `lower(value)`, computes `highestConfidence`, `fileCount`, picks canonical `type`.
  - Applies WOO-profile lookup (via existing `WooProfileService` or `IAppConfig`-backed profile config) to set `included` default.
  - Applies `minConfidence` threshold: entities below threshold → `included=false`.
  - Returns sorted array (highestConfidence DESC).

### Frontend

- `AnonymizationIndex.vue` — gains a step 3 "Review" inserted between "Analyse" and "Anonymise".
- `EntityReviewTable.vue` — new component:
  - Columns: checkbox (`included` toggle), Type (NcBadge), Value (text), Confidence (percentage), Files (count).
  - Header row: sortable by clicking any column label (chevron icon indicating sort direction).
  - Toolbar: text search input (filters by value substring, case-insensitive), type dropdown (PERSON / ORGANIZATION / EMAIL / PHONE / BSN / IBAN / ADDRESS / LOCATION / DATE / OTHER / All), "Select All Visible" button, "Deselect All Visible" button.
  - Low-confidence rows (below `minConfidence`): warning icon (NcIconWarning) in Type column; row styled with muted text.
  - Summary bar (below table): "X of Y entities selected for anonymization across Z files" — X = count of included=true, Y = total entity count, Z = unique file count across included entities.
- Pinia store: entity list lives in the anonymisation store slice; `toggleEntity(value)`, `setAllVisible(included, filteredValues)` mutations.

## Reuse Analysis

| Capability needed | Provided by | Notes |
|---|---|---|
| Per-file entity data retrieval | `EntityRelationMapper::findEntitiesForBatch()` (OpenRegister) | No custom mapper needed |
| WOO profile lookup | `WooProfileService` or `IAppConfig`-backed config (existing) | Check existing service before writing new one |
| Batch status check | `BatchService::getBatch(batchId)` (existing) | Reuse status field |
| Frontend table + sorting | `CnDataTable` (@conduction/nextcloud-vue) | Use if it supports checkbox column; otherwise `EntityReviewTable` adds lightweight custom table |
| Pinia store generation | `createObjectStore` (OpenRegister) | Entity list is transient (not an OpenRegister object), so a plain Pinia store slice is appropriate here |

No overlap with `ObjectService`, `RegisterService`, `SchemaService`, or `ConfigurationService` — the consolidated-entities endpoint reads entity relation data, not register objects.

## Seed Data

Not applicable. Entities are detected dynamically from uploaded files during the extract step and are not stored as OpenRegister schema objects — they are transient `EntityRelation` records produced per-file by `EntityRelationMapper`. There is no entity schema to seed.

Batch objects (the `anonymizationBatch` schema from the canonical `anonymization` capability) are seeded separately by that change. The review capability reads from the same batch.

## Open Questions

- **`findEntitiesForBatch` availability** — confirm `EntityRelationMapper` exposes a batch-scoped query, or whether the service must iterate per-file entity queries. Resolve during apply by checking current OpenRegister API surface.
- **WooProfileService name** — confirm the exact class name for the WOO-profile lookup during apply; fallback to `IAppConfig` key lookup if no dedicated service exists yet.
