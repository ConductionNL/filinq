# Tasks: contract-lifecycle-management

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add the `contract` schema to the `dossier` register in `lib/Settings/docudesk_register.json` (REQ-DDCLM-001, REQ-DDCLM-002, REQ-DDCLM-003)
  - Properties per design.md D1; `x-openregister-lifecycle` in OR's CANONICAL dialect (canonical `initial: draft` — verify against OR HEAD, do NOT copy the drifted `initialState` blocks); two `x-openregister-notifications` entries (`noticeDeadline`, `endDate`) mirroring the shipped `objectionDeadline` dialect; additive register version bump with changelog entry.

- [ ] 1.2 Add seed data: one notice-due `active` contract, one long-running `active` contract, one `draft` (design.md Seed Data)
  - Placeholder identifiers only (nil-UUID URNs, `seed-*`/`demostad-*`); validates on boot import.

- [ ] 1.3 Add register-declaration drift-pin unit tests (REQ-DDCLM-003, REQ-DDCLM-004)
  - Pins the canonical lifecycle dialect key OR honours, the notification entries, and the referenced external properties (`signingRequest.status`/`deadline`/`signatureLevel`) against the shipped register.

## 2. Backend

- [ ] 2.1 Implement `lib/Service/ContractService.php`: notice-deadline defaulting, renew, terminate, suggestion acceptance (REQ-DDCLM-001, REQ-DDCLM-002, REQ-DDCLM-005)
  - Defaulting never overwrites a user value; renew creates + links the successor (`renews`/`renewedBy`) and carries fields forward; terminate requires a reason; all saves PUT-semantic (carry ALL fields forward; test a non-changed field survives); no `_rbac`/`_multitenancy` overrides.

- [ ] 2.2 Implement `lib/Service/ContractTermSuggestionService.php` (REQ-DDCLM-005)
  - Local text extraction only; writes `keyTermSuggestions` records, never contract fields; honours `enable_contract_term_extraction` (default enabled); runs on document attach + on demand.

- [ ] 2.3 Add the three action routes (renew, terminate, accept-suggestion) via a thin `lib/Controller/ContractController.php` (REQ-DDCLM-002, REQ-DDCLM-005, REQ-DDCLM-006)
  - Explicit auth attributes + per-object guards on every method (hydra gates route-auth / no-admin-idor / semantic-auth); NO CRUD pass-through methods (redundant-controller gate) — frontend CRUD stays on OR's object API.

## 3. Frontend

- [ ] 3.1 Contracts index + detail manifest pages (REQ-DDCLM-001, REQ-DDCLM-004, REQ-DDCLM-006)
  - `CnIndexPage`/`CnDataTable` with status chips + facets; detail with parties (contact linkage), dates/value, documents (generate-from-template, attach, send-for-signature deep links storing references only), signing status, suggestions panel with per-field accept/reject, lifecycle actions; manifest schema refs use slugs; NL Design tokens via NC CSS variables.

- [ ] 3.2 Renewal pipeline view with the four urgency buckets (REQ-DDCLM-006)
  - Client-side date bucketing over authorised rows (expired / notice due ≤30d / expiring ≤90d / later); renew + terminate actionable from the pipeline; menu entry added.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for services, controller guards, defaulting, suggestion flow, drift pins — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/docudesk nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.

- [ ] 4.2 Playwright e2e `tests/e2e/workflows/contract-lifecycle.spec.ts` + `tests/e2e/spec-coverage/contracts.spec.ts` covering the `@e2e`-referenced scenarios on the Postgres dev instance
  - Includes the nldesign-theme accessibility pass on the new views.

- [ ] 4.3 i18n: EN + NL for all new UI strings (lifecycle actions, bucket labels, suggestion panel, notification subjects in the register)
  - Keys in English.

- [ ] 4.4 Documentation `docs/features/contract-lifecycle.md` with Playwright MCP screenshots (ADR-010); run `openspec validate contract-lifecycle-management --strict`
  - Documents the lean-CLM positioning, the suggestion-only rule, and the reference-only signing/template boundaries.
