# Tasks — enable-kenteken-entity-type

> DocuDesk-only. No OpenRegister change (unmapped whitelist types already pass through to OpenAnonymiser). No schema, migration, or new dependency.

## 1. Add KENTEKEN to the curated vocabulary

- [x] 1.1 Add `KENTEKEN` to `GrondslagProposalService::CURATED_ENTITY_TYPES` (makes it selectable in Settings, enabled by default, and whitelist-carryable).
- [x] 1.2 Add `KENTEKEN` to `ENTITY_TYPES` in `src/services/entityTypes.js` (colour/badge normalisation; falls back to the default tint).

## 2. Tests

- [x] 2.1 Extend `GrondslagProposalServiceTest::testGetSelectableEntityTypesReturnsCuratedList` to assert `KENTEKEN` is selectable.

## Acceptance criteria

- The Settings entity-type selector shows a `KENTEKEN` toggle; it is on by default.
- When a subset selection includes `KENTEKEN`, it is carried in the whitelist to the detector.
- No OpenRegister, schema, migration, or dependency change.

## Quality / test / i18n reminders

- `openspec validate "enable-kenteken-entity-type"` passes.
- phpcs/eslint clean on the changed files; unit test runs in-container.
- No new user-facing string (KENTEKEN is the raw code shown, already Dutch), so no new i18n key.
