## Tasks

### Deduplication Check

- [ ] 1. **Deduplication check** — verify that `EntityRelationMapper` (OpenRegister) exposes a batch-scoped entity query; search `openregister/lib/Service/` and `openregister/lib/Db/` for `findEntitiesForBatch` or equivalent. Document findings (method name + class). If not available, identify the per-file query to iterate. Confirm no overlap with `ObjectService`, `RegisterService`, or `SchemaService` for this use-case. Record findings in a comment in `EntityConsolidationService`.

---

### Backend — Consolidated Entities Endpoint (REQ-ERV-001)

- [ ] 2. **`EntityConsolidationService` — deduplication logic** — create `lib/Service/EntityConsolidationService.php`. Implement `consolidateEntitiesForBatch(string $batchId, float $minConfidence = 0.0): array`. Use `EntityRelationMapper` to retrieve all per-file entity records for the batch. Group by `strtolower(value)`; for each group compute `highestConfidence = max(score)`, `fileCount = count(distinct fileId)`, canonical `type` from the highest-confidence occurrence (ties: first seen). Return sorted by `highestConfidence` DESC.

- [ ] 3. **WOO-profile `included` defaults** — within `EntityConsolidationService::consolidateEntitiesForBatch`, after deduplication, look up the active WOO anonymise profile (via existing `WooProfileService` or `IAppConfig` key — confirm during apply). Set `included = true` for entity types in the anonymise list, `included = false` for types in the keep list, `included = true` for unknown types (fail-safe over-redaction). Then apply `minConfidence`: entities with `highestConfidence < minConfidence` → `included = false` (overrides profile).

- [ ] 4. **`BatchAnonymizationController::entities` action** — add a new public action `entities(string $batchId, Request $request): JSONResponse` to `lib/Controller/BatchAnonymizationController.php`. Validate batch exists and is in "review" status; return HTTP 409 `{ "error": "Batch extraction is not yet complete" }` if not. Parse optional `minConfidence` float query parameter (default 0.0; reject values outside [0.0, 1.0] with HTTP 400). Call `EntityConsolidationService::consolidateEntitiesForBatch` and return HTTP 200 JSON array.

- [ ] 5. **Route registration** — register `GET /api/anonymization/batch/{batchId}/entities` in `appinfo/routes.php` pointing to `BatchAnonymizationController::entities`. Confirm auth annotation matches existing batch routes (use same `#[NoAdminRequired]` / `@NoAdminRequired` pattern).

- [ ] 6. **Backend unit tests** — in `tests/Unit/Service/EntityConsolidationServiceTest.php`: (a) deduplication groups by case-insensitive value; (b) `highestConfidence` is the max across files; (c) `fileCount` counts distinct files; (d) WOO profile sets `included` defaults correctly; (e) `minConfidence` overrides profile for low-confidence entities; (f) unknown entity type defaults to `included: true`. In `tests/Unit/Controller/BatchAnonymizationControllerTest.php`: (g) HTTP 409 for extracting-status batch; (h) HTTP 400 for invalid `minConfidence`; (i) HTTP 200 with entity array for review-status batch.

- [ ] 7. **Integration test — Newman** — add a Newman collection test for `GET /api/anonymization/batch/{batchId}/entities`: (a) 409 when batch is not in review status; (b) 200 with deduplicated, sorted entity list when batch is in review status; (c) `minConfidence=0.7` correctly sets included=false for low-confidence entities.

---

### Frontend — Entity Review Table Component (REQ-ERV-002, REQ-ERV-004, REQ-ERV-005, REQ-ERV-006)

- [ ] 8. **Pinia store — entity review slice** — add an `entityReview` slice to the anonymisation Pinia store (or create `src/store/entityReview.js`). State: `entities[]` (full list from API), `searchQuery: ''`, `typeFilter: null`, `sortColumn: 'highestConfidence'`, `sortDirection: 'desc'`. Mutations: `setEntities(list)`, `toggleEntity(value)`, `setAllVisible(included, visibleValues)`. Getters: `filteredEntities` (applies search + type filter + sort), `includedCount`, `totalCount`, `includedFileCount`.

- [ ] 9. **`EntityReviewTable.vue` — table structure** — create `src/components/EntityReviewTable.vue`. Render a table with columns: checkbox (bound to entity `included`), Type (NcBadge or span), Value (text, ellipsis overflow), Confidence (`Math.round(highestConfidence * 100) + '%'`), Files (`fileCount`). Each column header is clickable and toggles sort direction. Show a chevron icon (up/down) next to the active sort column.

- [ ] 10. **`EntityReviewTable.vue` — toolbar** — add a toolbar above the table with: (a) text search input (NcTextField or plain input) bound to `searchQuery`; (b) type dropdown (NcSelect or `<select>`) with options PERSON, ORGANIZATION, EMAIL, PHONE, BSN, IBAN, ADDRESS, LOCATION, DATE, OTHER, and an "All types" reset option, bound to `typeFilter`; (c) "Select All Visible" button → dispatches `setAllVisible(true, filteredValues)`; (d) "Deselect All Visible" button → dispatches `setAllVisible(false, filteredValues)`. Display filter count text: "{filteredCount} of {totalCount} entities".

- [ ] 11. **`EntityReviewTable.vue` — low-confidence indicator** — for each row where `entity.highestConfidence < minConfidence` (prop passed from parent), render a warning icon (NcIconWarning or equivalent) in the Type column alongside or replacing the badge. Apply a CSS class (e.g. `entity-row--low-confidence`) for muted styling via scoped styles.

- [ ] 12. **`EntityReviewTable.vue` — summary bar** — render a summary bar below the table with the text "X of Y entities selected for anonymization across Z files". Bind X to `includedCount`, Y to `totalCount`, Z to `includedFileCount` from store getters.

- [ ] 13. **`AnonymizationIndex.vue` — Review step** — insert a "Review" step (step 3) between "Analyse" and "Anonymise" in the existing wizard. On entering the Review step: fetch `GET /api/anonymization/batch/{batchId}/entities` and populate the store. Render `EntityReviewTable` with a `minConfidence` prop from a threshold selector (default 0.0, slider or number input). The Anonymise button is enabled only when `includedCount > 0`.

- [ ] 14. **Anonymise payload — included-only entities** — in `AnonymizationIndex.vue` (or the anonymise action in the store), build the `entities[]` array for the POST request by filtering to `included: true` entities only. Confirm the payload shape matches the existing `POST /api/anonymization/batch/{batchId}/anonymize` contract (no payload schema changes).

---

### Quality and Verification

- [ ] 15. **Frontend unit tests** — Vitest (or Jest) tests for `EntityReviewTable.vue`: (a) renders all 5 columns; (b) search filter hides non-matching rows; (c) type filter hides non-matching rows; (d) combined filter applies both conditions; (e) "Select All Visible" toggles only filtered rows; (f) "Deselect All Visible" toggles only filtered rows; (g) low-confidence rows show warning icon; (h) summary bar displays correct counts; (i) column header click updates sort direction.

- [ ] 16. **Composer + PHPStan** — run `composer check:strict` on all touched PHP files and fix any pre-existing issues. Confirm `EntityConsolidationService` passes strict type checks.

- [ ] 17. **Documentation** — update or add to `docs/features/anonymization.md`: document the Review step, the consolidated-entities endpoint (parameters, response shape), the confidence threshold feature, and the frontend review table controls. Update CHANGELOG: "Added: entity review step with consolidated-entities endpoint, confidence threshold filter, search/type filter, bulk-select actions, and sortable review table."
