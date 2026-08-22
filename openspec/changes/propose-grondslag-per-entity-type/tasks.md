## 1. Mapping configuration

- [x] 1.1 `SettingsService`: add `filinq.grondslagen.entity_type_bases` — load via `json_decode(getValueString(...))` (default `{}`), save via `setValueString(json_encode(...))`; value is `{ "<TYPE>": ["<base-slug>", …] }`.
- [x] 1.2 Expose the mapping in the existing settings GET/PUT REST surface alongside the other settings keys.

## 2. Curated entity-type list + base options (data for the selector)

- [x] 2.1 Define a curated entity-type constant in Filinq, seeded from the backend's known emitted types (GLiNER `entity_mapping` targets + enabled pattern recognizers' types); document its provenance and verify the identifier strings against a live `/analyze` response.
- [x] 2.2 Load available `base` records (name + slug) from the configured register so operator-added bases appear automatically.
- [x] 2.3 Keep the type-source behind a single function/seam so the deferred backend-sourced list (see §7) can replace it without touching the UI or pre-fill.

## 3. Settings UI (Vue)

- [x] 3.1 Add a grondslag-mapping section to the Filinq settings page: one row per curated entity type (from 2.1), each with a multi-select of `base` records (from 2.2), defaulting to a single selection.
- [x] 3.2 Persist selections to the mapping config (1.2).

## 4. Detection-time pre-fill

- [x] 4.1 After detection creates the `EntityRelation` rows (post-detection / consolidation path), for each detected entity whose relation `bases` is empty, resolve the mapped base slug(s) for its type and PATCH `bases` via `EntityRelationMapper`.
- [x] 4.2 Skip relations whose `bases` is already non-empty (non-clobber); leave entity types with no mapping empty (no catch-all default).
- [x] 4.3 Guard on OpenRegister availability (reuse the existing consolidation-service check); no-op cleanly when unavailable.

## 5. Tests

- [x] 5.1 Pre-fill behavior: empty `bases` → filled from mapping; non-empty → unchanged; multiple mapped bases all applied; unmapped type → empty.
- [x] 5.2 No retroactive refresh: changing the mapping leaves existing filled relations untouched; re-analysis fills only still-empty relations.
- [x] 5.3 Settings: save/load the mapping; selector lists the curated entity types and `base` options including an operator-added base.
- [x] 5.4 Summary: proposed bases render in `GrondslagenSummaryService` identically to manually assigned bases (no proposed/confirmed indicator).

## 6. Quality & docs

- [x] 6.1 `composer check:strict` passes (PHPCS, PHPMD, Psalm, PHPStan); fix any pre-existing issues in touched files.
- [x] 6.2 Update Filinq settings/help docs to describe the grondslag-per-entity-type mapping.
- [x] 6.3 No `_registers.json` change (no new/modified schema); verify existing `base` seed records suffice for a demo environment.

## 7. Deferred (follow-up, not in this change)

- [ ] 7.1 OpenAnonymiser team: add a read-only supported-types endpoint (e.g. `GET /api/v1/entity-types`, flavor-aware). Owned by that team.
- [ ] 7.2 OpenRegister: thin passthrough exposing that list to apps (reuses backend selection + ExApp auth).
- [ ] 7.3 Filinq: swap the curated-list seam (2.3) for the live backend list, cached with fallback to the curated list.
