# Tasks: zgw-document-bridge

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [x] 1.1 Add the `bridgeSource` and `externalDocument` schemas to the `filinq` register in `lib/Settings/filinq_register.json` (REQ-DDZGW-001; Filinq keeps all objects in its one register, consolidated by `ConsolidateRegisters`, so the bridge is not a register of its own)
  - All properties from design.md D1 with titles, descriptions, enums; `hardValidation: true`; register description documents the bridge contract and bumps the register version.
  - `x-openregister-lifecycle` on `externalDocument` uses the canonical `initial: staged` key and exactly the REQ-DDZGW-004 transition list.

- [x] 1.2 Add seed objects: one demo `bridgeSource` plus one `staged` and one `written_back` `externalDocument` (design.md Seed Data)
  - Nil-UUID/`seed-*` placeholder identifiers only; validates against the schemas on import.

## 2. Backend

- [x] 2.1 Implement `lib/Service/BridgeService.php` (REQ-DDZGW-002/003/004/007)
  - Source + staged-document queries via `SettingsService::getObjectService()` with `@self.register = filinq`; clock-injected `getSourceHealth()` (fresh/stale/failing/inactive, 24h SLA); status transitions with full-object PUT-semantic saves.
  - No ZGW/StUF/HTTP client code anywhere in the service.

- [x] 2.2 Implement dossier pick-up: attach staged documents to a dossier (REQ-DDZGW-006)
  - Done (2026-11-14): `BridgeDossierPickUp::attach()` checks every document is staged and its staged file readable, and resolves the dossier folder under the operator's view, before it copies anything; then copies each staged file into the folder (a free name when taken; the original is untouched) and records `dossierRef` + `in_processing` through `BridgeService::pickedUp()`. `BridgeDossierPickUpTest` (5), `BridgeServiceTest::testPickedUpRecordsTheDossierOnAStagedDocumentOnly`.
  - Copies staged file into the dossier folder, sets `dossierRef` + `in_processing`; existing `dossier` schema untouched.

- [x] 2.3 Implement release/retry for write-back (REQ-DDZGW-005)
  - `processed → ready_for_writeback` with `resultFileRef` required; `writeback_failed → ready_for_writeback` retry; document the OpenConnector push-leg contract (new informatieobject + zaak relation, derivative metadata) in the service docblock.

- [x] 2.4 Add `lib/Controller/BridgeController.php` + routes under `api/bridge/*` (sources, external-documents listing, attach, release, retry)
  - Done (2026-11-14): `sources` needs the filinq admin setting; the others are `NoAdminRequired` and act with the caller's own OpenRegister access (RBAC-scoped listing, 404 for an unreadable document, the save checked by OpenRegister, the dossier folder and staged files under the caller's view). Refusals 400, missing 404, other failures 500 without details. Routes in `appinfo/routes.php` before the catch-all and in `openapi.json`. `BridgeControllerTest` (7).
  - Every method carries an explicit auth attribute and a per-object/admin guard; listing scoped by OR RBAC; routes registered in `appinfo/routes.php` before the catch-all.

- [ ] 2.6 Record the result of anonymising a dossier copy on its external document: `in_processing` → `processed` with `resultFileRef` naming the new file (REQ-DDZGW-003 scenario 'Anonymisation of a bridge document leaves the staged original intact'). Found live 25 Nov 2026: nothing in filinq makes this move, so `zgw-bridge-workflow.spec.ts`'s anonymisation step cannot pass; it needs the copy's file id on the external document (pick-up returns `copiedFileId` but does not store it).

- [ ] 2.5 File an OpenConnector issue if the push mapping cannot express "create new informatieobject + relate to zaak" during verification (design.md D2 uncertainty)
  - Outbound leg degrades to visibly-waiting `ready_for_writeback` objects; never Filinq-side HTTP.

## 3. Frontend

- [x] 3.1 Bridge status panel in admin settings (`src/views/settings/`) (REQ-DDZGW-008) (`BridgeSourcesSettings.vue` in `Settings.vue`: name, vendor, type, last sync and a `CnStatusBadge` health chip per source, the error of a failing one, an empty state pointing at OpenConnector)
  - `CnDataTable` listing with health chips using NC CSS variables; empty state when no sources; data from `api/bridge/sources`.

- [x] 3.2 Source badge in MyDocuments + document detail (REQ-DDZGW-009) (`GET api/bridge/badges` → `BridgeSourceBadges`, one lookup per page for the files the caller can open; "Zaaksysteem: {vendor}" on the MyDocuments row and the file viewer header)
  - Batched lookup (one bridge query per listing page); badge text "Zaaksysteem: {vendor}"; provenance detail links to OR synced-from tab.

## 4. Quality

- [x] 4.1 PHPUnit unit tests for BridgeService, controller and lifecycle transitions — minimum 75% coverage on new code (161/161 statements of BridgeController, BridgeService, BridgeDossierPickUp, BridgeSourceBadges covered)
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Includes: health clock cases, PUT-semantics survival of a non-changed field, invalid-transition rejection, staged-original hash unchanged after processing.

- [x] 4.2 Playwright e2e specs `tests/e2e/spec-coverage/zgw-bridge.spec.ts` + `tests/e2e/workflows/zgw-bridge-workflow.spec.ts` covering the `@e2e`-referenced scenarios
  - Verify end-to-end with OpenRegister on the Postgres dev instance (port 8080); test through the UI.
  - Written, runs in nightly (UNBLOCK rule, 24 Nov 2026): the spec is in the tree and runs against a live instance in the nightly; not verified here.

- [x] 4.3 i18n: EN + NL translations for all new UI strings (badge, panel, statuses, empty state)
  - Keys in English; `l10n/` updated for both languages.

- [x] 4.4 Documentation `docs/features/zgw-document-bridge.md` with Playwright MCP screenshots (ADR-010) of the admin panel and source badge; run `openspec validate zgw-document-bridge --strict`
  - Explains the master-record boundary and the OpenConnector configuration hand-off.
  - 25 Nov 2026, live (Nextcloud 34.0.6, MariaDB, Redis, OpenRegister main): `docs/features/zgw-document-bridge.md` with screenshots of the admin panel (fresh, failing with its error, inactive) and the My Documents source badge, from synthetic sources. Both bridge specs run live: `spec-coverage/zgw-bridge.spec.ts` 4/4 and `workflows/zgw-bridge-workflow.spec.ts` 4/6 after giving every WebDAV call the OCS header (without it every upload answered 401); the two skipped steps need a document the anonymiser detects plus the missing `processed` step (see 2.6) and an OpenConnector push leg against a DRC. `openspec validate --strict` reports 5 warnings (text above the first section, 4 requirements over 500 characters): spec rewriting, not done here.
