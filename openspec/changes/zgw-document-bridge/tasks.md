# Tasks: zgw-document-bridge

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 13.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `bridge` register with `bridgeSource` and `externalDocument` schemas to `lib/Settings/filinq_register.json` (REQ-DDZGW-001)
  - All properties from design.md D1 with titles, descriptions, enums; `hardValidation: true`; register description documents the bridge contract and bumps the register version.
  - `x-openregister-lifecycle` on `externalDocument` uses the canonical `initial: staged` key and exactly the REQ-DDZGW-004 transition list.

- [ ] 1.2 Add seed objects: one demo `bridgeSource` plus one `staged` and one `written_back` `externalDocument` (design.md Seed Data)
  - Nil-UUID/`seed-*` placeholder identifiers only; validates against the schemas on import.

## 2. Backend

- [ ] 2.1 Implement `lib/Service/BridgeService.php` (REQ-DDZGW-002/003/004/007)
  - Source + staged-document queries via `SettingsService::getObjectService()` with `@self.register = bridge`; clock-injected `getSourceHealth()` (fresh/stale/failing/inactive, 24h SLA); status transitions with full-object PUT-semantic saves.
  - No ZGW/StUF/HTTP client code anywhere in the service.

- [ ] 2.2 Implement dossier pick-up: attach staged documents to a dossier (REQ-DDZGW-006)
  - Copies staged file into the dossier folder, sets `dossierRef` + `in_processing`; existing `dossier` schema untouched.

- [ ] 2.3 Implement release/retry for write-back (REQ-DDZGW-005)
  - `processed → ready_for_writeback` with `resultFileRef` required; `writeback_failed → ready_for_writeback` retry; document the OpenConnector push-leg contract (new informatieobject + zaak relation, derivative metadata) in the service docblock.

- [ ] 2.4 Add `lib/Controller/BridgeController.php` + routes under `api/bridge/*` (sources, external-documents listing, attach, release, retry)
  - Every method carries an explicit auth attribute and a per-object/admin guard; listing scoped by OR RBAC; routes registered in `appinfo/routes.php` before the catch-all.

- [ ] 2.5 File an OpenConnector issue if the push mapping cannot express "create new informatieobject + relate to zaak" during verification (design.md D2 uncertainty)
  - Outbound leg degrades to visibly-waiting `ready_for_writeback` objects; never Filinq-side HTTP.

## 3. Frontend

- [ ] 3.1 Bridge status panel in admin settings (`src/views/settings/`) (REQ-DDZGW-008)
  - `CnDataTable` listing with health chips using NC CSS variables; empty state when no sources; data from `api/bridge/sources`.

- [ ] 3.2 Source badge in MyDocuments + document detail (REQ-DDZGW-009)
  - Batched lookup (one bridge query per listing page); badge text "Zaaksysteem: {vendor}"; provenance detail links to OR synced-from tab.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for BridgeService, controller and lifecycle transitions — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - Includes: health clock cases, PUT-semantics survival of a non-changed field, invalid-transition rejection, staged-original hash unchanged after processing.

- [ ] 4.2 Playwright e2e specs `tests/e2e/spec-coverage/zgw-bridge.spec.ts` + `tests/e2e/workflows/zgw-bridge-workflow.spec.ts` covering the `@e2e`-referenced scenarios
  - Verify end-to-end with OpenRegister on the Postgres dev instance (port 8080); test through the UI.

- [ ] 4.3 i18n: EN + NL translations for all new UI strings (badge, panel, statuses, empty state)
  - Keys in English; `l10n/` updated for both languages.

- [ ] 4.4 Documentation `docs/features/zgw-document-bridge.md` with Playwright MCP screenshots (ADR-010) of the admin panel and source badge; run `openspec validate zgw-document-bridge --strict`
  - Explains the master-record boundary and the OpenConnector configuration hand-off.
