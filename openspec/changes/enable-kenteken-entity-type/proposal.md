---
kind: code
---

## Why

DocuDesk's automatic anonymisation detects a curated set of entity types, but `KENTEKEN` (Dutch vehicle registration / license plate) is not among them — so license plates are neither offered as a toggle in Settings nor carried in the entity-type whitelist sent to the OpenAnonymiser detector. OpenAnonymiser already recognises license plates; DocuDesk just needs to include `KENTEKEN` in its curated vocabulary so operators can enable/disable it and it is detected automatically.

## What Changes

- **MODIFIED:** `GrondslagProposalService::CURATED_ENTITY_TYPES` gains `KENTEKEN`. This single seam makes it (a) a selectable toggle in Settings (`getSelectableEntityTypes` → `grondslagEntityTypes`), (b) enabled by default (all-on), and (c) part of the entity-type whitelist handed to OpenRegister's detection call (`getEntityTypeWhitelist` → `AnonymizationService` → `extractFile(entity_types)`), which passes unmapped types through to OpenAnonymiser unchanged.
- **MODIFIED:** `src/services/entityTypes.js` `ENTITY_TYPES` gains `KENTEKEN` so the sidebar badge / in-document highlight resolve a colour for it (falls back to the shared default tint until a dedicated colour is assigned).
- **NO OpenRegister change:** `EntityRecognitionHandler::mapToPresidioEntityTypes` already passes unmapped types (like OpenAnonymiser's own tags) through unchanged, so `KENTEKEN` reaches the detector as-is.

## Capabilities

### Modified Capabilities

- `anonymization` — `KENTEKEN` is part of the curated, toggleable entity-type set detected automatically.

## Impact

- **Affected code:** `lib/Service/GrondslagProposalService.php`, `src/services/entityTypes.js`, `tests/unit/Service/GrondslagProposalServiceTest.php`.
- **No schema, no migration, no new dependency, no HTTP surface change.**
- **Cross-app:** relies on the OpenAnonymiser ExApp recognising license plates (confirmed); no OpenRegister code change.
