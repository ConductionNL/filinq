# Tasks: custom-dictionary-recognition

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add `customDictionary` and `customDictionaryTerm` schemas to the `document` register in `lib/Settings/docudesk_register.json` (REQ-DDCDR-001)
  - `customDictionary` (label, description, colour, `matchMode` enum default `caseInsensitive`, `fuzzy` bool, `active` bool, `termCount` calculated); `customDictionaryTerm` (value, optional label, dictionary uuid ref); register-i18n on user-facing string fields; register version bump with changelog; seed one demo dictionary + two demo terms (design.md Seed Data). Schema refs in slug form.

## 2. Backend — matcher + catalogue

- [ ] 2.1 Implement `lib/Service/CustomDictionaryMatchService.php` pure matcher (REQ-DDCDR-002)
  - `exact` / `caseInsensitive` / `wordBoundary` modes; returns every occurrence with positions; blank terms skipped; longest-term-first ordering; `fuzzy` accepted-and-ignored. Pure function — no NC container.

- [ ] 2.2 Write matches into OR's catalogue as `CUSTOM_DICTIONARY` occurrences (REQ-DDCDR-003)
  - Lazy FQCN resolution of OR `EntityRelationMapper`; entity type `CUSTOM_DICTIONARY`, category `contextual_data`, `detectionMethod=custom_dictionary`, `confidence=1.0`, per-list label carried; re-run clears prior `custom_dictionary` relations for the file (no duplicates); best-effort, never blocks OR detection.

- [ ] 2.3 Hook the pass into `AnonymizationService::extractAndDetectEntities` respecting the type whitelist (REQ-DDCDR-003)
  - Runs after OR extraction, before normalize/policy; skipped when `CUSTOM_DICTIONARY` is disabled in `getEntityTypeWhitelist()`; a matcher failure is logged and surfaces a warning, not a crash.

## 3. Backend — CRUD + import

- [ ] 3.1 Implement `lib/Service/CustomDictionaryService.php` + `lib/Controller/CustomDictionaryController.php` org-gated CRUD (REQ-DDCDR-004)
  - `api/custom-dictionaries` (+ `/{uuid}`, `/{uuid}/terms`) via OR ObjectService; org gate fail-closed on every route (explicit auth attributes + in-method guard, semantic-auth); term-count enrichment; ADR-022 justification in the controller docblock.

- [ ] 3.2 Implement CSV / newline import `POST .../{uuid}/import` (REQ-DDCDR-005)
  - Accept `text/csv` (value + optional label column) or `text/plain` newline list; server-side trim/dedupe/skip-blank; bounded size; returns `{added, skipped, total}`; no client-side parsing.

## 4. Frontend (Manifest-V2 shell)

- [ ] 4.1 Custom dictionaries admin page + gated nav entry in `src/manifest.json` + `registry.js` (REQ-DDCDR-006)
  - `CnIndexPage`/`CnDataTable` (label, term count, match mode, active); NOT `src/router/index.js`; schema refs use slugs.

- [ ] 4.2 Dictionary detail/edit view with term management + import (REQ-DDCDR-006)
  - Term table (add/remove), import upload dialog in its own file under `src/dialogs/`, match-mode `NcSelect` with `inputLabel`, active toggle, colour picker; NL Design tokens.

## 5. Quality

- [ ] 5.1 PHPUnit unit tests for `CustomDictionaryMatchService` (all three modes, multi-occurrence, overlap/longest-first, blank skip) and `CustomDictionaryService` org-gate matrix + import dedupe — minimum 75% coverage on new code
  - Run in the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/custom-dictionary-recognition.spec.ts` covering the `@e2e` scenarios
  - Create a dictionary, import terms, extract a fixture document, assert a `CUSTOM_DICTIONARY` hit appears in review and is redacted; nldesign accessibility pass; test through the UI.

- [ ] 5.3 i18n EN + NL for all new UI strings (page, match-mode labels, import dialog, empty state)
  - Keys in English.

- [ ] 5.4 Documentation `docs/features/custom-dictionary-recognition.md` with Playwright MCP screenshots (ADR-010); run `openspec validate custom-dictionary-recognition --strict`
  - Documents the Octobox/Noordwijk positioning, org-scoping, the `CUSTOM_DICTIONARY` pipeline path and the deferred fuzzy flag.
