## Context

`GrondslagProposalService::CURATED_ENTITY_TYPES` is the single source of the selectable entity-type vocabulary. It backs three things: `getSelectableEntityTypes()` (the Settings toggle list, served as `grondslagEntityTypes`), `getEnabledEntityTypes()` (all-on default), and `getEntityTypeWhitelist()` (the subset handed to OpenRegister detection; null when everything is enabled). The whitelist reaches OpenAnonymiser via `AnonymizationService` → `TextExtractionService::extractFile(entity_types)` → `EntityRecognitionHandler`, whose `mapToPresidioEntityTypes()` maps known types to Presidio names and passes unmapped types (e.g. BSN, GLiNER tags) through unchanged.

## Goals / Non-Goals

**Goals:** make `KENTEKEN` a first-class curated entity type — toggleable in Settings, enabled by default, and included in the whitelist sent to the detector.

**Non-Goals:** any OpenRegister change (unmapped types already pass through); a bespoke colour for `KENTEKEN` (uses the shared default tint); a regex fallback (OpenAnonymiser detects license plates).

## Decisions

### D1 — Add `KENTEKEN` to `CURATED_ENTITY_TYPES` only

Adding the one constant entry is sufficient for all three behaviours (toggle, default-on, whitelist). No new config key, endpoint, or service is needed. The type identifier is `KENTEKEN` (the term OpenAnonymiser emits/accepts).

### D2 — Add `KENTEKEN` to the frontend colour vocabulary

`entityTypes.js`'s `ENTITY_TYPES` drives `normaliseEntityType`/`entityTypeColor`. Adding `KENTEKEN` lets a dedicated `--dd-entity-color-kenteken` be introduced later; until then it resolves to the shared default tint (unknown types already fall back, so this is consistency, not a fix).

### D3 — No display-label translation

The Settings selector renders the raw type code, and `KENTEKEN` is already the Dutch term, so no new i18n string is added.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| OpenAnonymiser does not actually emit `KENTEKEN` | Confirmed it recognises license plates; unmapped whitelist types pass through unchanged, so the identifier reaches the detector as-is. If the emitted tag differs, only `CURATED_ENTITY_TYPES` needs the correct string. |
| A stored subset selection predating this change omits `KENTEKEN` | `getEnabledEntityTypes()` sanitises against the curated list; a pre-existing subset simply won't include `KENTEKEN` until the operator enables it — expected. |

## Migration Plan

Code-only; no migration. Default behaviour (all types enabled → whitelist null → detect all) already surfaced license plates when OpenAnonymiser returned them; this change makes `KENTEKEN` explicitly toggleable and whitelist-carryable.

## Open Questions

- A dedicated highlight colour for `KENTEKEN` (currently the default tint).
