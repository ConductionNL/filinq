## Why

After PII analysis, an operator today must manually assign a grondslag (Woo Art. 5 / AVG legal basis) to every detected entity before the grondslagen summary becomes meaningful. This is repetitive and error-prone: within one organisation a given entity **type** (PERSON, BSN, EMAIL, …) almost always rests on the same grondslag. Proposing a per-type default at detection time removes the bulk of that manual work while keeping the operator in control. Because municipalities differ in which grondslagen apply — and maintain their own additional `base` records — the mapping must be configurable per instance rather than hard-coded.

## What Changes

- **NEW:** an instance-global configuration map `entityType → base slug(s)`, stored as Filinq app-config JSON under `filinq.grondslagen.entity_type_bases` (e.g. `{"PERSON":["base-slug"],"BSN":["base-slug"]}`). Values are arrays (default one base per type; multiple allowed).
- **NEW:** a per-entity-type grondslag selector in the Filinq admin settings panel. The selectable entity types come from a **curated list maintained in Filinq** (seeded from the entity types the anonymiser backend is known to emit). Each type maps to a multi-select of available `base` objects, so a municipality's own bases appear automatically.
- **NEW:** detection-time pre-fill — when entities are detected, each entity's `EntityRelation.bases` is populated from the mapping **only when it is empty**. Operator-assigned bases are never overwritten; entity types with no mapping are left empty.
- **No schema or migration change.** The existing `base` schema (slug `base`) and the existing `EntityRelation.bases` array are reused; `GrondslagenSummaryService` renders proposed bases unchanged.
- **Filinq-only.** No changes to OpenAnonymiser or OpenRegister are required for v1.

### Explicit non-goals (decided)
- No `proposed`-vs-`confirmed` flag on the entity relation — "fill only when empty" is the non-clobber guarantee, and the summary will not distinguish system-proposed from operator-confirmed bases.
- No retroactive refresh — changing the mapping does not update relations whose `bases` are already non-empty; only future detections onto empty `bases` get the new proposal.
- No global catch-all default basis for unmapped types.
- **Live backend-sourced entity types — deferred.** v1 uses a static curated list. Sourcing the list from the backend (and the cross-repo work it needs) waits until the OpenAnonymiser team exposes a supported-types endpoint.

## Capabilities

### New Capabilities
- `grondslag-proposal`: the `entityType → base` configuration map, the admin-settings selector that lists entity types from the backend (cached, with fallback) and offers the available `base` objects per type, and the detection-time "fill `bases` only when empty" behavior — including the non-clobber, no-retro-refresh, and leave-unmapped-empty rules.

### Modified Capabilities
<!-- None. The feature reuses the existing `base` schema and `EntityRelation.bases` field, renders within the existing Filinq admin-settings panel, and flows through the existing GrondslagenSummaryService without changing those capabilities' requirements. -->

## Impact

- **Code (filinq):**
  - `lib/Service/SettingsService.php` — new config key `filinq.grondslagen.entity_type_bases` (load/save as JSON).
  - `lib/Settings/FilinqAdmin.php` + `src/` Settings Vue — new per-entity-type base selector section.
  - `lib/Service/EntityDetectionService.php` / `lib/Service/EntityConsolidationService.php` — fill `bases` from the mapping when empty, at detection/normalization time.
  - a curated entity-type list maintained in Filinq (a constant), feeding the selector.
- **Deferred (future enhancement, not in this change):**
  - **OpenAnonymiser** (base service) — a read-only supported-types endpoint (e.g. `GET /api/v1/entity-types`) returning the active entity types (GLiNER `entity_mapping` targets ∪ enabled pattern recognizers' `supported_entities` ∪ spaCy NER labels; flavor-aware). Owned by the OpenAnonymiser team.
  - **OpenRegister** — a thin passthrough exposing that list to consuming apps (OR owns the AppAPI ExApp call + backend selection).
  - When both exist, Filinq swaps the curated-list source for the live backend list. The ExApp wrapper needs no change (its catch-all already forwards `/api/v1/*`).
- **Data:** no new schema, no migration. Reuses `base` (slug `base`) and `EntityRelation.bases`.
- **Compliance (GDPR/AVG + Woo Art. 5):** proposals are defaults only; the operator's review remains the decision of record; nothing auto-applies a basis for unmapped types. Processing stays local; no document content leaves Filinq.
- **Dependencies / risks:** the curated list can drift from the backend's actual emitted types — a type the backend emits but the list omits is simply unmappable (its `bases` stay empty) until the list is updated or the deferred backend endpoint replaces it.
