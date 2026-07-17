# Tasks: entity-search

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `entitySearchLog` schema to the `document` register in `lib/Settings/docudesk_register.json` (REQ-DDESR-004)
  - Properties per design.md D4 (`action`, `queryDigest`, `typeFilter`, `categoryFilter`, `resultCount`, `entityRef`, `occurrenceCount`, `collectedInto`, `performedBy`, `performedAt`); `x-openregister-processing` annotation `docudesk-entity-search` (rechtsgrond `public-task`, `logReads: true`); register-i18n tags on user-facing string fields; register version bump with changelog entry; one seed log object (nil-digest placeholder, design.md Seed Data).

## 2. Backend

- [ ] 2.1 Implement `lib/Service/EntitySearchService.php` catalogue query + organisation scoping (REQ-DDESR-001)
  - Lazy container resolution of OR's `EntityMapper`/`EntityRelationMapper`/`RiskLevelService` by FQCN (EmlBackend pattern; loadable without OR, explanatory unavailable state); substring/type/category query with pagination; fail-closed org scoping via OR `OrganisationService` mirroring #1825 semantics.

- [ ] 2.2 Implement occurrence enrichment (REQ-DDESR-002)
  - Group relations per fileId; resolve file name/path via `IRootFolder` honouring caller ACLs (opaque no-access aggregate); dossier membership via `@self.folder` search; anonymisation state via faceted `anonymizationLink` lookups both directions; risk level via OR `RiskLevelService`; object/email relations in an "other occurrences" group.

- [ ] 2.3 Implement the permission gate + `lib/Controller/EntitySearchController.php` with `api/entity-search/*` routes (REQ-DDESR-003)
  - `docudesk.entity_search.allowed_groups` (default admins-only, fail-closed incl. config read failure); explicit auth attributes on every method; in-method gate (semantic-auth); routes registered before any catch-all; ADR-022 justification (gate + log + enrichment) documented in the controller docblock.

- [ ] 2.4 Implement the fail-closed processing log (REQ-DDESR-004)
  - One log object per search/detail before the response; sha256 digest of lower-cased trimmed query, never the raw value; append-only (no update/delete endpoint); log-write failure refuses the lookup; `collectedInto` recorded on handoff.

- [ ] 2.5 Implement the Woo-request collection handoff (REQ-DDESR-006)
  - Presence-gated on woo-request-workflow + a `collecting` request; delegates to the existing collection endpoint (no copying/hashing/dedupe logic here); hidden-not-broken without it.

## 3. Frontend

- [ ] 3.1 Entity search index manifest page + gated navigation entry (REQ-DDESR-001, REQ-DDESR-005)
  - `CnIndexPage`/`CnDataTable`; search input, type/category filters (`NcSelect` with `inputLabel`); empty state explains only extracted documents are searchable; manifest schema refs use slugs.

- [ ] 3.2 Entity detail view with occurrence table and collect action (REQ-DDESR-002, REQ-DDESR-005, REQ-DDESR-006)
  - Document/dossier/anonymisation/risk columns with chips; no-access aggregate row; collect-into-Woo-verzoek dialog in its own file under `src/dialogs/`; NL Design tokens.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for EntitySearchService (org scoping fail-closed, opaque unreadable files, log-write-failure refusal, digest-not-raw-value, presence gates) and EntitySearchController authz matrix — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 4.2 Playwright e2e spec `tests/e2e/spec-coverage/entity-search.spec.ts` covering the `@e2e`-referenced scenarios end-to-end with OpenRegister on the Postgres dev instance
  - Seeds the catalogue by extracting fixture documents through the UI; includes the 403 non-member check and the nldesign-theme accessibility pass; test through the UI.

- [ ] 4.3 i18n: EN + NL for all new UI strings (filters, chips, empty state, 403 message, collect dialog)
  - Keys in English.

- [ ] 4.4 Documentation `docs/features/entity-search.md` with Playwright MCP screenshots (ADR-010); run `openspec validate entity-search --strict`
  - Documents the permission gate, the digest-only Art. 30 log, the OR catalogue dependency and the INDICA-category positioning.
