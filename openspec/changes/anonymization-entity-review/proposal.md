## Why

The DocuDesk anonymisation pipeline today is a three-step flow: upload → extract → anonymise. After extraction, all detected entities are forwarded verbatim to OpenAnonymiser with no structured review step. Operators cannot see which entities were detected across an entire batch, cannot selectively exclude false-positives (e.g. a well-known city name that should not be redacted), and cannot apply GDPR accuracy controls before committing to anonymisation.

This change introduces the `anonymization-entity-review` capability: a consolidated-entities backend endpoint that rolls up all detected entities across a batch into a single deduplicated list with confidence scores, file counts, and WOO-profile-derived inclusion defaults — plus a frontend review table with search, type filter, bulk-select, sortable columns, confidence threshold filtering, and a summary bar. Operators review and toggle entities before triggering the final anonymise call. The backend receives only the operator-approved set.

## What Changes

- **NEW capability:** `anonymization-entity-review` — the consolidated-entities endpoint and the frontend review step.
- **NEW endpoint:** `GET /api/anonymization/batch/{batchId}/entities` — returns all unique entities detected across all files in a batch, deduplicated by value (case-insensitive), enriched with `highestConfidence`, `fileCount`, and an `included` flag pre-set from the active WOO profile. Accepts optional `minConfidence` query parameter.
- **NEW frontend component:** entity review table with text search, type-filter dropdown, bulk-select actions ("Select All Visible" / "Deselect All Visible"), per-column sorting, low-confidence warning indicators, and a summary bar.
- **EXISTING endpoint used:** `POST /api/anonymization/batch/{batchId}/anonymize` — receives the final operator-approved entity list (included=true entries only) after review.

## Capabilities

### New Capabilities

- `anonymization-entity-review`

## Cross-app Dependencies

- **Hard** — `openregister:EntityRelationMapper` — used by the consolidated-entities endpoint to retrieve per-file entity data for deduplication and rollup.
- **Soft** — `docudesk:anonymization` — the upstream extract step populates the entity data this capability reads; must complete before entity review is triggered.

## Impact

- **Code (docudesk):** `lib/Controller/BatchAnonymizationController.php` (new `entities()` action), `lib/Service/EntityConsolidationService.php` (new service — deduplication, WOO profile lookup, confidence threshold application), frontend `src/views/AnonymizationIndex.vue` (new Review step), `src/components/EntityReviewTable.vue` (new component).
- **API contract:** new read-only endpoint `GET /api/anonymization/batch/{batchId}/entities` (HTTP 200 on review-status batch, 409 on not-yet-complete). No existing endpoints modified.
- **Privacy/compliance:** Implements GDPR Article 5(1)(d) accuracy principle — operators can remove false-positive entities and apply a confidence threshold to avoid over-redaction.
- **Migration:** None. No schema changes.
