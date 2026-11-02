# Tasks: intake-base-registry-enrichment

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. Register and settings

- [ ] 1.1 Add `registrySuggestion` and `registryLookupNote` to `intakeDocument`, `brpPurpose` to `intakeDefaultRule`, the BRP lookup to the `x-openregister-processing` entry, seed and version bump (REQ-IBR-001, REQ-IBR-002). Verify: `occ maintenance:repair` imports them and the seed shows in `/intake`.
- [ ] 1.2 Add the admin switch `intake.brp_lookup_enabled`, default off (D3) (REQ-IBR-002). Verify: Playwright on the admin settings page reads the switch off.

## 2. Lookup

- [ ] 2.1 Add `lib/Service/RegistryEnrichmentService.php` resolving `KvkProvider` and `BrpPersonProvider` duck-typed (D2) and writing suggestions or a note (D4) (REQ-IBR-001, REQ-IBR-003). Verify: PHPUnit with both providers present, absent and failing.
- [ ] 2.2 Queue the enrichment job from `IntakeService::receive()` after the save (D1) (REQ-IBR-001). Verify: PHPUnit asserts the job is queued and `receive()` returns before any lookup.
- [ ] 2.3 Guard the BRP path: rule purpose, admin switch, elfproef through openregister's `BsnFormat`, no BSN in logs or notes (D3) (REQ-IBR-002). Verify: PHPUnit captures the logger and asserts the BSN never appears.

## 3. Decision

- [ ] 3.1 Add accept and dismiss of a registry suggestion to `IntakeController` beside `decideParty()`, session user only (D4) (REQ-IBR-004). Verify: Newman for accept and dismiss; PHPUnit that accept writes the party fields and `written: true`.
- [ ] 3.2 Show the registry suggestion in the assign dialog of `src/views/intake/IntakeIndex.vue` with accept, correct and dismiss (REQ-IBR-004). Verify: Playwright accepts the seeded KvK suggestion.

## 4. Quality

- [ ] 4.1 Dutch and English strings (ADR-005), `@spec` tags, `docs/features/` with screenshots (ADR-010), PHPUnit at 75% on new code (ADR-009). Verify: `npm run check:l10n`, `npm run lint`, `composer check:strict`.
