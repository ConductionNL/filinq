# Tasks: intake-base-registry-enrichment

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register and settings

- [x] 1.1 Add `registrySuggestion` and `registryLookupNote` to `intakeDocument`, `brpPurpose` to `intakeDefaultRule`, the BRP lookup to the `x-openregister-processing` entry, seed and version bump (REQ-IBR-001, REQ-IBR-002). Verify: `occ maintenance:repair` imports them and the seed shows in `/intake`.
- [x] 1.2 Add the admin switch `intake.brp_lookup_enabled`, default off (D3) (REQ-IBR-002). Verify: Playwright on the admin settings page reads the switch off.

## 2. Lookup

- [x] 2.1 Add `lib/Service/RegistryEnrichmentService.php` resolving `KvkProvider` and `BrpPersonProvider` duck-typed (D2) and writing suggestions or a note (D4) (REQ-IBR-001, REQ-IBR-003). Verify: PHPUnit with both providers present, absent and failing.
- [x] 2.2 Queue the enrichment job from `IntakeService::receive()` after the save (D1) (REQ-IBR-001). Verify: PHPUnit asserts the job is queued and `receive()` returns before any lookup.
- [x] 2.3 Guard the BRP path: rule purpose, admin switch, elfproef through openregister's `BsnFormat`, no BSN in logs or notes (D3) (REQ-IBR-002). Verify: PHPUnit captures the logger and asserts the BSN never appears.

## 3. Decision

- [x] 3.1 Add accept and dismiss of a registry suggestion to `IntakeController` beside `decideParty()`, session user only (D4) (REQ-IBR-004). Verify: Newman for accept and dismiss; PHPUnit that accept writes the party fields and `written: true`.
- [x] 3.2 (`src/dialogs/IntakeAssignDialog.vue` lists the open registry suggestions and the lookup note through `src/components/intake/IntakeSuggestionCard.vue`, with accept, correct (name, address, KvK number) and dismiss, sent by `decideRegistrySuggestion()` in `src/services/intakeSuggestions.js`; `tests/vitest/intakeSuggestions.spec.js`; the Playwright run needs a live instance) Show the registry suggestion in the assign dialog of `src/views/intake/IntakeIndex.vue` with accept, correct and dismiss (REQ-IBR-004). Verify: Playwright accepts the seeded KvK suggestion.

- [x] 3.3 (the same dialog lists the party suggestions with confirm, correct and dismiss through `decidePartySuggestion()`. A confirmation is sent as `accepted`, a changed correction as `edited`, a dismissal as `rejected`. Same spec; the Playwright run needs a live instance) Show the party suggestion (`partySuggestion` on the intake document) in `src/views/intake/IntakeIndex.vue` with confirm, correct and dismiss through `decidePartySuggestion()` in `src/services/intakeService.js` (row in-party, REQ-IDW-03). Verify: Playwright confirms a suggested sender.

## 4. Quality

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010), PHPUnit at 75% on new code (ADR-009). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
  - Coverage measured (26 Nov 2026, pcov over the full unit suite on development 6f987cc): 821 of 869 statements, 94.5%, across the 9 lib files that carry this change's `@spec` tag; lowest `IntakeController` at 82.9%. The 75% figure is met; the `docs/features/` screenshots (need a live instance) keep this open.
