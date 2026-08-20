# Tasks: e-discovery-legal-hold

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 15.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `legalHoldCase` schema to the document register in `lib/Settings/docudesk_register.json` (REQ-DDEDL-001)
  - Properties/enums per design.md D1; `hardValidation: true`; `x-openregister-lifecycle` with canonical `initial: active` and only `active → released`; `archive.defaultNominatie: bewaren`; no `x-openregister-archival`; register version bump.

- [ ] 1.2 Seed one demo `woo-appeal` case (design.md Seed Data)
  - Nil-UUID scope references and `seed-*` users only; validates on import.

## 2. Backend

- [ ] 2.1 Implement `lib/Service/LegalHoldCaseService.php` — activation fan-out (REQ-DDEDL-002)
  - Places OR legal holds (reason `docudesk-hold-case:{uuid}`) per in-scope object via OR's legal-hold API; per-object fan-out status persisted on the case; retry for failures; pre-existing third-party holds recorded, never overwritten; verify the exact OR endpoint/service signatures against OR HEAD, not this spec's snapshot.

- [ ] 2.2 Implement overlap-safe release + incremental scope additions (REQ-DDEDL-003, REQ-DDEDL-006)
  - Coverage derived by querying active cases' scopes (no duplicated state); release lifts a hold only when no other active case covers the object, otherwise re-stamps the reason; mandatory releaseReason; additions to an active case fan out only for the added references; PUT-semantic saves carry ALL case fields forward.

- [ ] 2.3 Implement notifications on place and release (REQ-DDEDL-004)
  - NC notification manager with an absolute icon URL; owners of in-scope documents/dossiers + custodian; recipients recorded in `notifiedOwners[]`.

- [ ] 2.4 Add `lib/Controller/LegalHoldCaseController.php` + routes (create/release/add-scope/fan-out-status)
  - Every method carries an explicit auth attribute AND a server-side authority-group guard (semantic-auth: annotation matches the actual requirement); reads scoped by OR RBAC; routes registered before any catch-all.

- [ ] 2.5 File the OpenRegister issue for native multi-hold support (design.md D3 limitation)
  - Single hold slot per object forces the case-layer overlap ledger; link on tracking issue #234 with the adopt-and-delete plan.

- [ ] 2.6 Implement the `files_lock` file-level freeze backstop (REQ-DDEDL-007)
  - On activation place an app-scoped lock (`\OCP\Files\Lock\ILockManager`, `ILock::TYPE_APP`, owner = app id) on each in-scope document's file node; on release unlock overlap-safe (only when no other active case covers the file); probe `isLockProviderAvailable()` and record the backstop as unavailable when `files_lock` is absent (never claim file-level protection); per-object lock/unlock failures visible + retried like the record fan-out. OR record freeze stays primary.

## 3. Frontend

- [ ] 3.1 Hold register page + case detail (REQ-DDEDL-005)
  - `CnIndexPage`/`CnDataTable` with status/type/custodian filters; detail with scope list, per-object fan-out status, audit block; release via `CnFormDialog` with mandatory reason; modals in `src/modals/`; NcSelect with `inputLabel`; NC CSS variables only.

- [ ] 3.2 Active-hold indicator on document/dossier detail + Archiefbeheer case-name resolution (REQ-DDEDL-002, REQ-DDEDL-005)
  - Badge naming covering case(s); destruction-adjacent actions disabled with explanatory state; vernietigingslijst detail resolves hold exclusions to case names; dossier surface integration through the dossier capability's extension point (no dossier-register schema change — sibling ownership).

## 4. Quality

- [ ] 4.1 PHPUnit unit tests — minimum 75% coverage on new code (ADR-009)
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Includes: overlap matrix (two cases/one record, release order permutations) for BOTH the OR record hold and the files_lock file lock, partial fan-out failure visibility, file-lock backstop-unavailable degradation, incremental additions, release-reason guard, notification recipients, controller 403 for non-authority users, register-lint (lifecycle shape, bewaren config, no archival annotation), PUT-semantics survival of a non-changed case field.

- [ ] 4.2 Playwright e2e `tests/e2e/workflows/e-discovery-legal-hold.spec.ts` covering the `@e2e`-referenced scenarios
  - Create case → fan-out status → held record excluded from vernietigingslijst (case name shown) → owner notification → blocked reason-less release → release; verify on the Postgres dev instance (port 8080); test through the UI.

- [ ] 4.3 i18n: EN + NL translations for all new UI strings (ADR-005)
  - Keys in English; NL legal vocabulary (bezwaar, bewaring, vrijgave) correct in the NL locale.

- [ ] 4.4 Documentation `docs/features/e-discovery-legal-hold.md` with Playwright MCP screenshots (ADR-010)
  - Covers the hold lifecycle, overlap behaviour, the ZyLAB non-goal boundary, and the two-layer freeze (OR record-destruction freeze as primary + `files_lock` file-deletion backstop, and its honest degradation when `files_lock` is absent).

- [ ] 4.5 Gates + validation
  - `composer check:strict` zero new violations; `openspec validate e-discovery-legal-hold --strict` exits 0; fix pre-existing quality issues encountered on touched files.
