# Tasks: woo-request-workflow

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 14.
     Acceptance criteria are plain bullets, not checkboxes. -->

## 1. Register + seed data

- [ ] 1.1 Add `wooRequest` and `requestDocument` schemas to the `dossier` register in `lib/Settings/filinq_register.json` (REQ-DDWRW-001, REQ-DDWRW-008)
  - All properties per design.md D1 (no requester name/email/address fields); `x-openregister-lifecycle` on `wooRequest` with canonical `initial: registered` and the REQ-DDWRW-008 transitions; register-i18n tags on new user-facing string fields; register version bump with changelog entry.

- [ ] 1.2 Add seed data: demo `wooRequest` + unique/duplicate `requestDocument` rows, the additional Woo Art. 5.1/5.2 `base` ground objects, and `woo-inventarislijst` + `woo-besluit` template seeds (design.md Seed Data, REQ-DDWRW-005/006/007)
  - `base` schema untouched; article references carried in ground names/descriptions; placeholder identifiers only.

## 2. Backend

- [ ] 2.1 Implement intake + statutory deadline logic in `lib/Service/WooRequestService.php` (REQ-DDWRW-002)
  - Clock-injected; 4-week deadline; single 2-week-max extension with mandatory reason, second attempt refused; no delegation to OR's Art. 12(3) helper.

- [ ] 2.2 Implement collection + hash dedupe (REQ-DDWRW-003, REQ-DDWRW-004)
  - Folder + bridge-staged sources (bridge presence-gated); copies into the dossier folder; sha256 hashing; duplicate collapse with `duplicateOfRef`; duplicates excluded from assessment/inventory/package but listed.

- [ ] 2.3 Implement assessment + exemption-ground tagging (REQ-DDWRW-005)
  - `withhold`/`partially_disclose` require ≥1 ground; passage tags store `base` slugs; rendering via existing `BasesResolverService` with visible unknown-slug warnings.

- [ ] 2.4 Implement inventarislijst generation and disclosure-package assembly (REQ-DDWRW-006, REQ-DDWRW-007)
  - Inventory via existing `api/documents/generate` + seeded template with stable inventory numbers; besluit via existing correspondence generation; package refuses `partially_disclose` items without `redactedFileRef`; excludes `withhold` + duplicates.

- [ ] 2.5 Implement lifecycle guard conditions + `lib/Controller/WooRequestController.php` with `api/woo-requests/*` routes (REQ-DDWRW-008)
  - Cross-object guards evaluated before transitions; every controller method carries explicit auth attributes and per-object guards; routes registered before the catch-all.

## 3. Frontend

- [ ] 3.1 Woo-verzoeken index manifest page (REQ-DDWRW-009)
  - `CnIndexPage`/`CnDataTable`; status + deadline chips; manifest schema refs use slugs.

- [ ] 3.2 Request detail: lifecycle header + extension dialog, collection surface, assessment surface with grounds multi-select and passage-tag editor, document/package actions (REQ-DDWRW-002..007, REQ-DDWRW-009)
  - Deep-link to batch entity review (no duplicate review UI); modals/dialogs in own files; `NcSelect` with `inputLabel`; NL Design tokens.

## 4. Quality

- [ ] 4.1 PHPUnit unit tests for WooRequestService (deadlines, dedupe, guards, package refusal) and controller — minimum 75% coverage on new code
  - Run inside the container: `docker exec -w /var/www/html/custom_apps/filinq nextcloud php vendor/bin/phpunit -c phpunit-unit.xml`.
  - PUT-semantic saves verified (a non-changed field survives a status transition).

- [ ] 4.2 Playwright e2e specs `tests/e2e/workflows/woo-request-workflow.spec.ts` + `tests/e2e/spec-coverage/woo-requests.spec.ts` covering the `@e2e`-referenced scenarios end-to-end with OpenRegister on the Postgres dev instance
  - Test through the UI; includes the nldesign-theme accessibility pass.

- [ ] 4.3 Verify the anonymize → assess → package chain end-to-end with OpenRegister entity detection on a seeded request dossier
  - Redacted derivatives produced by the existing folder-batch flow land in the package; consent transitions remain valid per GDPR/WOO lifecycle.

- [ ] 4.4 i18n: EN + NL for all new UI strings (statuses, chips, assessment labels, dialogs)
  - Keys in English.

- [ ] 4.5 Documentation `docs/features/woo-request-workflow.md` with Playwright MCP screenshots (ADR-010); run `openspec validate woo-request-workflow --strict`
  - Documents the statutory-term semantics, the grondslagen reuse and the ZyLAB-category positioning.
