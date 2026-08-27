# Tasks: reversible-pseudonymization

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + schema

- [ ] 1.1 Add the `pseudonymMap` schema to the `document` register + additive `mappingRef` on `anonymizationLink` in `lib/Settings/filinq_register.json` (REQ-DDRPS-001)
  - `pseudonymMap` (`anonymizationLink` ref, `sourceFileId` facetable, `mappings` as `writeOnly` + `_render:false`, `algorithm`, `entryCount`, `scope`); nullable `mappingRef` added to `anonymizationLink` with no other field change; register-i18n on user-facing labels; register version bump with changelog; no seed map (would fabricate PII). Schema refs in slug form.

## 2. Backend — store + reversible mode

- [ ] 2.1 Implement `lib/Service/PseudonymMapService.php` encrypt-and-store (REQ-DDRPS-002)
  - Join OR `getLastPlaceholderMap()` with request entity values into `placeholder → {originalValue, entityType}`; `ICrypto::encrypt()` the JSON into the `_render:false` `mappings` field via OR ObjectService; idempotent per `anonymizationLink`; `read()` decrypts and is callable only from the restore path.

- [ ] 2.2 Wire a `reversible` mode into `AnonymizationService::anonymizeDocument` (REQ-DDRPS-003)
  - Default false = today's irreversible behaviour, stores nothing; when true, capture the placeholder map + originals after OR anonymisation and store via 2.1; create/update/delete the map in lockstep with `recordAnonymizationLink()` (lifecycle tied to the link, orphan-free).

## 3. Backend — restore (gated + audited)

- [ ] 3.1 Implement `lib/Service/PseudonymRestoreService.php` reversal (REQ-DDRPS-004)
  - Decrypt the map; reverse placeholders → originals longest-placeholder-first into a distinct restored copy (never mutate the anonymised file); return a re-identification report instead of a corrupted file when in-place text rewrite is unsafe.

- [ ] 3.2 Implement `lib/Controller/PseudonymisationController.php` + `api/pseudonymisation/{link}/restore` fail-closed + audit (REQ-DDRPS-005)
  - `filinq.pseudonymisation.restore_allowed_groups` (default admins-only, fail-closed incl. config-read failure); explicit auth attributes + in-method gate (semantic-auth); audit-log every restore AND every denial via OR audit trail; a failed audit write refuses the restore.

## 4. Frontend

- [ ] 4.1 Anonymise-mode choice (reversible vs irreversible) in the anonymise dialog (REQ-DDRPS-003, REQ-DDRPS-006)
  - `NcSelect`/radio with `inputLabel`, default irreversible; Manifest-V2 shell; NL Design tokens.

- [ ] 4.2 Gated "Restore original" action + audit-notice confirm dialog (REQ-DDRPS-006)
  - Shown only to permitted users; confirm dialog in its own file under `src/dialogs/` states the restore is audit-logged before proceeding; registered via `src/manifest.json` + `registry.js` (not `src/router/index.js`).

## 5. Quality

- [ ] 5.1 PHPUnit unit tests: `PseudonymMapService` encrypt/decrypt round-trip + `_render:false` payload never in a read, `PseudonymRestoreService` reversal (longest-first, copy-not-mutate, unsafe-format report), controller authz matrix + audit-on-deny + failed-audit-refuses — minimum 75% coverage on new code
  - Run in the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 5.2 Playwright e2e `tests/e2e/spec-coverage/reversible-pseudonymization.spec.ts` covering the `@e2e` scenarios
  - Reversibly anonymise a fixture, confirm placeholders, restore as a permitted user and get the original back, and assert a non-member is refused with 403; nldesign accessibility pass; test through the UI.

- [ ] 5.3 i18n EN + NL for mode labels, restore action, audit notice, 403 message, and documentation `docs/features/reversible-pseudonymization.md` (ADR-010); run `openspec validate reversible-pseudonymization --strict`
  - Keys in English; docs cover the reuse of OR's placeholder emission, the encrypted `_render:false` store, the fail-closed audited restore, and the Presidio-PII-Shield/xxllnc positioning.
