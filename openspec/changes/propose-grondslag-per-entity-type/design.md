## Context

Filinq delegates PII detection and anonymisation to OpenRegister, which owns the AppAPI ExApp calls to the OpenAnonymiser backend and the persistence of per-entity `EntityRelation` rows (`OCA\OpenRegister\Db\EntityRelationMapper`). Filinq reads those rows via the mapper (`findEntitiesForFile`) and, in the existing Wave 1.3 grondslagen flow, PATCHes them — `EntityDetectionService::normalizeEntities()` already surfaces `relationId`, `bases`, and `skipAnonymization` precisely so a relation row can be updated. The `base` schema (slug `base`) holds grondslag records (name + description, Woo Art. 5 / AVG), which municipalities extend with their own. `GrondslagenSummaryService` renders `EntityRelation.bases` resolved against `base` into the auditable per-document / per-dossier summary.

Today an operator assigns those bases entirely by hand. This change adds a configurable `entityType → base` default and applies it as a proposal at detection time, leaving the operator's manual assignments authoritative.

The OpenAnonymiser backend currently exposes only `/api/v1/health`, `/api/v1/analyze`, and `/api/v1/anonymize` — there is **no endpoint that lists its supported entity types**, and that service is owned by a separate team. So v1 sources the selector's entity types from a curated list inside Filinq, and treats live backend-sourcing as a deferred enhancement.

Constraints: local-only processing (no document content leaves Filinq); GDPR/AVG + Woo Art. 5 auditability; ADR-011 (reuse OpenRegister utilities — here the `EntityRelationMapper`, register resolution, and `base` records rather than new persistence).

## Goals / Non-Goals

**Goals:**
- An instance-global, operator-editable `entityType → base[]` mapping.
- A settings selector whose entity types come from a curated in-Filinq list and whose base options come from the live `base` register.
- Pre-fill each detected entity's `bases` from the mapping when, and only when, it is empty — persisted so it flows through the existing summary.
- Filinq-only for v1; no schema/migration change.

**Non-Goals:**
- No `proposed`-vs-`confirmed` flag on the relation ("fill only when empty" is the non-clobber guarantee).
- No retroactive refresh when the mapping changes.
- No global catch-all default basis for unmapped types.
- No change to how detection runs, or to the `base` / `EntityRelation` schemas.
- No change to OpenAnonymiser or OpenRegister in v1 (live backend-sourced types are deferred).

## Decisions

### D1. Store the mapping as app-config JSON (not a new schema)
A single JSON object under `filinq.grondslagen.entity_type_bases`, read/written through `SettingsService` via `IAppConfig::getValueString` / `setValueString` — the same pattern as existing keys (e.g. `filinq.anonymisation.default_output_format`). Value shape: `{"<ENTITY_TYPE>": ["<base-slug>", …], …}`.
- **Why:** this is instance-wide operator configuration, not domain data; app-config is the established home and needs no migration.
- **Alternatives:** a new `entityBaseMapping` OpenRegister schema (register-scoped, seedable) — rejected for v1 as heavier (schema + CRUD + UI) without a current need for per-register/per-dossier variation.

### D2. Apply the proposal as a post-detection PATCH from Filinq, fill-when-empty
After OpenRegister has created the `EntityRelation` rows for a file/batch, Filinq iterates the detected entities and, for any relation whose `bases` is empty, PATCHes it with the mapped base slugs for that entity type via `EntityRelationMapper`. Relations with non-empty `bases` are skipped.
- **Why:** the relation rows are OR-owned but already Filinq-writable, and the Wave 1.3 flow already PATCHes them — reusing that path keeps the proposal logic in Filinq with **no OpenRegister code change**. Persisting (vs computing on read) makes the proposal part of the auditable record and lets the operator edit from a concrete starting point.
- **Alternatives:** (a) compute proposed bases on-read in the review UI — rejected: the decision is to persist at detection time; (b) have OpenRegister apply the fill during detection — rejected: pushes Filinq-specific policy into OR.
- **Idempotency / non-clobber:** the empty-check is the whole guard. A re-run only ever fills still-empty relations; it never overwrites, satisfying both the non-clobber and no-retro-refresh requirements without extra state.

### D3. Source selectable entity types from a curated in-Filinq list (v1)
The settings selector lists entity types from a curated constant maintained in Filinq, seeded from the entity types the OpenAnonymiser backend is known to emit — the GLiNER `entity_mapping` targets (e.g. `PERSON`, `LOCATION`, `STREET_ADDRESS`, `ORGANIZATION`, `NORP`, `POLITICAL_PARTY`, `INCOME`, `EDUCATION_LEVEL`) plus the enabled pattern recognizers' entity types (e.g. `EMAIL`, `PHONE_NUMBER`, `IBAN`, `BSN`, `DATE_TIME`, passport/driver's-licence/VAT/KvK/licence-plate/IP/case-number/MAC). The exact identifier strings MUST match what the backend emits on detections, so the mapping keys line up at pre-fill time.
- **Why:** the backend has no supported-types endpoint today and is owned by another team; a curated list keeps v1 a single-repo change and needs no live backend call.
- **Alternatives:** (a) learn the list from past detections — rejected: empty until documents are processed, and still needs a fallback for first use; (b) free-text entry — rejected for v1: error-prone identifier typing; can be added later if drift becomes painful.

### D4. Live backend-sourced entity types — deferred (not in this change)
When the OpenAnonymiser team adds a read-only supported-types endpoint (e.g. `GET /api/v1/entity-types`, flavor-aware), and OpenRegister exposes a thin passthrough to it (OR owns backend selection + ExApp auth), Filinq swaps the curated-list source (D3) for that live list — likely cached, with fallback to the curated list when unreachable. The ExApp wrapper needs no change (its catch-all already forwards `/api/v1/*`). This is tracked as a follow-up, not built here.
- **Why deferred:** we cannot modify the backend API as part of this change; the curated list is a clean, swappable seam (a single source-of-types function) so adopting the endpoint later is localized.

### D5. Reuse the `base` schema and `EntityRelation.bases` field; no new persistence
Proposed bases are written to the same `bases` array used by manual assignment, so `GrondslagenSummaryService` renders them with no change and no proposed/confirmed indicator. Cardinality is an array (default one selection, multiple allowed) — the field is already an array, so multi-base needs no migration.

## Risks / Trade-offs

- **Curated list drifts from the backend's actual types.** → A type the backend emits but the list omits is simply unmappable (its `bases` stay empty) — conservative, never wrong. Mitigated by keeping the list in one place and by D4 replacing it with the live endpoint later. Document the list's provenance (the plugin `entity_mapping` + recognizer types) next to the constant.
- **No audit distinction between proposed and operator-confirmed bases.** → Accepted for v1; "fill only when empty" still guarantees operator edits are never overwritten.
- **Mapping changes don't propagate to already-filled relations.** → Documented behavior; operators re-assign manually where needed.
- **Filinq writing OR-owned relation rows.** → Not new; reuses the established Wave 1.3 PATCH path and the `EntityRelationMapper` already injected via the container; guarded by the OpenRegister-availability check the consolidation service already performs.

## Migration Plan

No data migration (no schema change). **Single repo (Filinq):** ship the config key, the curated-list-backed selector, and the post-detection pre-fill together. Rollback is clean — removing the settings/pre-fill leaves all existing `bases` (proposed or manual) intact and the summary unchanged; clearing `filinq.grondslagen.entity_type_bases` disables proposals without code changes (future detections simply leave `bases` empty).

The deferred D4 enhancement (backend endpoint + OR passthrough) is a separate, later change owned partly by the OpenAnonymiser team.

## Seed Data (ADR-016)

This change introduces **no new schema**, so it adds no entries to `_registers.json`. It depends on `base` (grondslag) records, which are owned and seeded by the existing dossier/grondslagen changes (`add-dossier-schema` and the grondslagen waves). No new seed objects are required here; the settings selector reads whatever `base` records exist in the configured register (including municipality-added ones). If a clean demo environment has no `base` records, the selector simply offers none until bases are seeded by those changes.

## Open Questions

- **Exact curated entity-type identifiers** — confirm the precise `entity_type` strings the deployed backend flavor emits (from its `plugins.*.yaml` `entity_mapping` targets and the pattern recognizers) so the mapping keys match at pre-fill time. Seed the constant from the gpu/classic configs and verify against a live `/analyze` response during implementation.
- **Cardinality default in the UI** — array storage is settled; confirm the selector defaults to a single pick with an "add another" affordance.
